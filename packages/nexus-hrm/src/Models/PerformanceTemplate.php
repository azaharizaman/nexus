<?php

declare(strict_types=1);

namespace Nexus\Hrm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hrm_performance_templates';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'template_data',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'template_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all reviews using this template
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
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if template is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }
}