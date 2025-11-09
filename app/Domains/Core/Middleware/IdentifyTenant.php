<?php

declare(strict_types=1);

namespace App\Domains\Core\Middleware;

use App\Domains\Core\Contracts\TenantManagerContract;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identify Tenant Middleware
 *
 * Resolves the current tenant from the authenticated user and injects it
 * into the request lifecycle. Sets tenant context in TenantManager for
 * use throughout the application.
 */
class IdentifyTenant
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
     * Resolves tenant from authenticated user and sets it in the TenantManager.
     * This middleware should be applied after authentication middleware (e.g., auth:sanctum).
     *
     * Returns error responses for different failure modes:
     * - 401 Unauthenticated: if no user is authenticated
     * - 403 Forbidden: if user has no tenant_id
     * - 404 Not Found: if tenant cannot be resolved from database
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tenant identification for system-level routes (e.g., tenant management)
        if ($this->shouldSkipTenantIdentification($request)) {
            return $next($request);
        }

        // Note: This check is redundant if auth middleware is properly applied
        // but provides a safety net for misconfigured routes
        if (! auth()->check()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user = auth()->user();

        // Check if user has a tenant
        if (! $user->tenant_id) {
            return response()->json([
                'message' => 'User does not belong to any tenant.',
            ], 403);
        }

        // Resolve tenant from user using direct query to avoid N+1
        $tenant = \App\Domains\Core\Models\Tenant::find($user->tenant_id);

        // Handle missing tenant gracefully
        if (! $tenant) {
            return response()->json([
                'message' => 'Tenant not found.',
            ], 404);
        }

        // Set tenant context in TenantManager
        $this->tenantManager->setActive($tenant);

        // Continue with request
        return $next($request);
    }

    /**
     * Determine if tenant identification should be skipped for this request
     *
     * @param  Request  $request  The HTTP request
     * @return bool
     */
    protected function shouldSkipTenantIdentification(Request $request): bool
    {
        $path = $request->path();

        // Skip for tenant management routes (admin-only)
        if (str_starts_with($path, 'api/v1/tenants')) {
            return true;
        }

        return false;
    }
}
