# Nexus Backoffice Package Refactoring Plan

## Refactoring Overview

This document outlines the comprehensive refactoring plan for the `nexus-backoffice` package to achieve full compliance with the Nexus ERP System Architecture Document's **Maximum Atomicity** principles.

## Current State Analysis

### Package Structure Assessment

| Component | Status | Compliance | Action Required |
| :---- | :---- | :---- | :---- |
| **Models** | ✅ Good | Compliant | Keep in package |
| **Traits** | ✅ Good | Compliant | Keep in package |
| **Enums** | ✅ Good | Compliant | Keep in package |
| **Helpers** | ✅ Good | Compliant | Keep in package |
| **Observers** | ⚠️ Problematic | Non-Compliant | Extract registration |
| **Policies** | ⚠️ Problematic | Non-Compliant | Extract registration |
| **Commands** | ❌ Critical | Non-Compliant | Move to orchestration |
| **Service Provider** | ⚠️ Needs Refactor | Partially Compliant | Simplify scope |
| **Testing** | ✅ Good | Compliant | Already independent |
| **Configuration** | ⚠️ Needs Review | Mostly Compliant | Remove route config |

### Critical Violations Identified

#### 1. Console Commands (Critical Priority)
**Location**: `src/Commands/`
- `InstallBackOfficeCommand.php` - Installation workflow
- `CreateOfficeTypesCommand.php` - Data seeding command  
- `ProcessResignationsCommand.php` - Business process automation
- `ProcessStaffTransfersCommand.php` - Transfer processing

**Violation**: Console commands are presentation layer concerns and should not exist in atomic packages.

#### 2. Observer Auto-Registration (High Priority)
**Location**: `BackofficeServiceProvider::registerObservers()`
```php
protected function registerObservers(): void
{
    Company::observe(CompanyObserver::class);
    Office::observe(OfficeObserver::class);
    Department::observe(DepartmentObserver::class);
    Staff::observe(StaffObserver::class);
    StaffTransfer::observe(StaffTransferObserver::class);
}
```
**Violation**: Auto-registering observers forces side effects on consuming applications.

#### 3. Policy Auto-Registration (High Priority)
**Location**: `BackofficeServiceProvider::registerPolicies()`
```php
protected function registerPolicies(): void
{
    Gate::policy(Company::class, CompanyPolicy::class);
    Gate::policy(Office::class, OfficePolicy::class);
    Gate::policy(Department::class, DepartmentPolicy::class);
    Gate::policy(Staff::class, StaffPolicy::class);
    Gate::policy(StaffTransfer::class, StaffTransferPolicy::class);
}
```
**Violation**: Authorization policies are application-level concerns.

#### 4. Configuration Issues (Medium Priority)
**Location**: `config/backoffice.php`
- Contains route configuration (lines 35-41)
- Model class mappings with wrong namespace references (line 24)

## Detailed Refactoring Plan

### Phase 1: Extract Presentation Layer (Critical - Week 1)

#### 1.1 Move Console Commands to Orchestration Layer

**Target Structure:**
```
src/
├── Console/
│   └── Commands/
│       └── Backoffice/
│           ├── InstallBackofficeCommand.php
│           ├── CreateOfficeTypesCommand.php
│           ├── ProcessResignationsCommand.php
│           └── ProcessStaffTransfersCommand.php
```

**Implementation Steps:**
1. Create `src/Console/Commands/Backoffice/` directory in main package
2. Move each command file with namespace updates:
   - From: `Nexus\Backoffice\Commands\*`
   - To: `Nexus\Erp\Console\Commands\Backoffice\*`
3. Update service provider to use orchestration commands
4. Update command signatures and descriptions
5. Remove command registration from backoffice service provider

**Code Changes Required:**

```php
// FROM: packages/nexus-backoffice/src/Commands/InstallBackOfficeCommand.php
namespace Nexus\Backoffice\Commands;

// TO: src/Console/Commands/Backoffice/InstallBackofficeCommand.php  
namespace Nexus\Erp\Console\Commands\Backoffice;
```

#### 1.2 Refactor Service Provider

**Current Issues:**
- Auto-registers observers (forced side effects)
- Auto-registers policies (authorization concerns)
- Registers console commands (presentation layer)

**Target Service Provider Scope:**
```php
<?php

declare(strict_types=1);

namespace Nexus\Backoffice;

use Illuminate\Support\ServiceProvider;

class BackofficeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/backoffice.php',
            'backoffice'
        );
    }

    public function boot(): void
    {
        $this->registerMigrations();
        $this->registerPublishables();
        
        // Remove: Observer registration
        // Remove: Policy registration  
        // Remove: Command registration
    }

    protected function registerMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    protected function registerPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/backoffice.php' => config_path('backoffice.php'),
            ], 'nexus-backoffice-config');

            // Publish migrations  
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'nexus-backoffice-migrations');
        }
    }
}
```

### Phase 2: Create Action Orchestration Layer (High Priority - Week 2)

#### 2.1 Required Laravel Actions

Create the following actions in `src/Actions/Backoffice/`:

**Company Management Actions:**
```php
namespace Nexus\Erp\Actions\Backoffice;

class CreateCompanyAction extends Action
{
    public function handle(array $companyData): Company
    {
        // Orchestrate company creation with validation
        return Company::create($companyData);
    }
}

class UpdateCompanyHierarchyAction extends Action 
{
    public function handle(Company $company, ?int $parentId): Company
    {
        // Handle hierarchy changes with validation
        return $company->update(['parent_company_id' => $parentId]);
    }
}

class GenerateOrganizationalChartAction extends Action
{
    public function handle(Company $company, array $options = []): array
    {
        // Use package helper for chart generation
        return OrganizationalChart::forCompany($company);
    }
}
```

**Staff Management Actions:**
```php
class CreateStaffAction extends Action
{
    public function handle(array $staffData): Staff
    {
        // Orchestrate staff creation with office/department assignment
    }
}

class TransferStaffAction extends Action
{
    public function handle(Staff $staff, array $transferData): StaffTransfer
    {
        // Use StaffTransferHelper for business logic
        return StaffTransferHelper::createTransfer($staff, $transferData);
    }
}

class ProcessStaffTransfersAction extends Action
{
    public function handle(?Company $company = null): array
    {
        // Process pending transfers (from command logic)
    }
}

class ProcessResignationsAction extends Action
{
    public function handle(?Company $company = null): array  
    {
        // Process staff resignations (from command logic)
    }
}
```

**Export and Reporting Actions:**
```php
class ExportOrganizationalDataAction extends Action
{
    public function handle(Company $company, string $format = 'json'): string
    {
        // Use OrganizationalChart helper for exports
        return OrganizationalChart::export($company, $format);
    }
}

class GenerateTransferStatisticsAction extends Action
{
    public function handle(Company $company): array
    {
        // Use StaffTransferHelper for statistics
        return StaffTransferHelper::getTransferStatistics($company);
    }
}
```

#### 2.2 Orchestration Service Provider

Create `src/Providers/BackofficeServiceProvider.php` in main package:

```php
<?php

namespace Nexus\Erp\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Nexus\Backoffice\Models\{Company, Office, Department, Staff, StaffTransfer};
use Nexus\Backoffice\Observers\{CompanyObserver, OfficeObserver, DepartmentObserver, StaffObserver, StaffTransferObserver};
use Nexus\Backoffice\Policies\{CompanyPolicy, OfficePolicy, DepartmentPolicy, StaffPolicy, StaffTransferPolicy};

class BackofficeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Optional observer registration
        if (config('nexus.backoffice.enable_observers', true)) {
            $this->registerObservers();
        }
        
        // Optional policy registration
        if (config('nexus.backoffice.enable_policies', true)) {
            $this->registerPolicies();
        }
        
        // Register console commands
        $this->registerCommands();
    }
    
    protected function registerObservers(): void
    {
        Company::observe(CompanyObserver::class);
        Office::observe(OfficeObserver::class);
        Department::observe(DepartmentObserver::class);
        Staff::observe(StaffObserver::class);
        StaffTransfer::observe(StaffTransferObserver::class);
    }
    
    protected function registerPolicies(): void
    {
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Office::class, OfficePolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Staff::class, StaffPolicy::class);
        Gate::policy(StaffTransfer::class, StaffTransferPolicy::class);
    }
    
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Nexus\Erp\Console\Commands\Backoffice\InstallBackofficeCommand::class,
                \Nexus\Erp\Console\Commands\Backoffice\CreateOfficeTypesCommand::class,
                \Nexus\Erp\Console\Commands\Backoffice\ProcessResignationsCommand::class,
                \Nexus\Erp\Console\Commands\Backoffice\ProcessStaffTransfersCommand::class,
            ]);
        }
    }
}
```

### Phase 3: Contract Abstraction (Medium Priority - Week 3)

#### 3.1 Define External Contracts

Create contracts for external dependencies:

```php
// packages/nexus-backoffice/src/Contracts/UserProviderContract.php
namespace Nexus\Backoffice\Contracts;

interface UserProviderContract
{
    public function findUser(int $userId): ?object;
    public function getUserRole(int $userId): ?string;
    public function getUserPermissions(int $userId): array;
    public function canUserAccessCompany(int $userId, int $companyId): bool;
}

// packages/nexus-backoffice/src/Contracts/NotificationContract.php
interface NotificationContract  
{
    public function notifyStaffTransfer(StaffTransfer $transfer): void;
    public function notifyResignation(Staff $staff): void;
    public function notifyOrganizationalChange(array $changes): void;
}

// packages/nexus-backoffice/src/Contracts/AuditContract.php
interface AuditContract
{
    public function logStaffChange(Staff $staff, array $changes): void;
    public function logTransfer(StaffTransfer $transfer): void;
    public function logCompanyChange(Company $company, array $changes): void;
}
```

#### 3.2 Update Policy Classes

Modify policies to use contracts instead of direct Auth access:

```php
<?php

namespace Nexus\Backoffice\Policies;

use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Contracts\UserProviderContract;

class StaffPolicy
{
    public function __construct(
        protected UserProviderContract $userProvider
    ) {}

    public function viewAny(object $user): bool
    {
        return $this->userProvider->canUserAccessCompany(
            $user->id, 
            $user->current_company_id ?? 0
        );
    }

    public function view(object $user, Staff $staff): bool
    {
        return $this->userProvider->canUserAccessCompany(
            $user->id, 
            $staff->company_id
        );
    }
}
```

### Phase 4: Configuration Cleanup (Low Priority - Week 4)

#### 4.1 Clean Package Configuration

Remove presentation layer concerns from config:

```php
<?php

// packages/nexus-backoffice/config/backoffice.php
return [
    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    */
    'table_prefix' => 'backoffice_',

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    */
    'models' => [
        'company' => \Nexus\Backoffice\Models\Company::class,
        'office' => \Nexus\Backoffice\Models\Office::class,
        'office_type' => \Nexus\Backoffice\Models\OfficeType::class,
        'department' => \Nexus\Backoffice\Models\Department::class,
        'staff' => \Nexus\Backoffice\Models\Staff::class,
        'position' => \Nexus\Backoffice\Models\Position::class,
        'unit' => \Nexus\Backoffice\Models\Unit::class,
        'unit_group' => \Nexus\Backoffice\Models\UnitGroup::class,
        'staff_transfer' => \Nexus\Backoffice\Models\StaffTransfer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Rules
    |--------------------------------------------------------------------------
    */
    'business_rules' => [
        'max_hierarchy_depth' => 10,
        'allow_lateral_transfers' => true,
        'require_transfer_approval' => true,
        'auto_process_transfers' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'company_code_required' => true,
        'staff_code_pattern' => '/^[A-Z]{2}\d{6}$/',
        'max_reporting_levels' => 8,
    ],
    
    // Remove: routes configuration
    // Remove: middleware configuration
];
```

## File Movement Mapping

### Files to Move to Orchestration Layer

| Current Location | New Location | Type |
| :---- | :---- | :---- |
| `packages/nexus-backoffice/src/Commands/InstallBackOfficeCommand.php` | `src/Console/Commands/Backoffice/InstallBackofficeCommand.php` | Console Command |
| `packages/nexus-backoffice/src/Commands/CreateOfficeTypesCommand.php` | `src/Console/Commands/Backoffice/CreateOfficeTypesCommand.php` | Console Command |
| `packages/nexus-backoffice/src/Commands/ProcessResignationsCommand.php` | `src/Console/Commands/Backoffice/ProcessResignationsCommand.php` | Console Command |
| `packages/nexus-backoffice/src/Commands/ProcessStaffTransfersCommand.php` | `src/Console/Commands/Backoffice/ProcessStaffTransfersCommand.php` | Console Command |

### Files to Keep in Package

| Location | Reason |
| :---- | :---- |
| `src/Models/*` | Core business entities |
| `src/Traits/*` | Business logic behaviors |
| `src/Enums/*` | Business value objects |
| `src/Helpers/*` | Business logic utilities |
| `src/Exceptions/*` | Domain exceptions |
| `src/Observers/*` | Business event handlers (registration moves) |
| `src/Policies/*` | Authorization logic (registration moves) |
| `database/migrations/*` | Schema definitions |
| `database/factories/*` | Test data factories |

## Breaking Changes Impact Analysis

### For Package Consumers

#### Command Usage Changes
```bash
# Before refactoring
php artisan backoffice:install

# After refactoring  
php artisan nexus:backoffice:install
```

#### Programmatic Changes
```php
// Before: Direct observer usage (auto-registered)
// Models automatically trigger observers

// After: Optional observer registration
// In config/nexus.php:
'backoffice' => [
    'enable_observers' => true,  // Default: true
    'enable_policies' => true,   // Default: true
],

// Before: Direct policy access (auto-registered)
Gate::allows('create', Staff::class)

// After: Same syntax (if policies enabled)
Gate::allows('create', Staff::class)
```

#### Action-Based Integration
```php
// Before: Direct package access
use Nexus\Backoffice\Helpers\OrganizationalChart;
$chart = OrganizationalChart::forCompany($company);

// After: Action-based orchestration
use Nexus\Erp\Actions\Backoffice\GenerateOrganizationalChartAction;
$chart = GenerateOrganizationalChartAction::make()->handle($company);

// OR via helper facade:
use Nexus\Erp\Facades\Backoffice;
$chart = Backoffice::generateChart($company);
```

## Validation and Testing Strategy

### Compliance Verification Commands

```bash
# 1. Verify package independence
cd packages/nexus-backoffice
composer install --no-dev
vendor/bin/phpunit

# 2. Check for presentation layer violations
grep -r "Controller\|Route::\|Middleware\|Auth::\|request(" src/

# 3. Verify no console commands in package
find src/ -name "*Command.php" | wc -l  # Should be 0

# 4. Test action orchestration
cd ../..  # Back to project root
php artisan test --filter=BackofficeAction

# 5. Verify observer/policy registration is optional
php artisan tinker
# Test: config(['nexus.backoffice.enable_observers' => false])
# Verify: No observers auto-registered
```

### Test Coverage Requirements

- [ ] All existing tests pass after refactoring
- [ ] New action tests cover all command functionality  
- [ ] Contract abstraction tests with mocked dependencies
- [ ] Breaking change integration tests
- [ ] Edward demo application integration tests

## Migration Guide for Consumers

### Step 1: Update Configuration
```php
// Add to config/nexus.php
'backoffice' => [
    'enable_observers' => true,
    'enable_policies' => true,
],
```

### Step 2: Update Command References
```bash
# Update scripts and automation
sed -i 's/backoffice:/nexus:backoffice:/g' scripts/*.sh
```

### Step 3: Update Code Integration
```php
// Replace direct helper usage with actions
// See "Action-Based Integration" examples above
```

### Step 4: Test Integration
```bash
composer update
php artisan migrate
php artisan test
```

## Success Metrics

### Architecture Compliance
- [ ] Zero console commands in atomic package
- [ ] Zero auto-registered observers/policies  
- [ ] 100% independent test coverage
- [ ] All presentation logic in orchestration layer

### Functional Preservation
- [ ] All existing functionality preserved
- [ ] Performance maintained or improved
- [ ] Edward demo application works unchanged (via orchestration)
- [ ] Backward compatibility maintained where possible

### Code Quality
- [ ] PHPStan level 8 compliance
- [ ] 100% test coverage maintained
- [ ] Documentation updated
- [ ] Migration guide complete

## Timeline and Resources

| Phase | Duration | Developer Days | Dependencies |
| :---- | :---- | :---- | :---- |
| Phase 1: Extract Presentation | 1 Week | 5 days | None |
| Phase 2: Action Orchestration | 1 Week | 5 days | Phase 1 complete |
| Phase 3: Contract Abstraction | 1 Week | 3 days | Phase 2 complete |
| Phase 4: Configuration Cleanup | 3 Days | 2 days | Phase 3 complete |
| **Total** | **3.5 Weeks** | **15 days** | Sequential |

## Risk Assessment and Mitigation

### High Risk Areas
1. **Command Migration**: Console commands contain complex business logic
   - **Mitigation**: Extract business logic to services/helpers first
   
2. **Observer Dependencies**: Other code may depend on auto-registration
   - **Mitigation**: Default enable observers, gradual opt-out approach
   
3. **Policy Integration**: Authorization may break in consuming apps
   - **Mitigation**: Maintain backward compatibility with default enabled

### Medium Risk Areas  
1. **Contract Implementation**: New abstractions may introduce bugs
   - **Mitigation**: Comprehensive test coverage with mocked contracts
   
2. **Configuration Changes**: Apps may depend on current config structure
   - **Mitigation**: Maintain old keys with deprecation warnings

## Post-Refactoring Benefits

### Architectural Benefits
- ✅ Full Maximum Atomicity compliance
- ✅ Clear separation of concerns  
- ✅ Independent package testability
- ✅ Reduced coupling between layers

### Development Benefits
- ✅ Actions provide clear integration points
- ✅ Contracts enable better testing
- ✅ Observers/policies become optional features
- ✅ Package can be used in any Laravel app

### Maintenance Benefits
- ✅ Clearer boundaries reduce maintenance complexity
- ✅ Independent testing speeds development
- ✅ Action pattern enables better monitoring/logging
- ✅ Contract abstractions improve flexibility
