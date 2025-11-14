<?php

declare(strict_types=1);

namespace Nexus\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * WorkflowInstance Model
 * 
 * Represents a running instance of a workflow attached to any model.
 * Tracks current state, context data, and lifecycle timestamps.
 * 
 * @property string $id
 * @property string|null $workflow_definition_id
 * @property string $subject_type
 * @property string $subject_id
 * @property string $current_state
 * @property array|null $data
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WorkflowInstance extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'workflow_instances';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'workflow_definition_id',
        'subject_type',
        'subject_id',
        'current_state',
        'data',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the workflow definition for this instance.
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    /**
     * Get the subject model (polymorphic).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all transitions for this workflow instance.
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class)->orderBy('performed_at');
    }

    /**
     * Get all user tasks for this workflow instance.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(UserTask::class);
    }

    /**
     * Scope to get active (not completed) workflow instances.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('completed_at');
    }

    /**
     * Scope to get completed workflow instances.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Scope to get instances in a specific state.
     */
    public function scopeInState($query, string $state)
    {
        return $query->where('current_state', $state);
    }

    /**
     * Scope to get instances for a specific subject.
     */
    public function scopeForSubject($query, Model $subject)
    {
        return $query->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->getKey());
    }

    /**
     * Check if workflow is completed.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Check if workflow is in a specific state.
     */
    public function isInState(string $state): bool
    {
        return $this->current_state === $state;
    }

    /**
     * Mark workflow as completed.
     */
    public function markAsCompleted(): bool
    {
        $this->completed_at = now();
        return $this->save();
    }

    /**
     * Get workflow history (all transitions).
     */
    public function getHistory(): array
    {
        return $this->transitions()
            ->get()
            ->map(fn($transition) => [
                'transition' => $transition->transition,
                'from' => $transition->from_state,
                'to' => $transition->to_state,
                'timestamp' => $transition->performed_at,
                'metadata' => $transition->metadata,
                'performed_by' => $transition->performed_by,
            ])
            ->toArray();
    }

    /**
     * Add context data to the workflow.
     */
    public function addData(string $key, $value): void
    {
        $data = $this->data ?? [];
        $data[$key] = $value;
        $this->data = $data;
        $this->save();
    }

    /**
     * Get context data from the workflow.
     */
    public function getData(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
}
