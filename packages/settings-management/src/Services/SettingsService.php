<?php

declare(strict_types=1);

namespace Nexus\Erp\SettingsManagement\Services;

use Nexus\Erp\SettingsManagement\Contracts\SettingsRepositoryContract;
use Nexus\Erp\SettingsManagement\Contracts\SettingsServiceContract;
use Nexus\Erp\SettingsManagement\Events\CacheInvalidatedEvent;
use Nexus\Erp\SettingsManagement\Events\SettingCreatedEvent;
use Nexus\Erp\SettingsManagement\Events\SettingUpdatedEvent;
use Nexus\Erp\SettingsManagement\Models\Setting;
use Nexus\Erp\SettingsManagement\Models\SettingHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Settings Service
 *
 * Core business logic for settings management with hierarchical resolution,
 * caching, and encryption support.
 */
class SettingsService implements SettingsServiceContract
{
    /**
     * Create a new settings service instance.
     *
     * @param SettingsRepositoryContract $repository
     */
    public function __construct(
        private readonly SettingsRepositoryContract $repository
    ) {}

    /**
     * Get a setting value with hierarchical resolution
     *
     * @param string $key The setting key in dot notation
     * @param mixed $default Default value if setting not found
     * @param string|null $scope Optional scope to limit search
     * @param int|null $tenantId Optional tenant ID
     * @param string|null $moduleName Optional module name
     * @param int|null $userId Optional user ID
     * @return mixed The setting value cast to appropriate type
     */
    public function get(
        string $key,
        mixed $default = null,
        ?string $scope = null,
        ?int $tenantId = null,
        ?string $moduleName = null,
        ?int $userId = null
    ): mixed {
        // Generate cache key
        $cacheKey = $this->getCacheKey($key, $scope, $tenantId, $moduleName, $userId);

        // Try to get from cache if enabled
        if (config('settings-management.cache.enabled', true)) {
            $cachedValue = Cache::get($cacheKey);
            if ($cachedValue !== null) {
                return $cachedValue;
            }
        }

        // Resolve setting from hierarchy
        $setting = $this->resolveHierarchy($key, $scope, $tenantId, $moduleName, $userId);

        if ($setting === null) {
            // Check for default in metadata
            $defaultValue = $this->getDefault($key);
            return $defaultValue ?? $default;
        }

        // Cast and decrypt value
        $value = $this->castValue($setting->value, $setting->type);

        // Cache the value if enabled
        if (config('settings-management.cache.enabled', true)) {
            $ttl = config('settings-management.cache.ttl', 3600);
            Cache::put($cacheKey, $value, $ttl);
        }

        return $value;
    }

    /**
     * Set a setting value
     *
     * @param string $key The setting key
     * @param mixed $value The setting value
     * @param string $type The value type
     * @param string $scope The scope level
     * @param array<string, mixed> $metadata Optional metadata
     * @param int|null $tenantId Optional tenant ID
     * @param string|null $moduleName Optional module name
     * @param int|null $userId Optional user ID
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
    ): bool {
        // Validate type
        if (!in_array($type, config('settings-management.supported_types', []))) {
            throw new \InvalidArgumentException("Unsupported setting type: {$type}");
        }

        // Validate scope
        $validScopes = [Setting::SCOPE_SYSTEM, Setting::SCOPE_TENANT, Setting::SCOPE_MODULE, Setting::SCOPE_USER];
        if (!in_array($scope, $validScopes)) {
            throw new \InvalidArgumentException("Invalid scope: {$scope}");
        }

        // Auto-inject tenant ID for non-system scopes
        if ($scope !== Setting::SCOPE_SYSTEM && $tenantId === null) {
            $tenantId = $this->getCurrentTenantId();
        }

        // Convert value to storage format
        $storedValue = $this->castToStorage($value, $type);

        DB::beginTransaction();
        try {
            // Find existing setting
            $existing = $this->repository->findByKey($key, $scope, $tenantId, $moduleName, $userId);

            if ($existing) {
                // Update existing setting
                $oldValue = $existing->value;
                $oldType = $existing->type;

                $this->repository->update($existing, [
                    'value' => $storedValue,
                    'type' => $type,
                    'metadata' => $metadata,
                ]);

                // Record history
                $this->recordHistory($existing, $oldValue, $storedValue, $oldType, $type, SettingHistory::ACTION_UPDATED);

                // Dispatch event
                event(new SettingUpdatedEvent(
                    $existing->id,
                    $key,
                    $oldValue,
                    $storedValue,
                    $scope,
                    $tenantId
                ));
            } else {
                // Create new setting
                $setting = $this->repository->create([
                    'key' => $key,
                    'value' => $storedValue,
                    'type' => $type,
                    'scope' => $scope,
                    'tenant_id' => $tenantId,
                    'module_name' => $moduleName,
                    'user_id' => $userId,
                    'metadata' => $metadata,
                ]);

                // Record history
                $this->recordHistory($setting, null, $storedValue, null, $type, SettingHistory::ACTION_CREATED);

                // Dispatch event
                event(new SettingCreatedEvent(
                    $setting->id,
                    $key,
                    $storedValue,
                    $type,
                    $scope,
                    $tenantId
                ));
            }

            DB::commit();

            // Invalidate cache
            $this->invalidateCache($key, $scope);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

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
    ): bool {
        if ($scope !== null) {
            return $this->repository->exists($key, $scope, $tenantId, $moduleName, $userId);
        }

        // Check all scopes in hierarchy
        $setting = $this->resolveHierarchy($key, $scope, $tenantId, $moduleName, $userId);
        return $setting !== null;
    }

    /**
     * Delete a setting
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
    ): bool {
        $setting = $this->repository->findByKey($key, $scope, $tenantId, $moduleName, $userId);

        if ($setting === null) {
            return false;
        }

        DB::beginTransaction();
        try {
            // Record history before deletion
            $this->recordHistory($setting, $setting->value, null, $setting->type, null, SettingHistory::ACTION_DELETED);

            // Delete setting
            $this->repository->delete($setting);

            DB::commit();

            // Invalidate cache
            $this->invalidateCache($key, $scope);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get all settings for a scope with hierarchical merging
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
    ): array {
        // Determine scopes to include based on requested scope
        $scopes = $this->getScopesForHierarchy($scope);

        // Get settings from all relevant scopes
        $settings = $this->repository->getByScopes($scopes, $tenantId, $moduleName, $userId);

        // Merge settings with priority (later scopes override earlier ones)
        $result = [];
        foreach ($settings as $setting) {
            $value = $this->castValue($setting->value, $setting->type);
            $result[$setting->key] = $value;
        }

        return $result;
    }

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
    ): int {
        $count = 0;

        foreach ($settings as $key => $value) {
            try {
                $this->set($key, $value, $type, $scope, [], $tenantId, $moduleName, $userId);
                $count++;
            } catch (\Exception $e) {
                // Log error but continue with other settings
                logger()->error("Failed to set setting {$key}: {$e->getMessage()}");
            }
        }

        return $count;
    }

    /**
     * Invalidate cache for a setting key
     *
     * When a setting is updated at any scope, we need to invalidate all cache entries
     * that might have resolved to that setting through hierarchical resolution.
     * Due to the complexity of tracking all possible cache key combinations (tenant_id,
     * module_name, user_id), we use cache tags or flush by pattern where possible.
     *
     * @param string $key The setting key
     * @param string|null $scope Optional specific scope to invalidate
     * @return void
     */
    public function invalidateCache(string $key, ?string $scope = null): void
    {
        if (!config('settings-management.cache.enabled', true)) {
            return;
        }

        // For comprehensive invalidation, we need to clear all possible cache key combinations
        // Since we cannot enumerate all tenant_id/module_name/user_id combinations efficiently,
        // we invalidate by pattern matching on the cache key prefix
        
        $prefix = config('settings-management.cache.prefix', 'settings');
        $pattern = "{$prefix}:{$key}:*";
        
        // Use cache driver's flush by pattern if available (Redis supports this)
        // For other cache drivers, we fall back to invalidating known scopes
        $cacheDriver = config('settings-management.cache.driver') ?? config('cache.default');
        
        if ($cacheDriver === 'redis') {
            // Redis: use pattern matching to clear all related keys
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                $redis->del($keys);
            }
            $invalidatedKeys = $keys;
        } else {
            // Fallback: invalidate all scope combinations without specific IDs
            $allScopes = config('settings-management.scope_hierarchy', ['user', 'module', 'tenant', 'system']);
            $invalidatedKeys = [];
            
            foreach ($allScopes as $s) {
                $cacheKey = $this->getCacheKey($key, $s);
                Cache::forget($cacheKey);
                $invalidatedKeys[] = $cacheKey;
            }
        }

        // Dispatch event
        event(new CacheInvalidatedEvent($invalidatedKeys, "Setting '{$key}' updated"));
    }

    /**
     * Warm cache for frequently accessed settings
     *
     * @param string|null $scope Optional scope to warm
     * @param int|null $tenantId Optional tenant ID
     * @return int Number of settings cached
     */
    public function warmCache(?string $scope = null, ?int $tenantId = null): int
    {
        if (!config('settings-management.cache.enabled', true)) {
            return 0;
        }

        $scopes = $scope !== null ? [$scope] : config('settings-management.scope_hierarchy', []);
        $count = 0;

        foreach ($scopes as $s) {
            $settings = $this->repository->getByScope($s, $tenantId);
            foreach ($settings as $setting) {
                $cacheKey = $this->getCacheKey($setting->key, $s, $setting->tenant_id, $setting->module_name, $setting->user_id);
                $value = $this->castValue($setting->value, $setting->type);
                $ttl = config('settings-management.cache.ttl', 3600);
                Cache::put($cacheKey, $value, $ttl);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Resolve setting from hierarchy
     *
     * @param string $key
     * @param string|null $scope
     * @param int|null $tenantId
     * @param string|null $moduleName
     * @param int|null $userId
     * @return Setting|null
     */
    protected function resolveHierarchy(
        string $key,
        ?string $scope,
        ?int $tenantId,
        ?string $moduleName,
        ?int $userId
    ): ?Setting {
        $scopes = $scope !== null ? [$scope] : $this->getScopesForHierarchy('user');

        foreach ($scopes as $s) {
            $setting = $this->repository->findByKey($key, $s, $tenantId, $moduleName, $userId);
            if ($setting !== null) {
                return $setting;
            }
        }

        return null;
    }

    /**
     * Get scopes for hierarchical resolution
     *
     * @param string $requestedScope
     * @return array<int, string>
     */
    protected function getScopesForHierarchy(string $requestedScope): array
    {
        $hierarchy = config('settings-management.scope_hierarchy', ['user', 'module', 'tenant', 'system']);
        $index = array_search($requestedScope, $hierarchy);

        if ($index === false) {
            return [$requestedScope];
        }

        // Return scopes from requested to system (reverse order for priority)
        return array_slice($hierarchy, $index);
    }

    /**
     * Cast value from storage format to PHP type
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected function castValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            Setting::TYPE_STRING => (string) $value,
            Setting::TYPE_INTEGER => (int) $value,
            Setting::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            Setting::TYPE_ARRAY, Setting::TYPE_JSON => json_decode($value, true),
            Setting::TYPE_ENCRYPTED => $this->decryptValue($value),
            default => $value,
        };
    }

    /**
     * Cast value to storage format
     *
     * @param mixed $value
     * @param string $type
     * @return string|null
     */
    protected function castToStorage(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            Setting::TYPE_STRING => (string) $value,
            Setting::TYPE_INTEGER => (string) $value,
            Setting::TYPE_BOOLEAN => $value ? '1' : '0',
            Setting::TYPE_ARRAY, Setting::TYPE_JSON => json_encode($value),
            Setting::TYPE_ENCRYPTED => $this->encryptValue($value),
            default => (string) $value,
        };
    }

    /**
     * Encrypt a value
     *
     * @param mixed $value
     * @return string
     */
    protected function encryptValue(mixed $value): string
    {
        if (!config('settings-management.encryption.enabled', true)) {
            throw new \RuntimeException('Encryption is disabled in configuration');
        }

        return Crypt::encryptString(is_string($value) ? $value : json_encode($value));
    }

    /**
     * Decrypt a value
     *
     * @param string $value
     * @return mixed
     */
    protected function decryptValue(string $value): mixed
    {
        if (!config('settings-management.encryption.enabled', true)) {
            throw new \RuntimeException('Encryption is disabled in configuration');
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            logger()->error("Failed to decrypt setting value: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Get default value from configuration
     *
     * @param string $key
     * @return mixed
     */
    protected function getDefault(string $key): mixed
    {
        $defaults = config('settings-management.defaults', []);
        
        if (isset($defaults[$key]['value'])) {
            return $defaults[$key]['value'];
        }

        return null;
    }

    /**
     * Generate cache key
     *
     * @param string $key
     * @param string|null $scope
     * @param int|null $tenantId
     * @param string|null $moduleName
     * @param int|null $userId
     * @return string
     */
    protected function getCacheKey(
        string $key,
        ?string $scope = null,
        ?int $tenantId = null,
        ?string $moduleName = null,
        ?int $userId = null
    ): string {
        $prefix = config('settings-management.cache.prefix', 'settings');
        $parts = [$prefix, $key];

        if ($scope !== null) {
            $parts[] = $scope;
        }
        if ($tenantId !== null) {
            $parts[] = "t{$tenantId}";
        }
        if ($moduleName !== null) {
            $parts[] = "m{$moduleName}";
        }
        if ($userId !== null) {
            $parts[] = "u{$userId}";
        }

        return implode(':', $parts);
    }

    /**
     * Get current tenant ID from context
     *
     * @return int|null
     */
    protected function getCurrentTenantId(): ?int
    {
        // Try to get from authenticated user
        if (auth()->check()) {
            return auth()->user()->tenant_id ?? null;
        }

        // Try to get from request context or session
        if (app()->bound('tenant')) {
            $tenant = app('tenant');
            return $tenant?->id;
        }

        return null;
    }

    /**
     * Record setting change history
     *
     * @param Setting $setting
     * @param string|null $oldValue
     * @param string|null $newValue
     * @param string|null $oldType
     * @param string|null $newType
     * @param string $action
     * @return void
     */
    protected function recordHistory(
        Setting $setting,
        ?string $oldValue,
        ?string $newValue,
        ?string $oldType,
        ?string $newType,
        string $action
    ): void {
        if (!config('settings-management.audit_logging.enabled', true)) {
            return;
        }

        $request = request();

        SettingHistory::create([
            'setting_id' => $setting->id,
            'key' => $setting->key,
            'scope' => $setting->scope,
            'tenant_id' => $setting->tenant_id,
            'module_name' => $setting->module_name,
            'user_id' => $setting->user_id,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'old_type' => $oldType,
            'new_type' => $newType,
            'action' => $action,
            'changed_by' => auth()->check() ? auth()->id() : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'changed_at' => now(),
        ]);
    }
}
