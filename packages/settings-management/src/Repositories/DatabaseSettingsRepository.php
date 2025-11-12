<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SettingsManagement\Repositories;

use Azaharizaman\Erp\SettingsManagement\Contracts\SettingsRepositoryContract;
use Azaharizaman\Erp\SettingsManagement\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Database Settings Repository
 *
 * Provides data access layer for settings with tenant isolation.
 */
class DatabaseSettingsRepository implements SettingsRepositoryContract
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
    ): ?Setting {
        $query = Setting::where('key', $key)
            ->where('scope', $scope);

        // Apply scope-specific filters
        if ($scope === Setting::SCOPE_SYSTEM) {
            $query->whereNull('tenant_id')
                ->whereNull('module_name')
                ->whereNull('user_id');
        } elseif ($scope === Setting::SCOPE_TENANT) {
            $query->where('tenant_id', $tenantId)
                ->whereNull('module_name')
                ->whereNull('user_id');
        } elseif ($scope === Setting::SCOPE_MODULE) {
            $query->where('tenant_id', $tenantId)
                ->where('module_name', $moduleName)
                ->whereNull('user_id');
        } elseif ($scope === Setting::SCOPE_USER) {
            $query->where('tenant_id', $tenantId)
                ->where('user_id', $userId);
        }

        return $query->first();
    }

    /**
     * Create a new setting
     *
     * @param array<string, mixed> $data The setting data
     * @return Setting The created setting model
     */
    public function create(array $data): Setting
    {
        return Setting::create($data);
    }

    /**
     * Update an existing setting
     *
     * @param Setting $setting The setting model to update
     * @param array<string, mixed> $data The updated data
     * @return bool True if update was successful
     */
    public function update(Setting $setting, array $data): bool
    {
        return $setting->update($data);
    }

    /**
     * Delete a setting (soft delete)
     *
     * @param Setting $setting The setting to delete
     * @return bool True if delete was successful
     */
    public function delete(Setting $setting): bool
    {
        return $setting->delete();
    }

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
    ): Collection {
        $query = Setting::where('scope', $scope);

        if ($scope === Setting::SCOPE_SYSTEM) {
            $query->whereNull('tenant_id')
                ->whereNull('module_name')
                ->whereNull('user_id');
        } elseif ($scope === Setting::SCOPE_TENANT) {
            $query->where('tenant_id', $tenantId)
                ->whereNull('module_name')
                ->whereNull('user_id');
        } elseif ($scope === Setting::SCOPE_MODULE) {
            $query->where('tenant_id', $tenantId)
                ->where('module_name', $moduleName)
                ->whereNull('user_id');
        } elseif ($scope === Setting::SCOPE_USER) {
            $query->where('tenant_id', $tenantId)
                ->where('user_id', $userId);
        }

        return $query->get();
    }

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
    ): Collection {
        $settings = collect();

        foreach ($scopes as $scope) {
            $scopeSettings = $this->getByScope($scope, $tenantId, $moduleName, $userId);
            $settings = $settings->merge($scopeSettings);
        }

        return $settings;
    }

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
    ): bool {
        return $this->findByKey($key, $scope, $tenantId, $moduleName, $userId) !== null;
    }

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
    ): Collection {
        // Convert wildcard pattern to SQL LIKE pattern
        $likePattern = str_replace('*', '%', $pattern);

        $query = Setting::where('key', 'LIKE', $likePattern)
            ->where('scope', $scope);

        if ($scope === Setting::SCOPE_SYSTEM) {
            $query->whereNull('tenant_id')
                ->whereNull('module_name')
                ->whereNull('user_id');
        } elseif ($scope === Setting::SCOPE_TENANT) {
            $query->where('tenant_id', $tenantId)
                ->whereNull('module_name')
                ->whereNull('user_id');
        } elseif ($scope === Setting::SCOPE_MODULE) {
            $query->where('tenant_id', $tenantId)
                ->where('module_name', $moduleName)
                ->whereNull('user_id');
        } elseif ($scope === Setting::SCOPE_USER) {
            $query->where('tenant_id', $tenantId)
                ->where('user_id', $userId);
        }

        return $query->get();
    }
}
