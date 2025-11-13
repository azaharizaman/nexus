<?php

declare(strict_types=1);

use Nexus\Erp\Core\Contracts\TenantManagerContract;
use Nexus\Erp\Core\Models\Tenant;

if (! function_exists('tenant')) {
    /**
     * Get the current active tenant from context
     *
     * Convenience helper to access the current tenant set by IdentifyTenant middleware.
     * Returns null if no tenant is set in the current request context.
     *
     * @return Tenant|null The current tenant or null if not set
     */
    function tenant(): ?Tenant
    {
        $tenantManager = app(TenantManagerContract::class);

        return $tenantManager->current();
    }
}
