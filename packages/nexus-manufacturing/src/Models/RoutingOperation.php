<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutingOperation extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_routing_operations';

    protected $fillable = [
        'routing_id',
        'operation_number',
        'work_center_id',
        'operation_description',
        'setup_time_minutes',
        'run_time_per_unit_minutes',
        'labor_required',
        'move_time_minutes',
        'is_critical',
    ];

    protected $casts = [
        'operation_number' => 'integer',
        'setup_time_minutes' => 'decimal:2',
        'run_time_per_unit_minutes' => 'decimal:4',
        'labor_required' => 'integer',
        'move_time_minutes' => 'decimal:2',
        'is_critical' => 'boolean',
    ];

    /**
     * Get the parent routing.
     */
    public function routing(): BelongsTo
    {
        return $this->belongsTo(Routing::class, 'routing_id');
    }

    /**
     * Get the work center.
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id');
    }

    /**
     * Calculate total time for a given quantity.
     */
    public function calculateTotalTime(float $quantity): float
    {
        return $this->setup_time_minutes 
            + ($this->run_time_per_unit_minutes * $quantity)
            + $this->move_time_minutes;
    }

    /**
     * Calculate labor hours for a given quantity.
     */
    public function calculateLaborHours(float $quantity): float
    {
        $totalMinutes = $this->calculateTotalTime($quantity);
        return ($totalMinutes / 60) * $this->labor_required;
    }
}
