<?php

namespace Paypercut\Telemetry;

/**
 * The store's installed extensions, for correlating a failure with a conflict.
 *
 * Codes and versions only. An extension's code is public — it is what the
 * marketplace serves it under — but its author and path are not needed to
 * reproduce a conflict.
 */
final class ActiveExtensions
{
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
                    $extensions[$code] = (string)$row['version'];
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
                    $extensions[$code] = '';
                }
            }
        } catch (\Throwable $exception) {
            // Best-effort inventory.
        }

        ksort($extensions);

        return $extensions;
    }
}
