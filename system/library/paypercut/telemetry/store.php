<?php

namespace Paypercut\Telemetry;

/**
 * Everything the telemetry code needs from OpenCart, in one place.
 *
 * Three storage shapes and a lock:
 *
 *  - the session record lives in its own `setting` row, so the settings form
 *    (which deletes and rewrites every `payment_paypercut` row on save) cannot
 *    author or lose it, and reading it on the storefront costs nothing —
 *    OpenCart loads every setting row into the config on every request;
 *  - runtime counters and the sent log live in a module-owned table, which is
 *    never eager-loaded;
 *  - the token, queue and inflight buffers live in the same table with an
 *    `expires_at` column, because OpenCart has no transient concept and a
 *    cache-only store would silently lose a merchant's diagnostics on a flush.
 */
final class Store
{
    const TABLE = 'paypercut_telemetry';

    const SETTING_CODE = 'paypercut_telemetry';

    const RECORD_KEY = 'paypercut_telemetry_session';

    const SETTINGS_CODE = 'payment_paypercut';

    /** @var \Opencart\System\Engine\Registry|null */
    private static $registry = null;

    /** @var array|null Per-request memo of the durable record. */
    private static $record_memo = null;

    /** @var array Owner tokens for the locks this request holds. */
    private static $lock_owners = [];

    /** @var bool Whether this request has already tried to create the table. */
    private static $table_checked = false;

    private function __construct()
    {
    }

    /**
     * Hand the telemetry code the OpenCart services it needs. Idempotent.
     */
    public static function bind($registry): void
    {
        if (self::$registry === null) {
            self::$registry = $registry;
        }
    }

    public static function bound(): bool
    {
        return self::$registry !== null;
    }

    public static function db()
    {
        return self::$registry !== null ? self::$registry->get('db') : null;
    }

    public static function config()
    {
        return self::$registry !== null ? self::$registry->get('config') : null;
    }

    /**
     * A setting value from any code, as OpenCart already loaded it.
     */
    public static function setting(string $key, $default = null)
    {
        $config = self::config();

        if ($config === null) {
            return $default;
        }

        $value = $config->get($key);

        return $value === null ? $default : $value;
    }

    /**
     * The durable session record, or [] when none was ever written.
     *
     * Read from the already-loaded config, so the storefront gate costs no
     * query at all.
     */
    public static function getRecord(): array
    {
        if (self::$record_memo !== null) {
            return self::$record_memo;
        }

        $stored = self::setting(self::RECORD_KEY);

        if (is_string($stored) && $stored !== '') {
            $stored = json_decode($stored, true);
        }

        self::$record_memo = is_array($stored) ? $stored : [];

        return self::$record_memo;
    }

    public static function putRecord(array $record): void
    {
        $db = self::db();

        if ($db === null) {
            return;
        }

        $db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '0' AND `code` = '" . $db->escape(self::SETTING_CODE) . "'");
        $db->query("
            INSERT INTO `" . DB_PREFIX . "setting`
            SET `store_id` = '0',
                `code` = '" . $db->escape(self::SETTING_CODE) . "',
                `key` = '" . $db->escape(self::RECORD_KEY) . "',
                `value` = '" . $db->escape((string)json_encode($record)) . "',
                `serialized` = '1'
        ");

        self::$record_memo = $record;

        $config = self::config();

        if ($config !== null) {
            $config->set(self::RECORD_KEY, $record);
        }
    }

    public static function deleteRecord(): void
    {
        $db = self::db();

        if ($db === null) {
            return;
        }

        $db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '0' AND `code` = '" . $db->escape(self::SETTING_CODE) . "'");

        self::$record_memo = [];

        $config = self::config();

        if ($config !== null) {
            $config->set(self::RECORD_KEY, []);
        }
    }

    /**
     * A durable blob that is never eager-loaded.
     */
    public static function getBlob(string $key, array $default = []): array
    {
        $stored = self::read($key, false);

        return $stored === null ? $default : $stored;
    }

    public static function putBlob(string $key, array $value): void
    {
        self::write($key, $value, 0);
    }

    public static function deleteBlob(string $key): void
    {
        self::delete($key);
    }

    /**
     * A blob with a deadline. The TTL is a backstop, never the authority —
     * every read re-validates against the session record.
     */
    public static function getExpiring(string $key): ?array
    {
        return self::read($key, true);
    }

    public static function putExpiring(string $key, array $value, int $ttl_seconds): void
    {
        self::write($key, $value, time() + max(60, $ttl_seconds));
    }

    public static function deleteExpiring(string $key): void
    {
        self::delete($key);
    }

    /**
     * Take a lock that genuinely fails under contention.
     *
     * A read-then-write would hand the lock to both of two concurrent callers,
     * and for the start lock that means two valid tokens, one of which no
     * teardown path knows about. INSERT IGNORE affects exactly one row for
     * exactly one caller.
     */
    public static function claimLock(string $name, int $ttl_seconds): bool
    {
        $owner = bin2hex(random_bytes(8));

        if (self::insertLock($name, $owner)) {
            return true;
        }

        if (!self::lockIsStale($name, $ttl_seconds)) {
            return false;
        }

        // An abandoned lock: clear it and try exactly once more, so a crashed
        // request cannot block the feature forever and a live holder is never
        // displaced by an unbounded retry loop.
        self::forceReleaseLock($name);

        return self::insertLock($name, $owner);
    }

    /**
     * One INSERT IGNORE, which affects a row for exactly one concurrent caller.
     */
    private static function insertLock(string $name, string $owner): bool
    {
        $db = self::db();

        if ($db === null) {
            return false;
        }

        $sql = "
            INSERT IGNORE INTO `" . DB_PREFIX . self::TABLE . "`
            SET `key` = '" . $db->escape($name) . "',
                `value` = '" . $db->escape((string)json_encode(['owner' => $owner, 'at' => time()])) . "',
                `expires_at` = '0',
                `updated_at` = NOW()
        ";

        try {
            $db->query($sql);
        } catch (\Throwable $exception) {
            if (!self::ensureTable()) {
                return false;
            }

            try {
                $db->query($sql);
            } catch (\Throwable $retry) {
                return false;
            }
        }

        if ($db->countAffected() === 1) {
            self::$lock_owners[$name] = $owner;

            return true;
        }

        return false;
    }

    /**
     * Create the module table once per request, for a store that installed the
     * extension before telemetry existed.
     */
    private static function ensureTable(): bool
    {
        if (self::$table_checked) {
            return false;
        }

        self::$table_checked = true;

        try {
            self::install();
        } catch (\Throwable $exception) {
            return false;
        }

        return true;
    }

    /**
     * Release a lock only if this request is still the holder: a request that
     * overran the TTL and had its lock stolen must not delete the new holder's.
     */
    public static function releaseLock(string $name): void
    {
        if (!isset(self::$lock_owners[$name])) {
            return;
        }

        $owner = self::$lock_owners[$name];
        unset(self::$lock_owners[$name]);

        $held = self::read($name, false);

        if (is_array($held) && isset($held['owner']) && $held['owner'] !== $owner) {
            return;
        }

        self::forceReleaseLock($name);
    }

    /**
     * Write a log line whatever the merchant's logging preference is.
     *
     * Starting and stopping a session is an audit event: a store with logging
     * switched off must still leave a record that data left it.
     */
    public static function audit(string $message, array $context = []): void
    {
        $line = $message . ' ' . (string)json_encode($context);

        try {
            $log = new \Opencart\System\Library\Log('paypercut_telemetry.log');
            $log->write($line);
        } catch (\Throwable $exception) {
            // Diagnostics must never break a checkout; a lost audit line is
            // the lesser failure.
        }
    }

    /**
     * Create the module-owned table. Called from the extension's install().
     */
    public static function install(): void
    {
        $db = self::db();

        if ($db === null) {
            return;
        }

        $db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . self::TABLE . "` (
                `key` varchar(64) NOT NULL,
                `value` mediumtext NOT NULL,
                `expires_at` int(11) NOT NULL DEFAULT 0,
                `updated_at` datetime NOT NULL,
                PRIMARY KEY (`key`),
                KEY `expires_at` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
    }

    /**
     * Remove every trace of the feature. Called from uninstall().
     */
    public static function purge(): void
    {
        $db = self::db();

        if ($db === null) {
            return;
        }

        $db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . self::TABLE . "`");

        self::deleteRecord();
    }

    private static function lockIsStale(string $name, int $ttl_seconds): bool
    {
        $held = self::read($name, false);

        if (!is_array($held) || !isset($held['at'])) {
            return true;
        }

        return (time() - (int)$held['at']) > $ttl_seconds;
    }

    private static function forceReleaseLock(string $name): void
    {
        self::delete($name);
    }

    private static function read(string $key, bool $honour_expiry): ?array
    {
        $db = self::db();

        if ($db === null) {
            return null;
        }

        try {
            $query = $db->query("SELECT `value`, `expires_at` FROM `" . DB_PREFIX . self::TABLE . "` WHERE `key` = '" . $db->escape($key) . "' LIMIT 1");
        } catch (\Throwable $exception) {
            return null;
        }

        if (!$query->num_rows) {
            return null;
        }

        $expires_at = (int)$query->row['expires_at'];

        if ($honour_expiry && $expires_at > 0 && $expires_at <= time()) {
            self::delete($key);

            return null;
        }

        $decoded = json_decode((string)$query->row['value'], true);

        return is_array($decoded) ? $decoded : null;
    }

    private static function write(string $key, array $value, int $expires_at): void
    {
        $db = self::db();

        if ($db === null) {
            return;
        }

        // An empty value leaves no row behind, so an emptied queue costs nothing.
        if (empty($value)) {
            self::delete($key);

            return;
        }

        try {
            $db->query("
                REPLACE INTO `" . DB_PREFIX . self::TABLE . "`
                SET `key` = '" . $db->escape($key) . "',
                    `value` = '" . $db->escape((string)json_encode($value)) . "',
                    `expires_at` = '" . (int)$expires_at . "',
                    `updated_at` = NOW()
            ");
        } catch (\Throwable $exception) {
            if (self::ensureTable()) {
                self::write($key, $value, $expires_at);
            }
        }
    }

    private static function delete(string $key): void
    {
        $db = self::db();

        if ($db === null) {
            return;
        }

        try {
            $db->query("DELETE FROM `" . DB_PREFIX . self::TABLE . "` WHERE `key` = '" . $db->escape($key) . "'");
        } catch (\Throwable $exception) {
            // Best-effort.
        }
    }
}
