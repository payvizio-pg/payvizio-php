<?php

namespace Payvizio;

/**
 * Payvizio server SDK for PHP. Uses ext-curl, ext-json, ext-hash — no Composer
 * dependencies beyond the PHP runtime.
 *
 * Usage:
 *
 *   $pv = new \Payvizio\Payvizio(['apiKey' => getenv('PAYVIZIO_API_KEY')]);
 *
 *   $session = $pv->payments->create([
 *       'orderId'  => 'ord_42',
 *       'amount'   => 1499.00,
 *       'currency' => 'INR',
 *   ], idempotencyKey: 'create-ord_42');
 *
 *   $ok = $pv->webhooks->verify($rawBody, $_SERVER['HTTP_X_PAYVIZIO_SIGNATURE'], $secret);
 */
final class Payvizio
{
    public PaymentsApi $payments;
    public RefundsApi  $refunds;
    public WebhooksApi $webhooks;

    private string $apiKey;
    private string $baseUrl;
    private int    $timeoutMs;

    public function __construct(array $options)
    {
        if (empty($options['apiKey'])) {
            throw new PayvizioError('apiKey is required');
        }
        $this->apiKey    = (string) $options['apiKey'];
        $this->baseUrl   = rtrim((string) ($options['baseUrl'] ?? 'https://api.payvizio.com'), '/');
        $this->timeoutMs = (int) ($options['timeoutMs'] ?? 15000);

        $this->payments = new PaymentsApi($this);
        $this->refunds  = new RefundsApi($this);
        $this->webhooks = new WebhooksApi();
    }

    public function request(string $method, string $path, ?array $body = null, ?string $idempotencyKey = null): mixed
    {
        $ch = curl_init($this->baseUrl . $path);
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $headers[] = 'Idempotency-Key: ' . $idempotencyKey;
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS     => $this->timeoutMs,
            CURLOPT_FAILONERROR    => false,
        ]);
        $raw    = curl_exec($ch);
        $err    = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false) {
            throw new PayvizioError('Network error: ' . $err);
        }
        $parsed = $raw === '' ? null : json_decode($raw, true);
        if ($status < 200 || $status >= 300) {
            $msg = is_array($parsed) ? ($parsed['error'] ?? $parsed['message'] ?? "HTTP $status") : "HTTP $status";
            throw new PayvizioError($msg, $status, is_array($parsed) ? ($parsed['code'] ?? null) : null, $parsed);
        }
        return $parsed;
    }
}

final class PaymentsApi
{
    public function __construct(private Payvizio $client) {}

    public function create(array $payload, ?string $idempotencyKey = null): mixed
    {
        return $this->client->request('POST', '/api/payments', $payload, $idempotencyKey);
    }

    public function get(string $sessionId): mixed
    {
        return $this->client->request('GET', '/api/payments/' . rawurlencode($sessionId));
    }

    public function capture(string $sessionId, float|string|null $amount = null, ?string $idempotencyKey = null): mixed
    {
        $body = $amount !== null ? ['amount' => $amount] : [];
        return $this->client->request('POST',
            '/api/payments/' . rawurlencode($sessionId) . '/capture',
            $body, $idempotencyKey);
    }

    public function cancel(string $sessionId): mixed
    {
        return $this->client->request('POST',
            '/api/payments/' . rawurlencode($sessionId) . '/cancel', []);
    }
}

final class RefundsApi
{
    public function __construct(private Payvizio $client) {}

    public function create(array $payload, ?string $idempotencyKey = null): mixed
    {
        return $this->client->request('POST', '/api/refunds', $payload, $idempotencyKey);
    }

    public function get(string $refundId): mixed
    {
        return $this->client->request('GET', '/api/refunds/' . rawurlencode($refundId));
    }
}

final class WebhooksApi
{
    /**
     * Verify HMAC-SHA256 webhook signatures with constant-time comparison.
     * Pass the *exact bytes* of the request body (file_get_contents('php://input')).
     */
    public function verify(string $rawBody, ?string $signatureHex, string $secret): bool
    {
        if ($rawBody === '' || empty($signatureHex) || $secret === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $signatureHex);
    }
}

final class PayvizioError extends \RuntimeException
{
    public function __construct(string $message, public ?int $status = null, public ?string $code = null, public mixed $body = null)
    {
        parent::__construct($message);
    }
}
