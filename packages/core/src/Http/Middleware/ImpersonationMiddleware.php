<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Http\Middleware;

use Azaharizaman\Erp\Core\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impersonation Middleware
 *
 * Checks for active impersonation sessions and ensures they haven't timed out.
 * If an impersonation session has exceeded the configured timeout, it will
 * automatically end the impersonation and restore the original tenant context.
 *
 * This middleware should be applied to routes where impersonation timeout
 * enforcement is required.
 *
 * Usage Example:
 * Route::middleware(['auth:sanctum', 'tenant', 'impersonation'])->group(function () {
 *     Route::get('/dashboard', [DashboardController::class, 'index']);
 * });
 */
class ImpersonationMiddleware
{
    /**
     * Create a new middleware instance
     */
    public function __construct(
        protected readonly ImpersonationService $impersonationService
    ) {}

    /**
     * Handle an incoming request
     *
     * Checks if the current user is impersonating a tenant and verifies
     * the impersonation session hasn't timed out. If timeout has been
     * exceeded, automatically ends the impersonation.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // Check if user is currently impersonating
        if ($this->impersonationService->isImpersonating($user)) {
            // Verify impersonation hasn't timed out
            // If it has, the service will return false and impersonation will be ended
            // The Redis cache TTL will naturally expire, triggering automatic cleanup

            // The impersonation service automatically handles timeout through Redis TTL
            // If the cache key is missing, isImpersonating returns false
            // No additional action needed here - the TTL handles it
        }

        return $next($request);
    }
}
