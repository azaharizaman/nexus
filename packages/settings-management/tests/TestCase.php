<?php

declare(strict_types=1);

namespace Nexus\Erp\SettingsManagement\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base Test Case for Settings Management Package
 *
 * Provides common setup for package tests.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        // Use base_path() for flexible path resolution
        return require base_path('apps/headless-erp-app/bootstrap/app.php');
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        // Load core package migrations (for tenants, users tables)
        $this->loadMigrationsFrom(base_path('packages/core/database/migrations'));

        // Additional setup if needed
    }
}
