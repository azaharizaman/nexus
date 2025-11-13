<?php

declare(strict_types=1);

namespace Nexus\Erp;

use Illuminate\Support\ServiceProvider;
use Nexus\Erp\Support\Contracts\ActivityLoggerContract;
use Nexus\Erp\Support\Contracts\PermissionServiceContract;
use Nexus\Erp\Support\Contracts\SearchServiceContract;
use Nexus\Erp\Support\Contracts\TokenServiceContract;
use Nexus\Erp\Support\Services\Auth\SanctumTokenService;
use Nexus\Erp\Support\Services\Logging\SpatieActivityLogger;
use Nexus\Erp\Support\Services\Permission\SpatiePermissionService;
use Nexus\Erp\Support\Services\Search\ScoutSearchService;

/**
 * ERP Service Provider
 *
 * Main service provider for the Nexus ERP package.
 * Registers all core ERP services, middleware, and components.
 *
 * @package Nexus\Erp
 */
class ErpServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register service contracts
        $this->registerContracts();

        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../apps/headless-erp-app/config/app.php',
            'nexus-erp'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Load helper functions
        require_once __DIR__.'/Support/Helpers/tenant.php';

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../apps/headless-erp-app/routes/api.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../apps/headless-erp-app/database/migrations');

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../apps/headless-erp-app/config/app.php' => config_path('nexus-erp.php'),
            ], 'nexus-erp-config');

            $this->publishes([
                __DIR__.'/../apps/headless-erp-app/database/migrations' => database_path('migrations'),
            ], 'nexus-erp-migrations');
        }
    }

    /**
     * Register service contracts
     *
     * @return void
     */
    protected function registerContracts(): void
    {
        // Activity Logger
        $this->app->singleton(ActivityLoggerContract::class, function ($app) {
            return new SpatieActivityLogger();
        });

        // Search Service
        $this->app->singleton(SearchServiceContract::class, function ($app) {
            return new ScoutSearchService();
        });

        // Token Service
        $this->app->singleton(TokenServiceContract::class, function ($app) {
            return new SanctumTokenService();
        });

        // Permission Service
        $this->app->singleton(PermissionServiceContract::class, function ($app) {
            return new SpatiePermissionService();
        });
    }
}
