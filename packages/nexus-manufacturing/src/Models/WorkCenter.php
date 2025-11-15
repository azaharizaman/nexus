<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkCenter extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_work_centers';

    protected $fillable = [
        'tenant_id',
        'work_center_code',
        'description',
        'department_id',
        'capacity_units_per_hour',
        'shifts_per_day',
        'overhead_rate_per_hour',
        'status',
        'cost_center_code',
    ];

    protected $casts = [
        'capacity_units_per_hour' => 'decimal:2',
        'shifts_per_day' => 'integer',
        'overhead_rate_per_hour' => 'decimal:4',
    ];

    /**
     * Check if work center is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Calculate daily capacity.
     */
    public function getDailyCapacity(int $hoursPerShift = 8): float
    {
        return $this->capacity_units_per_hour * $hoursPerShift * $this->shifts_per_day;
    }

    /**
     * Calculate weekly capacity.
     */
    public function getWeeklyCapacity(int $workDays = 5, int $hoursPerShift = 8): float
    {
        return $this->getDailyCapacity($hoursPerShift) * $workDays;
    }
}
