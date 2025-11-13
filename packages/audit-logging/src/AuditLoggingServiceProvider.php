<?php

declare(strict_types=1);

namespace Nexus\Erp\AuditLogging;

use Nexus\Erp\AuditLogging\Commands\PurgeExpiredLogsCommand;
use Nexus\Erp\AuditLogging\Contracts\AuditLogRepositoryContract;
use Nexus\Erp\AuditLogging\Contracts\LogExporterContract;
use Nexus\Erp\AuditLogging\Contracts\LogFormatterContract;
use Nexus\Erp\AuditLogging\Events\ActivityLoggedEvent;
use Nexus\Erp\AuditLogging\Listeners\NotifyHighValueActivityListener;
use Nexus\Erp\AuditLogging\Policies\AuditLogPolicy;
use Nexus\Erp\AuditLogging\Repositories\DatabaseAuditLogRepository;
use Nexus\Erp\AuditLogging\Services\LogExporterService;
use Nexus\Erp\AuditLogging\Services\LogFormatterService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

/**
 * Audit Logging Service Provider
 *
 * Registers the audit logging package services, bindings, and components.
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

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                PurgeExpiredLogsCommand::class,
            ]);
        }

        // Register event listeners
        $this->registerEventListeners();

        // Register policies
        $this->registerPolicies();
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

    /**
     * Register authorization policies
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Activity::class, AuditLogPolicy::class);
    }
}
