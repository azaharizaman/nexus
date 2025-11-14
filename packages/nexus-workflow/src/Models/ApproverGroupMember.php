<?php

declare(strict_types=1);

namespace Nexus\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ApproverGroupMember Model
 * 
 * Represents a single approver within an approver group.
 * Stores sequence for sequential strategy and weight for weighted strategy.
 * 
 * @property string $id
 * @property string $approver_group_id
 * @property string $user_id
 * @property int|null $sequence
 * @property int|null $weight
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ApproverGroupMember extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'approver_group_members';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'approver_group_id',
        'user_id',
        'sequence',
        'weight',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'sequence' => 'integer',
        'weight' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the approver group this member belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ApproverGroup::class, 'approver_group_id');
    }

    /**
     * Scope to get members by sequence order.
     */
    public function scopeBySequence($query)
    {
        return $query->orderBy('sequence');
    }

    /**
     * Scope to get members by weight (descending).
     */
    public function scopeByWeight($query)
    {
        return $query->orderByDesc('weight');
    }

    /**
     * Scope to get members with a specific sequence.
     */
    public function scopeWithSequence($query, int $sequence)
    {
        return $query->where('sequence', $sequence);
    }

    /**
     * Scope to get members for a specific user.
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if this member has a sequence assigned.
     */
    public function hasSequence(): bool
    {
        return $this->sequence !== null;
    }

    /**
     * Check if this member has a weight assigned.
     */
    public function hasWeight(): bool
    {
        return $this->weight !== null;
    }

    /**
     * Check if this is the first approver in sequence.
     */
    public function isFirstInSequence(): bool
    {
        if (!$this->hasSequence()) {
            return false;
        }

        $firstSequence = $this->group->members()->min('sequence');
        return $this->sequence === $firstSequence;
    }

    /**
     * Check if this is the last approver in sequence.
     */
    public function isLastInSequence(): bool
    {
        if (!$this->hasSequence()) {
            return false;
        }

        $lastSequence = $this->group->members()->max('sequence');
        return $this->sequence === $lastSequence;
    }

    /**
     * Get the next member in sequence.
     */
    public function getNextInSequence(): ?self
    {
        if (!$this->hasSequence()) {
            return null;
        }

        return $this->group->members()
            ->where('sequence', '>', $this->sequence)
            ->orderBy('sequence')
            ->first();
    }

    /**
     * Get the previous member in sequence.
     */
    public function getPreviousInSequence(): ?self
    {
        if (!$this->hasSequence()) {
            return null;
        }

        return $this->group->members()
            ->where('sequence', '<', $this->sequence)
            ->orderByDesc('sequence')
            ->first();
    }

    /**
     * Get normalized weight (as percentage of total).
     * Only applicable for weighted strategy.
     */
    public function getNormalizedWeight(): ?float
    {
        if (!$this->hasWeight()) {
            return null;
        }

        $totalWeight = $this->group->members()->sum('weight');
        if ($totalWeight === 0) {
            return null;
        }

        return ($this->weight / $totalWeight) * 100;
    }
}
