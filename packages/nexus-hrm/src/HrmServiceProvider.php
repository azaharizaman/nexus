<?php

declare(strict_types=1);

namespace Nexus\Hrm;

use Illuminate\Support\ServiceProvider;

/**
 * HRM Service Provider
 * 
 * Registers HRM package services, repositories, and event listeners.
 * Follows Maximum Atomicity principles - NO HTTP controllers, routes, or commands.
 * Those belong in Nexus\Erp orchestration layer.
 */
class HrmServiceProvider extends ServiceProvider
{
    /**
     * Register package services and bindings
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/hrm.php',
            'hrm'
        );

        // Register service contracts - implementations bound in orchestration layer
        // Examples:
        // $this->app->bind(OrganizationServiceContract::class, ...); // Bound by Nexus\Erp
        // $this->app->bind(WorkflowServiceContract::class, ...);     // Bound by Nexus\Erp
    }

    /**
     * Bootstrap package services
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Publish configuration (optional for end users)
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/hrm.php' => config_path('hrm.php'),
            ], 'hrm-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'hrm-migrations');
        }

        // Register event listeners (if any domain events need internal handling)
        // Most event handling done in Nexus\Erp orchestration layer
    }

    /**
     * Get the services provided by the provider
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            // List services this provider makes available
            // Used for deferred loading optimization
        ];
    }
}
