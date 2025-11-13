<?php

declare(strict_types=1);

namespace Nexus\Erp\AuditLogging\Jobs;

use Nexus\Erp\AuditLogging\Contracts\AuditLogRepositoryContract;
use Nexus\Erp\AuditLogging\Events\ActivityLoggedEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Log Activity Job
 *
 * Asynchronous job for writing audit log entries.
 * Prevents performance impact on request processing.
 */
class LogActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    /**
     * Log data to persist
     *
     * @var array<string, mixed>
     */
    protected array $logData;

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $logData  Audit log data
     */
    public function __construct(array $logData)
    {
        $this->logData = $logData;
    }

    /**
     * Execute the job.
     *
     * @param  AuditLogRepositoryContract  $repository  Audit log repository
     */
    public function handle(AuditLogRepositoryContract $repository): void
    {
        try {
            // Create the audit log entry
            $activity = $repository->create($this->logData);

            // Dispatch ActivityLoggedEvent for cross-module integration
            event(new ActivityLoggedEvent(
                logId: $activity->id,
                tenantId: $activity->tenant_id,
                event: $activity->event,
                subjectType: $activity->subject_type,
                subjectId: $activity->subject_id,
                causerType: $activity->causer_type,
                causerId: $activity->causer_id,
                loggedAt: $activity->created_at
            ));
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Failed to create audit log', [
                'error' => $e->getMessage(),
                'log_data' => $this->logData,
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger job retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception  The exception that caused the failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Audit log job failed after all retries', [
            'error' => $exception->getMessage(),
            'log_data' => $this->logData,
        ]);
    }
}
