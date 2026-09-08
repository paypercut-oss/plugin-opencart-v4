<?php

namespace Paypercut\Telemetry;

/**
 * Delivers queued diagnostic events to the telemetry edge.
 *
 * Runs only from authenticated admin requests: the panel's status poll, the
 * Stop handler, and one guarded backstop on the settings page. Never from a
 * storefront request, never from the webhook, never from cron.
 */
final class Flusher
{
    /** Backoff ladder applied after consecutive delivery failures, in seconds. */
    const BACKOFF_SECONDS = [30, 120, 300];

    /** @var EdgeClient */
    private $client;

    public function __construct(?EdgeClient $client = null)
    {
        $this->client = $client ?? new EdgeClient();
    }

    /**
     * Attempt to deliver at most one batch.
     *
     * @return bool Whether a delivery was attempted.
     */
    public function flushOnce(): bool
    {
        if (!Context::isAdmin()) {
            return false;
        }

        $record = TelemetrySession::record();

        if (($record['status'] ?? '') !== 'active' || (int)($record['expires_at'] ?? 0) <= time()) {
            return false;
        }

        $runtime = TelemetrySession::runtime();

        if ((int)($runtime['next_attempt_at'] ?? 0) > time()) {
            return false;
        }

        if (!TelemetrySession::claimFlushLock()) {
            return false;
        }

        try {
            return $this->deliver($record);
        } finally {
            TelemetrySession::releaseFlushLock();
        }
    }

    /**
     * Decide what an edge response means, with no side effects.
     *
     * Kept pure and separate from settle() so the whole branch table —
     * including the give-up ladder — can be exercised without an edge, a
     * database or a running session.
     *
     * @param int $failures Consecutive failures BEFORE this attempt.
     */
    public static function decide(int $status, int $retry_after, int $failures): array
    {
        if ($status === 202) {
            return self::outcome('accepted', false, 0, true);
        }

        if ($status === 401) {
            // Never re-mint. Every mint issues a token with a fresh expiry and
            // nothing can revoke one, so a re-mint would leave a credential
            // valid past the window the merchant agreed to.
            return self::outcome('token_rejected', true, 0, true);
        }

        if ($status === 413) {
            // Not a failure — the batch is being reshaped. A backoff rung would
            // punish a successful negotiation, and a step towards giving up
            // would end a session over a batch we can simply cut in half.
            return self::outcome('split', false, 0, false);
        }

        // Nothing in the edge answers 429; this covers infrastructure in front
        // of it. A hostile Retry-After must not park the session forever.
        if ($status === 429) {
            return self::outcome('throttled', false, $retry_after > 0 ? min($retry_after, 900) : 60, false);
        }

        if ($status === 503 || $status === 504) {
            // "My verification keys aren't ready" is a statement about the edge,
            // not about this token. Ending the session on a rolling deploy would
            // be a one-way door: there is no re-mint, so the merchant would have
            // to consent all over again.
            return self::outcome('unready', false, 120, false);
        }

        $attempt = $failures + 1;
        $give_up = $attempt >= TelemetrySession::MAX_CONSECUTIVE_SEND_FAILURES;
        $retry_in = self::BACKOFF_SECONDS[min($attempt, count(self::BACKOFF_SECONDS)) - 1];

        // Our bug, not the merchant's: drop the batch so the queue drains, but
        // still count it. An edge that rejects every batch we build makes the
        // session useless, and it should end rather than burn an hour silently
        // incrementing a dropped counter.
        if ($status === 400) {
            return self::outcome('poison', $give_up, $retry_in, true);
        }

        return self::outcome('failed', $give_up, $retry_in, false);
    }

    /**
     * Announce the end of a session on the wire, then tear it down.
     *
     * Every reason the merchant did not choose — a re-key, an environment
     * change, the Stop button — goes through here, so `session.stopped` is not
     * a merchant_stopped-only event.
     */
    public static function announceAndEnd(string $reason): void
    {
        $record = TelemetrySession::record();

        if (($record['status'] ?? '') === 'active') {
            $runtime = TelemetrySession::runtime();

            EventQueue::append([
                Event::sessionStopped(
                    (string)($record['session_id'] ?? ''),
                    $reason,
                    (int)($runtime['events_sent'] ?? 0),
                    (int)($runtime['events_dropped'] ?? 0)
                )->envelope()
            ]);

            // Twice: the first pass clears anything already parked in flight,
            // the second carries the stop event itself. Without it, end() would
            // delete the queue holding the event that announces the stop.
            // Bounded on purpose — each pass can block for up to the edge
            // timeout, and this runs behind a button click.
            $flusher = new self();

            for ($attempt = 0; $attempt < 2; $attempt++) {
                if (!$flusher->flushOnce()) {
                    break;
                }
            }
        }

        TelemetrySession::end($reason);
    }

    private function deliver(array $record): bool
    {
        $limits = self::limits();

        // A parked batch always drains first, so a retry never reorders delivery.
        $batch = EventQueue::inflight();

        if (empty($batch)) {
            $batch = EventQueue::takeBatch(TelemetrySession::MAX_BATCH_BYTES, $limits['max_events']);
        }

        if (empty($batch)) {
            return false;
        }

        $token = TelemetrySession::token();

        if ($token === '') {
            TelemetrySession::end('token_lost');

            return false;
        }

        $edge_base = (string)($record['edge_base'] ?? '');

        if ($edge_base === '') {
            TelemetrySession::end('environment_changed');

            return false;
        }

        $client = self::clientIdentity();
        $split = EventQueue::splitBatch($batch, self::eventsBudget($client), $limits['max_events']);
        $head = $split['batch'];
        $tail = $split['remainder'];

        $body = json_encode([
            'client' => $client,
            'events' => $head
        ]);

        if (!is_string($body)) {
            EventQueue::clearInflight();
            $this->countDropped(count($batch), 'encode_failed');

            return false;
        }

        $result = $this->client->send($edge_base, $token, $body);

        return $this->settle(
            (int)$result['status'],
            (int)$result['retry_after'],
            $head,
            $tail,
            is_array($result['body'] ?? null) ? $result['body'] : []
        );
    }

    /**
     * Identifies the software that produced the batch.
     */
    private static function clientIdentity(): array
    {
        return [
            'platform' => 'opencart',
            'version'  => Event::text(\Paypercut\Plugin::version()) ?: 'dev'
        ];
    }

    /**
     * Bytes left for the events array once the wrapper is paid for: the edge
     * caps the request body, not the events array.
     */
    private static function eventsBudget(array $client): int
    {
        $wrapper = json_encode([
            'client' => $client,
            'events' => []
        ]);

        return TelemetrySession::MAX_BATCH_BYTES - (is_string($wrapper) ? strlen($wrapper) : 128);
    }

    /**
     * The bounds a batch must satisfy, as last advertised by the edge.
     *
     * Clamped on the way in: the edge may only ever make us more conservative.
     */
    private static function limits(): array
    {
        $events = (int)(TelemetrySession::runtime()['edge_max_events'] ?? 0);

        return [
            'max_events' => $events > 0
                ? max(1, min($events, TelemetrySession::MAX_BATCH_EVENTS))
                : TelemetrySession::MAX_BATCH_EVENTS
        ];
    }

    private static function outcome(string $outcome, bool $end_session, int $retry_in, bool $clears_batch): array
    {
        return [
            'outcome'      => $outcome,
            'end_session'  => $end_session,
            'retry_in'     => $retry_in,
            'clears_batch' => $clears_batch
        ];
    }

    /**
     * Apply the edge's answer to the parked batch.
     *
     * @param array $head The events actually POSTed.
     * @param array $tail What stayed parked behind them.
     */
    private function settle(int $status, int $retry_after, array $head, array $tail, array $body): bool
    {
        $runtime = TelemetrySession::runtime();
        $failures = (int)($runtime['consecutive_edge_failures'] ?? 0);
        $decision = self::decide($status, $retry_after, $failures);
        $events = count($head);

        if ($decision['outcome'] === 'split') {
            return $this->resize($head, $tail, $body);
        }

        if ($decision['clears_batch']) {
            // Only the delivered head is settled; anything behind it stays parked.
            EventQueue::retainInflight($tail);
        }

        if ($decision['outcome'] === 'accepted') {
            // The edge drops malformed events individually and still answers
            // 202, so the counts it returns are the only honest accounting.
            $accepted = isset($body['accepted']) ? (int)$body['accepted'] : $events;
            $dropped = isset($body['dropped']) ? (int)$body['dropped'] : 0;

            SentLog::append($head);

            TelemetrySession::updateRuntime([
                'events_sent'               => (int)($runtime['events_sent'] ?? 0) + $accepted,
                'consecutive_edge_failures' => 0,
                'next_attempt_at'           => 0,
                'last_error'                => ''
            ]);

            if ($dropped > 0) {
                $this->countDropped($dropped, 'edge_dropped');
            }

            return true;
        }

        if ($decision['outcome'] === 'poison') {
            $this->countDropped($events, 'malformed_batch');
        }

        if ($decision['end_session']) {
            if ($decision['outcome'] !== 'token_rejected') {
                TelemetrySession::audit('Telemetry: giving up on delivery', [
                    'status'   => $status,
                    'failures' => $failures + 1
                ]);
            }

            TelemetrySession::end($decision['outcome'] === 'token_rejected' ? 'edge_rejected' : 'send_failed');

            return true;
        }

        $counts_as_failure = in_array($decision['outcome'], ['failed', 'poison'], true);

        TelemetrySession::updateRuntime([
            'consecutive_edge_failures' => $counts_as_failure ? $failures + 1 : $failures,
            'next_attempt_at'           => time() + $decision['retry_in'] + random_int(0, 30),
            'last_error'                => 'edge_' . $status
        ]);

        return true;
    }

    /**
     * Answer a 413 by making the next batch smaller.
     *
     * The queue is never touched. The head stays parked and is re-split on the
     * next flush, which is one round trip later on purpose: each attempt blocks
     * the merchant's browser for up to the edge timeout.
     */
    private function resize(array $head, array $tail, array $body): bool
    {
        if (count($head) === 1) {
            // A one-event batch cannot be split further, and `split` does not
            // advance the give-up ladder, so nothing else would break the loop.
            // Name and size only: the envelope is the one thing not to log.
            EventQueue::retainInflight($tail);
            $this->countDropped(1, 'oversize_event');
            TelemetrySession::audit('Telemetry: event too large to deliver', [
                'event' => (string)($head[0]['event'] ?? 'unknown'),
                'bytes' => EventQueue::bytes($head)
            ]);
        } else {
            // Halving guarantees progress on its own: a 413 raised by a proxy in
            // front of the edge carries no limits at all, and the edge's own
            // byte cap is larger than ours, so neither would shrink the batch.
            $limits = isset($body['limits']) && is_array($body['limits']) ? $body['limits'] : [];
            $advertised = (int)($limits['max_events'] ?? 0);
            $halved = (int)max(1, intdiv(count($head), 2));

            TelemetrySession::updateRuntime([
                'edge_max_events' => $advertised > 0 ? min($advertised, $halved) : $halved
            ]);
        }

        TelemetrySession::updateRuntime([
            'next_attempt_at' => 0,
            'last_error'      => 'edge_413'
        ]);

        return true;
    }

    private function countDropped(int $events, string $reason): void
    {
        TelemetrySession::audit('Telemetry: batch dropped', [
            'events' => $events,
            'reason' => $reason
        ]);

        TelemetrySession::updateRuntime([
            'events_dropped' => (int)(TelemetrySession::runtime()['events_dropped'] ?? 0) + $events
        ]);
    }
}
