<?php

declare(strict_types=1);

namespace Nexus\Contracts\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Contracts Service Provider
 *
 * This service provider registers the core contract interfaces.
 * Actual implementations are bound in their respective packages.
 *
 * @package Nexus\Contracts
 */
class ContractsServiceProvider extends ServiceProvider
{
    /**
     * Register services
     *
     * @return void
     */
    public function register(): void
    {
        // Contract interfaces are registered here
        // Implementations are bound in their respective packages
    }

    /**
     * Bootstrap services
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
