<?php

namespace Paypercut\Telemetry;

/**
 * The one HTTP client the telemetry code uses.
 *
 * Deliberately raw cURL rather than the extension's ordinary API calls: those
 * log request and response bodies (which would write the minted JWT into the
 * store's log files), share their timeout budget with the payment paths, and
 * treat a non-2xx as an error where every status here is branched on.
 */
final class Http
{
    private function __construct()
    {
    }

    /**
     * POST a JSON body, or no body at all when $json_body is null.
     *
     * Never throws on any HTTP status. A status of 0 is the sentinel for "no
     * answer at all" — DNS failure, refused connection, TLS failure, timeout,
     * or a host policy blocking the request — and is distinct from every real
     * HTTP status.
     */
    public static function postJson(
        string $url,
        array $headers,
        ?string $json_body,
        int $timeout_seconds,
        int $connect_timeout_seconds
    ): array {
        $started = microtime(true);

        if (!function_exists('curl_init')) {
            return self::failure(0);
        }

        $header_lines = [];

        foreach ($headers as $name => $value) {
            $header_lines[] = $name . ': ' . $value;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header_lines);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout_seconds);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout_seconds);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($json_body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);
        } else {
            // The mint endpoint takes no body at all; sending [] or {} is a
            // different request.
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        }

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $duration_ms = (int)round((microtime(true) - $started) * 1000);

        if ($response === false || $status === 0) {
            return self::failure($duration_ms);
        }

        return [
            'status'      => $status,
            'headers'     => self::parseHeaders(substr((string)$response, 0, $header_size)),
            'body'        => (string)substr((string)$response, $header_size),
            'duration_ms' => $duration_ms
        ];
    }

    /**
     * Anything that is not a JSON object is no answer at all.
     *
     * A 413 from a proxy in front of the edge is an HTML page, and a captive
     * portal will happily return 200 with a login form.
     */
    public static function decodeJsonObject(string $body, int $max_bytes = 4096): array
    {
        if ($body === '' || strlen($body) > $max_bytes) {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function parseHeaders(string $raw): array
    {
        $headers = [];

        foreach (preg_split('/\r?\n/', $raw) ?: [] as $line) {
            $split = strpos($line, ':');

            if ($split === false) {
                continue;
            }

            $headers[strtolower(trim(substr($line, 0, $split)))] = trim(substr($line, $split + 1));
        }

        return $headers;
    }

    private static function failure(int $duration_ms): array
    {
        return [
            'status'      => 0,
            'headers'     => [],
            'body'        => '',
            'duration_ms' => $duration_ms
        ];
    }
}
