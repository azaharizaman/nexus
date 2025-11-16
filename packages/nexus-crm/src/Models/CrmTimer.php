<?php

declare(strict_types=1);

namespace Nexus\Crm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmTimer extends Model
{
    use HasUlids;
    protected $fillable = [
        'entity_id',
        'name',
        'description',
        'type',
        'scheduled_at',
        'executed_at',
        'duration_minutes',
        'action_config',
        'status',
        'is_recurring',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
        'action_config' => 'array',
        'is_recurring' => 'boolean',
        'duration_minutes' => 'integer',
    ];

    /**
     * Get the entity for this timer.
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(CrmEntity::class, 'entity_id');
    }

    /**
     * Scope to pending timers.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to executed timers.
     */
    public function scopeExecuted($query)
    {
        return $query->where('status', 'executed');
    }

    /**
     * Scope to timers scheduled before a date.
     */
    public function scopeScheduledBefore($query, $date)
    {
        return $query->where('scheduled_at', '<', $date);
    }

    /**
     * Scope to timers of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to recurring timers.
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Check if timer is due.
     */
    public function isDue(): bool
    {
        return $this->status === 'pending' && $this->scheduled_at->isPast();
    }

    /**
     * Mark timer as executed.
     */
    public function markExecuted(): void
    {
        $this->update([
            'status' => 'executed',
            'executed_at' => now(),
        ]);
    }
}