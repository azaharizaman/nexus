<?php

declare(strict_types=1);

namespace Nexus\AuditLog\Tests;

use Nexus\AuditLog\AuditLoggingServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Base Test Case for Audit Logging Package
 *
 * Provides isolated test environment using Orchestra Testbench.
 * This enables the atomic package to be tested independently
 * without external dependencies or applications.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Configure factory namespace for model factories
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Nexus\\AuditLog\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            AuditLoggingServiceProvider::class,
        ];
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations(): void
    {
        // Load package migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        // Setup the database configuration
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup audit logging configuration
        $app['config']->set('audit-logging.storage_driver', 'database');
        $app['config']->set('audit-logging.retention_days', 30);
        $app['config']->set('audit-logging.notify_high_value_events', false);
    }
}
}
