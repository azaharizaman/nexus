<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Models;

use App\Support\Traits\HasActivityLogging;
use App\Support\Traits\IsSearchable;
use Nexus\Erp\Core\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasActivityLogging, HasFactory, HasUuids, IsSearchable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'domain',
        'status',
        'configuration',
        'subscription_plan',
        'billing_email',
        'contact_name',
        'contact_email',
        'contact_phone',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => TenantStatus::class,
        'configuration' => 'encrypted:array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the users associated with the tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class);
    }

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === TenantStatus::ACTIVE;
    }

    /**
     * Check if tenant is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::SUSPENDED;
    }

    /**
     * Check if tenant is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === TenantStatus::ARCHIVED;
    }

    /**
     * Scope a query to only include active tenants.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', TenantStatus::ACTIVE);
    }

    /**
     * Configure activity logging for this model.
     *
     * @return array<string, mixed>
     */
    protected function configureActivityLogging(): array
    {
        return [
            'log_name' => 'tenants',
            'log_attributes' => [
                'name',
                'domain',
                'status',
                'subscription_plan',
                'billing_email',
                'contact_name',
                'contact_email',
                'contact_phone',
            ],
            'log_only_dirty' => true,
            'dont_submit_empty_logs' => true,
        ];
    }

    /**
     * Configure search behavior for this model.
     *
     * @return array<string, mixed>
     */
    protected function configureSearchable(): array
    {
        return [
            'index_name' => 'tenants',
            'searchable_fields' => [
                'id',
                'name',
                'domain',
                'status',
                'subscription_plan',
                'billing_email',
                'contact_name',
                'contact_email',
                'created_at',
            ],
        ];
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\TenantFactory::new();
    }
}
