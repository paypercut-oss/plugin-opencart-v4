<?php

namespace Paypercut\Telemetry;

/**
 * A single diagnostic event, and the allow-list that defines what may leave
 * the store.
 *
 * There is deliberately no generic "record these fields" constructor. Every
 * event is built by a named constructor with declared scalar parameters, so
 * the set of things that can ever be transmitted is fixed at compile time
 * rather than at each call site. is_scalar() is explicitly NOT the boundary —
 * every secret this extension holds (the API key, the webhook secret) is a
 * scalar string sitting in the same settings row as the values we do report.
 */
final class Event
{
    /**
     * Longest string any single field may carry, in bytes.
     *
     * Bytes rather than codepoints because the edge bounds the raw Go string:
     * a 128-codepoint CJK theme name is 384 bytes and would be dropped whole.
     */
    const MAX_TEXT_BYTES = 256;

    /**
     * The edge keeps the first attributes in sorted key order and drops the
     * rest, so a single over-wide event would silently lose its version fields.
     */
    const MAX_ATTRS = 16;

    const MAX_STACK_FRAMES = 8;

    /** Card number lengths the Luhn scan considers, per ISO/IEC 7812. */
    const MIN_PAN_DIGITS = 13;

    const MAX_PAN_DIGITS = 19;

    /**
     * Issuer prefixes a card of each length may start with, per ISO/IEC 7812.
     *
     * The scan slides, so Luhn alone is not a discriminator: some window inside
     * a random 16-digit id passes it about two thirds of the time. A candidate
     * has to be shaped like a card of that length as well.
     */
    const PAN_PREFIXES = [
        13 => '/\\A[456]/',
        14 => '/\\A(3(0[0-5]|095|6|8|9)|6)/',
        15 => '/\\A(3[47]|2131|1800)/',
        16 => '/\\A[2-6]/',
        17 => '/\\A[3-6]/',
        18 => '/\\A[3-6]/',
        19 => '/\\A[3-6]/'
    ];

    /**
     * How far past the clamp the pre-clamp screen looks.
     *
     * Enough to see a PAN or a credential straddling the cut; bounded so that a
     * megabyte of attacker-supplied text costs a fixed amount of scanning.
     */
    const CLAMP_MARGIN = 64;

    /**
     * Uncaught throwables whose message PHP itself wrote.
     *
     * Everything else — every \Exception, and ValueError and
     * UnhandledMatchError, which quote the offending value — carries an
     * author's prose, which on this path means SQL, hostnames and shopper data.
     */
    const ENGINE_ERROR_TYPES = [
        'ArgumentCountError',
        'ArithmeticError',
        'DivisionByZeroError',
        'Error',
        'ParseError',
        'TypeError'
    ];

    /**
     * Shortest run of a credential that still counts as that credential.
     *
     * text() clamps before this assertion ever runs, so a secret at the end of
     * a long message reaches the wire with its tail cut off.
     */
    const MIN_SECRET_FRAGMENT = 8;

    /**
     * Deepest nesting the wire contract has: error, and error.stack inside it.
     *
     * Anything below that is an envelope nobody designed, so the assertion
     * denies it rather than walking past what it cannot screen.
     */
    const MAX_DEPTH = 2;

    /**
     * Stands in for a value whose clamped tail cut a card number in half.
     *
     * A partial PAN still passes containsCardNumber(), so the clamp has to hand
     * the gate something it will deny — this matches DENIED_VALUE_PATTERN.
     */
    const CLAMPED_DENIED = 'ppc_clamped_denied';

    /** Field names that must never appear, whatever their value. */
    const DENIED_KEY_PATTERN = '/secret|token|password|credential|nonce|auth|_key$/i';

    /**
     * Value shapes that must never appear, whatever their field name.
     *
     * Not anchored to the start of the string, because a stack frame or an
     * HTTP error carries the credential mid-string every time. Not left
     * unanchored either: a tripped assertion bins the whole event, and bare
     * sk_/pk_ also matches disk_usage and risk_free.
     */
    const DENIED_VALUE_PATTERN = '/(?:^|[^A-Za-z0-9_])(ppc_|sk_|pk_|whsec_|eyJ[A-Za-z0-9_-]+\.)/i';

    /**
     * Host and extension versions, read by environmentSnapshot().
     *
     * Both snapshot lists are iterated INSTEAD of the caller's array: pulling
     * keys out of a settings array is how a credential ends up on the wire.
     */
    const SNAPSHOT_FIELDS = [
        'plugin_version' => 'text',
        'oc_version'     => 'text',
        'php_version'    => 'text',
        'theme_name'     => 'text',
        'theme_version'  => 'text',
        'is_multistore'  => 'bool',
        'is_ssl'         => 'bool'
    ];

    /** Extension settings, read by environmentConfiguration(). */
    const CONFIGURATION_FIELDS = [
        'checkout_mode'             => 'identifier',
        'order_status'              => 'identifier',
        'connection_environment'    => 'identifier',
        'api_key_mode'              => 'identifier',
        'payment_enabled'           => 'bool',
        'google_pay_enabled'        => 'bool',
        'apple_pay_enabled'         => 'bool',
        'logging_enabled'           => 'bool',
        'webhook_configured'        => 'bool',
        'payment_domain_registered' => 'bool',
        'payment_config_selected'   => 'bool',
        'statement_descriptor_set'  => 'bool',
        'applepay_file_deployed'    => 'bool',
        'store_currency'            => 'identifier',
        'currency_supported'        => 'bool',
        'sort_order'                => 'identifier'
    ];

    /** @var string */
    private $name;

    /** @var array */
    private $fields;

    /** @var array Correlation fields, sent outside attrs. */
    private $correlation = [];

    /** @var array */
    private $error = [];

    private function __construct(string $name, array $fields)
    {
        $this->name = $name;
        $this->fields = $fields;
    }

    /**
     * Report something that happened and did not fail.
     *
     * Failures alone cannot answer the commonest support question, which is
     * whether the shopper ever reached us: a session with no checkout.* events
     * and one with a silent early return look identical.
     */
    public static function of(string $name, array $attrs = []): self
    {
        return new self($name, self::cleanAttrs($attrs));
    }

    /**
     * Report a failure, under whichever event name describes where it happened.
     *
     * The exception's message never travels. OpenCart's database adapter puts
     * the full SQL statement — and the database user@host — inside it, so the
     * type, the code and the scrubbed stack carry the diagnosis instead, and a
     * call site with prose of its own says so with because().
     */
    public static function failure(string $name, string $code, array $attrs = [], ?\Throwable $exception = null): self
    {
        $event = new self($name, self::cleanAttrs($attrs));

        $event->error = ['code' => self::text($code) ?: 'unknown'];

        if ($exception !== null) {
            $event->error['type'] = self::shortClassName($exception);
            $event->error['stack'] = self::stack($exception);

            $event->fields += self::origin(self::frameFiles($exception));
        }

        return $event;
    }

    /**
     * Report a Paypercut API failure with the fields the platform returned.
     *
     * The API quotes submitted input back — a rejected key arrives inside the
     * error prose — so error.message is always dropped here. api_code and
     * trace_id carry the diagnosis instead.
     */
    public static function apiFailure(string $name, int $status, array $body = [], array $attrs = []): self
    {
        $event = new self($name, self::cleanAttrs($attrs));

        $error = isset($body['error']) && is_array($body['error']) ? $body['error'] : [];

        $event->error = ['code' => 'http_' . $status];

        $type = self::identifier((string)($error['type'] ?? ''));
        if ($type !== '') {
            $event->error['type'] = $type;
        }

        $pairs = [
            'api_code'  => (string)($error['code'] ?? $body['code'] ?? ''),
            'api_param' => (string)($error['param'] ?? ''),
            'trace_id'  => (string)($body['trace_id'] ?? $error['trace_id'] ?? '')
        ];

        foreach ($pairs as $key => $value) {
            $clean = self::text($value);

            if ($clean !== '') {
                $event->fields[$key] = $clean;
            }
        }

        $event->fields['http_status'] = $status;

        return $event;
    }

    /**
     * Report the fatal that ended a request.
     *
     * Built from error_get_last(), which carries no exception and no trace —
     * the file that died is the only attribution available.
     */
    public static function fatal(string $message, string $file, int $line, int $level): self
    {
        $event = new self('php.fatal', ['level' => $level]);

        $event->fields += self::origin([$file]);

        $event->error = [
            'code'  => 'php_fatal',
            'type'  => self::fatalType($message),
            'stack' => [self::relativePath($file) . ':' . $line]
        ];

        $quotable = self::fatalMessage($message, $level);

        if ($quotable !== '') {
            $event->error['message'] = self::text($quotable);
        }

        return $event;
    }

    /**
     * Note what is absent: the admin user who started the session. The durable
     * record keeps the name for the admin notice, but a store-user identifier
     * is not covered by the merchant-facing disclosure, so it never travels.
     */
    public static function sessionStarted(string $session_id, string $environment, int $expires_at): self
    {
        return new self('session.started', [
            'session_id'  => self::identifier($session_id),
            'environment' => self::identifier($environment),
            'expires_at'  => $expires_at
        ]);
    }

    public static function sessionStopped(string $session_id, string $reason, int $events_sent, int $events_dropped): self
    {
        return new self('session.stopped', [
            'session_id'     => self::identifier($session_id),
            'reason'         => self::identifier($reason),
            'events_sent'    => $events_sent,
            'events_dropped' => $events_dropped
        ]);
    }

    /**
     * @param array $values Candidate values; only SNAPSHOT_FIELDS keys are read.
     */
    public static function environmentSnapshot(array $values): self
    {
        return new self('environment.snapshot', self::castFields(self::SNAPSHOT_FIELDS, $values));
    }

    /**
     * Separate from the environment snapshot only because the two together
     * exceed MAX_ATTRS; nothing else distinguishes them.
     *
     * @param array $values Candidate values; only CONFIGURATION_FIELDS keys are read.
     */
    public static function environmentConfiguration(array $values): self
    {
        return new self('environment.configuration', self::castFields(self::CONFIGURATION_FIELDS, $values));
    }

    /**
     * The installed-extension inventory, chunked to fit the attribute cap.
     *
     * The event name stays environment.plugins across every Paypercut plugin so
     * one query answers "which add-on broke this store" whatever the platform.
     *
     * @param array $plugins code => version, sorted by the caller.
     *
     * @return self[]
     */
    public static function environmentPlugins(array $plugins): array
    {
        $total = count($plugins);
        $chunks = array_chunk($plugins, self::MAX_ATTRS - 2, true);
        $events = [];

        foreach ($chunks as $index => $chunk) {
            $fields = [
                'plugin_count' => $total,
                'chunk'        => $index + 1
            ];

            $screened = 0;

            foreach ($chunk as $code => $version) {
                $key = self::text((string)$code);

                // An ordinary code like authorize_net or two_factor_auth
                // matches DENIED_KEY_PATTERN and would bin the whole chunk:
                // losing one entry beats losing fourteen and not knowing.
                if ($key === '' || preg_match(self::DENIED_KEY_PATTERN, $key) || self::deniedValue($key, self::storeSecrets())) {
                    $screened++;

                    continue;
                }

                $fields[$key] = self::text((string)$version);
            }

            if ($screened > 0) {
                $fields['screened'] = $screened;
            }

            $events[] = new self('environment.plugins', $fields);
        }

        return $events;
    }

    /**
     * Attach the ids that join this event to a payment.
     */
    public function about(array $correlation): self
    {
        foreach (['payment_intent_id', 'payment_id', 'order_ref'] as $field) {
            // identifier(), not text(): these three are copied straight out of
            // a webhook body, which on a store with no webhook secret is
            // anybody's to write. An OpenCart order ref is the numeric order id
            // and a Paypercut id is a prefixed ULID, so nothing real is lost.
            $value = self::identifier(trim((string)($correlation[$field] ?? '')));

            if ($value !== '') {
                $this->correlation[$field] = $value;
            }
        }

        return $this;
    }

    /**
     * A message this extension authored itself, for a failure with no exception
     * worth quoting.
     */
    public function because(string $message): self
    {
        $clean = self::text($message);

        if ($clean !== '') {
            $this->error['message'] = $clean;
        }

        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * The wire shape of a single event inside a batch.
     *
     * The contract's field is occurred_at, an RFC3339 STRING. Sending a unix
     * int under that name fails the whole event, so name and type move together.
     *
     * @param int|null $now Injected clock, so a test can pin timestamps.
     */
    public function envelope(?int $now = null): array
    {
        $envelope = [
            'event'       => $this->name,
            'occurred_at' => gmdate('Y-m-d\TH:i:s\Z', $now ?? time())
        ];

        foreach ($this->correlation as $field => $value) {
            $envelope[$field] = $value;
        }

        if (!empty($this->error)) {
            $envelope['error'] = $this->error;
        }

        // PHP renders an empty array as [], which the edge reads as "not an
        // object" and records as a drop against an otherwise clean event.
        if (!empty($this->fields)) {
            $envelope['attrs'] = self::boundAttrs($this->fields);
        }

        return $envelope;
    }

    /**
     * Enforce MAX_ATTRS where the wire actually begins.
     *
     * cleanAttrs() bounds what a call site passes, but apiFailure() and
     * failure() append api_code / trace_id / http_status / origin afterwards.
     * The edge keeps the first attributes in SORTED KEY ORDER, so the cap is
     * applied the same way here and both ends drop the same fields.
     */
    private static function boundAttrs(array $fields): array
    {
        if (count($fields) <= self::MAX_ATTRS) {
            return $fields;
        }

        ksort($fields);

        return array_slice($fields, 0, self::MAX_ATTRS, true);
    }

    /**
     * Attribute a failure to the code that raised it.
     *
     * The commonest support case is another extension breaking ours, and the
     * answer is in the stack: the first frame outside our own directory names
     * it. The wire values stay plugin/theme/core/paypercut on every platform so
     * support can compare stores.
     *
     * @param array $files Absolute paths, innermost first.
     */
    public static function origin(array $files): array
    {
        $ours = self::pluginRoot();

        foreach ($files as $file) {
            $file = (string)$file;

            if ($ours !== '' && strpos($file, $ours) === 0) {
                continue;
            }

            $extension_dir = self::directory('DIR_EXTENSION');

            if ($extension_dir !== '' && strpos($file, $extension_dir) === 0) {
                $relative = ltrim(substr($file, strlen($extension_dir)), '/');
                $parts = explode('/', $relative);

                return [
                    'origin'        => 'plugin',
                    'origin_plugin' => self::text((string)$parts[0])
                ];
            }

            foreach (['DIR_CATALOG', 'DIR_APPLICATION'] as $root) {
                $prefix = self::directory($root);

                if ($prefix !== '' && strpos($file, $prefix . 'view/') === 0) {
                    return ['origin' => 'theme'];
                }
            }

            return ['origin' => 'core'];
        }

        return ['origin' => 'paypercut'];
    }

    /**
     * Hard deny assertion: true when this event must be dropped entirely.
     *
     * A safety net behind the named constructors, not the primary control. It
     * drops the whole event rather than the offending field, because a field
     * that trips it means the event was assembled wrongly and the rest of it
     * cannot be trusted either.
     */
    public static function isDenied(array $fields, array $secrets = [], int $depth = 0): bool
    {
        foreach ($fields as $key => $value) {
            $name = (string)$key;

            if (preg_match(self::DENIED_KEY_PATTERN, $name)) {
                return true;
            }

            // A key is serialised exactly as a value is, so it gets the same
            // screens: a PAN or a credential in key position is still on the wire.
            if (self::deniedValue($name, $secrets)) {
                return true;
            }

            // The contract nests one level — error, and error.stack inside it.
            // Without recursion the assertion sees a non-string and gives up,
            // which is exactly where free text now lives.
            if (is_array($value)) {
                if ($depth >= self::MAX_DEPTH) {
                    return true;
                }

                if (self::isDenied($value, $secrets, $depth + 1)) {
                    return true;
                }

                continue;
            }

            if (!is_scalar($value)) {
                continue;
            }

            // Cast rather than require a string: an int attribute is a scalar
            // the edge serialises verbatim, and 16 digits are 16 digits.
            if (self::deniedValue((string)$value, $secrets)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every value screen, applied to one scalar.
     *
     * One function so that the gate, the key screen and the pre-clamp screen in
     * text() cannot drift apart: whatever any of them denies, all of them deny.
     */
    public static function deniedValue(string $value, array $secrets = []): bool
    {
        if ($value === '') {
            return false;
        }

        if (preg_match(self::DENIED_VALUE_PATTERN, $value)) {
            return true;
        }

        if (self::containsCardNumber($value)) {
            return true;
        }

        // Shape matching is a guess; comparing against the store's actual
        // credentials is not. This catches a secret in a format nobody
        // anticipated, including one a future Paypercut release introduces.
        foreach ($secrets as $secret) {
            if (!is_string($secret) || $secret === '') {
                continue;
            }

            if (strpos($value, $secret) !== false || self::endsWithSecretHead($value, $secret)) {
                return true;
            }
        }

        return false;
    }

    /**
     * True when the value ends in the head of a credential.
     *
     * Clamping only ever removes the tail, so a truncated secret survives as a
     * suffix of the value and the whole-string comparison above misses it.
     */
    private static function endsWithSecretHead(string $value, string $secret): bool
    {
        $longest = min(strlen($value), strlen($secret) - 1);

        for ($length = $longest; $length >= self::MIN_SECRET_FRAGMENT; $length--) {
            if (strncmp($secret, substr($value, -$length), $length) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * A card number anywhere in the value, at any offset in any digit run.
     *
     * The edge screens for a PAN too, but only when the whole value is one:
     * "Card 4111111111111111 was declined" passes it. Card data must never
     * leave a merchant estate, so the client is the right place to enforce it.
     */
    public static function containsCardNumber(string $value): bool
    {
        if (!preg_match_all('/\d(?:[ -]?\d){12,}/', $value, $matches)) {
            return false;
        }

        foreach ($matches[0] as $run) {
            $digits = (string)preg_replace('/\D/', '', $run);
            $length = strlen($digits);

            // Slide, rather than anchor the candidate to the run: a PAN with an
            // order number stuck to the front of it is still a PAN.
            for ($start = 0; $start + self::MIN_PAN_DIGITS <= $length; $start++) {
                foreach (self::PAN_PREFIXES as $take => $prefix) {
                    if ($start + $take > $length) {
                        break;
                    }

                    $candidate = substr($digits, $start, $take);

                    if (preg_match($prefix, $candidate) && self::luhnValid($candidate)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Free-ish text: printable characters only, hard byte cap.
     *
     * UTF-8 is preserved rather than stripped — a Greek or Japanese theme name
     * is one of the more useful diagnostics there is. Only control characters go.
     */
    public static function text(string $value): string
    {
        $clean = (string)preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        if ($clean === '' && $value !== '') {
            // Invalid UTF-8 made the unicode-mode replace fail; fall back to ASCII.
            $clean = (string)preg_replace('/[^\x20-\x7E]/', '', $value);
        }

        if (strlen($clean) <= self::MAX_TEXT_BYTES) {
            return $clean;
        }

        // Screened BEFORE the cut, not after: a clamp through the middle of a
        // PAN or a credential leaves the gate a fragment it no longer
        // recognises, so hand it a value it will deny instead. Only the head
        // that survives the cut, plus what straddles it, can reach the wire.
        if (self::deniedValue(substr($clean, 0, self::MAX_TEXT_BYTES + self::CLAMP_MARGIN), self::storeSecrets())) {
            return self::CLAMPED_DENIED;
        }

        // mb_strcut cuts on a byte budget while respecting codepoint
        // boundaries; mb_substr counts codepoints and would overshoot.
        return function_exists('mb_strcut')
            ? mb_strcut($clean, 0, self::MAX_TEXT_BYTES)
            : substr($clean, 0, self::MAX_TEXT_BYTES);
    }

    /**
     * Identifier-shaped values only; anything else is dropped, not mangled.
     *
     * \z rather than $, which accepts a trailing newline — and a webhook body
     * is where most of these values come from.
     */
    public static function identifier(string $value): string
    {
        return preg_match('/\A[A-Za-z0-9_.:-]{1,64}\z/', $value) ? $value : '';
    }

    /**
     * The class name without its namespace.
     *
     * Public because a call site that must not send an exception's message
     * still wants to name its type — a rejected credential is quoted back in
     * the message but never in the class.
     */
    public static function shortClassName(\Throwable $exception): string
    {
        $parts = explode('\\', get_class($exception));

        return self::text((string)end($parts)) ?: 'Throwable';
    }

    /**
     * Bound attributes a call site passed in, rather than trusting them.
     *
     * Booleans and ints are already bounded and pass through; strings are
     * clamped; anything else is not a diagnostic and is dropped.
     */
    private static function cleanAttrs(array $attrs): array
    {
        $fields = [];

        foreach ($attrs as $key => $value) {
            if (count($fields) >= self::MAX_ATTRS) {
                break;
            }

            $name = self::text((string)$key);

            if ($name === '' || !is_scalar($value)) {
                continue;
            }

            $fields[$name] = is_string($value) ? self::text($value) : $value;
        }

        return $fields;
    }

    private static function castFields(array $schema, array $values): array
    {
        $fields = [];

        foreach ($schema as $key => $cast) {
            if (!array_key_exists($key, $values)) {
                continue;
            }

            $value = $values[$key];

            if ($cast === 'bool') {
                $fields[$key] = (bool)$value;
                continue;
            }

            if (!is_scalar($value)) {
                continue;
            }

            $clean = $cast === 'identifier'
                ? self::identifier((string)$value)
                : self::text((string)$value);

            if ($clean !== '') {
                $fields[$key] = $clean;
            }
        }

        return $fields;
    }

    /**
     * File and line only, at most MAX_STACK_FRAMES of them.
     *
     * Never getTraceAsString(): that renders call arguments, which here are
     * checkout payloads and credentials.
     */
    private static function stack(\Throwable $exception): array
    {
        $frames = [];

        foreach ($exception->getTrace() as $frame) {
            if (count($frames) >= self::MAX_STACK_FRAMES) {
                break;
            }

            if (!isset($frame['file'], $frame['line'])) {
                continue;
            }

            $frames[] = self::relativePath((string)$frame['file']) . ':' . (int)$frame['line'];
        }

        return $frames;
    }

    /**
     * Absolute file paths from a throwable, its own location first.
     */
    private static function frameFiles(\Throwable $exception): array
    {
        $files = [$exception->getFile()];

        foreach ($exception->getTrace() as $frame) {
            if (isset($frame['file'])) {
                $files[] = (string)$frame['file'];
            }
        }

        return $files;
    }

    /**
     * Paths relative to an OpenCart root: an absolute path on shared hosting
     * names the merchant's account or domain.
     */
    private static function relativePath(string $file): string
    {
        foreach (['DIR_EXTENSION', 'DIR_APPLICATION', 'DIR_CATALOG', 'DIR_SYSTEM', 'DIR_OPENCART'] as $root) {
            $prefix = self::directory($root);

            if ($prefix !== '' && strpos($file, $prefix) === 0) {
                return ltrim(substr($file, strlen($prefix)), '/');
            }
        }

        return '[external]';
    }

    /**
     * Name the throwable that ended the request, when the message says.
     */
    private static function fatalType(string $message): string
    {
        if (!preg_match('/\AUncaught ([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)\s*:/', $message, $matches)) {
            return 'FatalError';
        }

        $parts = explode('\\', $matches[1]);

        return self::identifier((string)end($parts)) ?: 'FatalError';
    }

    /**
     * The part of PHP's fatal message that is PHP's own words, or ''.
     *
     * An uncaught throwable's message belongs to whoever raised it, and on this
     * path that is a database adapter quoting the SQL it just ran. Only the
     * engine's own text is quotable, and only after the trace, the "called in"
     * tail and the absolute paths — all reported elsewhere — come off it.
     */
    private static function fatalMessage(string $message, int $level): string
    {
        // trigger_error() text is a third-party author's prose, not PHP's.
        if ($level === E_USER_ERROR) {
            return '';
        }

        $trace = strpos($message, 'Stack trace:');

        if ($trace !== false) {
            $message = rtrim(substr($message, 0, $trace));
        }

        $called = strpos($message, ', called in ');

        if ($called !== false) {
            $message = substr($message, 0, $called);
        }

        if (preg_match('/\AUncaught ([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)\s*:\s*(.*)\z/s', $message, $matches)) {
            // Engine throwables are global; a namespaced one of the same short
            // name belongs to whoever declared it, and so does its message.
            if (strpos($matches[1], '\\') !== false || !in_array($matches[1], self::ENGINE_ERROR_TYPES, true)) {
                return '';
            }

            $message = $matches[2];
        }

        foreach (['DIR_EXTENSION', 'DIR_APPLICATION', 'DIR_CATALOG', 'DIR_SYSTEM', 'DIR_OPENCART'] as $root) {
            $prefix = self::directory($root);

            if ($prefix !== '') {
                $message = str_replace($prefix, '', $message);
            }
        }

        return $message;
    }

    private static function directory(string $constant): string
    {
        if (!defined($constant)) {
            return '';
        }

        $value = (string)constant($constant);

        return $value === '' ? '' : rtrim($value, '/') . '/';
    }

    /**
     * The store's own credentials, when there is a store bound to ask.
     *
     * text() runs in contexts with no registry at all (the suite, a CLI), where
     * the shape screens are all there is.
     */
    private static function storeSecrets(): array
    {
        return Store::bound() ? TelemetrySession::credentials() : [];
    }

    private static function pluginRoot(): string
    {
        return rtrim(dirname(__DIR__, 4), '/') . '/';
    }

    private static function luhnValid(string $digits): bool
    {
        $length = strlen($digits);

        if ($length < self::MIN_PAN_DIGITS || $length > self::MAX_PAN_DIGITS) {
            return false;
        }

        $sum = 0;
        $double = false;

        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int)$digits[$i];

            if ($double) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $double = !$double;
        }

        return $sum % 10 === 0;
    }
}
