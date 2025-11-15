<?php

declare(strict_types=1);

namespace Nexus\Manufacturing;

use Illuminate\Support\ServiceProvider;

class ManufacturingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/manufacturing.php',
            'manufacturing'
        );

        // Register repositories
        $this->registerRepositories();

        // Register services
        $this->registerServices();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/manufacturing.php' => config_path('manufacturing.php'),
        ], 'manufacturing-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Register repository bindings.
     */
    protected function registerRepositories(): void
    {
        $this->app->bind(
            \Nexus\Manufacturing\Contracts\BillOfMaterialRepositoryContract::class,
            \Nexus\Manufacturing\Repositories\BillOfMaterialRepository::class
        );

        $this->app->bind(
            \Nexus\Manufacturing\Contracts\WorkOrderRepositoryContract::class,
            \Nexus\Manufacturing\Repositories\WorkOrderRepository::class
        );

        $this->app->bind(
            \Nexus\Manufacturing\Contracts\ProductionReportRepositoryContract::class,
            \Nexus\Manufacturing\Repositories\ProductionReportRepository::class
        );

        $this->app->bind(
            \Nexus\Manufacturing\Contracts\QualityInspectionRepositoryContract::class,
            \Nexus\Manufacturing\Repositories\QualityInspectionRepository::class
        );
    }

    /**
     * Register service bindings.
     */
    protected function registerServices(): void
    {
        $this->app->singleton(
            \Nexus\Manufacturing\Contracts\BOMExplosionServiceContract::class,
            \Nexus\Manufacturing\Services\BOMExplosionService::class
        );

        $this->app->singleton(
            \Nexus\Manufacturing\Contracts\WorkOrderPlanningServiceContract::class,
            \Nexus\Manufacturing\Services\WorkOrderPlanningService::class
        );

        $this->app->singleton(
            \Nexus\Manufacturing\Contracts\ProductionExecutionServiceContract::class,
            \Nexus\Manufacturing\Services\ProductionExecutionService::class
        );

        $this->app->singleton(
            \Nexus\Manufacturing\Contracts\MaterialManagementServiceContract::class,
            \Nexus\Manufacturing\Services\MaterialManagementService::class
        );

        $this->app->singleton(
            \Nexus\Manufacturing\Contracts\QualityManagementServiceContract::class,
            \Nexus\Manufacturing\Services\QualityManagementService::class
        );

        $this->app->singleton(
            \Nexus\Manufacturing\Contracts\ProductionCostingServiceContract::class,
            \Nexus\Manufacturing\Services\ProductionCostingService::class
        );

        $this->app->singleton(
            \Nexus\Manufacturing\Contracts\TraceabilityServiceContract::class,
            \Nexus\Manufacturing\Services\TraceabilityService::class
        );
    }
}
