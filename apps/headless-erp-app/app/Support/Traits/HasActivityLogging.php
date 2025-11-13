<?php

declare(strict_types=1);

namespace Nexus\Erp\Support\Traits;

use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Trait HasActivityLogging
 *
 * Wrapper trait for activity logging functionality that decouples business logic
 * from the underlying Spatie Activitylog package. This trait still uses Spatie
 * internally but provides a consistent interface that can be replaced if needed.
 *
 * For direct service-level logging (not model events), inject ActivityLoggerContract instead.
 *
 * Usage:
 * ```
 * class YourModel extends Model
 * {
 *     use HasActivityLogging;
 *
 *     protected function configureActivityLogging(): array
 *     {
 *         return [
 *             'log_name' => 'your_model',
 *             'log_attributes' => ['name', 'status'],
 *             'log_only_dirty' => true,
 *         ];
 *     }
 * }
 * ```
 */
trait HasActivityLogging
{
    use LogsActivity;

    /**
     * Get the activity log options for this model
     *
     * Override configureActivityLogging() in your model to customize logging behavior.
     */
    public function getActivitylogOptions(): LogOptions
    {
        $config = $this->configureActivityLogging();

        $options = LogOptions::defaults();

        if (isset($config['log_name'])) {
            $options->useLogName($config['log_name']);
        }

        if (isset($config['log_attributes'])) {
            $options->logOnly($config['log_attributes']);
        } elseif (isset($config['log_all'])) {
            $options->logAll();
        }

        if (isset($config['log_only_dirty']) && $config['log_only_dirty']) {
            $options->logOnlyDirty();
        }

        if (isset($config['dont_submit_empty_logs']) && $config['dont_submit_empty_logs']) {
            $options->dontSubmitEmptyLogs();
        }

        return $options;
    }

    /**
     * Configure activity logging for this model
     *
     * Override this method in your model to specify logging configuration.
     *
     * Available options:
     * - log_name: Custom log name (default: model table name)
     * - log_attributes: Array of attributes to log (if not set, uses log_all)
     * - log_all: Boolean to log all attributes (default: false)
     * - log_only_dirty: Boolean to log only changed attributes (default: false)
     * - dont_submit_empty_logs: Boolean to skip empty logs (default: false)
     *
     * @return array<string, mixed>
     */
    protected function configureActivityLogging(): array
    {
        return [
            'log_only_dirty' => true,
            'dont_submit_empty_logs' => true,
        ];
    }

    /**
     * Get all activity logs for this model instance
     */
    public function getActivityLogs(): Collection
    {
        return $this->activities;
    }

    /**
     * Get the latest activity log for this model instance
     *
     * @return \Spatie\Activitylog\Models\Activity|null
     */
    public function getLatestActivity(): mixed
    {
        return $this->activities()->latest()->first();
    }
}
