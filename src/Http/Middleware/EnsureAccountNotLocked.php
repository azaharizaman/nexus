<?php

declare(strict_types=1);

namespace Nexus\Erp\Http\Middleware;

use Nexus\Erp\Exceptions\AccountLockedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Account Not Locked Middleware
 *
 * Checks if the authenticated user's account is locked.
 * If locked and lockout period has expired, automatically unlocks the account.
 * Otherwise, returns 423 Locked response.
 */
class EnsureAccountNotLocked
{
    /**
     * Handle an incoming request
     *
     *
     * @throws AccountLockedException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Check if account is locked
        if ($user->isLocked()) {
            // Check if temporary lockout has expired
            if ($user->locked_until && $user->locked_until->isPast()) {
                // Automatically unlock account
                $user->locked_until = null;
                $user->failed_login_attempts = 0;
                $user->save();

                return $next($request);
            }

            // Account is still locked
            throw new AccountLockedException(
                'Account is locked. ' . ($user->locked_until ? 'Try again after ' . $user->locked_until->format('Y-m-d H:i:s') : 'Please contact administrator.')
            );
        }

        return $next($request);
    }
}
