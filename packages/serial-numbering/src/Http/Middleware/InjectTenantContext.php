<?php

declare(strict_types=1);

namespace Nexus\Erp\SerialNumbering\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Inject Tenant Context Middleware
 *
 * Injects tenant_id from the authenticated user or tenant context
 * into the request for sequence operations.
 */
class InjectTenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  The HTTP request
     * @param  Closure  $next  The next middleware
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Try to get tenant_id from various sources
        $tenantId = $this->resolveTenantId($request);

        if ($tenantId !== null) {
            // Inject tenant_id into request
            $request->merge(['tenant_id' => $tenantId]);
        }

        return $next($request);
    }

    /**
     * Resolve tenant ID from request context.
     *
     * @param  Request  $request  The HTTP request
     * @return string|null The tenant ID or null
     */
    private function resolveTenantId(Request $request): ?string
    {
        // 1. Check if tenant_id is already in request (from previous middleware)
        if ($request->has('tenant_id')) {
            return (string) $request->get('tenant_id');
        }

        // 2. Check if user is authenticated and has tenant_id
        if ($request->user() && isset($request->user()->tenant_id)) {
            return (string) $request->user()->tenant_id;
        }

        // 3. Check for tenant context (from multi-tenancy middleware)
        if (function_exists('tenant') && tenant() !== null) {
            return (string) tenant()->id;
        }

        // 4. Check session for tenant context
        if ($request->session()->has('tenant_id')) {
            return (string) $request->session()->get('tenant_id');
        }

        return null;
    }
}
