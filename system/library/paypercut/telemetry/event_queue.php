<?php

namespace Paypercut\Telemetry;

/**
 * The buffered, best-effort store of diagnostic events awaiting delivery.
 *
 * Storefront requests only ever append here; delivery happens later, from an
 * authenticated admin request. Everything is capped, because a queue that can
 * grow without bound on a busy store is a denial of service against the store.
 */
final class EventQueue
{
    private function __construct()
    {
    }

    /**
     * Append envelopes, dropping the oldest if that overflows the caps.
     */
    public static function append(array $envelopes): void
    {
        $envelopes = self::assertSafe($envelopes);

        if (empty($envelopes)) {
            return;
        }

        $capped = self::cap(array_merge(self::read(TelemetrySession::QUEUE_KEY), $envelopes));

        self::write(TelemetrySession::QUEUE_KEY, $capped['envelopes']);

        // Counted only from admin requests: a storefront request must make at
        // most one write, and the queue write above is it. The panel already
        // presents this counter as approximate.
        if ($capped['dropped'] > 0 && Context::isAdmin()) {
            TelemetrySession::updateRuntime([
                'events_dropped' => (int)(TelemetrySession::runtime()['events_dropped'] ?? 0) + $capped['dropped']
            ]);
        }
    }

    /**
     * The last gate before anything is persisted for delivery.
     *
     * Every producer funnels through here — the storefront recorder and the
     * admin-side lifecycle events alike — so the deny assertion cannot be
     * bypassed by adding a new call site. A tripped assertion drops the whole
     * event, not the offending field: an event assembled wrongly cannot be
     * trusted in its other parts either.
     */
    private static function assertSafe(array $envelopes): array
    {
        if (empty($envelopes)) {
            return [];
        }

        $secrets = TelemetrySession::credentials();
        $safe = [];

        foreach ($envelopes as $envelope) {
            if (!self::isSafe($envelope, $secrets)) {
                TelemetrySession::audit('Telemetry: event dropped by the deny assertion', [
                    'event' => (string)($envelope['event'] ?? 'unknown')
                ]);

                continue;
            }

            $safe[] = $envelope;
        }

        return $safe;
    }

    /**
     * Screen one envelope exactly as it will be serialised.
     *
     * The whole envelope, not a named subset of it: `error` and `attrs` are not
     * the only places untrusted text lands. The correlation ids `about()`
     * writes as top-level siblings are copied from webhook bodies, which on a
     * store with no webhook secret configured are anybody's to write.
     *
     * Public so the suite can pin the gate itself rather than a copy of it.
     */
    public static function isSafe(array $envelope, array $secrets): bool
    {
        return !Event::isDenied($envelope, $secrets);
    }

    /**
     * Enforce the queue caps, dropping the oldest entries first.
     */
    public static function cap(array $envelopes): array
    {
        $dropped = 0;

        if (count($envelopes) > TelemetrySession::MAX_QUEUE_EVENTS) {
            $dropped = count($envelopes) - TelemetrySession::MAX_QUEUE_EVENTS;
            $envelopes = array_slice($envelopes, -TelemetrySession::MAX_QUEUE_EVENTS);
        }

        // Stop at one, mirroring splitBatch(): a single oversized envelope must
        // not empty the queue behind it.
        while (count($envelopes) > 1 && self::bytes($envelopes) > TelemetrySession::MAX_QUEUE_BYTES) {
            array_shift($envelopes);
            $dropped++;
        }

        return [
            'envelopes' => $envelopes,
            'dropped'   => $dropped
        ];
    }

    /**
     * Split a batch off the front of the queue, within both edge bounds.
     *
     * Always takes at least one envelope: a single oversized envelope would
     * otherwise wedge the queue forever, and the edge rejecting it once is a
     * cheaper outcome than never draining.
     */
    public static function splitBatch(array $envelopes, int $max_bytes, int $max_events): array
    {
        $batch = [];

        foreach ($envelopes as $envelope) {
            if (count($batch) >= $max_events) {
                break;
            }

            $candidate = array_merge($batch, [$envelope]);

            if (!empty($batch) && self::bytes($candidate) > $max_bytes) {
                break;
            }

            $batch = $candidate;
        }

        return [
            'batch'     => $batch,
            'remainder' => array_slice($envelopes, count($batch))
        ];
    }

    /**
     * Take a batch, shortening the stored queue immediately.
     *
     * The remainder is written back BEFORE the network call, and the batch is
     * parked under its own key. Holding the remainder across the request would
     * discard anything storefront requests appended while the POST was in
     * flight, and could resurrect an already-delivered batch.
     */
    public static function takeBatch(int $max_bytes, int $max_events): array
    {
        $split = self::splitBatch(self::read(TelemetrySession::QUEUE_KEY), $max_bytes, $max_events);

        if (empty($split['batch'])) {
            return [];
        }

        self::write(TelemetrySession::QUEUE_KEY, $split['remainder']);
        self::write(TelemetrySession::INFLIGHT_KEY, $split['batch']);

        return $split['batch'];
    }

    public static function inflight(): array
    {
        return self::read(TelemetrySession::INFLIGHT_KEY);
    }

    public static function clearInflight(): void
    {
        Store::deleteExpiring(TelemetrySession::INFLIGHT_KEY);
    }

    /**
     * Shorten the parked batch to what is left to deliver.
     *
     * The flusher may only ever SHORTEN inflight, never write the queue: the
     * flush lock excludes other flushers, but append() is an unlocked
     * read-modify-write from anonymous storefront requests, and takeBatch() has
     * already removed this batch from the queue.
     */
    public static function retainInflight(array $envelopes): void
    {
        self::write(TelemetrySession::INFLIGHT_KEY, $envelopes);
    }

    public static function size(): int
    {
        return count(self::read(TelemetrySession::QUEUE_KEY)) + count(self::read(TelemetrySession::INFLIGHT_KEY));
    }

    public static function bytes(array $envelopes): int
    {
        $json = json_encode($envelopes);

        return is_string($json) ? strlen($json) : 0;
    }

    private static function read(string $key): array
    {
        $stored = Store::getExpiring($key);

        return is_array($stored) ? $stored : [];
    }

    private static function write(string $key, array $envelopes): void
    {
        if (empty($envelopes)) {
            Store::deleteExpiring($key);

            return;
        }

        Store::putExpiring($key, $envelopes, self::ttl());
    }

    /**
     * Outlive the session slightly, so a final flush still finds its batch.
     */
    private static function ttl(): int
    {
        $expires_at = (int)(TelemetrySession::record()['expires_at'] ?? 0);

        return max(300, ($expires_at - time()) + 300);
    }
}
