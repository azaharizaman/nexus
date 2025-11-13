<?php

declare(strict_types=1);

namespace Nexus\Contracts\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Activity logger service contract
 *
 * @package Nexus\Contracts
 */
interface ActivityLoggerContract
{
    /**
     * Log an activity
     *
     * @param string $description
     * @param Model $subject
     * @param Model|null $causer
     * @param array<string, mixed> $properties
     * @param string|null $logName
     * @return void
     */
    public function log(
        string $description,
        Model $subject,
        ?Model $causer = null,
        array $properties = [],
        ?string $logName = null
    ): void;

    /**
     * Get activities for a subject
     *
     * @param Model $subject
     * @return Collection
     */
    public function getActivities(Model $subject): Collection;

    /**
     * Get activities by date range
     *
     * @param \Carbon\Carbon $from
     * @param \Carbon\Carbon $to
     * @param string|null $logName
     * @return Collection
     */
    public function getByDateRange(\Carbon\Carbon $from, \Carbon\Carbon $to, ?string $logName = null): Collection;
}
