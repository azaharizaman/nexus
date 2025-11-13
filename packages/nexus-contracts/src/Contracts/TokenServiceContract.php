<?php

declare(strict_types=1);

namespace Nexus\Contracts\Contracts;

use Illuminate\Support\Collection;

/**
 * Token service contract
 *
 * @package Nexus\Contracts
 */
interface TokenServiceContract
{
    /**
     * Create a new token for a user
     *
     * @param \Illuminate\Database\Eloquent\Model $user
     * @param string $name
     * @param array<string> $abilities
     * @return string Plain text token
     */
    public function createToken(\Illuminate\Database\Eloquent\Model $user, string $name, array $abilities = []): string;

    /**
     * Revoke a specific token
     *
     * @param \Illuminate\Database\Eloquent\Model $user
     * @param int|string $tokenId
     * @return bool
     */
    public function revokeToken(\Illuminate\Database\Eloquent\Model $user, int|string $tokenId): bool;

    /**
     * Revoke all tokens for a user
     *
     * @param \Illuminate\Database\Eloquent\Model $user
     * @return bool
     */
    public function revokeAllTokens(\Illuminate\Database\Eloquent\Model $user): bool;

    /**
     * Get active tokens for a user
     *
     * @param \Illuminate\Database\Eloquent\Model $user
     * @return Collection
     */
    public function getActiveTokens(\Illuminate\Database\Eloquent\Model $user): Collection;
}
