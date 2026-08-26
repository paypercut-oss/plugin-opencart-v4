<?php

/**
 * Telemetry unit tests.
 *
 * Deliberately dependency-free: OpenCart 4 ships no test runner, and the parts
 * worth pinning — the deny assertion, the environment pairing, the queue caps
 * and the flusher's decision table — are pure functions with no store behind
 * them. Run with `php tests/run.php`.
 */

define('DIR_OPENCART', '/var/www/html/');
define('DIR_APPLICATION', DIR_OPENCART . 'admin/');
define('DIR_CATALOG', DIR_OPENCART . 'catalog/');
define('DIR_SYSTEM', DIR_OPENCART . 'system/');
define('DIR_EXTENSION', DIR_OPENCART . 'extension/');

require_once __DIR__ . '/../system/library/paypercut/bootstrap.php';

\Paypercut\Bootstrap::load();

use Paypercut\Environment;
use Paypercut\Telemetry\Event;
use Paypercut\Telemetry\EventQueue;
use Paypercut\Telemetry\Flusher;
use Paypercut\Telemetry\TelemetrySession;

$failures = 0;
$assertions = 0;

function check(string $name, $actual, $expected): void
{
    global $failures, $assertions;

    $assertions++;

    if ($actual === $expected) {
        return;
    }

    $failures++;
    echo "FAIL  " . $name . "\n";
    echo "      expected: " . var_export($expected, true) . "\n";
    echo "      actual:   " . var_export($actual, true) . "\n";
}

/* ---------------------------------------------------------------- environment */

check('api base falls back to production', Environment::apiBaseUri(''), 'https://api.paypercut.io/');
check('api base for dev', Environment::apiBaseUri('dev'), 'https://api.dev.paypercut.net/');
check('api base for stage', Environment::apiBaseUri('stage'), 'https://api.stage.paypercut.net/');
check('api base for an unknown environment', Environment::apiBaseUri('sandbox'), 'https://api.paypercut.io/');

check('edge base for production', Environment::telemetryBaseUri('production'), 'https://telemetry.paypercut.io/');
check('edge base for dev', Environment::telemetryBaseUri('dev'), 'https://telemetry.dev.paypercut.net/');
check('edge base for stage', Environment::telemetryBaseUri('stage'), 'https://telemetry.stage.paypercut.net/');
check('edge base refuses an unset environment', Environment::telemetryBaseUri(''), '');
check('edge base refuses an unknown environment', Environment::telemetryBaseUri('sandbox'), '');

// Both hosts must come from the same value, or a token minted for one
// environment gets a 401 from another that is indistinguishable from a forgery.
foreach (['dev', 'stage', 'production'] as $environment) {
    $mint = parse_url(Environment::apiBaseUri($environment), PHP_URL_HOST);
    $edge = parse_url(Environment::telemetryBaseUri($environment), PHP_URL_HOST);

    check(
        'mint and edge agree on the environment suffix (' . $environment . ')',
        substr((string)$mint, strpos((string)$mint, '.') ?: 0),
        substr((string)$edge, strpos((string)$edge, '.') ?: 0)
    );
}

check('https on a paypercut host is accepted', Environment::allowedPaypercutBase('https://telemetry.paypercut.io'), 'https://telemetry.paypercut.io/');
check('a lookalike suffix is refused', Environment::allowedPaypercutBase('https://paypercut.io.evil.com/'), '');
check('a lookalike prefix is refused', Environment::allowedPaypercutBase('https://notpaypercut.io/'), '');
check('a lookalike tld is refused', Environment::allowedPaypercutBase('https://paypercut.io.co/'), '');
check('plain http is refused', Environment::allowedPaypercutBase('http://telemetry.paypercut.io/'), '');
check('an empty base is refused', Environment::allowedPaypercutBase(''), '');

/* ------------------------------------------------------------ deny assertion */

$secrets = ['sk_live_realstorekey', 'whsec_realwebhooksecret'];

function denied(array $attrs, array $secrets = []): bool
{
    return Event::isDenied(['attrs' => $attrs], $secrets);
}

check('a denied key name drops the event', denied(['api_client_secret' => 'x']), true);
check('a token key drops the event', denied(['telemetry_token' => 'x']), true);
check('an _key suffix drops the event', denied(['api_key' => 'x']), true);
check('a nonce drops the event', denied(['nonce' => 'x']), true);
check('an authorization key drops the event', denied(['authorization' => 'x']), true);
check('a password drops the event', denied(['password' => 'x']), true);

check('a credential shape mid-string drops the event', denied(['note' => 'rejected ppc_live_store_secret']), true);
check('a JWT shape drops the event', denied(['note' => 'token eyJhbGciOiJSUzI1NiJ9.body']), true);
check('disk_usage is permitted', denied(['note' => 'disk_usage exceeded']), false);
check('risk_free is permitted', denied(['note' => 'risk_free window elapsed']), false);
check('backpack_pk_none is permitted', denied(['note' => 'backpack_pk_none missing']), false);

check('a PAN mid-string drops the event', denied(['note' => 'Card 4111111111111111 was declined']), true);
check('a spaced PAN drops the event', denied(['note' => 'card 4111 1111 1111 1111 declined']), true);
check('a non-Luhn 16-digit id is permitted', denied(['note' => 'transaction 1234567890123456 not found']), false);
check('a millisecond timestamp is permitted', denied(['note' => 'expired at 1787250271000']), false);
check('a minor-unit amount is permitted', denied(['note' => 'amount 4250 refused']), false);

check('the literal API key drops the event', denied(['note' => 'call failed for sk_live_realstorekey'], $secrets), true);
check('the literal webhook secret drops the event', denied(['note' => 'whsec_realwebhooksecret'], $secrets), true);
check('an empty secret does not match everything', Event::isDenied(['attrs' => ['note' => 'fine']], ['']), false);

// `error` is a top-level sibling of `attrs`: if it is not screened explicitly
// it bypasses the one gate every producer funnels through.
check(
    'a secret in error.message drops the event',
    Event::isDenied(['error' => ['message' => 'rejected sk_live_realstorekey']], $secrets),
    true
);
check(
    'a secret in error.stack drops the event',
    Event::isDenied(['error' => ['stack' => ['file.php:1 ppc_live_leak']]], $secrets),
    true
);

/* --------------------------------------------------- named constructors only */

$snapshot = Event::environmentSnapshot([
    'plugin_version'                   => '1.0.5',
    'api_client_secret'                => 'sk_live_realstorekey',
    'payment_paypercut_webhook_secret' => 'whsec_realwebhooksecret'
]);

check('the snapshot walks its own schema, not the caller array', $snapshot->fields(), ['plugin_version' => '1.0.5']);

$configuration = Event::environmentConfiguration([
    'checkout_mode'     => 'hosted',
    'api_client_secret' => 'sk_live_realstorekey'
]);

check('the configuration snapshot walks its own schema', $configuration->fields(), ['checkout_mode' => 'hosted']);

check(
    'session.started carries no store-user identifier',
    array_keys(Event::sessionStarted('dbg_abc', 'production', 1787250271)->fields()),
    ['session_id', 'environment', 'expires_at']
);

/* ------------------------------------------------ upstream text never travels */

$api = Event::apiFailure('api.request_failed', 401, [
    'error' => [
        'type'    => 'invalid_request_error',
        'code'    => 'token_invalid',
        'message' => "The provided access token 'sk_test_probe' is invalid."
    ],
    'trace_id' => 'da74bc'
], ['api_context' => 'create_checkout']);

$envelope = $api->envelope(1787250271);

check('an API failure never carries the upstream message', isset($envelope['error']['message']), false);
check('an API failure keeps the error type', $envelope['error']['type'], 'invalid_request_error');
check('an API failure keeps the error code', $envelope['error']['code'], 'http_401');
check('an API failure carries api_code', $envelope['attrs']['api_code'], 'token_invalid');
check('an API failure carries trace_id', $envelope['attrs']['trace_id'], 'da74bc');
check('an API failure carries the status', $envelope['attrs']['http_status'], 401);

// A message this extension authored is the diagnosis and stays.
$mine = Event::failure('checkout.order_missing', 'order_not_found')->because('Order 178 had no Paypercut session');
check('an authored message survives', $mine->envelope(1)['error']['message'], 'Order 178 had no Paypercut session');

/* ---------------------------------------------------------- string bounding */

check('control characters are stripped', Event::text("a\x00b\x1Fc"), 'abc');
check('utf-8 survives', Event::text('Θέμα Ελλάδα'), 'Θέμα Ελλάδα');
check('text is clamped on a byte budget', strlen(Event::text(str_repeat('é', 400))) <= Event::MAX_TEXT_BYTES, true);
check('an identifier passes through', Event::identifier('hosted'), 'hosted');
check('an email is dropped, not mangled', Event::identifier('jane@example.com'), '');
check('an address is dropped', Event::identifier('12 Sunset Road'), '');
check('an over-long identifier is dropped', Event::identifier(str_repeat('a', 65)), '');

/* ------------------------------------------------------------------ envelope */

$plain = Event::of('checkout.hosted.redirected', ['order_status' => 'pending'])
    ->about(['order_ref' => '178', 'payment_id' => 'pay_1'])
    ->envelope(1787250271);

check('occurred_at is an RFC3339 string', $plain['occurred_at'], '2026-08-20T18:24:31Z');
check('correlation fields sit outside attrs', $plain['order_ref'], '178');
check('attrs are present when non-empty', $plain['attrs'], ['order_status' => 'pending']);
check('an empty event omits attrs', isset(Event::of('session.probe')->envelope(1)['attrs']), false);

check('a non-scalar attribute is dropped', Event::of('x', ['a' => ['b']])->fields(), []);
check('a false boolean survives', Event::of('x', ['duplicate' => false])->fields(), ['duplicate' => false]);
check('an int survives', Event::of('x', ['http_status' => 503])->fields(), ['http_status' => 503]);
check('attrs are capped', count(Event::of('x', array_fill_keys(range(1, 40), 'v'))->fields()), Event::MAX_ATTRS);

/* --------------------------------------------------------------- queue caps */

$many = [];
for ($i = 0; $i < 260; $i++) {
    $many[] = ['event' => 'e' . $i];
}

$capped = EventQueue::cap($many);
check('the queue caps at MAX_QUEUE_EVENTS', count($capped['envelopes']), TelemetrySession::MAX_QUEUE_EVENTS);
check('capping counts what it dropped', $capped['dropped'], 60);
check('capping drops the oldest first', $capped['envelopes'][0]['event'], 'e60');

$split = EventQueue::splitBatch($many, 200, 50);
check('a split never exceeds the event cap', count($split['batch']) <= 50, true);
check('a split loses nothing', count($split['batch']) + count($split['remainder']), 260);
check('a split never reorders', array_merge($split['batch'], $split['remainder']), $many);

$oversized = [['event' => 'big', 'attrs' => ['note' => str_repeat('x', 500)]], ['event' => 'small']];
$split = EventQueue::splitBatch($oversized, 10, 50);
check('a split always takes at least one envelope', count($split['batch']), 1);

/* ------------------------------------------------------- flusher decisions */

check('202 is accepted and clears the batch', Flusher::decide(202, 0, 0), ['outcome' => 'accepted', 'end_session' => false, 'retry_in' => 0, 'clears_batch' => true]);
check('401 ends the session and never re-mints', Flusher::decide(401, 0, 0), ['outcome' => 'token_rejected', 'end_session' => true, 'retry_in' => 0, 'clears_batch' => true]);
check('413 splits without counting a failure', Flusher::decide(413, 0, 3), ['outcome' => 'split', 'end_session' => false, 'retry_in' => 0, 'clears_batch' => false]);
check('429 honours Retry-After', Flusher::decide(429, 300, 0)['retry_in'], 300);
check('429 clamps a hostile Retry-After', Flusher::decide(429, 99999, 0)['retry_in'], 900);
check('429 without a header waits a minute', Flusher::decide(429, 0, 0)['retry_in'], 60);
check('429 never ends the session', Flusher::decide(429, 0, 9)['end_session'], false);
check('503 never ends the session', Flusher::decide(503, 0, 9), ['outcome' => 'unready', 'end_session' => false, 'retry_in' => 120, 'clears_batch' => false]);
check('504 never ends the session', Flusher::decide(504, 0, 9)['end_session'], false);
check('400 drops the batch', Flusher::decide(400, 0, 0), ['outcome' => 'poison', 'end_session' => false, 'retry_in' => 30, 'clears_batch' => true]);
check('400 gives up at the end of the ladder', Flusher::decide(400, 0, 3)['end_session'], true);
check('a transport failure retries', Flusher::decide(0, 0, 0), ['outcome' => 'failed', 'end_session' => false, 'retry_in' => 30, 'clears_batch' => false]);
check('the backoff ladder climbs', [Flusher::decide(0, 0, 0)['retry_in'], Flusher::decide(0, 0, 1)['retry_in'], Flusher::decide(0, 0, 2)['retry_in']], [30, 120, 300]);
check('the ladder gives up on the fourth attempt', Flusher::decide(500, 0, 3)['end_session'], true);

/* ------------------------------------------------------------------- origin */

check('no frames means our own code', Event::origin([]), ['origin' => 'paypercut']);
check('an extension frame names the extension', Event::origin([DIR_EXTENSION . 'other_module/catalog/controller/x.php']), ['origin' => 'plugin', 'origin_plugin' => 'other_module']);
check('a theme frame is a theme', Event::origin([DIR_CATALOG . 'view/theme/custom/template/x.twig']), ['origin' => 'theme']);
check('anything else is core', Event::origin([DIR_SYSTEM . 'engine/loader.php']), ['origin' => 'core']);

echo ($failures === 0 ? 'OK' : 'FAILED') . ': ' . $assertions . " assertions, " . $failures . " failures\n";

exit($failures === 0 ? 0 : 1);
