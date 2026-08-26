<?php

namespace Paypercut\Telemetry;

use Paypercut\Environment;
use Paypercut\Plugin;

/**
 * Builds the one-off environment snapshot sent when a session starts.
 *
 * Reads the store's own configuration only. Every value it collects is named
 * explicitly here and cast by Event::environmentSnapshot() /
 * Event::environmentConfiguration(); nothing is harvested by walking a settings
 * array, which is how a credential would end up on the wire.
 */
final class EnvironmentSnapshot
{
    const SUPPORTED_CURRENCIES = ['BGN', 'DKK', 'SEK', 'NOK', 'GBP', 'EUR', 'USD', 'CHF', 'CZK', 'HUF', 'PLN', 'RON'];

    private function __construct()
    {
    }

    public static function values(): array
    {
        $currency = strtoupper((string)Store::setting('config_currency', ''));
        $theme = (string)Store::setting('config_theme', '');

        return [
            'plugin_version'            => Plugin::version(),
            'oc_version'                => defined('VERSION') ? (string)constant('VERSION') : '',
            'php_version'               => PHP_VERSION,
            'theme_name'                => $theme,
            'theme_version'             => self::themeVersion($theme),
            'is_multistore'             => self::isMultistore(),
            'is_ssl'                    => self::isSsl(),
            'checkout_mode'             => (string)Store::setting('payment_paypercut_checkout_mode', 'hosted'),
            'order_status'              => (string)Store::setting('payment_paypercut_order_status_id', ''),
            'connection_environment'    => Environment::normalize(Store::setting('payment_paypercut_environment', '')),
            'api_key_mode'              => self::apiKeyMode((string)Store::setting('payment_paypercut_api_key', '')),
            'payment_enabled'           => (bool)Store::setting('payment_paypercut_status', false),
            'google_pay_enabled'        => (bool)Store::setting('payment_paypercut_google_pay', false),
            'apple_pay_enabled'         => (bool)Store::setting('payment_paypercut_apple_pay', false),
            'logging_enabled'           => (bool)Store::setting('payment_paypercut_logging', false),
            // Presence booleans derived from secret-bearing settings: the value
            // never travels, only whether one exists.
            'webhook_configured'        => (string)Store::setting('payment_paypercut_webhook_secret', '') !== '',
            'payment_domain_registered' => (string)Store::setting('payment_paypercut_domain_id', '') !== '',
            'payment_config_selected'   => (string)Store::setting('payment_paypercut_payment_method_config', '') !== '',
            'statement_descriptor_set'  => (string)Store::setting('payment_paypercut_statement_descriptor', '') !== '',
            'applepay_file_deployed'    => self::applePayFileDeployed(),
            'store_currency'            => $currency,
            'currency_supported'        => in_array($currency, self::SUPPORTED_CURRENCIES, true),
            'sort_order'                => (string)Store::setting('payment_paypercut_sort_order', '')
        ];
    }

    private static function apiKeyMode(string $api_key): string
    {
        if ($api_key === '') {
            return '';
        }

        if (strpos($api_key, 'sk_test') === 0) {
            return 'test';
        }

        if (strpos($api_key, 'sk_live') === 0) {
            return 'live';
        }

        return 'unknown';
    }

    private static function themeVersion(string $theme): string
    {
        $db = Store::db();

        if ($db === null || $theme === '') {
            return '';
        }

        try {
            $query = $db->query("SELECT `version` FROM `" . DB_PREFIX . "extension_install` WHERE `code` = '" . $db->escape($theme) . "' LIMIT 1");
        } catch (\Throwable $exception) {
            return '';
        }

        return $query->num_rows ? (string)$query->row['version'] : '';
    }

    private static function isMultistore(): bool
    {
        $db = Store::db();

        if ($db === null) {
            return false;
        }

        try {
            $query = $db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "store`");
        } catch (\Throwable $exception) {
            return false;
        }

        return (int)$query->row['total'] > 0;
    }

    private static function isSsl(): bool
    {
        return strpos(strtolower((string)Store::setting('config_url', '')), 'https://') === 0;
    }

    private static function applePayFileDeployed(): bool
    {
        if (!defined('DIR_OPENCART')) {
            return false;
        }

        return is_file(constant('DIR_OPENCART') . '.well-known/apple-developer-merchantid-domain-association');
    }
}
