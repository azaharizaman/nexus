<?php

declare(strict_types=1);

namespace App\Support\Contracts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Activity Logger Contract
 *
 * Defines the interface for activity logging operations, abstracting
 * the underlying implementation (Spatie, custom database, etc.)
 */
interface ActivityLoggerContract
{
    /**
     * Log an activity on a model
     *
     * @param  string  $description  Activity description
     * @param  Model  $subject  The model being acted upon
     * @param  Model|null  $causer  The user performing the action (null for system actions)
     * @param  array<string, mixed>  $properties  Additional properties to log
     * @param  string|null  $logName  Optional log name for categorization
     */
    public function log(
        string $description,
        Model $subject,
        ?Model $causer = null,
        array $properties = [],
        ?string $logName = null
    ): void;

    /**
     * Get all activities for a specific model
     *
     * @param  Model  $subject  The model to retrieve activities for
     * @return Collection<int, mixed>
     */
    public function getActivities(Model $subject): Collection;

    /**
     * Get activities within a date range
     *
     * @param  Carbon  $from  Start date
     * @param  Carbon  $to  End date
     * @param  string|null  $logName  Optional log name filter
     * @return Collection<int, mixed>
     */
    public function getByDateRange(Carbon $from, Carbon $to, ?string $logName = null): Collection;

    /**
     * Get activities by causer (user who performed the action)
     *
     * @param  Model  $causer  The user who performed actions
     * @param  int  $limit  Maximum number of activities to return
     * @return Collection<int, mixed>
     */
    public function getByCauser(Model $causer, int $limit = 50): Collection;

    /**
     * Get activity statistics
     *
     * @param  array<string, mixed>  $filters  Optional filters (date range, log name, etc.)
     * @return array<string, mixed> Statistics array with counts and aggregates
     */
    public function getStatistics(array $filters = []): array;

    /**
     * Delete old activities before a certain date
     *
     * @param  Carbon  $before  Delete activities older than this date
     * @return int Number of activities deleted
     */
    public function cleanup(Carbon $before): int;
}
