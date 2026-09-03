<?php

namespace Paypercut\Telemetry;

/**
 * A local copy of the events this store actually delivered.
 *
 * The queue is emptied as it drains, so by the time anyone looks there is
 * nothing left to see: the panel could report "37 events sent" and offer no way
 * to find out what they were. Consent to send diagnostics is worth more when
 * the sender can inspect what left.
 *
 * Nothing here runs on a storefront request — the flusher delivers only from
 * authenticated admin requests.
 */
final class SentLog
{
    const KEY = 'paypercut_telemetry_sent_log';

    /**
     * Entries kept before the oldest are discarded.
     *
     * A session is an hour; a busy store can deliver far more than this, so the
     * log is a tail rather than a transcript. The panel says so.
     */
    const MAX_ENTRIES = 100;

    const MAX_BYTES = 131072;

    private function __construct()
    {
    }

    /**
     * Record envelopes the edge accepted, newest last.
     */
    public static function append(array $envelopes): void
    {
        if (empty($envelopes)) {
            return;
        }

        $entries = array_merge(self::all(), $envelopes);

        if (count($entries) > self::MAX_ENTRIES) {
            $entries = array_slice($entries, -self::MAX_ENTRIES);
        }

        while (count($entries) > 1 && self::bytes($entries) > self::MAX_BYTES) {
            array_shift($entries);
        }

        Store::putBlob(self::KEY, $entries);
    }

    public static function all(): array
    {
        return Store::getBlob(self::KEY);
    }

    public static function clear(): void
    {
        Store::deleteBlob(self::KEY);
    }

    private static function bytes(array $entries): int
    {
        $json = json_encode($entries);

        return is_string($json) ? strlen($json) : 0;
    }
}
