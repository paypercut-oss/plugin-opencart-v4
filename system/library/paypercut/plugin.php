<?php

namespace Paypercut;

/**
 * Facts about this extension that the telemetry code needs.
 */
final class Plugin
{
    /** @var string|null */
    private static $version = null;

    private function __construct()
    {
    }

    /**
     * The installed extension version, or 'dev' when it cannot be read.
     *
     * Read from install.json rather than duplicated in a constant, so the
     * release pipeline's single version check stays the only source of truth.
     */
    public static function version(): string
    {
        if (self::$version !== null) {
            return self::$version;
        }

        self::$version = 'dev';

        $manifest = self::root() . 'install.json';

        if (is_file($manifest)) {
            $decoded = json_decode((string)file_get_contents($manifest), true);

            if (is_array($decoded) && !empty($decoded['version']) && is_string($decoded['version'])) {
                self::$version = $decoded['version'];
            }
        }

        return self::$version;
    }

    /**
     * Absolute path of the extension root, with a trailing slash.
     */
    public static function root(): string
    {
        return rtrim(dirname(__DIR__, 3), '/') . '/';
    }
}
