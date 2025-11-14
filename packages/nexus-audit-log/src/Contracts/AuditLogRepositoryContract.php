<?php

declare(strict_types=1);

namespace Nexus\AuditLog\Contracts;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Nexus\AuditLog\Models\AuditLog;

/**
 * Audit Log Repository Contract
 *
 * Defines the interface for audit log data access operations.
 * Implementations must provide append-only storage with tenant isolation.
 * 
 * NOTE: Uses internal AuditLog model to remove external dependencies
 * and enable independent testing of the atomic package.
 */
interface AuditLogRepositoryContract
{
    /**
     * Create a new audit log entry
     *
     * This is an append-only operation - no updates or deletes allowed.
     *
     * @param  array<string, mixed>  $data  Log entry data
     * @return AuditLog Created audit log instance
     */
    public function create(array $data): AuditLog;

    /**
     * Find an audit log by ID
     *
     * @param  int  $id  Audit log ID
     * @return AuditLog|null Audit log instance or null if not found
     */
    public function find(int $id): ?AuditLog;

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
     * @return LengthAwarePaginator Paginated results
     */
    public function search(array $filters, int $perPage = 50): LengthAwarePaginator;

    /**
     * Get audit logs for a specific subject
     *
     * @param  string  $subjectType  Model class name
     * @param  int  $subjectId  Model ID
     * @param  int  $limit  Maximum results
     * @return Collection<int, AuditLog> Audit logs collection
     */
    public function getForSubject(string $subjectType, int $subjectId, int $limit = 100): Collection;

    /**
     * Get audit logs by causer
     *
     * @param  string  $causerType  Model class name
     * @param  int  $causerId  Model ID
     * @param  int  $limit  Maximum results
     * @return Collection<int, AuditLog> Audit logs collection
     */
    public function getByCauser(string $causerType, int $causerId, int $limit = 100): Collection;

    /**
     * Get audit logs within a date range
     *
     * @param  Carbon  $from  Start date
     * @param  Carbon  $to  End date
     * @param  string|null  $tenantId  Tenant ID for filtering
     * @return Collection<int, AuditLog> Audit logs collection
     */
    public function getByDateRange(Carbon $from, Carbon $to, ?string $tenantId = null): Collection;

    /**
     * Purge audit logs older than specified date
     *
     * Used for retention policy enforcement.
     *
     * @param  Carbon  $before  Delete logs older than this date
     * @param  string|null  $tenantId  Optional tenant ID for tenant-specific purging
     * @return int Number of logs purged
     */
    public function purgeExpired(Carbon $before, ?string $tenantId = null): int;

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
     * @return Collection<int, AuditLog> Audit logs collection
     */
    public function export(array $filters, int $maxRecords = 10000): Collection;
}
