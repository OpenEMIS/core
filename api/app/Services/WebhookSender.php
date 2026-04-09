<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * WebhookSender
 *
 * POCOR-9257: Send HTTP webhook requests using Guzzle
 *
 * Features:
 * - Multiple HTTP methods (GET, POST, PUT, PATCH, DELETE)
 * - Custom headers support
 * - Auth support (Bearer, Basic, API Key)
 * - HMAC signature validation
 * - Response tracking (status, body, duration)
 * - Timeout handling
 */
class WebhookSender
{
    private Client $client;
    private int $timeout;
    private int $connectTimeout;

    public function __construct()
    {
        $this->timeout = config('webhooks.timeout', 30); // 30 seconds default
        $this->connectTimeout = config('webhooks.connect_timeout', 10); // 10 seconds default

        $this->client = new Client([
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'http_errors' => false, // Don't throw exceptions on 4xx/5xx
            'verify' => config('webhooks.verify_ssl', true), // SSL verification
        ]);
    }

    /**
     * Send webhook HTTP request
     *
     * @param array $webhookData Queue entry data
     * @return array Response data [success, status_code, body, duration_ms, error]
     */
    public function send(array $webhookData): array
    {
        $startTime = microtime(true);

        try {
            // Validate required fields
            if (empty($webhookData['target_url'])) {
                throw new \InvalidArgumentException('Missing target_url');
            }

            if (empty($webhookData['http_method'])) {
                throw new \InvalidArgumentException('Missing http_method');
            }

            // Prepare request options
            $options = $this->buildRequestOptions($webhookData);

            // Send HTTP request
            $response = $this->client->request(
                strtoupper($webhookData['http_method']),
                $webhookData['target_url'],
                $options
            );

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            // Consider 2xx and 3xx as success
            $success = $statusCode >= 200 && $statusCode < 400;

            return [
                'success' => $success,
                'status_code' => $statusCode,
                'body' => $this->truncateResponseBody($body),
                'duration_ms' => $duration,
                'error' => $success ? null : "HTTP $statusCode: " . substr($body, 0, 200),
            ];

        } catch (RequestException $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : null;

            $errorMessage = $e->getMessage();
            if ($statusCode) {
                $errorMessage = "HTTP $statusCode: " . $errorMessage;
            }

            Log::error("[WebhookSender] Request exception: " . $errorMessage);

            return [
                'success' => false,
                'status_code' => $statusCode,
                'body' => $this->truncateResponseBody($body),
                'duration_ms' => $duration,
                'error' => $errorMessage,
            ];

        } catch (\Throwable $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            Log::error("[WebhookSender] Exception: " . $e->getMessage());

            return [
                'success' => false,
                'status_code' => null,
                'body' => null,
                'duration_ms' => $duration,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build Guzzle request options from webhook data
     *
     * @param array $webhookData
     * @return array
     */
    private function buildRequestOptions(array $webhookData): array
    {
        $options = [];

        // Parse headers (JSON string to array)
        $headers = [];
        if (!empty($webhookData['headers'])) {
            $decoded = is_string($webhookData['headers'])
                ? json_decode($webhookData['headers'], true)
                : $webhookData['headers'];

            if (is_array($decoded)) {
                $headers = $decoded;
            }
        }

        // Add authentication headers
        $headers = $this->addAuthHeaders($headers, $webhookData);

        // Add HMAC signature if present
        if (!empty($webhookData['signature'])) {
            $headers['X-Webhook-Signature'] = $webhookData['signature'];
        }

        $options['headers'] = $headers;

        // Add request body for POST/PUT/PATCH
        $method = strtoupper($webhookData['http_method']);
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($webhookData['payload'])) {
            // Payload is already JSON string from queue table
            $options['body'] = $webhookData['payload'];
        }

        return $options;
    }

    /**
     * Add authentication headers based on auth_type
     *
     * @param array $headers
     * @param array $webhookData
     * @return array
     */
    private function addAuthHeaders(array $headers, array $webhookData): array
    {
        $authType = $webhookData['auth_type'] ?? null;
        $authCredentials = $webhookData['auth_credentials'] ?? null;

        if (empty($authType) || empty($authCredentials)) {
            return $headers;
        }

        // Decode auth credentials (JSON)
        $credentials = is_string($authCredentials)
            ? json_decode($authCredentials, true)
            : $authCredentials;

        if (!is_array($credentials)) {
            return $headers;
        }

        switch (strtolower($authType)) {
            case 'bearer':
                if (!empty($credentials['token'])) {
                    $headers['Authorization'] = 'Bearer ' . $credentials['token'];
                }
                break;

            case 'basic':
                if (!empty($credentials['username']) && !empty($credentials['password'])) {
                    $encoded = base64_encode($credentials['username'] . ':' . $credentials['password']);
                    $headers['Authorization'] = 'Basic ' . $encoded;
                }
                break;

            case 'api_key':
                if (!empty($credentials['key']) && !empty($credentials['header_name'])) {
                    $headers[$credentials['header_name']] = $credentials['key'];
                } elseif (!empty($credentials['key'])) {
                    $headers['X-API-Key'] = $credentials['key'];
                }
                break;

            case 'hmac':
                // HMAC signature already added in buildRequestOptions
                break;
        }

        return $headers;
    }

    /**
     * Truncate response body to prevent database overflow
     *
     * @param string|null $body
     * @param int $maxLength
     * @return string|null
     */
    private function truncateResponseBody(?string $body, int $maxLength = 10000): ?string
    {
        if ($body === null) {
            return null;
        }

        if (strlen($body) <= $maxLength) {
            return $body;
        }

        return substr($body, 0, $maxLength) . '... [truncated]';
    }

    /**
     * Generate HMAC signature for payload
     *
     * @param string $payload
     * @param string $secret
     * @param string $algorithm
     * @return string
     */
    public static function generateSignature(string $payload, string $secret, string $algorithm = 'sha256'): string
    {
        return hash_hmac($algorithm, $payload, $secret);
    }
}
