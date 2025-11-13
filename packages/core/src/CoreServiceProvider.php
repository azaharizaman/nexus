<?php

declare(strict_types=1);

namespace Nexus\Erp\Core;

use Nexus\Erp\Core\Contracts\TenantManagerContract;
use Nexus\Erp\Core\Contracts\TenantRepositoryContract;
use Nexus\Erp\Core\Http\Middleware\EnsureTenantActive;
use Nexus\Erp\Core\Middleware\IdentifyTenant;
use Nexus\Erp\Core\Models\Tenant;
use Nexus\Erp\Core\Repositories\TenantRepository;
use Nexus\Erp\Core\Services\ImpersonationService;
use Nexus\Erp\Core\Services\TenantManager;
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

        // Define Gates for tenant management
        Gate::define('view-tenants', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('view-tenant', function ($user, Tenant $tenant) {
            return $user->hasRole('admin');
        });

        Gate::define('create-tenant', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('update-tenant', function ($user, Tenant $tenant) {
            return $user->hasRole('admin');
        });

        Gate::define('suspend-tenant', function ($user, Tenant $tenant) {
            return $user->hasRole('admin');
        });

        Gate::define('activate-tenant', function ($user, Tenant $tenant) {
            return $user->hasRole('admin');
        });

        Gate::define('archive-tenant', function ($user, Tenant $tenant) {
            return $user->hasRole('admin');
        });

        Gate::define('delete-tenant', function ($user, Tenant $tenant) {
            return $user->hasRole('admin');
        });

        // Define Gate for tenant impersonation
        Gate::define('impersonate-tenant', function ($user, Tenant $tenant) {
            // Allow users with 'admin' or 'support' role to impersonate
            return $user->hasRole('admin') || $user->hasRole('support');
        });
    }
}
