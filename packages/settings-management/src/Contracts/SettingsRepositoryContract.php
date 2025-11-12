<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SettingsManagement\Contracts;

use Azaharizaman\Erp\SettingsManagement\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Settings Repository Contract
 *
 * Defines the data access interface for settings storage.
 */
interface SettingsRepositoryContract
{
    /**
     * Find a setting by key and scope identifiers
     *
     * @param string $key The setting key
     * @param string $scope The scope level
     * @param int|null $tenantId Optional tenant ID
     * @param string|null $moduleName Optional module name
     * @param int|null $userId Optional user ID
     * @return Setting|null The setting model or null if not found
     */
    public function findByKey(
        string $key,
        string $scope,
        ?int $tenantId = null,
        ?string $moduleName = null,
        ?int $userId = null
    ): ?Setting;

    /**
     * Create a new setting
     *
     * @param array<string, mixed> $data The setting data
     * @return Setting The created setting model
     */
    public function create(array $data): Setting;

    /**
     * Update an existing setting
     *
     * @param Setting $setting The setting model to update
     * @param array<string, mixed> $data The updated data
     * @return bool True if update was successful
     */
    public function update(Setting $setting, array $data): bool;

    /**
     * Delete a setting (soft delete)
     *
     * @param Setting $setting The setting to delete
     * @return bool True if delete was successful
     */
    public function delete(Setting $setting): bool;

    /**
     * Get all settings for a specific scope
     *
     * @param string $scope The scope level
     * @param int|null $tenantId Optional tenant ID
     * @param string|null $moduleName Optional module name
     * @param int|null $userId Optional user ID
     * @return Collection<int, Setting> Collection of settings
     */
    public function getByScope(
        string $scope,
        ?int $tenantId = null,
        ?string $moduleName = null,
        ?int $userId = null
    ): Collection;

    /**
     * Get all settings for multiple scopes (for hierarchical resolution)
     *
     * Returns settings from all specified scopes that match the identifiers.
     *
     * @param array<int, string> $scopes Array of scope levels in priority order
     * @param int|null $tenantId Optional tenant ID
     * @param string|null $moduleName Optional module name
     * @param int|null $userId Optional user ID
     * @return Collection<int, Setting> Collection of settings from all scopes
     */
    public function getByScopes(
        array $scopes,
        ?int $tenantId = null,
        ?string $moduleName = null,
        ?int $userId = null
    ): Collection;

    /**
     * Check if a setting exists
     *
     * @param string $key The setting key
     * @param string $scope The scope level
     * @param int|null $tenantId Optional tenant ID
     * @param string|null $moduleName Optional module name
     * @param int|null $userId Optional user ID
     * @return bool True if setting exists
     */
    public function exists(
        string $key,
        string $scope,
        ?int $tenantId = null,
        ?string $moduleName = null,
        ?int $userId = null
    ): bool;

    /**
     * Get settings by key pattern (for bulk operations)
     *
     * @param string $pattern Key pattern (e.g., 'email.*' for all email settings)
     * @param string $scope The scope level
     * @param int|null $tenantId Optional tenant ID
     * @param string|null $moduleName Optional module name
     * @param int|null $userId Optional user ID
     * @return Collection<int, Setting> Collection of matching settings
     */
    public function findByKeyPattern(
        string $pattern,
        string $scope,
        ?int $tenantId = null,
        ?string $moduleName = null,
        ?int $userId = null
    ): Collection;
}
