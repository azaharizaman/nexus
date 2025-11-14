<?php

declare(strict_types=1);

namespace Nexus\AuditLog\Repositories;

use Nexus\AuditLog\Contracts\AuditLogRepositoryContract;
use Nexus\AuditLog\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Database Audit Log Repository
 *
 * Implements audit log storage using the database driver (PostgreSQL/MySQL).
 * Provides append-only storage with tenant isolation.
 * 
 * NOTE: Uses internal AuditLog model to remove Spatie dependency
 * and enable independent testing.
 */
class DatabaseAuditLogRepository implements AuditLogRepositoryContract
{
    /**
     * Create a new audit log entry
     *
     * @param  array<string, mixed>  $data  Log entry data
     * @return AuditLog Created audit log instance
     */
    public function create(array $data): AuditLog
    {
        // Ensure tenant_id is present for tenant-scoped logs
        if (! isset($data['tenant_id']) && auth()->check() && isset(auth()->user()->tenant_id)) {
            $data['tenant_id'] = auth()->user()->tenant_id;
        }

        // Create the audit log entry (append-only)
        /** @var AuditLog $auditLog */
        $auditLog = AuditLog::create([
            'tenant_id' => $data['tenant_id'] ?? null,
            'log_name' => $data['log_name'] ?? 'default',
            'description' => $data['description'],
            'subject_type' => $data['subject_type'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'causer_type' => $data['causer_type'] ?? null,
            'causer_id' => $data['causer_id'] ?? null,
            'event' => $data['event'] ?? null,
            'properties' => $data['properties'] ?? [],
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'batch_uuid' => $data['batch_uuid'] ?? null,
            'audit_level' => $data['audit_level'] ?? 1,
            'retention_days' => $data['retention_days'] ?? config('audit-logging.retention_days', 90),
        ]);

        return $auditLog;
    }

    /**
     * Find an audit log by ID
     *
     * @param  int  $id  Audit log ID
     * @return AuditLog|null Audit log instance or null if not found
     */
    public function find(int $id): ?AuditLog
    {
        return AuditLog::with(['subject', 'causer'])->find($id);
    }

    /**
     * Search audit logs with filters and pagination
     *
     * @param  array<string, mixed>  $filters  Search filters
     * @param  int  $perPage  Results per page
     * @return LengthAwarePaginator Paginated results
     */
    public function search(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = AuditLog::query();

        // Always apply tenant_id filter for tenant isolation
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        } elseif (auth()->check() && isset(auth()->user()->tenant_id)) {
            $query->where('tenant_id', auth()->user()->tenant_id);
        }

        // Filter by causer_id (user who performed the action)
        if (isset($filters['causer_id'])) {
            $query->where('causer_id', $filters['causer_id']);
        }

        // Filter by event type
        if (isset($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        // Filter by subject type
        if (isset($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        // Filter by subject id
        if (isset($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        // Filter by log name
        if (isset($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        // Filter by date range
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        // Full-text search in description
        if (isset($filters['search_query']) && ! empty($filters['search_query'])) {
            // Escape SQL wildcards to prevent injection
            $searchQuery = str_replace(['%', '_'], ['\\%', '\\_'], $filters['search_query']);
            
            $query->where(function ($q) use ($searchQuery) {
                $q->where('description', 'like', '%'.$searchQuery.'%')
                    ->orWhereJsonContains('properties', $searchQuery);
            });
        }

        // Enforce max per page limit
        $maxPerPage = config('audit-logging.search.max_per_page', 1000);
        $perPage = min($perPage, $maxPerPage);

        // Eager load relationships
        $query->with(['subject', 'causer']);

        // Order by created_at descending (newest first)
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get audit logs for a specific subject
     *
     * @param  string  $subjectType  Model class name
     * @param  int  $subjectId  Model ID
     * @param  int  $limit  Maximum results
     * @return Collection<int, AuditLog> Activity logs collection
     */
    public function getForSubject(string $subjectType, int $subjectId, int $limit = 100): Collection
    {
        return AuditLog::where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->with(['causer'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs by causer
     *
     * @param  string  $causerType  Model class name
     * @param  int  $causerId  Model ID
     * @param  int  $limit  Maximum results
     * @return Collection<int, AuditLog> Activity logs collection
     */
    public function getByCauser(string $causerType, int $causerId, int $limit = 100): Collection
    {
        return AuditLog::where('causer_type', $causerType)
            ->where('causer_id', $causerId)
            ->with(['subject'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs within a date range
     *
     * @param  Carbon  $from  Start date
     * @param  Carbon  $to  End date
     * @param  string|null  $tenantId  Tenant ID for filtering
     * @return Collection<int, AuditLog> Activity logs collection
     */
    public function getByDateRange(Carbon $from, Carbon $to, ?string $tenantId = null): Collection
    {
        $query = AuditLog::whereBetween('created_at', [$from, $to])
            ->with(['subject', 'causer'])
            ->orderBy('created_at', 'desc');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get();
    }

    /**
     * Purge audit logs older than specified date
     *
     * @param  Carbon  $before  Delete logs older than this date
     * @param  string|null  $tenantId  Optional tenant ID for tenant-specific purging
     * @return int Number of logs purged
     */
    public function purgeExpired(Carbon $before, ?string $tenantId = null): int
    {
        $query = AuditLog::where('created_at', '<', $before);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->delete();
    }

    /**
     * Get activity statistics
     *
     * @param  array<string, mixed>  $filters  Optional filters
     * @return array<string, mixed> Statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $query = AuditLog::query();

        // Apply tenant filter
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        // Apply date range filter if provided
        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('created_at', [
                Carbon::parse($filters['date_from']),
                Carbon::parse($filters['date_to']),
            ]);
        }

        // Apply log name filter if provided
        if (isset($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        $totalCount = $query->count();

        $byLogName = (clone $query)
            ->selectRaw('log_name, COUNT(*) as count')
            ->groupBy('log_name')
            ->pluck('count', 'log_name')
            ->toArray();

        $byEvent = (clone $query)
            ->selectRaw('event, COUNT(*) as count')
            ->whereNotNull('event')
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();

        return [
            'total_count' => $totalCount,
            'by_log_name' => $byLogName,
            'by_event' => $byEvent,
            'first_activity' => $query->min('created_at'),
            'last_activity' => $query->max('created_at'),
        ];
    }

    /**
     * Export audit logs based on filters
     *
     * @param  array<string, mixed>  $filters  Export filters
     * @param  int  $maxRecords  Maximum records to export
     * @return Collection<int, AuditLog> Activity logs collection
     */
    public function export(array $filters, int $maxRecords = 10000): Collection
    {
        $query = AuditLog::query();

        // Apply same filters as search
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['causer_id'])) {
            $query->where('causer_id', $filters['causer_id']);
        }

        if (isset($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (isset($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        // Eager load relationships
        $query->with(['subject', 'causer']);

        // Order by created_at descending
        $query->orderBy('created_at', 'desc');

        // Limit results
        $query->limit($maxRecords);

        return $query->get();
    }
}
