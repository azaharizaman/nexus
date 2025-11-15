<?php

declare(strict_types=1);

namespace Nexus\OrgStructure;

use Illuminate\Support\ServiceProvider;

class OrgStructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/org-structure.php', 'org-structure');
    }

    public function boot(): void
    {
        // Load package migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/org-structure.php' => config_path('org-structure.php'),
            ], 'org-structure-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'org-structure-migrations');
        }
    }
}
