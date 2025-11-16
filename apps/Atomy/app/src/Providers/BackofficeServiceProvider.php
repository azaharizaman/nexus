<?php

declare(strict_types=1);

namespace Nexus\Atomy\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

// Models
use App\Models\Company;
use App\Models\Office;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Models\Position;
use App\Models\OfficeType;
use App\Models\StaffTransfer;

// Observers
use App\Observers\CompanyObserver;
use App\Observers\OfficeObserver;
use App\Observers\DepartmentObserver;
use App\Observers\StaffObserver;
use App\Observers\StaffTransferObserver;

// Policies
use App\Policies\CompanyPolicy;
use App\Policies\OfficePolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\StaffPolicy;
use App\Policies\StaffTransferPolicy;

// Package Contracts
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Nexus\Backoffice\Contracts\OfficeRepositoryInterface;
use Nexus\Backoffice\Contracts\DepartmentRepositoryInterface;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;
use Nexus\Backoffice\Contracts\UnitRepositoryInterface;
use Nexus\Backoffice\Contracts\UnitGroupRepositoryInterface;
use Nexus\Backoffice\Contracts\PositionRepositoryInterface;
use Nexus\Backoffice\Contracts\OfficeTypeRepositoryInterface;
use Nexus\Backoffice\Contracts\StaffTransferRepositoryInterface;

// Repository Implementations
use App\Repositories\Backoffice\CompanyRepository;
use App\Repositories\Backoffice\OfficeRepository;
use App\Repositories\Backoffice\DepartmentRepository;
use App\Repositories\Backoffice\StaffRepository;
use App\Repositories\Backoffice\UnitRepository;
use App\Repositories\Backoffice\UnitGroupRepository;
use App\Repositories\Backoffice\PositionRepository;
use App\Repositories\Backoffice\OfficeTypeRepository;
use App\Repositories\Backoffice\StaffTransferRepository;

/**
 * Backoffice Service Provider for Atomy
 * 
 * Implements the contracts from the Nexus\Backoffice package.
 * Binds repository implementations, registers observers, and policies.
 */
class BackofficeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind repository interfaces to concrete implementations
        $this->registerRepositories();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        if ($this->shouldRegisterObservers()) {
            $this->registerObservers();
        }

        // Register authorization policies
        if ($this->shouldRegisterPolicies()) {
            $this->registerPolicies();
        }
    }

    /**
     * Register repository bindings.
     */
    protected function registerRepositories(): void
    {
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->bind(OfficeRepositoryInterface::class, OfficeRepository::class);
        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
        $this->app->bind(StaffRepositoryInterface::class, StaffRepository::class);
        $this->app->bind(UnitRepositoryInterface::class, UnitRepository::class);
        $this->app->bind(UnitGroupRepositoryInterface::class, UnitGroupRepository::class);
        $this->app->bind(PositionRepositoryInterface::class, PositionRepository::class);
        $this->app->bind(OfficeTypeRepositoryInterface::class, OfficeTypeRepository::class);
        $this->app->bind(StaffTransferRepositoryInterface::class, StaffTransferRepository::class);
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
     * Determine if observers should be registered.
     */
    protected function shouldRegisterObservers(): bool
    {
        return config('backoffice.enable_observers', true);
    }

    /**
     * Determine if policies should be registered.
     */
    protected function shouldRegisterPolicies(): bool
    {
        return config('backoffice.enable_policies', true);
    }
}
