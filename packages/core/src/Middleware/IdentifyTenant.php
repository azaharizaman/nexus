<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Middleware;

use Nexus\Erp\Core\Contracts\TenantManagerContract;
use Nexus\Erp\Core\Contracts\TenantRepositoryContract;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identify Tenant Middleware
 *
 * Resolves the current tenant from the authenticated user and injects it
 * into the request lifecycle. Sets tenant context in TenantManager for
 * use throughout the application.
 *
 * This middleware implements cache-first tenant loading with Redis for
 * improved performance. Cached tenants have a configurable TTL.
 *
 * Error Response Codes:
 * - 401 Unauthenticated: if no user is authenticated
 * - 403 Forbidden: if user has no tenant_id
 * - 404 Not Found: if tenant cannot be resolved from database
 *
 * Middleware Ordering:
 * This middleware MUST be applied after authentication middleware (e.g., auth:sanctum)
 * to ensure the user is authenticated before resolving tenant context.
 *
 * Usage Example:
 * Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
 *     Route::get('/dashboard', [DashboardController::class, 'index']);
 * });
 */
class IdentifyTenant
{
    /**
     * Create a new middleware instance
     */
    public function __construct(
        protected readonly TenantManagerContract $tenantManager,
        protected readonly TenantRepositoryContract $tenantRepository
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

        // Load tenant with cache-first strategy
        $tenant = $this->loadTenantFromCacheOrDatabase($user->tenant_id);

        // Handle missing tenant gracefully
        if ($tenant === null) {
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
     * Load tenant from cache first, fall back to database if not cached
     *
     * @param  string  $tenantId  The tenant ID
     * @return \Nexus\Erp\Core\Models\Tenant|null
     */
    protected function loadTenantFromCacheOrDatabase(string $tenantId)
    {
        $cacheKey = "tenant:{$tenantId}";
        $cacheTtl = config('erp-core.tenant_cache_ttl', 3600);

        // Try to get tenant from cache first
        $tenant = Cache::get($cacheKey);

        if ($tenant !== null) {
            // Cache hit - return cached tenant
            return $tenant;
        }

        // Cache miss - load from database
        Log::warning("Tenant cache miss for ID: {$tenantId}");

        $tenant = $this->tenantRepository->findById($tenantId);

        // Store in cache if tenant was found
        if ($tenant !== null) {
            Cache::put($cacheKey, $tenant, $cacheTtl);
        }

        return $tenant;
    }

    /**
     * Determine if tenant identification should be skipped for this request
     *
     * @param  Request  $request  The HTTP request
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
