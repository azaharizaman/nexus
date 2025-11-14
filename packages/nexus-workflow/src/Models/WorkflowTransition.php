<?php

declare(strict_types=1);

namespace Nexus\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WorkflowTransition Model
 * 
 * Immutable audit trail record of state transitions within a workflow instance.
 * Captures who performed the transition, when, and any associated metadata.
 * 
 * @property string $id
 * @property string $workflow_instance_id
 * @property string $transition
 * @property string $from_state
 * @property string $to_state
 * @property array|null $metadata
 * @property string|null $performed_by
 * @property \Carbon\Carbon $performed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WorkflowTransition extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'workflow_transitions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'workflow_instance_id',
        'transition',
        'from_state',
        'to_state',
        'metadata',
        'performed_by',
        'performed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'metadata' => 'array',
        'performed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     * 
     * We use performed_at as the canonical timestamp.
     */
    public $timestamps = true;

    /**
     * Get the workflow instance this transition belongs to.
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    /**
     * Scope to get transitions by performer.
     */
    public function scopeByPerformer($query, string $userId)
    {
        return $query->where('performed_by', $userId);
    }

    /**
     * Scope to get transitions within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('performed_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get transitions by transition name.
     */
    public function scopeByTransition($query, string $transition)
    {
        return $query->where('transition', $transition);
    }

    /**
     * Scope to get transitions from a specific state.
     */
    public function scopeFromState($query, string $state)
    {
        return $query->where('from_state', $state);
    }

    /**
     * Scope to get transitions to a specific state.
     */
    public function scopeToState($query, string $state)
    {
        return $query->where('to_state', $state);
    }

    /**
     * Get formatted transition description.
     */
    public function getDescription(): string
    {
        return sprintf(
            '%s: %s → %s',
            $this->transition,
            $this->from_state,
            $this->to_state
        );
    }

    /**
     * Get metadata value by key.
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }
}
