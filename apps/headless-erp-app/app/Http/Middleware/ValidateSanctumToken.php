<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validate Sanctum Token Middleware
 *
 * Validates Sanctum tokens with caching to improve performance.
 * Checks token expiration from cache first before hitting the database.
 */
class ValidateSanctumToken
{
    /**
     * Handle an incoming request
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $token = $user->currentAccessToken();

        if (! $token) {
            return response()->json([
                'message' => 'Invalid or expired token',
            ], 401);
        }

        // Get cache TTL from config
        $cacheTtl = config('authentication.cache_ttl', 3600);

        // Create cache key
        $cacheKey = "token_valid:{$token->id}";

        // Check token validity from cache
        $isValid = Cache::remember($cacheKey, $cacheTtl, function () use ($token) {
            // Check if token has expiration and if it's expired
            if ($token->expires_at && $token->expires_at->isPast()) {
                return false;
            }

            return true;
        });

        if (! $isValid) {
            return response()->json([
                'message' => 'Token has expired',
            ], 401);
        }

        return $next($request);
    }
}
