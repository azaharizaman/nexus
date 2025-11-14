<?php

declare(strict_types=1);

namespace Nexus\Backoffice;

use Illuminate\Support\ServiceProvider;

/**
 * Nexus Backoffice Service Provider
 * 
 * Registers core package components following Maximum Atomicity principles.
 * 
 * This service provider is focused only on atomic package concerns:
 * - Configuration merging
 * - Migration registration  
 * - Asset publishing
 * 
 * Presentation layer concerns (commands, observers, policies) are handled
 * by the orchestration layer in the main Nexus ERP package.
 */
class BackofficeServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     */
    public array $bindings = [];

    /**
     * All of the container singletons that should be registered.
     */
    public array $singletons = [];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/backoffice.php',
            'backoffice'
        );

        // Register services
        $this->registerServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register migrations
        $this->registerMigrations();

        // Register configuration
        $this->registerConfiguration();

        // Register publishables
        $this->registerPublishables();

        // NOTE: Observer, policy, and command registration moved to orchestration layer
        // This maintains Maximum Atomicity compliance by removing presentation concerns
    }

    /**
     * Register package services.
     */
    protected function registerServices(): void
    {
        // Register any package services here
    }

    /**
     * Register package migrations.
     */
    protected function registerMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    /**
     * Register package configuration.
     */
    protected function registerConfiguration(): void
    {
        // Configuration is already registered in the register method
    }

    /**
     * Register publishable assets.
     */
    protected function registerPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/backoffice.php' => config_path('backoffice.php'),
            ], 'nexus-backoffice-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'nexus-backoffice-migrations');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            // Add any services that this provider provides
        ];
    }
}