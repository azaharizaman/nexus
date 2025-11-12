<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SettingsManagement;

use Azaharizaman\Erp\SettingsManagement\Console\Commands\WarmSettingsCacheCommand;
use Azaharizaman\Erp\SettingsManagement\Contracts\SettingsRepositoryContract;
use Azaharizaman\Erp\SettingsManagement\Contracts\SettingsServiceContract;
use Azaharizaman\Erp\SettingsManagement\Models\Setting;
use Azaharizaman\Erp\SettingsManagement\Policies\SettingPolicy;
use Azaharizaman\Erp\SettingsManagement\Repositories\DatabaseSettingsRepository;
use Azaharizaman\Erp\SettingsManagement\Services\SettingsService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Settings Management Service Provider
 *
 * Registers and bootstraps the settings management package.
 */
class SettingsManagementServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/settings-management.php',
            'settings-management'
        );

        // Bind repository contract to implementation
        $this->app->singleton(
            SettingsRepositoryContract::class,
            DatabaseSettingsRepository::class
        );

        // Bind service contract to implementation
        $this->app->singleton(
            SettingsServiceContract::class,
            SettingsService::class
        );

        // Bind facade accessor
        $this->app->singleton('settings', function ($app) {
            return $app->make(SettingsServiceContract::class);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../config/settings-management.php' => config_path('settings-management.php'),
        ], 'settings-management-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                WarmSettingsCacheCommand::class,
            ]);
        }

        // Register policies
        Gate::policy(Setting::class, SettingPolicy::class);

        // Define additional Gates for permissions
        $this->defineGates();
    }

    /**
     * Define authorization gates.
     *
     * @return void
     */
    protected function defineGates(): void
    {
        // Export settings gate
        Gate::define('export-settings', function ($user) {
            return $user->hasRole('admin') || $user->hasRole('super-admin');
        });

        // Import settings gate
        Gate::define('import-settings', function ($user) {
            return $user->hasRole('admin') || $user->hasRole('super-admin');
        });

        // View encrypted settings gate
        Gate::define('view-encrypted-settings', function ($user) {
            return $user->hasRole('admin') || $user->hasRole('super-admin');
        });

        // Manage system settings gate
        Gate::define('manage-system-settings', function ($user) {
            return $user->hasRole('super-admin');
        });
    }
}
