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
use Paypercut\Telemetry\Store;
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

// A constant that moved the edge without moving the mint host would earn a 401
// the session never recovers from, so there is no override to honour.
define('PAYPERCUT_TELEMETRY_BASE_URI', 'https://telemetry.elsewhere.paypercut.net/');
check('no config constant can move the edge host', Environment::telemetryBaseUri('dev'), 'https://telemetry.dev.paypercut.net/');

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
check('a bare auth key drops the event', denied(['auth' => 'x']), true);

// The key screen matches name segments, not substrings: extension slugs are
// keys too, and a substring match costs the inventory its auth extensions.
check('an authorizenet slug is permitted', denied(['payment.authorizenet_aim' => '3.0']), false);
check('a two_factor_auth slug is permitted', denied(['ocmod.two_factor_auth' => '1.2']), false);
check('a nonce_shield slug is permitted', denied(['ocmod.nonce_shield' => '2.1']), false);
check('an author key is permitted', denied(['theme_author' => 'acme']), false);
check('a keyword key is permitted', denied(['keyword' => 'checkout']), false);

check('a credential shape mid-string drops the event', denied(['note' => 'rejected ppc_live_store_secret']), true);
check('a JWT shape drops the event', denied(['note' => 'token eyJhbGciOiJSUzI1NiJ9.body']), true);
check('disk_usage is permitted', denied(['note' => 'disk_usage exceeded']), false);
check('risk_free is permitted', denied(['note' => 'risk_free window elapsed']), false);
check('backpack_pk_none is permitted', denied(['note' => 'backpack_pk_none missing']), false);

check('a PAN mid-string drops the event', denied(['note' => 'Card 4111111111111111 was declined']), true);
check('a spaced PAN drops the event', denied(['note' => 'card 4111 1111 1111 1111 declined']), true);

// Space and hyphen are not the only way a PAN gets grouped on its way into a
// log line, and \d never matched the digits that only look like ASCII ones.
foreach (['.', '/', '_', '|', ',', ':', "\xc2\xa0", "\xe2\x80\x89"] as $separator) {
    check(
        'a PAN grouped by ' . bin2hex($separator) . ' drops the event',
        denied(['note' => implode($separator, ['4111', '1111', '1111', '1111'])]),
        true
    );
}
check('a fullwidth PAN drops the event', denied(['note' => '４１１１１１１１１１１１１１１１']), true);
check('an Arabic-Indic PAN drops the event', denied(['note' => '٤١١١١١١١١١١١١١١١']), true);
check('a dotted version list is permitted', denied(['note' => '1.2.3.4.5.6.7.8.9.10.11.12.13.14']), false);
check('a non-Luhn 16-digit id is permitted', denied(['note' => 'transaction 1234567890123456 not found']), false);
check('a millisecond timestamp is permitted', denied(['note' => 'expired at 1787250271000']), false);
check('a minor-unit amount is permitted', denied(['note' => 'amount 4250 refused']), false);

// Anchoring the candidate to the start of the digit run let a PAN through with
// anything at all stuck to the front of it.
check('a PAN with digits in front drops the event', denied(['note' => str_repeat('7', 40) . '4111111111111111']), true);
check('a PAN with digits behind drops the event', denied(['note' => '4111111111111111' . str_repeat('7', 40)]), true);
check('a PAN buried in a longer run drops the event', denied(['note' => str_repeat('7', 20) . '5555555555554444' . str_repeat('9', 20)]), true);
check('a 15-digit Amex PAN drops the event', denied(['note' => 'card 378282246310005 declined']), true);
check('a 19-digit PAN drops the event', denied(['note' => '4917610000000000003']), true);

// The scan slides, so Luhn alone would deny most long numeric ids; a candidate
// has to be card-shaped for its length as well.
check('a 20-digit reference is permitted', denied(['note' => 'ref 12345678901234567890']), false);
check('a Luhn-valid run that is not card-shaped is permitted', denied(['note' => 'id 100000000000042']), false);

check('the literal API key drops the event', denied(['note' => 'call failed for sk_live_realstorekey'], $secrets), true);
check('the literal webhook secret drops the event', denied(['note' => 'whsec_realwebhooksecret'], $secrets), true);
check('an empty secret does not match everything', Event::isDenied(['attrs' => ['note' => 'fine']], ['']), false);

// The clamp cuts the tail, but an error body quotes from the middle: a match
// anchored to either end of the credential misses the slice that matters.
check('a credential head drops the event', denied(['note' => 'key sk_live_re'], $secrets), true);
check('a credential middle drops the event', denied(['note' => 'key ive_realsto rejected'], $secrets), true);
check('a credential tail drops the event', denied(['note' => 'ends _realstorekey'], $secrets), true);
check('a seven-character slice is still permitted', denied(['note' => 'ive_rea'], $secrets), false);

// A float attribute is stringified by `precision` and serialised by
// `serialize_precision`; screening only the cast leaves the digits on the wire.
check('an integer PAN drops the event', denied(['note' => 4111111111111111]), true);
check('a float PAN drops the event', denied(['note' => 4111111111111111.0]), true);
check('a float Amex PAN drops the event', denied(['note' => 371449635398431.0]), true);
check('a float duration is permitted', denied(['note' => 342.5]), false);
check('a true boolean is permitted', denied(['note' => true]), false);

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

/* ------------------------------------------- whole-envelope deny screening */

// The gate screens the envelope as it will be sent. A correlation id copied
// from a webhook body is as untrusted as an attribute, and on a store with no
// webhook secret configured it is anybody's to write.

/**
 * The same structure with every leaf replaced by a value that must be denied.
 */
function poisonLeaves($value, string $poison)
{
    if (!is_array($value)) {
        return $poison;
    }

    foreach ($value as $key => $item) {
        $value[$key] = poisonLeaves($item, $poison);
    }

    return $value;
}

$opaque = '9f8a7b6c5d4e3f21';
$store_secrets = array_merge($secrets, [$opaque]);

$maximal = Event::failure(
    'checkout.hosted.create_failed',
    'session_create',
    ['api_context' => 'create_checkout'],
    new \RuntimeException('boom')
)
    ->because('order 178 had no Paypercut session')
    ->about(['payment_intent_id' => 'pi_1', 'payment_id' => 'pay_1', 'order_ref' => '178'])
    ->envelope(1787250271);

// Fails the moment a field is added to Event::envelope() without a decision
// about screening it: the poison loop below is driven by these keys.
check('the envelope carries exactly the fields the screen covers', array_keys($maximal), [
    'event',
    'occurred_at',
    'payment_intent_id',
    'payment_id',
    'order_ref',
    'error',
    'attrs'
]);

check('a clean envelope survives the gate', EventQueue::isSafe($maximal, $store_secrets), true);

$poisons = [
    'a Luhn-valid PAN'          => 'Card 4111111111111111 was declined',
    'a spaced PAN'              => '4111 1111 1111 1111',
    'a credential shape'        => 'rejected ppc_live_store_secret',
    'the literal API key'       => 'call failed for ' . $opaque,
    'an API key cut by a clamp' => str_repeat('x', 245) . substr($opaque, 0, 11),
    'a PAN cut by a clamp'      => Event::text(str_repeat('z', 250) . '4111111111111111'),
    'a PAN sent as an int'      => 4111111111111111
];

foreach (array_keys($maximal) as $field) {
    foreach ($poisons as $label => $poison) {
        $envelope = $maximal;
        $envelope[$field] = poisonLeaves($envelope[$field], $poison);

        check($label . ' in ' . $field . ' drops the event', EventQueue::isSafe($envelope, $store_secrets), false);
    }
}

// The regression that matters: screening is driven by the envelope itself, not
// by a list of field names, so a field added tomorrow is screened on arrival.
foreach ($poisons as $label => $poison) {
    check(
        $label . ' in a field nobody has added yet drops the event',
        EventQueue::isSafe(array_merge($maximal, ['field_added_tomorrow' => $poison]), $store_secrets),
        false
    );
}

// The suite's own blind spot until now: poisonLeaves() replaces VALUES, so a
// PAN or a credential in KEY position was never screened by any of the above.
// Keys are serialised exactly as values are.
foreach ($poisons as $label => $poison) {
    $key = (string)$poison;

    // Assigned, not array_merge()d: merge renumbers an integer key, which is
    // what a PAN-shaped key becomes.
    $top = $maximal;
    $top[$key] = 1;
    check($label . ' as a top-level key drops the event', EventQueue::isSafe($top, $store_secrets), false);

    $in_attrs = $maximal;
    $in_attrs['attrs'][$key] = 1;
    check($label . ' as an attribute key drops the event', EventQueue::isSafe($in_attrs, $store_secrets), false);

    $in_error = $maximal;
    $in_error['error'][$key] = 1;
    check($label . ' as an error key drops the event', EventQueue::isSafe($in_error, $store_secrets), false);

    // environmentPlugins() is the one call site that puts merchant-controlled
    // text in key position; either it screens the code out or the gate bins the
    // chunk, but the code never reaches the wire.
    $chunk = Event::environmentPlugins([$key => '1.0.0', 'paypercut' => '1.0.5'])[0]->envelope(1);
    check(
        $label . ' as an extension code never reaches the wire',
        EventQueue::isSafe($chunk, $store_secrets) && isset($chunk['attrs'][$key]),
        false
    );
}

// Nesting the contract does not have is an envelope nobody screened; the
// assertion denies it rather than walking past what it cannot reach.
check(
    'nesting deeper than the contract drops the event',
    EventQueue::isSafe(['event' => 'x', 'error' => ['stack' => ['frames' => ['ppc_live_leak']]]], []),
    false
);

// A clamp through the middle of a PAN leaves a run Luhn no longer recognises.
check('a clamped PAN never reaches the wire', Event::text(str_repeat('z', 250) . '4111111111111111'), Event::CLAMPED_DENIED);
check('the clamp marker is denied by the gate', Event::isDenied(['attrs' => ['note' => Event::CLAMPED_DENIED]]), true);
check('an ordinary clamp is untouched', strlen(Event::text(str_repeat('z', 300))), Event::MAX_TEXT_BYTES);

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

// OpenCart's database adapter puts the whole statement, and the database
// user@host, inside the exception message. None of it may reach the wire.
$sql = new \Exception("Error: Duplicate entry 'x' for key 'paypercut_id'<br/>Error No: 1062<br/> SELECT * FROM `oc_paypercut_customer` WHERE email = 'shopper@example.com'");
$thrown = Event::failure('checkout.hosted.create_failed', 'session_create', array(), $sql)->envelope(1);

check('an exception message never travels', isset($thrown['error']['message']), false);
check('the exception type still travels', $thrown['error']['type'], 'Exception');
check('the exception code still travels', $thrown['error']['code'], 'session_create');

$uncaught_sql = Event::fatal(
    "Uncaught mysqli_sql_exception: Error: Duplicate entry 'x'<br/>Error No: 1062<br/> SELECT * FROM `oc_paypercut_customer` WHERE email = 'shopper@example.com' in " . DIR_SYSTEM . "library/db/mysqli.php:122\nStack trace:\n#0 {main}",
    DIR_SYSTEM . 'library/db/mysqli.php',
    122,
    E_ERROR
)->envelope(1);

check('an uncaught exception message never travels', isset($uncaught_sql['error']['message']), false);
check('an uncaught exception is still named', $uncaught_sql['error']['type'], 'mysqli_sql_exception');
check('a fatal still reports where it died', $uncaught_sql['error']['stack'], ['library/db/mysqli.php:122']);

$engine = Event::fatal(
    'Uncaught Error: Call to a member function getId() on null in ' . DIR_CATALOG . "controller/x.php:12\nStack trace:\n#0 {main}",
    DIR_CATALOG . 'controller/x.php',
    12,
    E_ERROR
)->envelope(1);

check('PHP\'s own fatal text survives', $engine['error']['message'], 'Call to a member function getId() on null in controller/x.php:12');
check('an engine fatal is typed', $engine['error']['type'], 'Error');

$argument = Event::fatal(
    'Uncaught TypeError: total(): Argument #1 ($amount) must be of type int, string given, called in ' . DIR_CATALOG . 'controller/x.php on line 9',
    DIR_CATALOG . 'controller/x.php',
    9,
    E_ERROR
)->envelope(1);

check('the "called in" tail comes off a fatal', $argument['error']['message'], 'total(): Argument #1 ($amount) must be of type int, string given');

$triggered = Event::fatal('Order for jane@example.com could not be saved', DIR_CATALOG . 'controller/x.php', 4, E_USER_ERROR)->envelope(1);

check('trigger_error prose never travels', isset($triggered['error']['message']), false);

/* ---------------------------------------------------------- string bounding */

check('control characters are stripped', Event::text("a\x00b\x1Fc"), 'abc');
check('utf-8 survives', Event::text('Θέμα Ελλάδα'), 'Θέμα Ελλάδα');
check('text is clamped on a byte budget', strlen(Event::text(str_repeat('é', 400))) <= Event::MAX_TEXT_BYTES, true);
check('an identifier passes through', Event::identifier('hosted'), 'hosted');
check('an email is dropped, not mangled', Event::identifier('jane@example.com'), '');
check('an address is dropped', Event::identifier('12 Sunset Road'), '');
check('an over-long identifier is dropped', Event::identifier(str_repeat('a', 65)), '');
check('a trailing newline is not identifier-shaped', Event::identifier("charge.succeeded\n"), '');
check('a bare order id passes through', Event::identifier('10042'), '10042');
check('a prefixed ULID passes through', Event::identifier('pi_01KB23MA6A5B8M4PJ9XQ7K2ABC'), 'pi_01KB23MA6A5B8M4PJ9XQ7K2ABC');
check('a traversal is dropped', Event::identifier('../../etc/passwd'), '');
check('punctuation alone is dropped', Event::identifier('..'), '');

/* ------------------------------------------------------------------ envelope */

$plain = Event::of('checkout.hosted.redirected', ['order_status' => 'pending'])
    ->about(['order_ref' => '178', 'payment_id' => 'pay_1'])
    ->envelope(1787250271);

check('occurred_at is an RFC3339 string', $plain['occurred_at'], '2026-08-20T18:24:31Z');

// These three are copied straight out of a webhook body, which on a store with
// no webhook secret configured is anybody's to write.
$hostile = Event::of('webhook.order_updated')->about([
    'payment_id'        => 'shopper@example.com',
    'payment_intent_id' => '<script>x</script>',
    'order_ref'         => "0'; DROP--"
])->envelope(1787250271);

check('a correlation id that is not identifier-shaped is dropped', array_keys($hostile), ['event', 'occurred_at']);
check(
    'a real Paypercut id still travels',
    Event::of('x')->about(['payment_id' => 'pi_01KB23MA6A5B8M4PJ9XQ7K2ABC'])->envelope(1)['payment_id'],
    'pi_01KB23MA6A5B8M4PJ9XQ7K2ABC'
);
check('a numeric order ref still travels', Event::of('x')->about(['order_ref' => '10521'])->envelope(1)['order_ref'], '10521');
check(
    'a 300-byte correlation id reaches nothing at all',
    isset(Event::of('x')->about(['payment_id' => str_repeat('a', 300)])->envelope(1)['payment_id']),
    false
);
check('correlation fields sit outside attrs', $plain['order_ref'], '178');
check('attrs are present when non-empty', $plain['attrs'], ['order_status' => 'pending']);
check('an empty event omits attrs', isset(Event::of('session.probe')->envelope(1)['attrs']), false);

check('a non-scalar attribute is dropped', Event::of('x', ['a' => ['b']])->fields(), []);
check('a false boolean survives', Event::of('x', ['duplicate' => false])->fields(), ['duplicate' => false]);
check('an int survives', Event::of('x', ['http_status' => 503])->fields(), ['http_status' => 503]);
check('attrs are capped', count(Event::of('x', array_fill_keys(range(1, 40), 'v'))->fields()), Event::MAX_ATTRS);

// cleanAttrs() is not the boundary: apiFailure() appends four more fields after
// it, and the edge silently drops whatever is past the cap in sorted key order.
$wide = Event::apiFailure('api.request_failed', 401, ['error' => ['code' => 'token_invalid'], 'trace_id' => 'da74bc'], array_fill_keys(array_map(function ($i) {
    return 'attr_' . $i;
}, range(1, 16)), 'v'))->envelope(1);

check('the envelope enforces MAX_ATTRS', count($wide['attrs']), Event::MAX_ATTRS);
check('the envelope caps in sorted key order, as the edge does', array_key_first($wide['attrs']), 'api_code');

// Real extension slugs sit in key position, so the key screen has to be
// segment-anchored: a bare 'auth' substring costs support the very entries most
// likely to be the conflict. A genuinely credential-shaped code still goes.
$inventory = Event::environmentPlugins([
    'payment.authorizenet_aim' => '3.0',
    'ocmod.nonce_shield'       => '2.1',
    'ocmod.two_factor_auth'    => '1.2',
    'payment.api_secret'       => '1.0.0',
    'paypercut'                => '1.0.5',
    'ocmod_theme'              => '2.1'
]);

check('a denied-shaped code does not bin the inventory', array_keys($inventory[0]->fields()), [
    'plugin_count',
    'chunk',
    'payment.authorizenet_aim',
    'ocmod.nonce_shield',
    'ocmod.two_factor_auth',
    'paypercut',
    'ocmod_theme',
    'screened'
]);
check('the inventory says how many codes it screened out', $inventory[0]->fields()['screened'], 1);
check('the inventory survives the gate', EventQueue::isSafe($inventory[0]->envelope(1), []), true);

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

/* --------------------------------------- the clamp cannot hide a credential */

// text() clamps at construction, long before the gate sees the value, so the
// pre-clamp screen is the only thing standing between a credential that
// straddles the cut and the wire. It needs the store's own keys, hence a
// registry: below MIN_SECRET_FRAGMENT surviving characters nothing downstream
// of the clamp can recognise what it is looking at.
final class SettingsStub
{
    /** @var array */
    private $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function get($key)
    {
        return $this->values[$key] ?? null;
    }
}

final class RegistryStub
{
    /** @var SettingsStub */
    private $config;

    public function __construct(SettingsStub $config)
    {
        $this->config = $config;
    }

    public function get($key)
    {
        return $key === 'config' ? $this->config : null;
    }
}

$store_key = 'Xk92QpLmT4vRb7Ns1WdZ0Yc5';

Store::bind(new RegistryStub(new SettingsStub(['payment_paypercut_api_key' => $store_key])));

check('the library reads the store credential', TelemetrySession::credentials(), [$store_key]);

foreach ([240, 248, 249, 252, 255] as $filler) {
    check(
        'a credential clamped to ' . (Event::MAX_TEXT_BYTES - $filler) . ' surviving characters never reaches the wire',
        Event::text(str_repeat('F', $filler) . $store_key),
        Event::CLAMPED_DENIED
    );
}

check(
    'a credential in key position is clamped to the same marker',
    array_key_first(Event::of('x', [str_repeat('F', 252) . $store_key => 1])->fields()),
    Event::CLAMPED_DENIED
);

check('an ordinary clamp is still untouched with a store bound', strlen(Event::text(str_repeat('z', 300))), Event::MAX_TEXT_BYTES);

// A clean event must still deliver: a gate that denies everything is not a gate.
check(
    'a clean event still survives the gate with a store bound',
    EventQueue::isSafe(
        Event::of('checkout.hosted.redirected', ['order_status' => '5', 'duration_ms' => 342])
            ->about(['order_ref' => '10521', 'payment_id' => 'chk_01K755J9SY55CS04SQ3JX1NX36'])
            ->envelope(1787250271),
        TelemetrySession::credentials()
    ),
    true
);

echo ($failures === 0 ? 'OK' : 'FAILED') . ': ' . $assertions . " assertions, " . $failures . " failures\n";

exit($failures === 0 ? 0 : 1);
