<?php

declare(strict_types=1);

namespace Nexus\Backoffice;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\StaffTransfer;
use Nexus\Backoffice\Models\Unit;
use Nexus\Backoffice\Models\UnitGroup;
use Nexus\Backoffice\Models\OfficeType;
use Nexus\Backoffice\Observers\CompanyObserver;
use Nexus\Backoffice\Observers\OfficeObserver;
use Nexus\Backoffice\Observers\DepartmentObserver;
use Nexus\Backoffice\Observers\StaffObserver;
use Nexus\Backoffice\Observers\StaffTransferObserver;
use Nexus\Backoffice\Policies\CompanyPolicy;
use Nexus\Backoffice\Policies\OfficePolicy;
use Nexus\Backoffice\Policies\DepartmentPolicy;
use Nexus\Backoffice\Policies\StaffPolicy;
use Nexus\Backoffice\Policies\StaffTransferPolicy;
use Nexus\Backoffice\Commands\InstallBackOfficeCommand;
use Nexus\Backoffice\Commands\CreateOfficeTypesCommand;
use Nexus\Backoffice\Commands\ProcessResignationsCommand;
use Nexus\Backoffice\Commands\ProcessStaffTransfersCommand;

/**
 * BackOffice Service Provider
 * 
 * Registers all package components including models, observers, policies,
 * commands, and configuration.
 */
class BackofficeServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     */
    public array $bindings = [];

    /**
     * All of the container singletons that should be registered.
     */
    public array $singletons = [];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/backoffice.php',
            'backoffice'
        );

        // Register services
        $this->registerServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register migrations
        $this->registerMigrations();

        // Register configuration
        $this->registerConfiguration();

        // Register commands
        $this->registerCommands();

        // Register observers
        $this->registerObservers();

        // Register policies
        $this->registerPolicies();

        // Register publishables
        $this->registerPublishables();
    }

    /**
     * Register package services.
     */
    protected function registerServices(): void
    {
        // Register any package services here
    }

    /**
     * Register package migrations.
     */
    protected function registerMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    /**
     * Register package configuration.
     */
    protected function registerConfiguration(): void
    {
        // Configuration is already registered in the register method
    }

    /**
     * Register package commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallBackOfficeCommand::class,
                CreateOfficeTypesCommand::class,
                ProcessResignationsCommand::class,
                ProcessStaffTransfersCommand::class,
            ]);
        }
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        Company::observe(CompanyObserver::class);
        Office::observe(OfficeObserver::class);
        Department::observe(DepartmentObserver::class);
        Staff::observe(StaffObserver::class);
        StaffTransfer::observe(StaffTransferObserver::class);
    }

    /**
     * Register authorization policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Office::class, OfficePolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Staff::class, StaffPolicy::class);
        Gate::policy(StaffTransfer::class, StaffTransferPolicy::class);
    }

    /**
     * Register publishable assets.
     */
    protected function registerPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/backoffice.php' => config_path('backoffice.php'),
            ], 'backoffice-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'backoffice-migrations');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            // Add any services that this provider provides
        ];
    }
}