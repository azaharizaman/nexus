<?php

declare(strict_types=1);

namespace Nexus\Crm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmEntity extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'entity_type',
        'definition_id',
        'owner_id',
        'data',
        'status',
        'current_stage_id',
        'assigned_users',
        'assignment_strategy',
        'score',
        'priority',
        'last_activity_at',
        'due_date',
    ];

    protected $casts = [
        'data' => 'array',
        'assigned_users' => 'array',
        'score' => 'decimal:2',
        'last_activity_at' => 'datetime',
        'due_date' => 'datetime',
    ];

    /**
     * Get the definition for this entity.
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(CrmDefinition::class, 'definition_id');
    }

    /**
     * Get the current stage for this entity.
     */
    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(CrmStage::class, 'current_stage_id');
    }

    /**
     * Get the assignments for this entity.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(CrmAssignment::class, 'entity_id');
    }

    /**
     * Get the timers for this entity.
     */
    public function timers(): HasMany
    {
        return $this->hasMany(CrmTimer::class, 'entity_id');
    }

    /**
     * Scope to entities with specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to entities of specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('entity_type', $type);
    }

    /**
     * Scope to entities assigned to a user.
     */
    public function scopeAssignedTo($query, string $userId)
    {
        return $query->whereJsonContains('assigned_users', $userId);
    }

    /**
     * Scope to entities due before a date.
     */
    public function scopeDueBefore($query, $date)
    {
        return $query->where('due_date', '<', $date);
    }

    /**
     * Scope to high priority entities.
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', 8);
    }
}