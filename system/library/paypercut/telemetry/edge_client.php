<?php

namespace Paypercut\Telemetry;

/**
 * Delivers a batch of diagnostic events to the public telemetry edge.
 *
 * The edge verifies the bearer token offline and never calls back into the
 * platform, so a request never blocks on the payment platform.
 *
 * The body is worth reading. A 202 carries {"accepted":N,"dropped":M} — the
 * only way a client learns the edge discarded part of a batch it accepted — and
 * a 413 carries the limits a batch must be split to satisfy.
 */
final class EdgeClient
{
    const PATH = 'v1/telemetry';

    /** The edge's own responses are a few dozen bytes; anything larger is not one. */
    const MAX_RESPONSE_BYTES = 4096;

    /**
     * POST one batch. A status of 0 means the request never completed.
     */
    public function send(string $edge_base, string $jwt, string $json_body): array
    {
        $response = Http::postJson(
            rtrim($edge_base, '/') . '/' . self::PATH,
            [
                'Authorization' => 'Bearer ' . $jwt,
                'Content-Type'  => 'application/json'
            ],
            $json_body,
            TelemetrySession::EDGE_TIMEOUT_SECONDS,
            TelemetrySession::EDGE_CONNECT_TIMEOUT_SECONDS
        );

        return [
            'status'      => (int)$response['status'],
            'retry_after' => (int)($response['headers']['retry-after'] ?? 0),
            'body'        => Http::decodeJsonObject($response['body'], self::MAX_RESPONSE_BYTES)
        ];
    }
}
