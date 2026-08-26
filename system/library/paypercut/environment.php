<?php

namespace Paypercut;

/**
 * Resolves the Paypercut service hosts a store talks to.
 *
 * Both hosts come from the one stored environment value: a telemetry token
 * minted for one environment is rejected by every other environment's edge
 * with a 401 that looks exactly like a forged token, so the mint host and the
 * edge host must never be resolved from two independent settings.
 */
final class Environment
{
    const DEFAULT_ENVIRONMENT = 'production';

    const ENVIRONMENTS = ['dev', 'stage', 'production'];

    private static $api_base_uris = [
        'dev'        => 'https://api.dev.paypercut.net/',
        'stage'      => 'https://api.stage.paypercut.net/',
        'production' => 'https://api.paypercut.io/'
    ];

    private static $telemetry_base_uris = [
        'dev'        => 'https://telemetry.dev.paypercut.net/',
        'stage'      => 'https://telemetry.stage.paypercut.net/',
        'production' => 'https://telemetry.paypercut.io/'
    ];

    private function __construct()
    {
    }

    /**
     * The stored environment, or '' when it is unset or unrecognised.
     */
    public static function normalize($environment): string
    {
        $environment = strtolower(trim((string)$environment));

        return in_array($environment, self::ENVIRONMENTS, true) ? $environment : '';
    }

    /**
     * Base URI of the Paypercut API.
     *
     * Falls back to production for an unknown environment: this is the payment
     * API, and a store that has never chosen an environment must keep working.
     */
    public static function apiBaseUri(string $environment = ''): string
    {
        $environment = self::normalize($environment);

        if ($environment === '') {
            $environment = self::DEFAULT_ENVIRONMENT;
        }

        $base = self::allowedPaypercutBase(self::$api_base_uris[$environment]);

        return $base !== '' ? $base : self::$api_base_uris[self::DEFAULT_ENVIRONMENT];
    }

    /**
     * Base URI of the telemetry edge.
     *
     * Unlike the API base this does NOT fall back to production: an unknown
     * environment must yield no debug session rather than a confusing one.
     */
    public static function telemetryBaseUri(string $environment = ''): string
    {
        $environment = self::normalize($environment);

        if ($environment === '') {
            return '';
        }

        // Not honoured on production: a constant left in config.php after
        // debugging must not retarget a live store's telemetry, and the mint
        // host — resolved separately — would not follow it.
        if ($environment !== 'production' && defined('PAYPERCUT_TELEMETRY_BASE_URI')) {
            return self::allowedPaypercutBase((string)constant('PAYPERCUT_TELEMETRY_BASE_URI'));
        }

        return self::allowedPaypercutBase(self::$telemetry_base_uris[$environment]);
    }

    /**
     * Accept a base URI only on an https Paypercut host.
     *
     * The store's API key travels on the mint request, so an override must not
     * be able to redirect one elsewhere. The end-of-string anchor is
     * load-bearing: it rejects https://paypercut.io.evil.com/ and friends.
     */
    public static function allowedPaypercutBase(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        $scheme = isset($parts['scheme']) ? strtolower((string)$parts['scheme']) : '';
        $host = isset($parts['host']) ? strtolower((string)$parts['host']) : '';

        if ($scheme !== 'https' || $host === '' || !preg_match('/(^|\.)paypercut\.(net|io)\z/D', $host)) {
            return '';
        }

        return rtrim($url, '/') . '/';
    }
}
