<?php

declare(strict_types=1);

namespace App\Domains\Core\Models;

use App\Domains\Core\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Tenant extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

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
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', TenantStatus::ACTIVE);
    }

    /**
     * Get the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'domain',
                'status',
                'subscription_plan',
                'billing_email',
                'contact_name',
                'contact_email',
                'contact_phone',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
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
