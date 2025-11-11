<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core;

use Azaharizaman\Erp\Core\Contracts\TenantManagerContract;
use Azaharizaman\Erp\Core\Contracts\TenantRepositoryContract;
use Azaharizaman\Erp\Core\Repositories\TenantRepository;
use Azaharizaman\Erp\Core\Services\TenantManager;
use Illuminate\Support\ServiceProvider;

/**
 * Core Service Provider for ERP Core Package
 *
 * Registers core domain services and bindings for the multi-tenancy
 * infrastructure and foundational ERP functionality.
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Bind TenantRepository contract to implementation
        $this->app->singleton(TenantRepositoryContract::class, TenantRepository::class);

        // Bind TenantManager contract to implementation
        $this->app->singleton(TenantManagerContract::class, TenantManager::class);
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
            __DIR__.'/../config/erp-core.php' => config_path('erp-core.php'),
        ], 'erp-core-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        // Load routes if needed
        // $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
