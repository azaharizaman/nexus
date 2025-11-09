<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Core\Contracts\TenantManagerContract;
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
        // Bind TenantManager contract to implementation
        $this->app->singleton(TenantManagerContract::class, TenantManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
