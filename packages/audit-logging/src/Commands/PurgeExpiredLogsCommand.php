<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\AuditLogging\Commands;

use Azaharizaman\Erp\AuditLogging\Contracts\AuditLogRepositoryContract;
use Azaharizaman\Erp\AuditLogging\Events\LogRetentionExpiredEvent;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Purge Expired Logs Command
 *
 * Artisan command to purge audit logs older than configured retention period.
 * Should be scheduled to run daily via Task Scheduler.
 */
class PurgeExpiredLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:purge-expired
                            {--tenant= : Purge logs for specific tenant only}
                            {--days= : Override retention days from config}
                            {--dry-run : Preview how many logs would be purged without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge audit logs older than configured retention period';

    /**
     * Execute the console command.
     *
     * @param  AuditLogRepositoryContract  $repository  Audit log repository
     * @return int Command exit code
     */
    public function handle(AuditLogRepositoryContract $repository): int
    {
        $this->info('Starting audit log purge process...');

        // Get retention period
        $retentionDays = $this->option('days')
            ? (int) $this->option('days')
            : config('audit-logging.retention_days', 2555);

        $this->info("Retention period: {$retentionDays} days");

        // Calculate cutoff date
        $cutoffDate = now()->subDays($retentionDays);
        $this->info("Cutoff date: {$cutoffDate->toDateTimeString()}");

        // Get tenant ID if specified
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $this->info("Purging logs for tenant: {$tenantId}");
        } else {
            $this->info('Purging logs for all tenants');
        }

        // Dry run check
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No logs will be deleted');

            // Count logs that would be purged
            $count = $this->countExpiredLogs($repository, $cutoffDate, $tenantId);
            $this->info("Would purge {$count} log(s)");

            return self::SUCCESS;
        }

        // Confirm before purging (if not in quiet mode)
        if (! $this->option('quiet')) {
            $count = $this->countExpiredLogs($repository, $cutoffDate, $tenantId);

            if ($count === 0) {
                $this->info('No expired logs to purge');

                return self::SUCCESS;
            }

            $confirmed = $this->confirm("About to purge {$count} log(s). Continue?", true);

            if (! $confirmed) {
                $this->info('Purge cancelled');

                return self::SUCCESS;
            }
        }

        // Purge expired logs
        $purgedCount = $repository->purgeExpired($cutoffDate, $tenantId);

        $this->info("Successfully purged {$purgedCount} audit log(s)");

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

        return self::SUCCESS;
    }

    /**
     * Count expired logs without deleting
     *
     * @param  AuditLogRepositoryContract  $repository  Repository
     * @param  Carbon  $cutoffDate  Cutoff date
     * @param  string|null  $tenantId  Optional tenant ID
     * @return int Number of logs
     */
    protected function countExpiredLogs(
        AuditLogRepositoryContract $repository,
        Carbon $cutoffDate,
        ?string $tenantId
    ): int {
        $query = \Spatie\Activitylog\Models\Activity::where('created_at', '<', $cutoffDate);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->count();
    }
}
