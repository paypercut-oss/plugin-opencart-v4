<?php

namespace Paypercut\Telemetry;

/**
 * Reports the fatal errors a debug session would otherwise never see.
 *
 * A fatal on the checkout page breaks our payment form whichever extension
 * raised it, and it never reaches a catch block — so the session sees nothing
 * at all unless the shutdown handler looks.
 */
final class FatalErrorWatch
{
    /** The levels that end a request. A warning is noise; these are the bug. */
    const FATAL_LEVELS = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    /** @var bool */
    private static $registered = false;

    private function __construct()
    {
    }

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        // Registered after EventRecorder's own shutdown hook has had a chance
        // to be registered, so this still runs once the buffer is persisted.
        register_shutdown_function([self::class, 'report']);
    }

    /**
     * Record the fatal that ended this request, if there was one.
     */
    public static function report(): void
    {
        $error = error_get_last();

        if ($error === null || !in_array($error['type'] ?? 0, self::FATAL_LEVELS, true)) {
            return;
        }

        if (!Store::bound() || !TelemetrySession::isActiveFast()) {
            return;
        }

        $event = Event::fatal(
            (string)($error['message'] ?? ''),
            (string)($error['file'] ?? ''),
            (int)($error['line'] ?? 0),
            (int)($error['type'] ?? 0)
        );

        try {
            // The recorder's own shutdown hook has already run, so this writes
            // directly rather than buffering for a flush that will never come.
            EventQueue::append([$event->envelope()]);
        } catch (\Throwable $exception) {
            // Nothing useful can be done from inside a shutdown handler.
        }
    }

    /**
     * Test seam.
     */
    public static function reset(): void
    {
        self::$registered = false;
    }
}
