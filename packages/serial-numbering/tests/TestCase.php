<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base Test Case for Serial Numbering Package
 *
 * Provides common setup for package tests.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        // Use base_path() for flexible path resolution
        $app = require base_path('apps/headless-erp-app/bootstrap/app.php');

        return $app;
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Run migrations
        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }
}
