<?php

declare(strict_types=1);

namespace Nexus\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ApproverGroup Model
 * 
 * Defines a group of approvers for a workflow transition with a specific approval strategy.
 * Strategies: sequential, parallel, quorum, any, weighted.
 * 
 * @property string $id
 * @property string $workflow_definition_id
 * @property string $transition
 * @property string $strategy
 * @property int|null $quorum_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ApproverGroup extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'approver_groups';

    /**
     * Approval strategy constants.
     */
    public const STRATEGY_SEQUENTIAL = 'sequential';
    public const STRATEGY_PARALLEL = 'parallel';
    public const STRATEGY_QUORUM = 'quorum';
    public const STRATEGY_ANY = 'any';
    public const STRATEGY_WEIGHTED = 'weighted';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'workflow_definition_id',
        'transition',
        'strategy',
        'quorum_count',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quorum_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the workflow definition this approver group belongs to.
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    /**
     * Get all members of this approver group.
     */
    public function members(): HasMany
    {
        return $this->hasMany(ApproverGroupMember::class);
    }

    /**
     * Get members ordered by sequence (for sequential strategy).
     */
    public function membersInSequence(): HasMany
    {
        return $this->members()->orderBy('sequence');
    }

    /**
     * Scope to get approver groups for a specific transition.
     */
    public function scopeForTransition($query, string $transition)
    {
        return $query->where('transition', $transition);
    }

    /**
     * Scope to get approver groups with a specific strategy.
     */
    public function scopeWithStrategy($query, string $strategy)
    {
        return $query->where('strategy', $strategy);
    }

    /**
     * Check if strategy is sequential.
     */
    public function isSequential(): bool
    {
        return $this->strategy === self::STRATEGY_SEQUENTIAL;
    }

    /**
     * Check if strategy is parallel.
     */
    public function isParallel(): bool
    {
        return $this->strategy === self::STRATEGY_PARALLEL;
    }

    /**
     * Check if strategy is quorum.
     */
    public function isQuorum(): bool
    {
        return $this->strategy === self::STRATEGY_QUORUM;
    }

    /**
     * Check if strategy is any.
     */
    public function isAny(): bool
    {
        return $this->strategy === self::STRATEGY_ANY;
    }

    /**
     * Check if strategy is weighted.
     */
    public function isWeighted(): bool
    {
        return $this->strategy === self::STRATEGY_WEIGHTED;
    }

    /**
     * Get the required number of approvals.
     * 
     * - Sequential: All members in sequence
     * - Parallel: All members
     * - Quorum: quorum_count
     * - Any: 1
     * - Weighted: Custom logic based on weights
     */
    public function getRequiredApprovalCount(): ?int
    {
        return match($this->strategy) {
            self::STRATEGY_SEQUENTIAL => $this->members()->count(),
            self::STRATEGY_PARALLEL => $this->members()->count(),
            self::STRATEGY_QUORUM => $this->quorum_count,
            self::STRATEGY_ANY => 1,
            self::STRATEGY_WEIGHTED => null, // Weighted is calculated dynamically
            default => null,
        };
    }

    /**
     * Get the next approver in sequence.
     * Only applicable for sequential strategy.
     */
    public function getNextApprover(int $currentSequence): ?ApproverGroupMember
    {
        if (!$this->isSequential()) {
            return null;
        }

        return $this->members()
            ->where('sequence', '>', $currentSequence)
            ->orderBy('sequence')
            ->first();
    }

    /**
     * Get all approvers for parallel/quorum strategies.
     */
    public function getAllApprovers()
    {
        return $this->members()->get();
    }

    /**
     * Validate strategy configuration.
     */
    public function validateConfiguration(): bool
    {
        // Quorum strategy must have quorum_count set
        if ($this->isQuorum() && !$this->quorum_count) {
            return false;
        }

        // Quorum count must not exceed member count
        if ($this->isQuorum() && $this->quorum_count > $this->members()->count()) {
            return false;
        }

        // Sequential strategy must have sequence numbers set
        if ($this->isSequential()) {
            $membersWithoutSequence = $this->members()->whereNull('sequence')->count();
            if ($membersWithoutSequence > 0) {
                return false;
            }
        }

        // Weighted strategy must have weights set
        if ($this->isWeighted()) {
            $membersWithoutWeight = $this->members()->whereNull('weight')->count();
            if ($membersWithoutWeight > 0) {
                return false;
            }
        }

        return true;
    }
}
