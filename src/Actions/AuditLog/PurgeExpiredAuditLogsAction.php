<?php

declare(strict_types=1);

namespace Nexus\Erp\Actions\AuditLog;

use Lorisleiva\Actions\Concerns\AsAction;
use Nexus\AuditLog\Contracts\AuditLogRepositoryContract;
use Nexus\AuditLog\Events\LogRetentionExpiredEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Purge Expired Audit Logs Action
 *
 * Purges audit logs older than configured retention period.
 * Available as CLI command and direct invocation.
 */
class PurgeExpiredAuditLogsAction
{
    use AsAction;

    public function __construct(
        protected AuditLogRepositoryContract $repository
    ) {}

    /**
     * Handle the audit log purge
     *
     * @param int $retentionDays Number of days to retain logs
     * @param string|null $tenantId Optional tenant ID to filter purge
     * @param bool $dryRun Whether to perform a dry run
     * @return array Purge results
     */
    public function handle(int $retentionDays = null, ?string $tenantId = null, bool $dryRun = false): array
    {
        $retentionDays = $retentionDays ?? config('audit-logging.retention_days', 90);
        // Calculate cutoff date
        $cutoffDate = now()->subDays($retentionDays);

        if ($dryRun) {
            // Count logs that would be purged
            $count = $this->countExpiredLogs($cutoffDate, $tenantId);
            
            return [
                'dry_run' => true,
                'would_purge' => $count,
                'cutoff_date' => $cutoffDate,
                'tenant_id' => $tenantId,
            ];
        }

        // Purge expired logs
        $purgedCount = $this->repository->purgeExpired($cutoffDate, $tenantId);

        // Dispatch event
        event(new LogRetentionExpiredEvent(
            tenantId: $tenantId,
            purgedCount: $purgedCount,
            cutoffDate: $cutoffDate
        ));

        // Log the purge action using Laravel's standard logging to avoid circular dependency
        Log::info('Purged expired audit logs', [
            'retention_days' => $retentionDays,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            'purged_count' => $purgedCount,
            'tenant_id' => $tenantId,
        ]);

        return [
            'dry_run' => false,
            'purged_count' => $purgedCount,
            'cutoff_date' => $cutoffDate,
            'tenant_id' => $tenantId,
        ];
    }

    /**
     * Count expired logs without deleting
     *
     * Uses the same logic as purgeExpired to ensure accurate count.
     * Note: The search method uses <= for date_to, but purge uses <,
     * so we directly query the model to match the purge behavior.
     *
     * @param Carbon $cutoffDate Cutoff date
     * @param string|null $tenantId Optional tenant ID
     * @return int Number of logs that will be purged
     */
    protected function countExpiredLogs(Carbon $cutoffDate, ?string $tenantId): int
    {
        // Query the model directly to use the same < comparison as purgeExpired
        // This ensures the count accurately reflects what will be deleted
        $query = \Nexus\AuditLog\Models\AuditLog::where('created_at', '<', $cutoffDate);
        
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        
        return $query->count();
    }
}