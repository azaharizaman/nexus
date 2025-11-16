<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Nexus\Crm\Contracts\IntegrationContract;
use Nexus\Crm\Models\CrmEntity;

/**
 * Webhook Integration
 *
 * Sends webhooks as part of CRM pipeline actions.
 */
class WebhookIntegration implements IntegrationContract
{
    /**
     * Execute webhook integration.
     */
    public function execute(CrmEntity $entity, array $config, array $context = []): void
    {
        $url = $config['url'] ?? '';
        $method = $config['method'] ?? 'POST';
        $headers = $config['headers'] ?? [];

        if (!$url) {
            throw new \InvalidArgumentException('Webhook URL is required');
        }

        // Security: Validate URL against whitelist
        $this->validateWebhookUrl($url);

        // Security: Filter sensitive data before sending
        $payload = [
            'entity' => $this->filterSensitiveData($entity->toArray()),
            'context' => $this->filterSensitiveData($context),
            'timestamp' => now()->toISOString(),
        ];

        // Security: Sign payload if secret is configured
        if (!empty($config['secret'])) {
            $headers['X-Webhook-Signature'] = $this->signPayload($payload, $config['secret']);
        }

        try {
            Http::withHeaders($headers)
                ->timeout(10) // Add timeout
                ->send($method, $url, ['json' => $payload]);
        } catch (\Exception $e) {
            Log::error('CRM Webhook Integration Failed', [
                'entity_id' => $entity->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate webhook URL against whitelist.
     */
    private function validateWebhookUrl(string $url): void
    {
        $whitelist = config('crm.webhook_whitelist', []);

        if (empty($whitelist)) {
            return; // No whitelist configured, allow all
        }

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';

        foreach ($whitelist as $allowedPattern) {
            if (fnmatch($allowedPattern, $host)) {
                return; // URL is whitelisted
            }
        }

        throw new \RuntimeException("Webhook URL '{$url}' is not whitelisted");
    }

    /**
     * Filter sensitive data from payload.
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitiveFields = config('crm.webhook_sensitive_fields', [
            'password',
            'secret',
            'token',
            'api_key',
            'credit_card',
            'ssn',
        ]);

        return $this->recursiveFilter($data, $sensitiveFields);
    }

    /**
     * Recursively filter sensitive fields from array.
     */
    private function recursiveFilter(array $data, array $sensitiveFields): array
    {
        foreach ($data as $key => $value) {
            // Check if key matches any sensitive field pattern
            foreach ($sensitiveFields as $field) {
                if (stripos($key, $field) !== false) {
                    $data[$key] = '[REDACTED]';
                    continue 2;
                }
            }

            // Recursively filter nested arrays
            if (is_array($value)) {
                $data[$key] = $this->recursiveFilter($value, $sensitiveFields);
            }
        }

        return $data;
    }

    /**
     * Sign webhook payload with HMAC.
     */
    private function signPayload(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Compensate webhook integration (no-op for webhooks).
     */
    public function compensate(CrmEntity $entity, array $config, array $context = []): void
    {
        // Webhooks don't need compensation
    }
}