<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Traits;

use Azaharizaman\Erp\Core\Models\Tenant;
use Azaharizaman\Erp\Core\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait for a model.
     *
     * This method is automatically called by Laravel when a model using this trait
     * is booted. It adds the TenantScope global scope and auto-sets the tenant_id
     * when creating new models.
     */
    protected static function bootBelongsToTenant(): void
    {
        // Add the global scope for automatic tenant filtering
        static::addGlobalScope(new TenantScope);

        // Automatically set tenant_id when creating a new model
        static::creating(function ($model) {
            if (! $model->tenant_id) {
                $model->tenant_id = static::getCurrentTenantIdForModel();
            }
        });
    }

    /**
     * Get the tenant that the model belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Query the model without the tenant scope applied.
     *
     * This method allows bypassing the automatic tenant filtering,
     * useful for administrative operations that need to access
     * data across all tenants.
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope(TenantScope::class);
    }

    /**
     * Query all records across all tenants.
     *
     * Alias for withoutTenantScope() for better readability.
     */
    public static function withAllTenants(): Builder
    {
        return static::withoutTenantScope();
    }

    /**
     * Get the current tenant ID for setting on model creation.
     *
     * This method retrieves the tenant ID from the authenticated user or
     * from the application container.
     */
    protected static function getCurrentTenantIdForModel(): ?string
    {
        // Try to get tenant from authenticated user
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }

        // Try to get tenant from application container
        if (app()->bound('tenant.current')) {
            $tenant = app('tenant.current');

            return $tenant?->id;
        }

        return null;
    }
}
