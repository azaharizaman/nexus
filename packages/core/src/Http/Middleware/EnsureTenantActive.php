<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Http\Middleware;

use Nexus\Erp\Core\Contracts\TenantManagerContract;
use Nexus\Erp\Core\Enums\TenantStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Tenant Active Middleware
 *
 * Verifies that the current tenant is in an active state.
 * Blocks access for suspended or archived tenants.
 *
 * This middleware should be applied after the IdentifyTenant middleware
 * to ensure tenant context is already established.
 *
 * Error responses:
 * - 403 Forbidden: if tenant is suspended
 * - 403 Forbidden: if tenant is archived
 */
class EnsureTenantActive
{
    /**
     * Create a new middleware instance
     */
    public function __construct(
        protected readonly TenantManagerContract $tenantManager
    ) {}

    /**
     * Handle an incoming request
     *
     * Verifies that the current tenant is active. Returns 403 Forbidden
     * if tenant is suspended or archived.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantManager->current();

        // If no tenant is set, let the request continue
        // (IdentifyTenant middleware should have handled this)
        if ($tenant === null) {
            return $next($request);
        }

        // Check if tenant is suspended
        if ($tenant->status === TenantStatus::SUSPENDED) {
            return response()->json([
                'message' => 'Tenant is suspended.',
            ], 403);
        }

        // Check if tenant is archived
        if ($tenant->status === TenantStatus::ARCHIVED) {
            return response()->json([
                'message' => 'Tenant is archived.',
            ], 403);
        }

        // Tenant is active, continue with request
        return $next($request);
    }
}
