<?php

declare(strict_types=1);

namespace Nexus\AuditLog\Contracts;

/**
 * Audit Log Repository Contract
 *
 * Defines the interface for audit log data access operations.
 * Implementations must provide append-only storage with tenant isolation.
 * 
 * This contract is framework-agnostic and defines persistence needs
 * without depending on Laravel or any concrete implementations.
 */
interface AuditLogRepositoryContract
{
    /**
     * Create a new audit log entry
     *
     * This is an append-only operation - no updates or deletes allowed.
     *
     * @param  array<string, mixed>  $data  Log entry data
     * @return AuditLogInterface Created audit log instance
     */
    public function create(array $data): AuditLogInterface;

    /**
     * Find an audit log by ID
     *
     * @param  int  $id  Audit log ID
     * @return AuditLogInterface|null Audit log instance or null if not found
     */
    public function find(int $id): ?AuditLogInterface;

    /**
     * Search audit logs with filters and pagination
     *
     * Filters:
     * - tenant_id (required, auto-injected): Tenant isolation
     * - user_id (optional): Filter by causer user ID
     * - causer_id (optional): Filter by causer ID
     * - event (optional): Filter by event type (created, updated, deleted)
     * - subject_type (optional): Filter by subject model class
     * - subject_id (optional): Filter by subject model ID
     * - date_from (optional): Start date for created_at range
     * - date_to (optional): End date for created_at range
     * - log_name (optional): Filter by log name
     * - search_query (optional): Full-text search in description/properties
     *
     * @param  array<string, mixed>  $filters  Search filters
     * @param  int  $perPage  Results per page (max 1000)
     * @return array{data: array<int, AuditLogInterface>, total: int, per_page: int, current_page: int, last_page: int}
     */
    public function search(array $filters, int $perPage = 50): array;

    /**
     * Get audit logs for a specific subject
     *
     * @param  string  $subjectType  Model class name
     * @param  int  $subjectId  Model ID
     * @param  int  $limit  Maximum results
     * @return array<int, AuditLogInterface> Audit logs array
     */
    public function getForSubject(string $subjectType, int $subjectId, int $limit = 100): array;

    /**
     * Get audit logs by causer
     *
     * @param  string  $causerType  Model class name
     * @param  int  $causerId  Model ID
     * @param  int  $limit  Maximum results
     * @return array<int, AuditLogInterface> Audit logs array
     */
    public function getByCauser(string $causerType, int $causerId, int $limit = 100): array;

    /**
     * Get audit logs within a date range
     *
     * @param  \DateTimeInterface  $from  Start date
     * @param  \DateTimeInterface  $to  End date
     * @param  string|null  $tenantId  Tenant ID for filtering
     * @return array<int, AuditLogInterface> Audit logs array
     */
    public function getByDateRange(\DateTimeInterface $from, \DateTimeInterface $to, ?string $tenantId = null): array;

    /**
     * Purge audit logs older than specified date
     *
     * Used for retention policy enforcement.
     *
     * @param  \DateTimeInterface  $before  Delete logs older than this date
     * @param  string|null  $tenantId  Optional tenant ID for tenant-specific purging
     * @return int Number of logs purged
     */
    public function purgeExpired(\DateTimeInterface $before, ?string $tenantId = null): int;

    /**
     * Get activity statistics
     *
     * @param  array<string, mixed>  $filters  Optional filters
     * @return array<string, mixed> Statistics including total count, counts by log name, by day, etc.
     */
    public function getStatistics(array $filters = []): array;

    /**
     * Export audit logs based on filters
     *
     * Similar to search but returns full collection instead of pagination.
     * Limited by max_records configuration.
     *
     * @param  array<string, mixed>  $filters  Export filters
     * @param  int  $maxRecords  Maximum records to export
     * @return array<int, AuditLogInterface> Audit logs array
     */
    public function export(array $filters, int $maxRecords = 10000): array;
}
