<?php

/**
 * Keeps the event catalogue honest.
 *
 * Scans every source file for a named event constructor and asserts the name
 * appears in docs/telemetry.md, so an event added at a call site nobody
 * documents fails here rather than surfacing in Loki six months later.
 *
 * It also asserts that each payment-outcome path still contains a recorder
 * call: each of these was silent before, which is why a merchant's "it just did
 * nothing" was unanswerable, and a file dropping off this list is that
 * regression returning.
 */

$root = dirname(__DIR__);

require_once $root . '/system/library/paypercut/bootstrap.php';

\Paypercut\Bootstrap::load();

$documented = file_get_contents($root . '/docs/telemetry.md');

/** Emitted by the library rather than by a call site. */
$library_events = [
    'session.started',
    'session.stopped',
    'environment.snapshot',
    'environment.configuration',
    'environment.plugins',
    'php.fatal'
];

$instrumented_paths = [
    'catalog/controller/extension/paypercut/payment/paypercut.php',
    'admin/controller/extension/paypercut/payment/paypercut.php',
    'admin/controller/extension/paypercut/payment/paypercut_order.php'
];

$failures = 0;
$names = $library_events;

foreach (['admin', 'catalog'] as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory));

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        preg_match_all(
            "/Event::(?:of|failure|apiFailure)\(\s*'([a-z_]+\.[a-z_.]+)'/",
            (string)file_get_contents($file->getPathname()),
            $matches
        );

        foreach ($matches[1] as $name) {
            $names[] = $name;
        }
    }
}

$names = array_values(array_unique($names));
sort($names);

foreach ($names as $name) {
    if (strpos($documented, '`' . $name . '`') === false) {
        echo "FAIL  " . $name . " is emitted but not documented in docs/telemetry.md\n";
        $failures++;
    }

    // The gate screens the whole envelope, the event name included, so a name
    // that trips the deny patterns would silently bin its own event.
    if (\Paypercut\Telemetry\Event::isDenied(['event' => $name])) {
        echo "FAIL  " . $name . " trips the deny assertion\n";
        $failures++;
    }
}

foreach ($instrumented_paths as $path) {
    if (strpos((string)file_get_contents($root . '/' . $path), '$this->report(') === false) {
        echo "FAIL  " . $path . " reports nothing\n";
        $failures++;
    }
}

echo ($failures === 0 ? 'OK' : 'FAILED') . ': ' . count($names) . " event names, " . $failures . " failures\n";

exit($failures === 0 ? 0 : 1);
