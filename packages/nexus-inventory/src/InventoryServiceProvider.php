<?php

namespace Nexus\Inventory;

use Nexus\Inventory\Facades\Inventory as InventoryFacade;
use Nexus\Inventory\Services\InventoryService;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (class_exists(AliasLoader::class)) {
            AliasLoader::getInstance()->alias('Inventory', InventoryFacade::class);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/inventory-management.php' => $this->app->configPath('inventory-management.php'),
            ], 'inventory-management-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => $this->app->databasePath('migrations'),
            ], 'inventory-management-migrations');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/inventory-management.php', 'inventory-management'
        );

        $this->app->singleton(InventoryService::class, function ($app) {
            return new InventoryService($app);
        });

        $this->app->alias(InventoryService::class, 'inventory-management.service');
    }
}
