<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core;

use Azaharizaman\Erp\Core\Contracts\TenantManagerContract;
use Azaharizaman\Erp\Core\Contracts\TenantRepositoryContract;
use Azaharizaman\Erp\Core\Http\Middleware\EnsureTenantActive;
use Azaharizaman\Erp\Core\Middleware\IdentifyTenant;
use Azaharizaman\Erp\Core\Models\Tenant;
use Azaharizaman\Erp\Core\Repositories\TenantRepository;
use Azaharizaman\Erp\Core\Services\ImpersonationService;
use Azaharizaman\Erp\Core\Services\TenantManager;
use Illuminate\Support\Facades\Gate;
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
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/erp-core.php',
            'erp-core'
        );

        // Bind TenantRepository contract to implementation
        $this->app->singleton(TenantRepositoryContract::class, TenantRepository::class);

        // Bind TenantManager contract to implementation
        $this->app->singleton(TenantManagerContract::class, TenantManager::class);

        // Bind ImpersonationService
        $this->app->singleton(ImpersonationService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../config/erp-core.php' => config_path('erp-core.php'),
        ], 'erp-core-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register middleware aliases
        $router = $this->app['router'];
        $router->aliasMiddleware('tenant', IdentifyTenant::class);
        $router->aliasMiddleware('tenant.active', EnsureTenantActive::class);
        $router->aliasMiddleware('impersonation', \Azaharizaman\Erp\Core\Http\Middleware\ImpersonationMiddleware::class);

        // Define Gate for tenant impersonation
        Gate::define('impersonate-tenant', function ($user, Tenant $tenant) {
            // Allow users with 'admin' or 'support' role to impersonate
            return $user->hasRole('admin') || $user->hasRole('support');
        });

        // Define Gate for creating tenants
        Gate::define('create-tenant', function ($user) {
            return $user->hasRole('admin');
        });

        // Define Gate for updating tenants
        Gate::define('update-tenant', function ($user, Tenant $tenant) {
            return $user->hasRole('admin');
        });
    }
}
