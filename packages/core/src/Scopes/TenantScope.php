<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * This method automatically adds a WHERE clause to filter records by tenant_id.
     * It ensures that all queries on tenant-aware models only return records
     * belonging to the current tenant, providing data isolation at the database level.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Get the current tenant from the request context
        $tenantId = $this->getCurrentTenantId();

        // Only apply the scope if a tenant is set
        if ($tenantId !== null) {
            $builder->where($model->getTable().'.tenant_id', '=', $tenantId);
        }
    }

    /**
     * Get the current tenant ID from the application context.
     *
     * This method retrieves the tenant ID from the authenticated user or
     * from the application container if set via TenantManager service.
     */
    protected function getCurrentTenantId(): ?string
    {
        // Try to get tenant from authenticated user
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }

        // Try to get tenant from application container (for CLI commands, queued jobs, etc.)
        if (app()->bound('tenant.current')) {
            $tenant = app('tenant.current');

            return $tenant?->id;
        }

        return null;
    }
}
