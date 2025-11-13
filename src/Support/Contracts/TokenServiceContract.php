<?php

declare(strict_types=1);

namespace Nexus\Erp\Support\Contracts;

use Nexus\Erp\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Token Service Contract
 *
 * Defines the interface for API token management operations,
 * abstracting the underlying implementation (Sanctum, JWT, etc.)
 */
interface TokenServiceContract
{
    /**
     * Create a new API token for a user
     *
     * @param  User  $user  The user to create token for
     * @param  string  $name  Token name/device identifier
     * @param  array<int, string>  $abilities  Token abilities/permissions
     * @return string The plain text token
     */
    public function createToken(User $user, string $name, array $abilities = ['*']): string;

    /**
     * Revoke a specific token
     *
     * @param  User  $user  The user who owns the token
     * @param  int|string  $tokenId  The token ID to revoke
     * @return bool True if token was revoked
     */
    public function revokeToken(User $user, int|string $tokenId): bool;

    /**
     * Revoke all tokens for a user
     *
     * @param  User  $user  The user whose tokens to revoke
     * @return bool True if tokens were revoked
     */
    public function revokeAllTokens(User $user): bool;

    /**
     * Get all active tokens for a user
     *
     * @param  User  $user  The user to get tokens for
     * @return Collection<int, mixed> Collection of tokens
     */
    public function getActiveTokens(User $user): Collection;

    /**
     * Validate a token and return the associated user
     *
     * @param  string  $token  The plain text token to validate
     * @return User|null The user if token is valid, null otherwise
     */
    public function validateToken(string $token): ?User;

    /**
     * Check if a user has a specific token ability
     *
     * @param  User  $user  The user to check
     * @param  string  $ability  The ability to check for
     * @return bool True if user's current token has the ability
     */
    public function tokenCan(User $user, string $ability): bool;

    /**
     * Get the current access token for a user
     *
     * @param  User  $user  The user to get current token for
     * @return mixed|null The current token or null
     */
    public function currentAccessToken(User $user): mixed;
}
