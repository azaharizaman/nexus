<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SettingsManagement\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Settings Facade
 *
 * Provides convenient static access to the Settings service.
 *
 * @method static mixed get(string $key, mixed $default = null, ?string $scope = null, ?int $tenantId = null, ?string $moduleName = null, ?int $userId = null)
 * @method static bool set(string $key, mixed $value, string $type = 'string', string $scope = 'tenant', array $metadata = [], ?int $tenantId = null, ?string $moduleName = null, ?int $userId = null)
 * @method static bool has(string $key, ?string $scope = null, ?int $tenantId = null, ?string $moduleName = null, ?int $userId = null)
 * @method static bool forget(string $key, string $scope = 'tenant', ?int $tenantId = null, ?string $moduleName = null, ?int $userId = null)
 * @method static array all(string $scope = 'tenant', ?int $tenantId = null, ?string $moduleName = null, ?int $userId = null)
 * @method static int setMany(array $settings, string $type = 'string', string $scope = 'tenant', ?int $tenantId = null, ?string $moduleName = null, ?int $userId = null)
 * @method static void invalidateCache(string $key, ?string $scope = null)
 * @method static int warmCache(?string $scope = null, ?int $tenantId = null)
 *
 * @see \Azaharizaman\Erp\SettingsManagement\Contracts\SettingsServiceContract
 */
class Settings extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'settings';
    }
}
