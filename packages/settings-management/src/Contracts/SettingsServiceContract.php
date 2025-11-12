<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SettingsManagement\Contracts;

/**
 * Settings Service Contract
 *
 * Defines the interface for settings management operations with hierarchical
 * scope resolution (user → module → tenant → system).
 */
interface SettingsServiceContract
{
    /**
     * Get a setting value with hierarchical resolution
     *
     * Resolves settings in order: user → module → tenant → system
     * Returns default value if setting not found at any level.
     *
     * @param string $key The setting key in dot notation (e.g., 'email.smtp.host')
     * @param mixed $default Default value if setting not found
     * @param string|null $scope Optional scope to limit search (system, tenant, module, user)
     * @param int|null $tenantId Optional tenant ID (uses current tenant if null)
     * @param string|null $moduleName Optional module name for module-scoped settings
     * @param int|null $userId Optional user ID for user-scoped settings
     * @return mixed The setting value cast to appropriate type
     */
    public function get(
        string $key,
        mixed $default = null,
        ?string $scope = null,
        ?int $tenantId = null,
        ?string $moduleName = null,
        ?int $userId = null
    ): mixed;

    /**
     * Set a setting value
     *
     * Creates or updates a setting with the specified scope.
     * Automatically encrypts values with type='encrypted'.
     * Invalidates related caches.
     *
     * @param string $key The setting key in dot notation
     * @param mixed $value The setting value
     * @param string $type The value type (string, integer, boolean, array, json, encrypted)
     * @param string $scope The scope level (system, tenant, module, user)
     * @param array<string, mixed> $metadata Optional metadata (validation rules, description, etc.)
     * @param int|null $tenantId Optional tenant ID (uses current tenant if null for non-system scope)
     * @param string|null $moduleName Optional module name for module-scoped settings
     * @param int|null $userId Optional user ID for user-scoped settings
     * @return bool True if setting was saved successfully
     */
    public function set(
        string $key,
        mixed $value,
        string $type = 'string',
        string $scope = 'tenant',
        array $metadata = [],
        ?int $tenantId = null,
        ?string $moduleName = null,
        ?int $userId = null
    ): bool;

    /**
     * Check if a setting exists
     *
     * @param string $key The setting key
     * @param string|null $scope Optional scope to limit check
     * @param int|null $tenantId Optional tenant ID
     * @param string|null $moduleName Optional module name
     * @param int|null $userId Optional user ID
     * @return bool True if setting exists
     */
    public function has(
        string $key,
        ?string $scope = null,
        ?int $tenantId = null,
        ?string $moduleName = null,
        ?int $userId = null
    ): bool;

    /**
     * Delete a setting
     *
     * Soft deletes the setting and invalidates cache.
     *
     * @param string $key The setting key
     * @param string $scope The scope level
     * @param int|null $tenantId Optional tenant ID
     * @param string|null $moduleName Optional module name
     * @param int|null $userId Optional user ID
     * @return bool True if setting was deleted
     */
    public function forget(
        string $key,
        string $scope = 'tenant',
        ?int $tenantId = null,
        ?string $moduleName = null,
        ?int $userId = null
    ): bool;

    /**
     * Get all settings for a scope with hierarchical merging
     *
     * Returns merged settings from all applicable scopes.
     * For tenant scope, merges system + tenant settings.
     * For module scope, merges system + tenant + module settings.
     * For user scope, merges system + tenant + module + user settings.
     *
     * @param string $scope The scope level
     * @param int|null $tenantId Optional tenant ID
     * @param string|null $moduleName Optional module name
     * @param int|null $userId Optional user ID
     * @return array<string, mixed> Array of setting key => value pairs
     */
    public function all(
        string $scope = 'tenant',
        ?int $tenantId = null,
        ?string $moduleName = null,
        ?int $userId = null
    ): array;

    /**
     * Set multiple settings at once
     *
     * @param array<string, mixed> $settings Array of key => value pairs
     * @param string $type The value type for all settings
     * @param string $scope The scope level
     * @param int|null $tenantId Optional tenant ID
     * @param string|null $moduleName Optional module name
     * @param int|null $userId Optional user ID
     * @return int Number of settings successfully saved
     */
    public function setMany(
        array $settings,
        string $type = 'string',
        string $scope = 'tenant',
        ?int $tenantId = null,
        ?string $moduleName = null,
        ?int $userId = null
    ): int;

    /**
     * Invalidate cache for a setting key
     *
     * @param string $key The setting key
     * @param string|null $scope Optional specific scope to invalidate
     * @return void
     */
    public function invalidateCache(string $key, ?string $scope = null): void;

    /**
     * Warm cache for frequently accessed settings
     *
     * @param string|null $scope Optional scope to warm (null = all scopes)
     * @param int|null $tenantId Optional tenant ID
     * @return int Number of settings cached
     */
    public function warmCache(?string $scope = null, ?int $tenantId = null): int;
}
