<?php

namespace Nexus\Backoffice\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Nexus\Backoffice\BackofficeServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        $this->artisan('migrate');
    }

    protected function getPackageProviders($app)
    {
        return [
            BackofficeServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}