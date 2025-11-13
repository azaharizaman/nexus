<?php

declare(strict_types=1);

namespace Nexus\Erp\AuditLogging\Traits;

use Nexus\Erp\AuditLogging\Contracts\AuditLogRepositoryContract;
use Illuminate\Support\Str;

/**
 * Logs System Activity Trait
 *
 * Provides helper methods for logging activities from system processes
 * (cron jobs, queue workers, CLI commands).
 *
 * Usage:
 * ```
 * class ImportDataAction
 * {
 *     use LogsSystemActivity;
 *
 *     public function handle(): void
 *     {
 *         $this->logSystemActivity('Started data import', [
 *             'file' => 'data.csv',
 *             'records' => 1000,
 *         ]);
 *
 *         // ... import logic ...
 *
 *         $this->logSystemActivity('Completed data import', [
 *             'imported' => 950,
 *             'failed' => 50,
 *         ]);
 *     }
 * }
 * ```
 */
trait LogsSystemActivity
{
    /**
     * Log a system activity
     *
     * @param  string  $description  Activity description
     * @param  array<string, mixed>  $properties  Additional properties
     * @param  string|null  $logName  Optional log name (defaults to 'system')
     */
    protected function logSystemActivity(
        string $description,
        array $properties = [],
        ?string $logName = null
    ): void {
        $repository = app(AuditLogRepositoryContract::class);

        $logData = [
            'tenant_id' => null, // System-level logs have no tenant
            'log_name' => $logName ?? 'system',
            'description' => $description,
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null, // System as causer
            'causer_id' => null,
            'event' => 'system',
            'properties' => array_merge($properties, [
                'process_id' => getmypid(),
                'command' => $this->getCommandName(),
                'php_version' => PHP_VERSION,
                'timestamp' => now()->toIso8601String(),
            ]),
            'ip_address' => null,
            'user_agent' => 'CLI',
            'request_id' => Str::uuid()->toString(),
        ];

        $repository->create($logData);
    }

    /**
     * Log system activity with error level
     *
     * @param  string  $description  Error description
     * @param  array<string, mixed>  $properties  Additional properties
     */
    protected function logSystemError(string $description, array $properties = []): void
    {
        $this->logSystemActivity(
            'ERROR: '.$description,
            array_merge($properties, ['level' => 'error']),
            'system-errors'
        );
    }

    /**
     * Log system activity with warning level
     *
     * @param  string  $description  Warning description
     * @param  array<string, mixed>  $properties  Additional properties
     */
    protected function logSystemWarning(string $description, array $properties = []): void
    {
        $this->logSystemActivity(
            'WARNING: '.$description,
            array_merge($properties, ['level' => 'warning']),
            'system-warnings'
        );
    }

    /**
     * Get the current command name
     *
     * @return string Command name or 'unknown'
     */
    protected function getCommandName(): string
    {
        if (app()->runningInConsole()) {
            $args = $_SERVER['argv'] ?? [];

            if (count($args) >= 2) {
                return $args[1];
            }
        }

        return 'unknown';
    }

    /**
     * Log batch operation start
     *
     * @param  string  $operationName  Operation name
     * @param  int  $totalRecords  Total records to process
     * @return string Batch ID for tracking
     */
    protected function logBatchStart(string $operationName, int $totalRecords): string
    {
        $batchId = Str::uuid()->toString();

        $this->logSystemActivity("Batch operation started: {$operationName}", [
            'batch_id' => $batchId,
            'operation' => $operationName,
            'total_records' => $totalRecords,
            'status' => 'started',
        ]);

        return $batchId;
    }

    /**
     * Log batch operation completion
     *
     * @param  string  $batchId  Batch ID from logBatchStart
     * @param  string  $operationName  Operation name
     * @param  int  $processedRecords  Number of processed records
     * @param  int  $failedRecords  Number of failed records
     */
    protected function logBatchComplete(
        string $batchId,
        string $operationName,
        int $processedRecords,
        int $failedRecords = 0
    ): void {
        $this->logSystemActivity("Batch operation completed: {$operationName}", [
            'batch_id' => $batchId,
            'operation' => $operationName,
            'processed_records' => $processedRecords,
            'failed_records' => $failedRecords,
            'status' => $failedRecords > 0 ? 'completed_with_errors' : 'completed',
        ]);
    }

    /**
     * Log scheduled task execution
     *
     * @param  string  $taskName  Task name
     * @param  array<string, mixed>  $result  Task execution result
     */
    protected function logScheduledTask(string $taskName, array $result = []): void
    {
        $this->logSystemActivity("Scheduled task executed: {$taskName}", array_merge([
            'task_name' => $taskName,
            'executed_at' => now()->toDateTimeString(),
        ], $result), 'scheduled-tasks');
    }
}
