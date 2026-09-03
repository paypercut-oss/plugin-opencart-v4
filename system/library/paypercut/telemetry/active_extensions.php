<?php

namespace Paypercut\Telemetry;

/**
 * The store's installed extensions, for correlating a failure with a conflict.
 *
 * Codes and versions only. An extension's code is public — it is what the
 * marketplace serves it under — but its author and path are not needed to
 * reproduce a conflict.
 *
 * A version is never an empty string: the edge discards an attribute whose
 * value is empty, and it discards the key with it, so an unversioned extension
 * used to arrive as no extension at all — which is the one thing this event
 * exists to name.
 */
final class ActiveExtensions
{
    /**
     * Stands in for a version OpenCart never recorded.
     */
    public const UNKNOWN_VERSION = 'unknown';

    private function __construct()
    {
    }

    /**
     * @return array code => version, sorted by code.
     */
    public static function values(): array
    {
        $db = Store::db();

        if ($db === null) {
            return [];
        }

        $extensions = [];

        try {
            $query = $db->query("SELECT `code`, `version` FROM `" . DB_PREFIX . "extension_install`");

            foreach ($query->rows as $row) {
                $code = (string)$row['code'];

                if ($code !== '') {
                    $version = trim((string)$row['version']);
                    $extensions[$code] = $version === '' ? self::UNKNOWN_VERSION : $version;
                }
            }
        } catch (\Throwable $exception) {
            // A store on a trimmed schema still gets the enabled list below.
        }

        try {
            $query = $db->query("SELECT DISTINCT `extension` FROM `" . DB_PREFIX . "extension`");

            foreach ($query->rows as $row) {
                $code = (string)$row['extension'];

                if ($code !== '' && !isset($extensions[$code])) {
                    $extensions[$code] = self::UNKNOWN_VERSION;
                }
            }
        } catch (\Throwable $exception) {
            // Best-effort inventory.
        }

        ksort($extensions);

        return $extensions;
    }
}
