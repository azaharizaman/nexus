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
use Nexus\AuditLog\Contracts\AuditLogRepositoryContract;
use Nexus\Erp\Support\Services\Logging\SpatieActivityLoggerAdapter;
use Nexus\Erp\Support\Services\Permission\SpatiePermissionService;
use Nexus\Erp\Support\Services\Search\ScoutSearchService;
use Nexus\Hrm\Contracts\OrganizationServiceContract as HrmOrganizationServiceContract;
use Nexus\OrgStructure\Contracts\OrganizationServiceContract as OrgOrganizationServiceContract;
use Nexus\OrgStructure\Services\DefaultOrganizationService;
use Nexus\Erp\Support\Adapters\OrgStructure\OrganizationServiceAdapter;

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
        // Merge Nexus ERP configuration FIRST
        $this->mergeConfigFrom(
            __DIR__.'/../config/nexus.php',
            'nexus'
        );

        // Register service contracts
        $this->registerContracts();

        // Register atomic package orchestration providers
        $this->registerOrchestrationProviders();

        // Merge package configuration (if exists)
        if (file_exists(__DIR__.'/../apps/edward/config/app.php')) {
            $this->mergeConfigFrom(
                __DIR__.'/../apps/edward/config/app.php',
                'nexus-erp'
            );
        }
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

        // Load Nexus ERP routes
        $this->loadRoutesFrom(__DIR__.'/../routes/audit-log.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/console.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/api-backoffice.php');

        // Load routes (if api.php exists in Edward)
        if (file_exists(__DIR__.'/../apps/edward/routes/api.php')) {
            $this->loadRoutesFrom(__DIR__.'/../apps/edward/routes/api.php');
        }

        // Load migrations (if migrations directory exists in Edward)
        if (is_dir(__DIR__.'/../apps/edward/database/migrations')) {
            $this->loadMigrationsFrom(__DIR__.'/../apps/edward/database/migrations');
        }

        // Publish configuration (optional, commented for CLI-only apps)
        // if ($this->app->runningInConsole()) {
        //     $this->publishes([
        //         __DIR__.'/../apps/edward/config/app.php' => config_path('nexus-erp.php'),
        //     ], 'nexus-erp-config');
        //
        //     $this->publishes([
        //         __DIR__.'/../apps/edward/database/migrations' => database_path('migrations'),
        //     ], 'nexus-erp-migrations');
        // }
    }

    /**
     * Register service contracts
     *
     * @return void
     */
    protected function registerContracts(): void
    {
        // Register Spatie Activity Logger Adapter
        $this->app->singleton(SpatieActivityLoggerAdapter::class, function ($app) {
            return new SpatieActivityLoggerAdapter(
                $app->make(AuditLogRepositoryContract::class)
            );
        });

        // Activity Logger
        $this->app->singleton(ActivityLoggerContract::class, function ($app) {
            return new SpatieActivityLogger(
                $app->make(AuditLogRepositoryContract::class),
                $app->make(SpatieActivityLoggerAdapter::class)
            );
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

        // Organization Structure Service binding for HRM contract via adapter
        if (class_exists(DefaultOrganizationService::class) && interface_exists(HrmOrganizationServiceContract::class)) {
            // Bind underlying org service if not already
            $this->app->singleton(OrgOrganizationServiceContract::class, DefaultOrganizationService::class);

            // Bind HRM-facing contract to adapter
            $this->app->singleton(HrmOrganizationServiceContract::class, function ($app) {
                return new OrganizationServiceAdapter($app->make(OrgOrganizationServiceContract::class));
            });
        }
    }

    /**
     * Register atomic package orchestration providers.
     *
     * @return void
     */
    protected function registerOrchestrationProviders(): void
    {
        // Check if auto-registration is enabled
        if (!config('nexus.packages.auto_register', true)) {
            return;
        }

        // Register orchestration providers for atomic packages
        $providers = [];

        // Backoffice orchestration provider
        if (config('nexus.packages.enabled.backoffice', true)) {
            $providers[] = \Nexus\Erp\Providers\BackofficeServiceProvider::class;
        }

        // Register all orchestration providers
        foreach ($providers as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }
}
