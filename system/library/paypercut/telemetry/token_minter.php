<?php

namespace Paypercut\Telemetry;

use Paypercut\Environment;

/**
 * Exchanges the store's API key for a short-lived telemetry token.
 */
final class TokenMinter
{
    const PATH = 'v1/telemetry/tokens';

    private function __construct()
    {
    }

    /**
     * Request a telemetry token.
     *
     * @param string $secret    The store's API key.
     * @param string $mint_base Base URI of the Paypercut API for this environment.
     */
    public static function mint(string $secret, string $mint_base): array
    {
        // The store's long-lived API key travels on this request, so validate
        // the destination here rather than trusting whatever resolved it.
        if (Environment::allowedPaypercutBase($mint_base) === '') {
            return self::failure();
        }

        $response = Http::postJson(
            rtrim($mint_base, '/') . '/' . self::PATH,
            [
                'Authorization' => 'Bearer ' . $secret,
                'Accept'        => 'application/json'
            ],
            null,
            TelemetrySession::MINT_TIMEOUT_SECONDS,
            TelemetrySession::MINT_CONNECT_TIMEOUT_SECONDS
        );

        $body = Http::decodeJsonObject($response['body'], 8192);
        $headers = $response['headers'];

        return [
            'status'     => (int)$response['status'],
            'body'       => $body,
            'token'      => isset($body['token']) && is_string($body['token']) ? $body['token'] : '',
            'expires_at' => isset($body['expires_at']) && is_string($body['expires_at']) ? $body['expires_at'] : '',
            'date'       => (string)($headers['date'] ?? ''),
            // The gateway sets Trace-Id; on an error it also repeats it in the
            // body, which is the more reliable of the two.
            'trace_id'   => (string)($headers['trace-id'] ?? '') !== ''
                ? (string)$headers['trace-id']
                : (isset($body['trace_id']) && is_string($body['trace_id']) ? $body['trace_id'] : ''),
            'request_id' => (string)($headers['x-request-id'] ?? '')
        ];
    }

    /**
     * How long the token is good for, measured on the MINT's clock.
     *
     * expires_at is stamped by the mint; time() is this server's idea of now.
     * Stores routinely drift by minutes, so the two are not comparable: copying
     * the timestamp would either overrun the token (clock behind) or make Start
     * permanently impossible (clock ahead). expires_at - Date is a duration,
     * which is portable to any clock.
     */
    public static function deriveLifetime(string $expires_at, string $date_header, int $now): int
    {
        $expiry = strtotime($expires_at);

        if ($expiry === false) {
            return 0;
        }

        $issued = $date_header !== '' ? strtotime($date_header) : false;

        if ($issued === false) {
            $issued = $now;
        }

        return (int)$expiry - (int)$issued;
    }

    /**
     * Signed difference between the mint's clock and this server's, in seconds.
     *
     * Logged on every successful mint so support can spot a drifting store
     * before it turns into an unexplainable failure.
     */
    public static function skew(string $date_header, int $now): int
    {
        if ($date_header === '') {
            return 0;
        }

        $issued = strtotime($date_header);

        return $issued === false ? 0 : (int)$issued - $now;
    }

    private static function failure(): array
    {
        return [
            'status'     => 0,
            'body'       => [],
            'token'      => '',
            'expires_at' => '',
            'date'       => '',
            'trace_id'   => '',
            'request_id' => ''
        ];
    }
}
