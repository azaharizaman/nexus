<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Core\Contracts\TenantManagerContract;
use App\Domains\Core\Contracts\TenantRepositoryContract;
use App\Domains\Core\Repositories\TenantRepository;
use App\Domains\Core\Services\TenantManager;
use Illuminate\Support\ServiceProvider;

/**
 * Core Service Provider
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
        // Bind TenantRepository contract to implementation
        $this->app->singleton(TenantRepositoryContract::class, TenantRepository::class);

        // Bind TenantManager contract to implementation
        $this->app->singleton(TenantManagerContract::class, TenantManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register policies
        \Illuminate\Support\Facades\Gate::policy(
            \App\Domains\Core\Models\Tenant::class,
            \App\Domains\Core\Policies\TenantPolicy::class
        );

        // Define gate for tenant impersonation
        // Only users with 'super-admin' role or specific permission can impersonate
        \Illuminate\Support\Facades\Gate::define('impersonate-tenant', function ($user, $tenant) {
            // Check if user has the impersonate-tenant permission
            // This allows integration with spatie/laravel-permission or similar
            return method_exists($user, 'can') && $user->can('impersonate-tenants');
        });
    }
}
