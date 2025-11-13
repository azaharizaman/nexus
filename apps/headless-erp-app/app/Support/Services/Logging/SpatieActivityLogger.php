<?php

declare(strict_types=1);

namespace Nexus\Erp\Support\Services\Logging;

use Nexus\Erp\Support\Contracts\ActivityLoggerContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

/**
 * Spatie Activity Logger
 *
 * Adapter implementation using Spatie Laravel Activitylog package.
 * This isolates the Spatie package from our business logic.
 */
class SpatieActivityLogger implements ActivityLoggerContract
{
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
        $activity = activity()
            ->performedOn($subject)
            ->withProperties($properties);

        if ($logName !== null) {
            $activity->useLog($logName);
        }

        if ($causer !== null) {
            $activity->causedBy($causer);
        } elseif (auth()->check()) {
            $activity->causedBy(auth()->user());
        }

        $activity->log($description);
    }

    /**
     * Get all activities for a specific model
     *
     * @param  Model  $subject  The model to retrieve activities for
     * @return Collection<int, mixed>
     */
    public function getActivities(Model $subject): Collection
    {
        return Activity::forSubject($subject)
            ->with(['causer', 'subject'])
            ->latest()
            ->get();
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
        $query = Activity::query()
            ->whereBetween('created_at', [$from, $to])
            ->with(['causer', 'subject'])
            ->latest();

        if ($logName !== null) {
            $query->where('log_name', $logName);
        }

        return $query->get();
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
        return Activity::causedBy($causer)
            ->with(['subject'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity statistics
     *
     * @param  array<string, mixed>  $filters  Optional filters
     * @return array<string, mixed>
     */
    public function getStatistics(array $filters = []): array
    {
        $query = Activity::query();

        // Apply date range filter if provided
        if (isset($filters['from']) && isset($filters['to'])) {
            $query->whereBetween('created_at', [$filters['from'], $filters['to']]);
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

        $byDay = (clone $query)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->pluck('count', 'date')
            ->toArray();

        return [
            'total_count' => $totalCount,
            'by_log_name' => $byLogName,
            'by_day' => $byDay,
            'first_activity' => $query->min('created_at'),
            'last_activity' => $query->max('created_at'),
        ];
    }

    /**
     * Delete old activities before a certain date
     *
     * @param  Carbon  $before  Delete activities older than this date
     * @return int Number of activities deleted
     */
    public function cleanup(Carbon $before): int
    {
        $deleted = Activity::where('created_at', '<', $before)->delete();

        return $deleted === false || $deleted === null ? 0 : $deleted;
    }
}
