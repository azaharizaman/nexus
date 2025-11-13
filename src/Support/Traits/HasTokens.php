<?php

declare(strict_types=1);

namespace Nexus\Erp\Support\Traits;

use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;

/**
 * Trait HasTokens
 *
 * Wrapper trait for API token functionality that decouples business logic
 * from the underlying Laravel Sanctum package. This trait still uses Sanctum
 * internally but provides a consistent interface that can be replaced if needed.
 *
 * For direct token operations in services, inject TokenServiceContract instead.
 *
 * Usage:
 * ```
 * class User extends Authenticatable
 * {
 *     use HasTokens;
 *
 *     protected function configureTokens(): array
 *     {
 *         return [
 *             'default_abilities' => ['read', 'write'],
 *             'token_prefix' => 'api',
 *         ];
 *     }
 * }
 * ```
 */
trait HasTokens
{
    use HasApiTokens;

    /**
     * Create a new personal access token for the user
     *
     * @param  array<string>  $abilities
     */
    public function createApiToken(string $name, array $abilities = ['*']): NewAccessToken
    {
        $config = $this->configureTokens();

        // Apply default abilities if configured and none provided
        if ($abilities === ['*'] && isset($config['default_abilities'])) {
            $abilities = $config['default_abilities'];
        }

        // Apply token prefix if configured
        if (isset($config['token_prefix'])) {
            $name = $config['token_prefix'] . '-' . $name;
        }

        return $this->createToken($name, $abilities);
    }

    /**
     * Revoke a specific token by ID
     */
    public function revokeApiToken(int|string $tokenId): bool
    {
        return $this->tokens()->where('id', $tokenId)->delete() > 0;
    }

    /**
     * Revoke all tokens for this user
     */
    public function revokeAllApiTokens(): bool
    {
        return $this->tokens()->delete() > 0;
    }

    /**
     * Get all active tokens for this user
     */
    public function getActiveTokens(): Collection
    {
        return $this->tokens;
    }

    /**
     * Check if the current token has a specific ability
     */
    public function currentTokenHasAbility(string $ability): bool
    {
        $token = $this->currentAccessToken();

        if (! $token) {
            return false;
        }

        return $token->can($ability);
    }

    /**
     * Configure token behavior for this model
     *
     * Override this method in your model to specify token configuration.
     *
     * Available options:
     * - default_abilities: Array of default abilities for new tokens (default: ['*'])
     * - token_prefix: String prefix for token names (default: none)
     *
     * @return array<string, mixed>
     */
    protected function configureTokens(): array
    {
        return [];
    }
}
