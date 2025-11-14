<?php

declare(strict_types=1);

namespace Nexus\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserTask Model
 * 
 * Represents a task assigned to a user as part of a workflow.
 * Manages task lifecycle, assignment, priority, and completion.
 * 
 * @property string $id
 * @property string $workflow_instance_id
 * @property string $transition
 * @property string|null $assigned_to
 * @property string|null $assigned_by
 * @property string $status
 * @property string $priority
 * @property string|null $title
 * @property string|null $description
 * @property \Carbon\Carbon|null $due_at
 * @property array|null $result
 * @property \Carbon\Carbon|null $completed_at
 * @property string|null $completed_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class UserTask extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'user_tasks';

    /**
     * Task status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Task priority constants.
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'workflow_instance_id',
        'transition',
        'assigned_to',
        'assigned_by',
        'status',
        'priority',
        'title',
        'description',
        'due_at',
        'result',
        'completed_at',
        'completed_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'result' => 'array',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the workflow instance this task belongs to.
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    /**
     * Scope to get tasks assigned to a specific user.
     */
    public function scopeAssignedTo($query, string $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope to get tasks with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending tasks.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get in-progress tasks.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope to get completed tasks.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get overdue tasks.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_at', '<', now())
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Scope to get tasks by priority.
     */
    public function scopeWithPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to order tasks by priority.
     */
    public function scopeOrderByPriority($query)
    {
        // Urgent > High > Medium > Low
        return $query->orderByRaw(
            "CASE priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END"
        );
    }

    /**
     * Check if task is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if task is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if task is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if task is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if task is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_at !== null
            && $this->due_at->isPast()
            && !$this->isCompleted()
            && !$this->isCancelled();
    }

    /**
     * Mark task as in progress.
     */
    public function markAsInProgress(): bool
    {
        $this->status = self::STATUS_IN_PROGRESS;
        return $this->save();
    }

    /**
     * Mark task as completed.
     */
    public function markAsCompleted(array $result = [], ?string $completedBy = null): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->result = $result;
        $this->completed_at = now();
        $this->completed_by = $completedBy;
        return $this->save();
    }

    /**
     * Mark task as cancelled.
     */
    public function markAsCancelled(): bool
    {
        $this->status = self::STATUS_CANCELLED;
        return $this->save();
    }

    /**
     * Reassign task to another user.
     */
    public function reassign(string $newAssignee, ?string $reassignedBy = null): bool
    {
        $this->assigned_to = $newAssignee;
        if ($reassignedBy) {
            $this->assigned_by = $reassignedBy;
        }
        return $this->save();
    }

    /**
     * Get result value by key.
     */
    public function getResult(string $key, $default = null)
    {
        return $this->result[$key] ?? $default;
    }
}
