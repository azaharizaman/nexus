<?php

declare(strict_types=1);

namespace Nexus\Erp\Support\Services\Logging;

use Nexus\Erp\Support\Contracts\ActivityLoggerContract;
use Nexus\AuditLog\Contracts\AuditLogRepositoryContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

/**
 * Spatie Activity Logger
 *
 * Orchestration layer implementation that uses the internal atomic AuditLog package
 * while maintaining backward compatibility with Spatie ActivityLog interfaces.
 * 
 * This service provides the bridge between external expectations (Spatie models)
 * and internal atomic package implementation.
 */
class SpatieActivityLogger implements ActivityLoggerContract
{
    public function __construct(
        private AuditLogRepositoryContract $auditLogRepository,
        private SpatieActivityLoggerAdapter $adapter
    ) {}

    /**
     * Log an activity on a model
     *
     * @param  string  $description  Activity description
     * @param  Model  $subject  The model being acted upon
     * @param  Model|null  $causer  The user performing the action
     * @param  array<string, mixed>  $properties  Additional properties to log
     * @param  string|null  $logName  Optional log name for categorization
     */
    public function log(
        string $description,
        Model $subject,
        ?Model $causer = null,
        array $properties = [],
        ?string $logName = null
    ): void {
        // Prepare data for internal audit log
        $data = [
            'log_name' => $logName ?? 'default',
            'description' => $description,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->getKey(),
            'causer_type' => $causer ? get_class($causer) : (auth()->check() ? get_class(auth()->user()) : null),
            'causer_id' => $causer ? $causer->getKey() : (auth()->check() ? auth()->id() : null),
            'properties' => $properties,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->header('User-Agent'),
            'tenant_id' => $this->getCurrentTenantId(),
        ];

        // Use internal audit log repository through adapter
        $this->adapter->logActivity($data);
    }

    /**
     * Get all activities for a specific model
     *
     * @param  Model  $subject  The model to retrieve activities for
     * @return Collection<int, mixed>
     */
    public function getActivities(Model $subject): Collection
    {
        // Use internal repository for consistency
        $internalLogs = $this->auditLogRepository->getForSubject(
            get_class($subject),
            $subject->getKey()
        );

        // Convert to Spatie-compatible collection for backward compatibility
        return $internalLogs->map(function ($log) {
            return $this->adapter->convertToSpatieActivity($log);
        });
    }

    /**
     * Get activities within a date range
     *
     * @param  Carbon  $from  Start date
     * @param  Carbon  $to  End date
     * @param  string|null  $logName  Optional log name filter
     * @return Collection<int, mixed>
     */
    public function getByDateRange(Carbon $from, Carbon $to, ?string $logName = null): Collection
    {
        // Use internal repository
        $internalLogs = $this->auditLogRepository->getByDateRange($from, $to);
        
        if ($logName !== null) {
            $internalLogs = $internalLogs->where('log_name', $logName);
        }

        // Convert to Spatie-compatible collection
        return $internalLogs->map(function ($log) {
            return $this->adapter->convertToSpatieActivity($log);
        });
    }

    /**
     * Get activities by causer (user who performed the action)
     *
     * @param  Model  $causer  The user who performed actions
     * @param  int  $limit  Maximum number of activities to return
     * @return Collection<int, mixed>
     */
    public function getByCauser(Model $causer, int $limit = 50): Collection
    {
        // Use internal repository
        $internalLogs = $this->auditLogRepository->getByCauser(
            get_class($causer),
            $causer->getKey(),
            $limit
        );

        // Convert to Spatie-compatible collection
        return $internalLogs->map(function ($log) {
            return $this->adapter->convertToSpatieActivity($log);
        });
    }

    /**
     * Get activity statistics
     *
     * @param  array<string, mixed>  $filters  Optional filters
     * @return array<string, mixed>
     */
    public function getStatistics(array $filters = []): array
    {
        // Use internal repository for statistics
        return $this->auditLogRepository->getStatistics($filters);
    }

    /**
     * Delete old activities before a certain date
     *
     * @param  Carbon  $before  Delete activities older than this date
     * @return int Number of activities deleted
     */
    public function cleanup(Carbon $before): int
    {
        // Use internal repository for cleanup
        return $this->auditLogRepository->purgeExpired($before);
    }

    /**
     * Get the current tenant ID for multi-tenant logging
     */
    private function getCurrentTenantId(): ?string
    {
        // Try to get from authenticated user's method first
        if (auth()->check() && method_exists(auth()->user(), 'getTenantId')) {
            return auth()->user()->getTenantId();
        }
        
        // Try to get from authenticated user's property
        if (auth()->check() && property_exists(auth()->user(), 'tenant_id')) {
            return auth()->user()->tenant_id;
        }

        // Try to get from current tenant context
        if (function_exists('currentTenant')) {
            $tenant = currentTenant();
            if ($tenant && method_exists($tenant, 'getId')) {
                return $tenant->getId();
            }
            if ($tenant && isset($tenant->id)) {
                return $tenant->id;
            }
        }

        // Try to get from session/request
        if (session()->has('tenant_id')) {
            return session()->get('tenant_id');
        }

        return null;
    }
}
