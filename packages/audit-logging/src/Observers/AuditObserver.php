<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\AuditLogging\Observers;

use Azaharizaman\Erp\AuditLogging\Jobs\LogActivityJob;
use Azaharizaman\Erp\AuditLogging\Services\LogFormatterService;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit Observer
 *
 * Observes model events and dispatches log activity jobs for auditing.
 * Automatically attached to models using the Auditable trait.
 */
class AuditObserver
{
    /**
     * Log formatter service instance
     */
    protected LogFormatterService $formatter;

    /**
     * Create a new observer instance
     */
    public function __construct(LogFormatterService $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Handle the model "created" event.
     *
     * @param  Model  $model  The model that was created
     */
    public function created(Model $model): void
    {
        if (! $this->shouldLog($model, 'created')) {
            return;
        }

        $properties = [
            'attributes' => $this->getModelAttributes($model),
        ];

        // Add custom properties if method exists
        if (method_exists($model, 'auditAdditionalProperties')) {
            $properties = array_merge($properties, $model->auditAdditionalProperties('created'));
        }

        $this->dispatchLogJob($model, 'created', $properties);
    }

    /**
     * Handle the model "updated" event.
     *
     * @param  Model  $model  The model that was updated
     */
    public function updated(Model $model): void
    {
        if (! $this->shouldLog($model, 'updated')) {
            return;
        }

        // Check if model should log before/after state
        $shouldLogBeforeAfter = method_exists($model, 'auditShouldLogBeforeAfter')
            ? $model->auditShouldLogBeforeAfter()
            : config('audit-logging.enable_before_after', true);

        $properties = [];

        if ($shouldLogBeforeAfter) {
            $properties = $this->formatter->extractBeforeAfterState($model);
        } else {
            // Only log changed attributes without old values
            $properties = [
                'attributes' => $this->getOnlyDirty($model),
            ];
        }

        // Add custom properties if method exists
        if (method_exists($model, 'auditAdditionalProperties')) {
            $properties = array_merge($properties, $model->auditAdditionalProperties('updated'));
        }

        $this->dispatchLogJob($model, 'updated', $properties);
    }

    /**
     * Handle the model "deleted" event.
     *
     * @param  Model  $model  The model that was deleted
     */
    public function deleted(Model $model): void
    {
        if (! $this->shouldLog($model, 'deleted')) {
            return;
        }

        $properties = [
            'attributes' => $this->getModelAttributes($model),
        ];

        // Add custom properties if method exists
        if (method_exists($model, 'auditAdditionalProperties')) {
            $properties = array_merge($properties, $model->auditAdditionalProperties('deleted'));
        }

        $this->dispatchLogJob($model, 'deleted', $properties);
    }

    /**
     * Dispatch the log activity job
     *
     * @param  Model  $model  The model being logged
     * @param  string  $event  Event type
     * @param  array<string, mixed>  $properties  Log properties
     */
    protected function dispatchLogJob(Model $model, string $event, array $properties): void
    {
        // Get log name from model if method exists
        $logName = method_exists($model, 'auditLogName')
            ? $model->auditLogName()
            : $model->getTable();

        // Get custom description from model if method exists
        $description = null;
        if (method_exists($model, 'auditDescription')) {
            $description = $model->auditDescription($event);
        }

        $logData = $this->formatter->format($model, $event, [
            'log_name' => $logName,
            'description' => $description,
            'properties' => $properties,
        ]);

        // Check if audit logging is enabled
        if (! config('audit-logging.enabled', true)) {
            return;
        }

        // Dispatch job to queue for asynchronous processing
        LogActivityJob::dispatch($logData)
            ->onQueue(config('audit-logging.queue_name', 'audit-logs'))
            ->onConnection(config('audit-logging.queue_connection', config('queue.default')));
    }

    /**
     * Check if the event should be logged for this model
     *
     * @param  Model  $model  The model
     * @param  string  $event  Event name
     * @return bool True if should log
     */
    protected function shouldLog(Model $model, string $event): bool
    {
        // Check if model has shouldAuditEvent method
        if (method_exists($model, 'shouldAuditEvent')) {
            return $model->shouldAuditEvent($event);
        }

        // Default to checking auditableEvents if method exists
        if (method_exists($model, 'auditableEvents')) {
            return in_array($event, $model->auditableEvents(), true);
        }

        return true;
    }

    /**
     * Get model attributes excluding audit excluded attributes
     *
     * @param  Model  $model  The model
     * @return array<string, mixed> Model attributes
     */
    protected function getModelAttributes(Model $model): array
    {
        $attributes = $model->getAttributes();

        // Exclude attributes if method exists
        if (method_exists($model, 'auditExcludeAttributes')) {
            $excludeAttributes = $model->auditExcludeAttributes();
            $attributes = array_diff_key($attributes, array_flip($excludeAttributes));
        }

        return $this->formatter->maskSensitiveFields($attributes);
    }

    /**
     * Get only dirty (changed) attributes
     *
     * @param  Model  $model  The model
     * @return array<string, mixed> Dirty attributes
     */
    protected function getOnlyDirty(Model $model): array
    {
        $dirty = $model->getDirty();

        // Exclude attributes if method exists
        if (method_exists($model, 'auditExcludeAttributes')) {
            $excludeAttributes = $model->auditExcludeAttributes();
            $dirty = array_diff_key($dirty, array_flip($excludeAttributes));
        }

        return $this->formatter->maskSensitiveFields($dirty);
    }
}
