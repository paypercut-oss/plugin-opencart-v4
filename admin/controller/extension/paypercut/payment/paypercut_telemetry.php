<?php

namespace Opencart\Admin\Controller\Extension\Paypercut\Payment;

/**
 * Paypercut Debug Session Controller
 *
 * The merchant-facing half of the telemetry feature: the consent panel and the
 * three endpoints behind it. Everything here runs in the admin application,
 * which OpenCart only reaches with a valid user token — the guard delivery
 * needs before it may mint, POST or tear a session down.
 */
class PaypercutTelemetry extends \Opencart\System\Engine\Controller
{
    const PERMISSION = 'extension/paypercut/payment/paypercut';

    /**
     * Render the panel in all four of its states.
     *
     * The server paints the current state so the panel is correct with no round
     * trip; the script then keeps the countdown and counters live. Reaping here
     * doubles as the backstop that expires a session for a merchant who started
     * one and navigated away.
     */
    public function panel(): string
    {
        $this->bootTelemetry();
        $this->load->language('extension/paypercut/payment/paypercut');

        \Paypercut\Telemetry\TelemetrySession::reap();
        (new \Paypercut\Telemetry\Flusher())->flushOnce();

        $state = \Paypercut\Telemetry\TelemetrySession::describe();

        $data = $state;
        $data['now'] = time();
        $data['user_token'] = $this->session->data['user_token'];
        $data['poll_seconds'] = \Paypercut\Telemetry\TelemetrySession::POLL_INTERVAL_SECONDS;
        $data['log_max_entries'] = \Paypercut\Telemetry\SentLog::MAX_ENTRIES;
        $data['ends_at'] = $state['expires_at'] > 0 ? date('H:i', $state['expires_at']) : '';
        $data['sent_log'] = $this->sentLogRows();
        $data['sent_log_raw'] = (string)json_encode(
            \Paypercut\Telemetry\SentLog::all(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        // One source for the panel and the consent modal. Rendered here rather
        // than with a Twig include: the Twig loader is rooted at the OpenCart
        // directory, so an include path would not resolve to this template.
        $data['disclosure'] = $this->load->view(
            'extension/paypercut/payment/paypercut_telemetry_disclosure',
            array_merge($this->language->all(), $data)
        );

        return $this->load->view(
            'extension/paypercut/payment/paypercut_telemetry',
            array_merge($this->language->all(), $data)
        );
    }

    /**
     * Mint a telemetry token and publish a session.
     */
    public function start(): void
    {
        $this->bootTelemetry();
        $this->load->language('extension/paypercut/payment/paypercut');

        $json = [];

        if (!$this->user->hasPermission('modify', self::PERMISSION)) {
            $this->respond(['error' => $this->language->get('error_permission')], 403);

            return;
        }

        \Paypercut\Telemetry\TelemetrySession::reap();

        $state = \Paypercut\Telemetry\TelemetrySession::describe();

        if ($state['state'] === 'running') {
            $this->respond($this->success($state, true));

            return;
        }

        if (!\Paypercut\Telemetry\TelemetrySession::claimStartLock()) {
            $this->respond(['error' => $this->language->get('error_debug_session_starting')], 409);

            return;
        }

        // The response is built first and emitted afterwards: the OpenCart
        // response is flushed at the end of the request, but an exception path
        // that exits early would strand the start lock for its full TTL.
        try {
            $result = $this->mintSession();
        } finally {
            \Paypercut\Telemetry\TelemetrySession::releaseStartLock();
        }

        $this->respond($result['data'], $result['status']);
    }

    /**
     * End the session early at the merchant's request.
     */
    public function stop(): void
    {
        $this->bootTelemetry();
        $this->load->language('extension/paypercut/payment/paypercut');

        if (!$this->user->hasPermission('modify', self::PERMISSION)) {
            $this->respond(['error' => $this->language->get('error_permission')], 403);

            return;
        }

        \Paypercut\Telemetry\Flusher::announceAndEnd('merchant_stopped');

        $this->respond($this->success(\Paypercut\Telemetry\TelemetrySession::describe()));
    }

    /**
     * The panel's poll, which doubles as the delivery trigger while the
     * merchant has the screen open: an authenticated admin request is the only
     * place events are sent from.
     */
    public function status(): void
    {
        $this->bootTelemetry();
        $this->load->language('extension/paypercut/payment/paypercut');

        if (!$this->user->hasPermission('access', self::PERMISSION)) {
            $this->respond(['error' => $this->language->get('error_permission')], 403);

            return;
        }

        \Paypercut\Telemetry\TelemetrySession::reap();
        (new \Paypercut\Telemetry\Flusher())->flushOnce();

        $this->respond($this->success(\Paypercut\Telemetry\TelemetrySession::describe()));
    }

    /**
     * Tell every admin user that this store is currently sending diagnostics,
     * and drain the queue while we are here.
     *
     * The permission is held by more than one person, and the extension's own
     * logger is gated on a merchant preference, so without this a session could
     * run with no visible trace for anyone but whoever started it.
     *
     * Bound to `admin/view/common/header/after`, so it only ever runs on a
     * rendered admin page — never on the storefront, never on the webhook.
     *
     * @param string $route  The view being rendered.
     * @param array  $data   The view's data.
     * @param string $output The rendered header, appended to in place.
     */
    public function notice(string &$route, array &$data, string &$output): void
    {
        if (!$this->user->hasPermission('access', self::PERMISSION)) {
            return;
        }

        $this->bootTelemetry();

        $record = \Paypercut\Telemetry\TelemetrySession::record();

        if (($record['status'] ?? '') !== 'active') {
            return;
        }

        \Paypercut\Telemetry\TelemetrySession::reap();

        if (!\Paypercut\Telemetry\TelemetrySession::isActiveFast()) {
            return;
        }

        // The panel's poll is the primary delivery trigger; this is the backstop
        // for a merchant who started a session and navigated away.
        if (\Paypercut\Telemetry\EventQueue::size() > 0) {
            (new \Paypercut\Telemetry\Flusher())->flushOnce();
        }

        $this->load->language('extension/paypercut/payment/paypercut');

        $output .= '<div class="container-fluid"><div class="alert alert-info">'
            . '<i class="fas fa-stethoscope"></i> '
            . sprintf(
                $this->language->get('text_debug_notice'),
                htmlspecialchars((string)($record['started_by_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars(date('H:i', (int)($record['expires_at'] ?? 0)), ENT_QUOTES, 'UTF-8')
            )
            . ' <a href="' . htmlspecialchars($this->url->link(self::PERMISSION, 'user_token=' . $this->session->data['user_token'], true), ENT_QUOTES, 'UTF-8') . '">'
            . $this->language->get('text_debug_notice_manage') . '</a>'
            . '</div></div>';
    }

    /**
     * @return array{data: array, status: int}
     */
    private function mintSession(): array
    {
        $api_key = (string)$this->config->get('payment_paypercut_api_key');

        if ($api_key === '') {
            return $this->refusal($this->language->get('error_debug_session_no_key'), 400);
        }

        // Both hosts come from this one environment value. A token minted for
        // one environment is rejected by every other environment's edge, so
        // they must never be resolved independently.
        $environment = \Paypercut\Environment::normalize($this->config->get('payment_paypercut_environment'));
        $mint_base = \Paypercut\Environment::apiBaseUri($environment);
        $edge_base = \Paypercut\Environment::telemetryBaseUri($environment);

        if ($edge_base === '') {
            return $this->refusal(
                $environment === ''
                    ? $this->language->get('error_debug_session_no_environment')
                    : $this->language->get('error_debug_session_environment'),
                400
            );
        }

        $response = \Paypercut\Telemetry\TokenMinter::mint($api_key, $mint_base);
        $status = (int)$response['status'];

        if ($status !== 200) {
            return $this->reject(\Paypercut\Telemetry\MintErrorMapper::map($status, $response['body']), $response, $status);
        }

        if ($response['token'] === '' || $response['expires_at'] === '') {
            return $this->reject(\Paypercut\Telemetry\MintErrorMapper::badResponse(), $response, 502);
        }

        $now = time();
        $lifetime = \Paypercut\Telemetry\TokenMinter::deriveLifetime($response['expires_at'], $response['date'], $now);
        $skew = \Paypercut\Telemetry\TokenMinter::skew($response['date'], $now);

        if ($lifetime < \Paypercut\Telemetry\TelemetrySession::MIN_LIFETIME_SECONDS) {
            return $this->reject(\Paypercut\Telemetry\MintErrorMapper::clockSkew($skew), $response, 400);
        }

        $expires_at = $now
            + min($lifetime, \Paypercut\Telemetry\TelemetrySession::SESSION_MAX_SECONDS)
            - \Paypercut\Telemetry\TelemetrySession::SKEW_SECONDS;

        // Re-check under the lock: if anything published a session while the
        // mint was in flight, discard this token rather than store a second
        // one. An unreferenced token cannot be deleted by any teardown path,
        // and nothing can revoke it.
        $existing = \Paypercut\Telemetry\TelemetrySession::describe();

        if ($existing['state'] === 'running') {
            return ['data' => $this->success($existing, true), 'status' => 200];
        }

        $session_id = 'dbg_' . substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(16))), 0, 16);

        \Paypercut\Telemetry\TelemetrySession::begin(
            [
                'status'          => 'active',
                'session_id'      => $session_id,
                'environment'     => $environment,
                'edge_base'       => $edge_base,
                'started_at'      => $now,
                'expires_at'      => $expires_at,
                'started_by'      => (int)$this->user->getId(),
                'started_by_name' => \Paypercut\Telemetry\Event::text((string)$this->user->getUserName()),
                'key_fingerprint' => \Paypercut\Telemetry\TelemetrySession::fingerprint($api_key),
                'ended_at'        => 0,
                'reason_code'     => '',
                'trace_id'        => \Paypercut\Telemetry\Event::text($response['trace_id']),
                'request_id'      => \Paypercut\Telemetry\Event::text($response['request_id'])
            ],
            $response['token']
        );

        $snapshot = \Paypercut\Telemetry\EnvironmentSnapshot::values();

        $envelopes = [
            \Paypercut\Telemetry\Event::sessionStarted($session_id, $environment, $expires_at)->envelope(),
            \Paypercut\Telemetry\Event::environmentSnapshot($snapshot)->envelope(),
            \Paypercut\Telemetry\Event::environmentConfiguration($snapshot)->envelope()
        ];

        // The list support compares against a working store when a conflict is
        // suspected; chunked because a store can run more extensions than one
        // event has room for.
        foreach (\Paypercut\Telemetry\Event::environmentPlugins(\Paypercut\Telemetry\ActiveExtensions::values()) as $event) {
            $envelopes[] = $event->envelope();
        }

        \Paypercut\Telemetry\EventQueue::append($envelopes);

        \Paypercut\Telemetry\TelemetrySession::audit('Telemetry: debug session started', [
            'session_id'   => $session_id,
            'environment'  => $environment,
            'expires_at'   => $expires_at,
            'clock_skew_s' => $skew
        ]);

        return ['data' => $this->success(\Paypercut\Telemetry\TelemetrySession::describe()), 'status' => 200];
    }

    /**
     * Record a start that did not happen, so the merchant can see why.
     *
     * @return array{data: array, status: int}
     */
    private function reject(array $mapped, array $response, int $status): array
    {
        $trace_id = \Paypercut\Telemetry\Event::text($response['trace_id']);
        $request_id = \Paypercut\Telemetry\Event::text($response['request_id']);

        \Paypercut\Telemetry\TelemetrySession::fail($mapped, $trace_id, $request_id);

        \Paypercut\Telemetry\TelemetrySession::audit('Telemetry: mint rejected', [
            'status'      => (int)$response['status'],
            'reason_code' => $mapped['reason_code'],
            'trace_id'    => $trace_id,
            'request_id'  => $request_id
        ]);

        return [
            'data'   => [
                'error'       => $mapped['message'],
                'reason_code' => $mapped['reason_code'],
                'retryable'   => $mapped['retryable'],
                'trace_id'    => $trace_id,
                'request_id'  => $request_id
            ],
            'status' => $status >= 400 && $status < 600 ? $status : 502
        ];
    }

    /**
     * @return array{data: array, status: int}
     */
    private function refusal(string $message, int $status): array
    {
        return ['data' => ['error' => $message], 'status' => $status];
    }

    /**
     * `now` travels with `expires_at` so the countdown is driven by the
     * server's clock; a browser with a wrong clock would otherwise show a
     * remaining time that does not match when the session actually ends.
     */
    private function success(array $state, bool $already_running = false): array
    {
        $state['success'] = true;
        $state['now'] = time();

        if ($already_running) {
            $state['already_running'] = true;
        }

        return $state;
    }

    private function respond(array $json, int $status = 200): void
    {
        if ($status !== 200) {
            $this->response->addHeader('HTTP/1.1 ' . $status);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * One scannable line per delivered event, so the table is readable without
     * the JSON.
     */
    private function sentLogRows(): array
    {
        $rows = [];

        foreach (\Paypercut\Telemetry\SentLog::all() as $entry) {
            $rows[] = [
                'occurred_at' => (string)($entry['occurred_at'] ?? '—'),
                'event'       => (string)($entry['event'] ?? '—'),
                'detail'      => $this->eventDetail($entry)
            ];
        }

        return $rows;
    }

    private function eventDetail(array $entry): string
    {
        $parts = [];
        $error = isset($entry['error']) && is_array($entry['error']) ? $entry['error'] : [];

        if (isset($error['code'])) {
            $parts[] = (string)$error['code'];
        }

        foreach (['order_ref', 'payment_id', 'payment_intent_id'] as $key) {
            if (!empty($entry[$key])) {
                $parts[] = $key . '=' . (string)$entry[$key];
            }
        }

        $attrs = isset($entry['attrs']) && is_array($entry['attrs']) ? $entry['attrs'] : [];

        foreach (['origin_plugin', 'http_status', 'reason', 'webhook'] as $key) {
            if (isset($attrs[$key]) && is_scalar($attrs[$key])) {
                $parts[] = $key . '=' . (string)$attrs[$key];
            }
        }

        // Lifecycle events carry none of the keys above, and a row of dashes
        // tells the merchant nothing. Fall back to whatever the event does have.
        if (empty($parts)) {
            foreach ($attrs as $key => $value) {
                if (count($parts) >= 3) {
                    break;
                }

                if (is_scalar($value)) {
                    $parts[] = $key . '=' . (is_bool($value) ? ($value ? 'true' : 'false') : (string)$value);
                }
            }
        }

        return empty($parts) ? '—' : implode(' · ', $parts);
    }

    private function bootTelemetry(): void
    {
        require_once dirname(__DIR__, 5) . '/system/library/paypercut/bootstrap.php';

        \Paypercut\Bootstrap::boot($this->registry, true);
    }
}
