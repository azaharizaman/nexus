<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\AuditLogging\Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base Test Case for Audit Logging Package
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
        // This would be configured based on the Laravel application setup
        // For now, we're creating a minimal structure for documentation
        return require __DIR__.'/../../../../../apps/headless-erp-app/bootstrap/app.php';
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Additional setup if needed
    }
}
