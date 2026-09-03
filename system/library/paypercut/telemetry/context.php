<?php

namespace Paypercut\Telemetry;

/**
 * Which kind of request this is.
 *
 * The guard has to be stricter than "an admin controller is running": the
 * storefront webhook and the checkout AJAX endpoints live in the catalog
 * application but are anonymous, and nothing there may mint, POST, tear a
 * session down or write a file. Only a request that entered through the admin
 * front controller with a valid user token qualifies.
 */
final class Context
{
    /** @var bool */
    private static $admin = false;

    private function __construct()
    {
    }

    /**
     * Mark this request as an authenticated admin request. Called by the admin
     * controllers, which OpenCart only reaches with a valid user token.
     */
    public static function markAdmin(): void
    {
        self::$admin = true;
    }

    public static function isAdmin(): bool
    {
        return self::$admin;
    }
}
