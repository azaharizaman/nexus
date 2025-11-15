<?php

declare(strict_types=1);

namespace Nexus\Hrm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Training extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hrm_trainings';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'category',
        'training_type', // internal, external, online, classroom
        'duration_hours',
        'provider',
        'cost',
        'max_participants',
        'prerequisites',
        'objectives',
        'materials',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'duration_hours' => 'decimal:2',
        'cost' => 'decimal:2',
        'max_participants' => 'integer',
        'prerequisites' => 'array',
        'objectives' => 'array',
        'materials' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all enrollments for this training
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    /**
     * Scope for tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for active trainings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for category
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('training_type', $type);
    }

    /**
     * Check if training is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if training has available spots
     */
    public function hasAvailableSpots(): bool
    {
        if (!$this->max_participants) {
            return true;
        }

        return $this->enrollments()->where('status', 'enrolled')->count() < $this->max_participants;
    }

    /**
     * Get current enrollment count
     */
    public function getCurrentEnrollmentCount(): int
    {
        return $this->enrollments()->where('status', 'enrolled')->count();
    }
}