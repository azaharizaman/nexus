<?php

declare(strict_types=1);

namespace Nexus\Erp\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\StaffTransfer;
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
use Nexus\Erp\Console\Commands\Backoffice\InstallBackofficeCommand;
use Nexus\Erp\Console\Commands\Backoffice\CreateOfficeTypesCommand;
use Nexus\Erp\Console\Commands\Backoffice\ProcessResignationsCommand;
use Nexus\Erp\Console\Commands\Backoffice\ProcessStaffTransfersCommand;

/**
 * Nexus Backoffice Orchestration Service Provider
 * 
 * Handles the registration of presentation layer components for the Nexus Backoffice package.
 * This provider manages the orchestration concerns that were extracted from the atomic package
 * to maintain Maximum Atomicity compliance.
 * 
 * Responsibilities:
 * - Console command registration
 * - Model observer registration (configurable)
 * - Authorization policy registration (configurable)
 * - Orchestration layer configuration
 */
class BackofficeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register orchestration-specific configurations
        $this->registerOrchestrationConfig();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register console commands
        $this->registerCommands();

        // Conditionally register observers
        if ($this->shouldRegisterObservers()) {
            $this->registerObservers();
        }

        // Conditionally register policies  
        if ($this->shouldRegisterPolicies()) {
            $this->registerPolicies();
        }
    }

    /**
     * Register orchestration-specific configuration.
     */
    protected function registerOrchestrationConfig(): void
    {
        // Configuration is already merged by the main ErpServiceProvider
        // This method is available for future orchestration-specific config needs
    }

    /**
     * Register console commands for backoffice operations.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallBackofficeCommand::class,
                CreateOfficeTypesCommand::class,
                ProcessResignationsCommand::class,
                ProcessStaffTransfersCommand::class,
            ]);
        }
    }

    /**
     * Register model observers for backoffice entities.
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
     * Register authorization policies for backoffice entities.
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
     * Determine if observers should be registered.
     */
    protected function shouldRegisterObservers(): bool
    {
        return config('nexus.backoffice.enable_observers', true);
    }

    /**
     * Determine if policies should be registered.
     */
    protected function shouldRegisterPolicies(): bool
    {
        return config('nexus.backoffice.enable_policies', true);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            InstallBackofficeCommand::class,
            CreateOfficeTypesCommand::class,
            ProcessResignationsCommand::class,
            ProcessStaffTransfersCommand::class,
        ];
    }
}