<?php

declare(strict_types=1);

namespace Nexus\Hrm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hrm_performance_reviews';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'reviewer_id',
        'performance_cycle_id',
        'review_template_id',
        'review_date',
        'overall_rating',
        'reviewer_comments',
        'employee_comments',
        'status',
        'scores',
        'goals_assessment',
        'development_plan',
        'next_review_date',
    ];

    protected $casts = [
        'review_date' => 'date',
        'next_review_date' => 'date',
        'scores' => 'array',
        'goals_assessment' => 'array',
        'development_plan' => 'array',
        'overall_rating' => 'decimal:2',
    ];

    /**
     * Get the employee this review belongs to
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the reviewer (manager/supervisor)
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_id');
    }

    /**
     * Get the performance cycle this review belongs to
     */
    public function performanceCycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class);
    }

    /**
     * Get the review template used
     */
    public function reviewTemplate(): BelongsTo
    {
        return $this->belongsTo(PerformanceTemplate::class);
    }

    /**
     * Scope for tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for employee
     */
    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope for status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if review is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if review is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Calculate average score from scores array
     */
    public function getAverageScoreAttribute(): ?float
    {
        if (!$this->scores) {
            return null;
        }

        $scores = array_column($this->scores, 'score');
        return count($scores) > 0 ? array_sum($scores) / count($scores) : null;
    }
}
