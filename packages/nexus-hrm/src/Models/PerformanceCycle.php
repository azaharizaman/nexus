<?php

declare(strict_types=1);

namespace Nexus\Hrm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceCycle extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hrm_performance_cycles';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'frequency', // annual, bi-annual, quarterly, monthly
        'status', // draft, active, completed, archived
        'auto_schedule_reviews',
        'review_deadline_days',
        'reminder_days_before',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'auto_schedule_reviews' => 'boolean',
        'review_deadline_days' => 'integer',
        'reminder_days_before' => 'integer',
    ];

    /**
     * Get all reviews for this cycle
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    /**
     * Scope for tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for active cycles
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if cycle is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if cycle is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}