<?php

declare(strict_types=1);

namespace Nexus\Backoffice;

use Illuminate\Support\ServiceProvider;

/**
 * Nexus Backoffice Service Provider
 * 
 * Lightweight service provider following the new architectural guidelines.
 * This package is framework-agnostic and contains only business logic contracts and services.
 * 
 * All concrete implementations (Models, Repositories, Migrations) are handled
 * by the Atomy application, not this package.
 */
class BackofficeServiceProvider extends ServiceProvider
{
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration if running in console
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/backoffice.php' => config_path('backoffice.php'),
            ], 'nexus-backoffice-config');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}