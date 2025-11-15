<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationLog extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_operation_logs';

    protected $fillable = [
        'work_order_id',
        'operation_id',
        'operator_id',
        'start_time',
        'end_time',
        'status',
        'setup_time_actual',
        'run_time_actual',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'setup_time_actual' => 'decimal:2',
        'run_time_actual' => 'decimal:2',
    ];

    /**
     * Get the parent work order.
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    /**
     * Get the operation.
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(RoutingOperation::class, 'operation_id');
    }

    /**
     * Get total time in minutes.
     */
    public function getTotalTimeMinutes(): ?float
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Check if operation is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if operation is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
