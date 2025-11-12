<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\AuditLogging\Services;

use Azaharizaman\Erp\AuditLogging\Contracts\LogFormatterContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Log Formatter Service
 *
 * Handles formatting and masking of audit log data.
 */
class LogFormatterService implements LogFormatterContract
{
    /**
     * Format model data for audit logging
     *
     * @param  Model  $model  The model being logged
     * @param  string  $event  Event type (created, updated, deleted)
     * @param  array<string, mixed>  $options  Additional options
     * @return array<string, mixed> Formatted log data
     */
    public function format(Model $model, string $event, array $options = []): array
    {
        $actor = $this->getActor();
        $requestContext = $this->getRequestContext();
        $tenantId = $this->getTenantContext($model);

        return [
            'tenant_id' => $tenantId,
            'log_name' => $options['log_name'] ?? $model->getTable(),
            'description' => $options['description'] ?? $this->generateDescription($model, $event),
            'subject_type' => get_class($model),
            'subject_id' => $model->getKey(),
            'causer_type' => $actor['type'] === 'user' ? get_class(auth()->user()) : null,
            'causer_id' => $actor['id'],
            'event' => $event,
            'properties' => $this->formatProperties($options['properties'] ?? []),
            'ip_address' => $requestContext['ip_address'],
            'user_agent' => $requestContext['user_agent'],
            'request_id' => $requestContext['request_id'],
        ];
    }

    /**
     * Mask sensitive fields in data array
     *
     * @param  array<string, mixed>  $data  Data to mask
     * @param  array<string>|null  $sensitiveFields  Optional override of sensitive fields list
     * @return array<string, mixed> Masked data
     */
    public function maskSensitiveFields(array $data, ?array $sensitiveFields = null): array
    {
        $sensitiveFields = $sensitiveFields ?? config('audit-logging.mask_sensitive_fields', []);

        return $this->maskRecursive($data, $sensitiveFields);
    }

    /**
     * Recursively mask sensitive fields
     *
     * @param  mixed  $data  Data to mask
     * @param  array<string>  $sensitiveFields  Sensitive field names
     * @return mixed Masked data
     */
    protected function maskRecursive(mixed $data, array $sensitiveFields): mixed
    {
        if (is_array($data)) {
            $masked = [];
            foreach ($data as $key => $value) {
                // Check if key matches any sensitive field pattern
                $shouldMask = false;
                foreach ($sensitiveFields as $field) {
                    if (Str::contains(strtolower($key), strtolower($field))) {
                        $shouldMask = true;
                        break;
                    }
                }

                if ($shouldMask) {
                    $masked[$key] = '[REDACTED]';
                } else {
                    $masked[$key] = $this->maskRecursive($value, $sensitiveFields);
                }
            }

            return $masked;
        }

        if (is_object($data)) {
            return '[OBJECT]';
        }

        return $data;
    }

    /**
     * Extract before/after state for model update
     *
     * @param  Model  $model  The model being updated
     * @return array<string, mixed> Array with 'attributes' (new) and 'old' (previous) keys
     */
    public function extractBeforeAfterState(Model $model): array
    {
        $original = $model->getOriginal();
        $attributes = $model->getAttributes();

        // Get only dirty (changed) attributes
        $dirty = $model->getDirty();

        $old = [];
        $new = [];

        foreach ($dirty as $key => $value) {
            $old[$key] = $original[$key] ?? null;
            $new[$key] = $value;
        }

        return [
            'old' => $this->maskSensitiveFields($old),
            'attributes' => $this->maskSensitiveFields($new),
        ];
    }

    /**
     * Format properties for storage
     *
     * @param  array<string, mixed>  $properties  Raw properties
     * @return array<string, mixed> Formatted properties
     */
    public function formatProperties(array $properties): array
    {
        return $this->maskSensitiveFields($properties);
    }

    /**
     * Get actor information from context
     *
     * @return array<string, mixed> Actor information
     */
    public function getActor(): array
    {
        if (auth()->check()) {
            $user = auth()->user();

            return [
                'type' => 'user',
                'id' => $user->id,
                'name' => method_exists($user, 'getName') ? $user->getName() : ($user->name ?? 'Unknown'),
            ];
        }

        return [
            'type' => 'system',
            'id' => null,
            'name' => 'System',
        ];
    }

    /**
     * Get request context information
     *
     * @return array<string, mixed> Request context
     */
    public function getRequestContext(): array
    {
        if (! app()->runningInConsole() && request()) {
            return [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'request_id' => request()->header('X-Request-ID') ?? Str::uuid()->toString(),
            ];
        }

        return [
            'ip_address' => null,
            'user_agent' => 'CLI',
            'request_id' => Str::uuid()->toString(),
        ];
    }

    /**
     * Get tenant context information
     *
     * @param  Model|null  $model  Optional model to extract tenant from
     * @return string|null Tenant ID or null for system-level logs
     */
    public function getTenantContext(?Model $model = null): ?string
    {
        // Try to get tenant from model
        if ($model && isset($model->tenant_id)) {
            return (string) $model->tenant_id;
        }

        // Try to get tenant from authenticated user
        if (auth()->check() && isset(auth()->user()->tenant_id)) {
            return (string) auth()->user()->tenant_id;
        }

        // Try to get tenant from application container
        if (app()->bound('tenant.current')) {
            $tenant = app('tenant.current');

            return $tenant?->id ? (string) $tenant->id : null;
        }

        return null;
    }

    /**
     * Generate default description for event
     *
     * @param  Model  $model  The model
     * @param  string  $event  Event type
     * @return string Description
     */
    protected function generateDescription(Model $model, string $event): string
    {
        $modelName = class_basename($model);
        $modelId = $model->getKey();

        return match ($event) {
            'created' => "{$modelName} #{$modelId} was created",
            'updated' => "{$modelName} #{$modelId} was updated",
            'deleted' => "{$modelName} #{$modelId} was deleted",
            default => "{$modelName} #{$modelId} - {$event}",
        };
    }
}
