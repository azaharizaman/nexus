<?php

declare(strict_types=1);

namespace Nexus\Erp\AuditLogging\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Log Formatter Contract
 *
 * Defines the interface for formatting and masking audit log data.
 */
interface LogFormatterContract
{
    /**
     * Format model data for audit logging
     *
     * Extracts relevant data from model including:
     * - Actor (user or system)
     * - IP address from request
     * - User agent from request
     * - Tenant ID from model or context
     * - Before/after states if enabled
     *
     * @param  Model  $model  The model being logged
     * @param  string  $event  Event type (created, updated, deleted)
     * @param  array<string, mixed>  $options  Additional options
     * @return array<string, mixed> Formatted log data
     */
    public function format(Model $model, string $event, array $options = []): array;

    /**
     * Mask sensitive fields in data array
     *
     * Recursively traverse array/object and replace sensitive field values
     * with '[REDACTED]' based on configured sensitive field names.
     *
     * @param  array<string, mixed>  $data  Data to mask
     * @param  array<string>|null  $sensitiveFields  Optional override of sensitive fields list
     * @return array<string, mixed> Masked data
     */
    public function maskSensitiveFields(array $data, ?array $sensitiveFields = null): array;

    /**
     * Extract before/after state for model update
     *
     * @param  Model  $model  The model being updated
     * @return array<string, mixed> Array with 'attributes' (new) and 'old' (previous) keys
     */
    public function extractBeforeAfterState(Model $model): array;

    /**
     * Format properties for storage
     *
     * Ensures properties are properly formatted for JSON storage,
     * applies masking, and handles nested data structures.
     *
     * @param  array<string, mixed>  $properties  Raw properties
     * @return array<string, mixed> Formatted properties
     */
    public function formatProperties(array $properties): array;

    /**
     * Get actor information from context
     *
     * Returns actor details:
     * - type: 'user' or 'system'
     * - id: User ID or null for system
     * - name: User name or 'System'
     *
     * @return array<string, mixed> Actor information
     */
    public function getActor(): array;

    /**
     * Get request context information
     *
     * Returns request details:
     * - ip_address: Client IP
     * - user_agent: User agent string
     * - request_id: Unique request identifier
     *
     * @return array<string, mixed> Request context
     */
    public function getRequestContext(): array;

    /**
     * Get tenant context information
     *
     * Returns tenant ID from authenticated user or application context.
     *
     * @param  Model|null  $model  Optional model to extract tenant from
     * @return string|null Tenant ID or null for system-level logs
     */
    public function getTenantContext(?Model $model = null): ?string;
}
