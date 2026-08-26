<?php

namespace Paypercut\Telemetry;

use Paypercut\Environment;

/**
 * Owns the debug session: its state, its token custody, and its teardown.
 *
 * A debug session is a merchant-granted, self-expiring window during which the
 * store may send diagnostic events to Paypercut. The deadline is an absolute
 * unix timestamp in a durable, cheaply-readable record, and every read
 * recomputes liveness against it — which is what makes the session end on time
 * with no scheduled job: there is no timer to miss and nothing to orphan if the
 * process dies. The token's own `exp` is the matching bound on the server side.
 */
final class TelemetrySession
{
    /**
     * Hard ceiling on a session, independent of what the mint hands back.
     *
     * With no revocation anywhere, this ceiling IS the consent: the merchant is
     * told "about 60 minutes", so the extension must not run for longer even if
     * a future deployment issues longer-lived tokens.
     */
    const SESSION_MAX_SECONDS = 3600;

    /** Stop this long before the token expires, so the edge never rejects it. */
    const SKEW_SECONDS = 30;

    const MIN_LIFETIME_SECONDS = 60;

    const MAX_QUEUE_EVENTS = 200;

    const MAX_QUEUE_BYTES = 65536;

    /**
     * Kept well under the edge's 64 KiB body cap: the edge does not
     * deduplicate, so a bigger batch only means losing more per failed POST.
     */
    const MAX_BATCH_BYTES = 16384;

    /** The edge's own MaxEventsPerBatch. A batch over it is refused with 413. */
    const MAX_BATCH_EVENTS = 50;

    const MAX_CONSECUTIVE_SEND_FAILURES = 4;

    const MINT_TIMEOUT_SECONDS = 10;

    const MINT_CONNECT_TIMEOUT_SECONDS = 5;

    const EDGE_TIMEOUT_SECONDS = 5;

    const EDGE_CONNECT_TIMEOUT_SECONDS = 3;

    const START_LOCK_TTL = 60;

    const FLUSH_LOCK_TTL = 60;

    const FAILED_NOTICE_TTL = 600;

    const ENDED_NOTICE_TTL = 86400;

    const POLL_INTERVAL_SECONDS = 60;

    const SLOW_REQUEST_MS = 3000;

    const TOKEN_KEY = 'paypercut_telemetry_token';

    const QUEUE_KEY = 'paypercut_telemetry_queue';

    const INFLIGHT_KEY = 'paypercut_telemetry_inflight';

    const RUNTIME_KEY = 'paypercut_telemetry_runtime';

    const START_LOCK_KEY = 'paypercut_telemetry_start_lock';

    const FLUSH_LOCK_KEY = 'paypercut_telemetry_flush_lock';

    /** @var bool|null Per-request memo for the storefront gate. */
    private static $active_memo = null;

    private function __construct()
    {
    }

    /**
     * The storefront gate: is a session live right now?
     *
     * Reads one already-loaded setting and nothing else — no queries, no
     * writes, no HTTP. This runs on anonymous checkout requests, so anything
     * more expensive belongs behind an admin guard.
     */
    public static function isActiveFast(): bool
    {
        if (self::$active_memo !== null) {
            return self::$active_memo;
        }

        $record = self::record();

        self::$active_memo = ($record['status'] ?? '') === 'active'
            && (int)($record['expires_at'] ?? 0) > time();

        return self::$active_memo;
    }

    public static function flushMemo(): void
    {
        self::$active_memo = null;
    }

    public static function record(): array
    {
        return Store::getRecord();
    }

    /**
     * The session state as the admin panel should present it.
     */
    public static function describe(): array
    {
        $record = self::record();
        $runtime = self::runtime();
        $now = time();

        $status = (string)($record['status'] ?? '');
        $state = 'idle';

        if ($status === 'active') {
            $state = (int)($record['expires_at'] ?? 0) > $now ? 'running' : 'ended';
        } elseif ($status === 'failed') {
            $state = ($now - (int)($record['ended_at'] ?? 0)) < self::FAILED_NOTICE_TTL ? 'failed' : 'idle';
        } elseif ($status === 'stopped' || $status === 'expired') {
            $state = ($now - (int)($record['ended_at'] ?? 0)) < self::ENDED_NOTICE_TTL ? 'ended' : 'idle';
        }

        return [
            'state'           => $state,
            'session_id'      => (string)($record['session_id'] ?? ''),
            'expires_at'      => (int)($record['expires_at'] ?? 0),
            'started_at'      => (int)($record['started_at'] ?? 0),
            'ended_at'        => (int)($record['ended_at'] ?? 0),
            'started_by_name' => (string)($record['started_by_name'] ?? ''),
            'reason_code'     => (string)($record['reason_code'] ?? ''),
            'trace_id'        => (string)($record['trace_id'] ?? ''),
            'request_id'      => (string)($record['request_id'] ?? ''),
            'retryable'       => (bool)($record['retryable'] ?? false),
            'message'         => (string)($record['message'] ?? ''),
            'events_sent'     => (int)($runtime['events_sent'] ?? 0),
            'events_dropped'  => (int)($runtime['events_dropped'] ?? 0),
            'queued'          => EventQueue::size()
        ];
    }

    /**
     * The telemetry token, or '' when there is not a usable one.
     *
     * Every condition here is a reason the token must not be used, and each is
     * checked rather than assumed: the stored TTL is a backstop, never the
     * authority.
     */
    public static function token(): string
    {
        $record = self::record();

        if (($record['status'] ?? '') !== 'active') {
            return '';
        }

        $expires_at = (int)($record['expires_at'] ?? 0);

        if ($expires_at <= time()) {
            return '';
        }

        $stored = Store::getExpiring(self::TOKEN_KEY);

        if (!is_array($stored) || !isset($stored['token']) || !is_string($stored['token'])) {
            return '';
        }

        if ((int)($stored['expires_at'] ?? 0) !== $expires_at) {
            return '';
        }

        if (!self::credentialMatches($record)) {
            return '';
        }

        $decoded = base64_decode($stored['token'], true);

        return is_string($decoded) ? $decoded : '';
    }

    /**
     * Does the stored record still describe the connection the store has today?
     */
    public static function credentialMatches(array $record): bool
    {
        $connection = self::connection();
        $fingerprint = self::fingerprint($connection['secret']);

        if ($fingerprint === '' || $fingerprint !== (string)($record['key_fingerprint'] ?? '')) {
            return false;
        }

        return $connection['environment'] === (string)($record['environment'] ?? '');
    }

    /**
     * The gateway's stored credential and environment.
     */
    public static function connection(): array
    {
        return [
            'secret'      => (string)Store::setting('payment_paypercut_api_key', ''),
            'environment' => Environment::normalize(Store::setting('payment_paypercut_environment', ''))
        ];
    }

    /**
     * Every credential the store holds, for the deny assertion to compare
     * against.
     *
     * This list must enumerate every credential-bearing setting: comparing a
     * value against the actual secret is the only screen that catches a format
     * nobody anticipated, and it is silently useless for a key not named here.
     * A future gateway adding its own settings row breaks it.
     */
    public static function credentials(): array
    {
        $secrets = [self::token()];

        foreach (['payment_paypercut_api_key', 'payment_paypercut_webhook_secret'] as $key) {
            $value = Store::setting($key, '');

            if (is_string($value) && $value !== '') {
                $secrets[] = $value;
            }
        }

        return array_values(array_filter($secrets));
    }

    /**
     * A short, non-reversing marker for "the same API key as before".
     */
    public static function fingerprint(string $secret): string
    {
        return $secret === '' ? '' : substr(hash('sha256', $secret), 0, 12);
    }

    /**
     * Publish a new session and store its token.
     */
    public static function begin(array $record, string $jwt): void
    {
        $expires_at = (int)$record['expires_at'];

        Store::putExpiring(
            self::TOKEN_KEY,
            [
                'token'      => base64_encode($jwt),
                'expires_at' => $expires_at
            ],
            max(60, $expires_at - time())
        );

        // Never inherit a previous session's buffer: those events were gathered
        // under a different consent and would ship under this session's id.
        Store::deleteExpiring(self::QUEUE_KEY);
        Store::deleteExpiring(self::INFLIGHT_KEY);

        // A previous session's tail would misattribute the events the merchant
        // is reading to decide what happened.
        SentLog::clear();

        Store::putRecord($record);

        Store::putBlob(self::RUNTIME_KEY, [
            'events_sent'               => 0,
            'events_dropped'            => 0,
            'consecutive_edge_failures' => 0,
            'next_attempt_at'           => 0,
            'last_error'                => ''
        ]);

        self::flushMemo();
    }

    /**
     * Record a start that never happened, so the merchant sees why.
     */
    public static function fail(array $mapped, string $trace_id = '', string $request_id = ''): void
    {
        // Never overwrite a live session with a failure notice: a concurrent
        // start that loses a race would otherwise erase the winner's record and
        // strand its token beyond the reach of every teardown path.
        if ((self::record()['status'] ?? '') === 'active') {
            return;
        }

        Store::putRecord([
            'status'      => 'failed',
            'ended_at'    => time(),
            'reason_code' => $mapped['reason_code'],
            'message'     => $mapped['message'],
            'retryable'   => $mapped['retryable'],
            'trace_id'    => $trace_id,
            'request_id'  => $request_id
        ]);

        self::flushMemo();
    }

    /**
     * End the session and destroy every trace of its credential.
     *
     * Idempotent, and the single teardown path: expiry, the Stop button, a
     * re-key, an environment change and uninstall all arrive here, so there is
     * exactly one place that can forget something.
     */
    public static function end(string $reason): void
    {
        $record = self::record();

        Store::deleteExpiring(self::TOKEN_KEY);
        Store::deleteExpiring(self::QUEUE_KEY);
        Store::deleteExpiring(self::INFLIGHT_KEY);

        if (($record['status'] ?? '') !== 'active') {
            Store::deleteBlob(self::RUNTIME_KEY);
            self::flushMemo();

            return;
        }

        $runtime = self::runtime();

        Store::putRecord([
            'status'          => $reason === 'expired' ? 'expired' : 'stopped',
            'session_id'      => (string)($record['session_id'] ?? ''),
            'environment'     => (string)($record['environment'] ?? ''),
            'started_at'      => (int)($record['started_at'] ?? 0),
            'expires_at'      => (int)($record['expires_at'] ?? 0),
            'started_by'      => (int)($record['started_by'] ?? 0),
            'started_by_name' => (string)($record['started_by_name'] ?? ''),
            'ended_at'        => time(),
            'reason_code'     => $reason,
            'events_sent'     => (int)($runtime['events_sent'] ?? 0),
            'events_dropped'  => (int)($runtime['events_dropped'] ?? 0)
        ]);

        Store::deleteBlob(self::RUNTIME_KEY);

        self::flushMemo();

        Store::audit('Telemetry: debug session ended', [
            'session_id' => (string)($record['session_id'] ?? ''),
            'reason'     => $reason
        ]);
    }

    /**
     * Tear down a session whose deadline has passed, or whose connection
     * changed. Admin context only — it writes.
     *
     * This is what turns "the gate is closed" into "the token is gone": the
     * gate flips the instant the deadline passes, but the stored copy is
     * removed by the next admin request that runs this.
     */
    public static function reap(): void
    {
        $record = self::record();

        if (($record['status'] ?? '') !== 'active') {
            // No live session, but a stored token means the record was lost
            // without one. The credential is referenced by nothing, so destroy
            // it here rather than leave it to expire.
            if (Store::getExpiring(self::TOKEN_KEY) !== null) {
                self::end('token_orphaned');
            }

            return;
        }

        if ((int)($record['expires_at'] ?? 0) <= time()) {
            self::end('expired');

            return;
        }

        if (!self::credentialMatches($record)) {
            self::end('connection_changed');

            return;
        }

        if (self::token() === '') {
            self::end('token_lost');
        }
    }

    public static function runtime(): array
    {
        return Store::getBlob(self::RUNTIME_KEY);
    }

    public static function updateRuntime(array $values): void
    {
        Store::putBlob(self::RUNTIME_KEY, array_merge(self::runtime(), $values));
    }

    /**
     * Claim an exclusive right to mint.
     *
     * Without a real mutex, two clicks in two tabs both mint. The loser's token
     * is then either overwritten in storage or discarded by the re-check — and
     * either way one fully valid credential exists that no teardown path knows
     * about and nothing can revoke.
     */
    public static function claimStartLock(): bool
    {
        return Store::claimLock(self::START_LOCK_KEY, self::START_LOCK_TTL);
    }

    public static function releaseStartLock(): void
    {
        Store::releaseLock(self::START_LOCK_KEY);
    }

    public static function claimFlushLock(): bool
    {
        return Store::claimLock(self::FLUSH_LOCK_KEY, self::FLUSH_LOCK_TTL);
    }

    public static function releaseFlushLock(): void
    {
        Store::releaseLock(self::FLUSH_LOCK_KEY);
    }

    public static function audit(string $message, array $context = []): void
    {
        Store::audit($message, $context);
    }
}
