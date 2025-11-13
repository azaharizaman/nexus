<?php

declare(strict_types=1);

namespace Nexus\Erp\AuditLogging\Traits;

use Nexus\Erp\AuditLogging\Observers\AuditObserver;

/**
 * Auditable Trait
 *
 * Provides automatic audit logging for models using the observer pattern.
 * Models using this trait will have CRUD operations automatically logged.
 *
 * Usage:
 * ```
 * class Invoice extends Model
 * {
 *     use Auditable;
 *
 *     protected function auditableEvents(): array
 *     {
 *         return ['created', 'updated', 'deleted'];
 *     }
 *
 *     protected function auditShouldLogBeforeAfter(): bool
 *     {
 *         return true; // High-value transactions need before/after state
 *     }
 *
 *     protected function auditLogName(): string
 *     {
 *         return 'invoices';
 *     }
 *
 *     protected function auditExcludeAttributes(): array
 *     {
 *         return ['updated_at', 'cached_total'];
 *     }
 * }
 * ```
 */
trait Auditable
{
    /**
     * Boot the Auditable trait for a model.
     *
     * Registers the AuditObserver to listen to model events.
     */
    protected static function bootAuditable(): void
    {
        static::observe(AuditObserver::class);
    }

    /**
     * Get the log name for this model
     *
     * Override this method to customize the log name.
     * Default is the table name.
     *
     * @return string Log name for categorization
     */
    protected function auditLogName(): string
    {
        return $this->getTable();
    }

    /**
     * Get the events that should be audited
     *
     * Override this method to customize which events are logged.
     * Default is created, updated, and deleted events.
     *
     * @return array<string> Array of event names
     */
    protected function auditableEvents(): array
    {
        return ['created', 'updated', 'deleted'];
    }

    /**
     * Determine if before/after state should be logged
     *
     * Override this method to enable/disable before/after state capture.
     * Recommended for high-value transactional models (Invoice, Payment, etc.)
     *
     * @return bool True to log before/after state
     */
    protected function auditShouldLogBeforeAfter(): bool
    {
        // Default to config setting
        return config('audit-logging.enable_before_after', true);
    }

    /**
     * Get attributes to exclude from audit logging
     *
     * Override this method to exclude certain attributes from being logged.
     * Useful for excluding computed fields, timestamps, etc.
     *
     * @return array<string> Array of attribute names to exclude
     */
    protected function auditExcludeAttributes(): array
    {
        return ['updated_at'];
    }

    /**
     * Get custom description for the audit log
     *
     * Override this method to provide custom descriptions for audit logs.
     * Available placeholders: {event}, {model}, {id}
     *
     * @param  string  $event  Event name (created, updated, deleted)
     * @return string|null Custom description or null to use default
     */
    protected function auditDescription(string $event): ?string
    {
        return null; // Use default description
    }

    /**
     * Get additional properties to log
     *
     * Override this method to add custom properties to the audit log.
     *
     * @param  string  $event  Event name
     * @return array<string, mixed> Additional properties
     */
    protected function auditAdditionalProperties(string $event): array
    {
        return [];
    }

    /**
     * Check if the given event should be audited
     *
     * @param  string  $event  Event name
     * @return bool True if event should be audited
     */
    public function shouldAuditEvent(string $event): bool
    {
        return in_array($event, $this->auditableEvents(), true);
    }
}
