<?php

declare(strict_types=1);

namespace Nexus\Hrm\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Nexus\Hrm\HrmServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Eloquent connection resolver is set
        \Illuminate\Database\Eloquent\Model::setConnectionResolver($this->app['db']);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            HrmServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Use in-memory SQLite for tests
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // HRM config defaults (can be overridden per-test)
        $app['config']->set('hrm.leave.enable_negative_balance', false);
        $app['config']->set('hrm.leave.max_negative_balance_days', 0);
        $app['config']->set('hrm.leave.auto_approve_threshold_days', 0);
        $app['config']->set('hrm.leave.require_workflow_approval', true);
    }
}
