<?php

/**
 * Loads the Paypercut telemetry classes and binds them to OpenCart.
 *
 * Explicit requires rather than the OpenCart autoloader: an extension's
 * namespaces are only registered while an extension list page is rendering, so
 * a storefront or webhook request cannot rely on them being in place.
 */

namespace Paypercut;

use Paypercut\Telemetry\Context;
use Paypercut\Telemetry\FatalErrorWatch;
use Paypercut\Telemetry\Store;

final class Bootstrap
{
    /** @var bool */
    private static $loaded = false;

    private function __construct()
    {
    }

    /**
     * Make the telemetry API available and point it at this store.
     *
     * @param object $registry The OpenCart registry.
     * @param bool   $admin    True only for a request that entered through the
     *                         admin front controller with a valid user token.
     */
    public static function boot($registry, bool $admin = false): void
    {
        self::load();

        Store::bind($registry);

        if ($admin) {
            Context::markAdmin();
        }

        // Registered after the recorder's class is loaded, so the recorder's
        // own shutdown hook is always the earlier of the two.
        FatalErrorWatch::register();
    }

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        self::$loaded = true;

        $base = __DIR__ . '/';

        require_once $base . 'plugin.php';
        require_once $base . 'environment.php';
        require_once $base . 'telemetry/context.php';
        require_once $base . 'telemetry/store.php';
        require_once $base . 'telemetry/event.php';
        require_once $base . 'telemetry/telemetry_session.php';
        require_once $base . 'telemetry/event_queue.php';
        require_once $base . 'telemetry/event_recorder.php';
        require_once $base . 'telemetry/sent_log.php';
        require_once $base . 'telemetry/http.php';
        require_once $base . 'telemetry/mint_error_mapper.php';
        require_once $base . 'telemetry/token_minter.php';
        require_once $base . 'telemetry/edge_client.php';
        require_once $base . 'telemetry/flusher.php';
        require_once $base . 'telemetry/environment_snapshot.php';
        require_once $base . 'telemetry/active_extensions.php';
        require_once $base . 'telemetry/fatal_error_watch.php';
    }
}
