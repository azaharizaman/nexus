<?php

declare(strict_types=1);

namespace Nexus\AuditLog;

use Nexus\AuditLog\Contracts\AuditLogRepositoryContract;
use Nexus\AuditLog\Contracts\LogExporterContract;
use Nexus\AuditLog\Contracts\LogFormatterContract;
use Nexus\AuditLog\Events\ActivityLoggedEvent;
use Nexus\AuditLog\Listeners\NotifyHighValueActivityListener;
use Nexus\AuditLog\Repositories\DatabaseAuditLogRepository;
use Nexus\AuditLog\Services\LogExporterService;
use Nexus\AuditLog\Services\LogFormatterService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Audit Logging Service Provider
 *
 * Registers the atomic audit logging package services and bindings.
 * 
 * NOTE: This is an ATOMIC package - contains only domain logic, no presentation layer.
 * HTTP controllers, commands, routes, and policies are handled by Nexus\Erp orchestration.
 */
class AuditLoggingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/audit-logging.php',
            'audit-logging'
        );

        // Bind contracts to implementations
        $this->app->bind(
            LogFormatterContract::class,
            LogFormatterService::class
        );

        $this->app->bind(
            LogExporterContract::class,
            LogExporterService::class
        );

        // Bind repository based on configured storage driver
        $this->app->bind(AuditLogRepositoryContract::class, function ($app) {
            $driver = config('audit-logging.storage_driver', 'database');

            return match ($driver) {
                'database' => $app->make(DatabaseAuditLogRepository::class),
                // 'mongodb' => $app->make(MongoAuditLogRepository::class), // Future implementation
                default => $app->make(DatabaseAuditLogRepository::class),
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/audit-logging.php' => config_path('audit-logging.php'),
        ], 'audit-logging-config');

        // Load and publish migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'audit-logging-migrations');

        // Register event listeners
        $this->registerEventListeners();
    }

    /**
     * Register event listeners
     */
    protected function registerEventListeners(): void
    {
        // Listen to ActivityLoggedEvent for high-value entity notifications
        if (config('audit-logging.notify_high_value_events', false)) {
            Event::listen(
                ActivityLoggedEvent::class,
                NotifyHighValueActivityListener::class
            );
        }

        // Additional event listeners can be registered here
    }
}
