<?php

declare(strict_types=1);

namespace App\Support\Services\Auth;

use App\Models\User;
use App\Support\Contracts\TokenServiceContract;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Sanctum Token Service
 *
 * Adapter implementation using Laravel Sanctum package.
 * This isolates the Sanctum package from our business logic.
 */
class SanctumTokenService implements TokenServiceContract
{
    /**
     * Create a new API token for a user
     *
     * @param  User  $user  The user to create token for
     * @param  string  $name  Token name/device identifier
     * @param  array<int, string>  $abilities  Token abilities/permissions
     * @return string The plain text token
     */
    public function createToken(User $user, string $name, array $abilities = ['*']): string
    {
        return $user->createToken($name, $abilities)->plainTextToken;
    }

    /**
     * Revoke a specific token
     *
     * @param  User  $user  The user who owns the token
     * @param  int|string  $tokenId  The token ID to revoke
     * @return bool True if token was revoked
     */
    public function revokeToken(User $user, int|string $tokenId): bool
    {
        $deleted = $user->tokens()->where('id', $tokenId)->delete();

        return $deleted > 0;
    }

    /**
     * Revoke all tokens for a user
     *
     * @param  User  $user  The user whose tokens to revoke
     * @return bool True if tokens were revoked
     */
    public function revokeAllTokens(User $user): bool
    {
        $deleted = $user->tokens()->delete();

        return $deleted > 0;
    }

    /**
     * Get all active tokens for a user
     *
     * @param  User  $user  The user to get tokens for
     * @return Collection<int, mixed>
     */
    public function getActiveTokens(User $user): Collection
    {
        return $user->tokens()->get();
    }

    /**
     * Validate a token and return the associated user
     *
     * @param  string  $token  The plain text token to validate
     */
    public function validateToken(string $token): ?User
    {
        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken === null) {
            return null;
        }

        $tokenable = $accessToken->tokenable;

        return $tokenable instanceof User ? $tokenable : null;
    }

    /**
     * Check if a user has a specific token ability
     *
     * @param  User  $user  The user to check
     * @param  string  $ability  The ability to check for
     */
    public function tokenCan(User $user, string $ability): bool
    {
        return $user->tokenCan($ability);
    }

    /**
     * Get the current access token for a user
     *
     * @param  User  $user  The user to get current token for
     * @return mixed|null
     */
    public function currentAccessToken(User $user): mixed
    {
        return $user->currentAccessToken();
    }
}
