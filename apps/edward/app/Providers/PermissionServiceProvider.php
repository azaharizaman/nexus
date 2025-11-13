<?php

declare(strict_types=1);

namespace Nexus\Erp\Providers;

use Nexus\Erp\Support\Contracts\PermissionServiceContract;
use Nexus\Erp\Support\Services\Permission\SpatiePermissionService;
use Illuminate\Support\ServiceProvider;

/**
 * Permission Service Provider
 *
 * Binds the PermissionServiceContract to the appropriate implementation.
 * By default, uses SpatiePermissionService (wraps spatie/laravel-permission).
 */
class PermissionServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->app->singleton(PermissionServiceContract::class, function ($app) {
            // Could switch based on config in the future
            // $driver = config('packages.permission_service', 'spatie');

            return new SpatiePermissionService();
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}
