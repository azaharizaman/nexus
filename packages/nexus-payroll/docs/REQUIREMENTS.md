# nexus-payroll Package Requirements

**Package Name:** `nexus/payroll`  
**Namespace:** `Nexus\Payroll`  
**Version:** 1.0.0  
**Status:** Design Phase  
**Created:** November 15, 2025  
**Updated:** November 15, 2025

---

## Executive Summary

Complete Payroll Management atomic package implementing Payroll Processing Engine, Statutory Compliance (Malaysia PCB/EPF/SOCSO/EIS), Payslip Generation, Tax Relief Management, Multi-Frequency Payroll Runs, and Retroactive Recalculation following the **Maximum Atomicity** architectural principles.

### Architectural Context

**Atomic Package Compliance:**
This package MUST adhere to Maximum Atomicity principles defined in the [System Architectural Document](../../../docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md):
- ✅ **Headless Design** - Contains only domain logic, no presentation layer
- ✅ **Independent Testability** - Complete test suite runnable with `composer test` 
- ✅ **Zero Cross-Package Dependencies** - Cannot directly depend on other Nexus packages
- ✅ **Contract-Based Communication** - External integration via interfaces and events
- ✅ **Laravel Actions Integration** - Business logic exposed via orchestration layer

**Orchestration Pattern:**
- Presentation layer (HTTP/CLI) handled by `Nexus\Erp` orchestration using Laravel Actions
- Package provides domain logic and contracts; orchestration provides API endpoints
- Integration testing performed at orchestration level, not within atomic package

**Why This Package:**
Payroll Management components are consolidated because they:
1. **Share tight domain coupling** - Tax calculations depend on earnings, EPF depends on gross salary, statutory submissions require complete payslip data
2. **Have constant data flow** - Every pay run requires tax, EPF, SOCSO calculations before payslip generation
3. **Cannot function independently** - Statutory compliance cannot exist without payroll processing engine
4. **Share regulatory semantics** - All components follow Malaysia LHDN/KWSP/SOCSO regulations
5. **Violate atomicity if separated** - Would require complex cross-package choreography for simple salary payments

**Internal Modularity Maintained:**
- Namespace separation (`Nexus\Payroll\PayrollEngine`, `Nexus\Payroll\StatutoryCompliance`)
- Bounded contexts with clear internal interfaces  
- Domain events for internal component communication
- Separate repositories and services per subdomain

---

## Architectural Compliance

### Maximum Atomicity Requirements

| Requirement | Status | Implementation Notes |
|-------------|---------|---------------------|
| **No HTTP Controllers** | ✅ Must Comply | Controllers moved to `Nexus\Erp\Actions\Payroll\*` |
| **No CLI Commands** | ✅ Must Comply | Commands converted to Actions in orchestration layer |
| **No Routes Definition** | ✅ Must Comply | Routes handled by `Nexus\Erp` service provider |
| **Independent Testability** | ✅ Must Comply | Complete test suite with Orchestra Testbench |
| **Zero Package Dependencies** | ✅ Must Comply | Communication via contracts and events only |
| **Contract-Based Integration** | ✅ Must Comply | Define interfaces for external dependencies |

### Package Dependencies

**Allowed Dependencies:**
- `laravel/framework` (framework core)
- `brick/math` (precise decimal calculations for tax/salary)
- Testing packages (`orchestra/testbench`, `pestphp/pest`)

**Forbidden Dependencies:**
- Other `nexus/*` packages (violates atomicity)
- HTTP presentation packages (`inertiajs/inertia-laravel`)
- Package-specific external services (must be abstracted)

**External Integration:**
```php
// ✅ CORRECT: Define contracts for external dependencies
interface EmployeeServiceContract {
    public function getEmployee(string $employeeId): Employee;
    public function getActiveEmployees(): Collection;
}

interface AccountingServiceContract {
    public function postJournalEntry(JournalEntryData $data): JournalEntry;
}

// ✅ CORRECT: Register in Nexus\Erp orchestration layer  
$this->app->bind(EmployeeServiceContract::class, HrmIntegrationService::class);
$this->app->bind(AccountingServiceContract::class, AccountingIntegrationService::class);
```

---

## Functional Requirements

### 1. Payroll Processing Engine

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-PAY-ENG-001** | Execute **monthly payroll runs** for all active employees with automatic component calculation | High |
| **FR-PAY-ENG-002** | Support **recurring payroll components** (fixed allowances, deductions, employer contributions) | High |
| **FR-PAY-ENG-003** | Process **variable payroll items** (overtime, claims, bonuses, commissions, unpaid leave deductions) | High |
| **FR-PAY-ENG-004** | Calculate **Year-to-Date (YTD)** tracking for all earnings, deductions, and statutory contributions | High |
| **FR-PAY-ENG-005** | Implement **pay run locking** to prevent duplicate processing and enable rollback on errors | High |
| **FR-PAY-ENG-006** | Support **multi-frequency payroll** (monthly, semi-monthly, weekly, bonus-only runs) | Medium |
| **FR-PAY-ENG-007** | Post **automatic GL journal entries** to nexus-accounting for salary expense and liabilities | High |

### 2. Statutory Compliance (Malaysia)

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-PAY-STAT-001** | Calculate **PCB (Monthly Tax Deduction)** using embedded LHDN tax tables (YA2025-2027) | High |
| **FR-PAY-STAT-002** | Support **all tax reliefs** (individual, spouse, children, parents, education, medical, lifestyle) | High |
| **FR-PAY-STAT-003** | Calculate **EPF (Employees Provident Fund)** with age-based rates, ceilings, and employer/employee share | High |
| **FR-PAY-STAT-004** | Calculate **SOCSO (Social Security)** with category-based rates and maximum contribution ceilings | High |
| **FR-PAY-STAT-005** | Calculate **EIS (Employment Insurance System)** with contribution rates and ceilings | High |
| **FR-PAY-STAT-006** | Support **HRDF (Human Resource Development Fund)** levy calculation for applicable employers | Medium |
| **FR-PAY-STAT-007** | Calculate **Zakat** deductions for Muslim employees with state-specific rates | Medium |
| **FR-PAY-STAT-008** | Handle **additional remuneration** (bonuses, commissions) with special PCB calculation rules | High |

### 3. Payslip Generation

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-PAY-SLIP-001** | Generate **PDF payslips** with customizable templates and company branding | High |
| **FR-PAY-SLIP-002** | Provide **JSON payslip data** for API consumption by headless frontends | High |
| **FR-PAY-SLIP-003** | Support **multi-language payslips** (English, Malay, Chinese) with configurable defaults | Medium |
| **FR-PAY-SLIP-004** | Include **YTD figures** for all earnings, deductions, and statutory contributions | High |
| **FR-PAY-SLIP-005** | Implement **payslip access control** with secure employee portal integration | High |
| **FR-PAY-SLIP-006** | Support **payslip regeneration** for corrections without affecting pay run integrity | Medium |

### 4. Retroactive Recalculation

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-PAY-RETRO-001** | Process **retroactive salary adjustments** with automatic tax recalculation for past months | High |
| **FR-PAY-RETRO-002** | Generate **CP159 tax adjustment notices** when retroactive changes affect previous PCB calculations | High |
| **FR-PAY-RETRO-003** | Support **EPF/SOCSO retroactive contributions** with arrears calculation | Medium |
| **FR-PAY-RETRO-004** | Track **retroactive payment history** with audit trail and reason documentation | High |
| **FR-PAY-RETRO-005** | Generate **adjustment payslips** clearly showing original vs adjusted amounts | High |

### 5. Tax Relief Management

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-PAY-TAX-001** | Allow employees to **declare tax reliefs** (PCB TP1 form data) for accurate monthly deductions | High |
| **FR-PAY-TAX-002** | Support **zakat rebate claims** with automatic PCB adjustment calculations | Medium |
| **FR-PAY-TAX-003** | Validate **relief eligibility** based on LHDN rules (e.g., parent age, disability status) | High |
| **FR-PAY-TAX-004** | Track **relief changes throughout the year** with effective date handling | Medium |
| **FR-PAY-TAX-005** | Generate **relief summary reports** for year-end EA Form preparation | High |

### 6. Statutory Reporting

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-PAY-REP-001** | Generate **EA Form (Annual Tax Statement)** in LHDN-compliant format | High |
| **FR-PAY-REP-002** | Generate **E Form (Employer Declaration)** with summary of all EA Forms | High |
| **FR-PAY-REP-003** | Generate **CP39 (Employer's Return)** for quarterly tax remittance reconciliation | High |
| **FR-PAY-REP-004** | Generate **CP8D (Tax Deduction Certificate)** for additional remuneration | Medium |
| **FR-PAY-REP-005** | Generate **Borang TP1/TP3** for new employee tax declarations | Medium |
| **FR-PAY-REP-006** | Support **EPF/SOCSO submission files** (i-Akaun, ASSIST portal formats) | High |

### 7. Multi-Currency & FWL Support

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-PAY-CURR-001** | Support **multi-currency salary payments** with automatic exchange rate conversion | Medium |
| **FR-PAY-CURR-002** | Calculate **Foreign Worker Levy (FWL)** for expatriate employees | Low |
| **FR-PAY-CURR-003** | Handle **currency conversion for statutory contributions** (EPF/SOCSO in MYR regardless of salary currency) | Medium |

---

## Business Rules

| Rule ID | Description | Scope |
|---------|-------------|-------|
| **BR-PAY-001** | PCB calculations MUST use **exact LHDN rounding rules** (to nearest sen) to match tax department calculations | PCB |
| **BR-PAY-002** | Pay runs MUST be **locked after completion** to prevent accidental modifications | Engine |
| **BR-PAY-003** | Only **locked pay runs** can generate payslips and post to accounting | Engine |
| **BR-PAY-004** | Retroactive recalculations MUST **recalculate all months from change date forward** | Retro |
| **BR-PAY-005** | EPF contributions CANNOT exceed **statutory ceiling** (RM5,000 salary base as of 2025) | EPF |
| **BR-PAY-006** | SOCSO eligibility ends at **age 60** for new contributors (existing contributors continue) | SOCSO |
| **BR-PAY-007** | EIS contributions required for employees earning **below RM4,000 per month** | EIS |
| **BR-PAY-008** | Payslip data MUST be **immutable** once generated (regeneration creates new record) | Payslip |
| **BR-PAY-009** | Additional remuneration MUST use **special PCB calculation tables** from LHDN | PCB |

---

## Data Requirements

| Requirement ID | Description | Scope |
|----------------|-------------|-------|
| **DR-PAY-001** | Payroll runs table: period, frequency, status, locked_at, total_gross, total_net, run_by_user_id | Engine |
| **DR-PAY-002** | Payroll items table: run_id, employee_id, item_type (earning/deduction/contribution), amount, ytd_amount | Engine |
| **DR-PAY-003** | Statutory rates table: year, type (PCB/EPF/SOCSO/EIS), rate_brackets, ceilings, effective_date | Statutory |
| **DR-PAY-004** | Employee tax reliefs table: employee_id, year, relief_type, amount, supporting_documents | Tax Relief |
| **DR-PAY-005** | Payslips table: run_id, employee_id, gross_pay, net_pay, pdf_path, hash, generated_at | Payslip |
| **DR-PAY-006** | Statutory submissions table: type (EA/CP39), year/month, status, file_path, submitted_at | Reporting |
| **DR-PAY-007** | Retroactive adjustments table: employee_id, adjustment_type, effective_date, reason, calculated_difference | Retro |

---

## Integration Requirements

### Internal Package Communication

| Component | Integration Method | Implementation |
|-----------|-------------------|----------------|
| **Nexus\Hrm** | Service contract | Use `EmployeeServiceContract` to fetch active employees, attendance data, leave deductions |
| **Nexus\Accounting** | Service contract | Use `AccountingServiceContract` to post salary expense and liability journal entries |
| **Nexus\Workflow** | Service contract | Use `WorkflowServiceContract` for payroll approval routing (if required) |
| **Nexus\Tenancy** | Event-driven | Listen to `TenantCreatedEvent` for default payroll policy setup |
| **Nexus\AuditLog** | Service contract | Use `ActivityLoggerContract` for all payroll processing changes |

### API Contracts Definition

```php
// Package defines contracts for external services
interface EmployeeServiceContract {
    public function getEmployee(string $employeeId): Employee;
    public function getActiveEmployees(?string $departmentId = null): Collection;
    public function getAttendanceHours(string $employeeId, Carbon $startDate, Carbon $endDate): float;
    public function getLeaveDeductions(string $employeeId, Carbon $startDate, Carbon $endDate): float;
}

interface AccountingServiceContract {
    public function postJournalEntry(JournalEntryData $data): JournalEntry;
    public function getChartOfAccounts(): Collection;
}

// Orchestration layer binds implementations
class ErpServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(EmployeeServiceContract::class, HrmIntegrationService::class);
        $this->app->bind(AccountingServiceContract::class, AccountingIntegrationService::class);
    }
}
```

---

## Orchestration Layer Integration

### Laravel Actions Implementation

The presentation layer is handled by `Nexus\Erp\Actions\Payroll\*` following the Laravel Actions pattern:

```php
// Atomic package provides business logic
namespace Nexus\Payroll\Services;
class PayrollProcessingService {
    public function runMonthlyPayroll(RunPayrollData $data): PayrollRun {
        // Domain logic implementation
        // - Fetch employees
        // - Calculate all components
        // - Apply statutory calculations
        // - Lock pay run
    }
}

// Orchestration layer exposes via Actions  
namespace Nexus\Erp\Actions\Payroll;
class RunMonthlyPayrollAction {
    use AsAction;
    
    public function handle(RunPayrollRequest $request): PayrollRun {
        return app(PayrollProcessingService::class)->runMonthlyPayroll(
            RunPayrollData::fromRequest($request)
        );
    }
    
    // Can be invoked as HTTP, CLI, Job, Event Listener
    public function asController(RunPayrollRequest $request) { 
        return $this->handle($request); 
    }
}
```

### Event-Driven Architecture

```php
// Package publishes domain events
namespace Nexus\Payroll\Events;
class PayrollRunCompletedEvent {
    public function __construct(
        public PayrollRun $payrollRun,
        public int $employeeCount,
        public float $totalGross,
        public float $totalNet
    ) {}
}

// External packages subscribe via orchestration layer
class ErpEventServiceProvider extends EventServiceProvider {
    protected $listen = [
        PayrollRunCompletedEvent::class => [
            PostToAccountingListener::class,
            GeneratePayslipsListener::class,
            NotifyHrManagerListener::class,
        ],
    ];
}
```

---

## Performance Requirements

| Requirement ID | Description | Target |
|----------------|-------------|--------|
| **PR-PAY-001** | Process monthly payroll for 5,000 employees | < 15 seconds |
| **PR-PAY-002** | Generate single payslip PDF | < 2 seconds |
| **PR-PAY-003** | Retroactive recalculation (12 months, 1,000 employees) | < 30 seconds |
| **PR-PAY-004** | PCB calculation with all reliefs | < 50ms per employee |
| **PR-PAY-005** | EA Form generation for 5,000 employees | < 10 seconds |

---

## Security Requirements

| Requirement ID | Description |
|----------------|-------------|
| **SR-PAY-001** | Encrypt all payroll data (salary, tax reliefs) at rest using Laravel encryption |
| **SR-PAY-002** | Implement immutable payslip records with cryptographic hash verification |
| **SR-PAY-003** | Enforce role-based access control for payroll processing operations |
| **SR-PAY-004** | Audit all payroll changes using `ActivityLoggerContract` |
| **SR-PAY-005** | Support tenant isolation via automatic scoping |
| **SR-PAY-006** | Implement secure payslip access with employee-level authorization |

---

## Dependencies

### Framework Dependencies
```json
{
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0",
        "brick/math": "^0.12"
    },
    "require-dev": {
        "orchestra/testbench": "^10.0",
        "pestphp/pest": "^2.0",
        "pestphp/pest-plugin-laravel": "^2.0"
    }
}
```

### Service Contracts
External dependencies MUST be abstracted behind contracts:

| Service | Contract Interface | Bound In Orchestration |
|---------|-------------------|----------------------|
| Activity Logging | `ActivityLoggerContract` | `Nexus\Erp\ErpServiceProvider` |
| Employee Data | `EmployeeServiceContract` | `Nexus\Erp\ErpServiceProvider` |
| Accounting Integration | `AccountingServiceContract` | `Nexus\Erp\ErpServiceProvider` |
| Workflow Engine | `WorkflowServiceContract` | `Nexus\Erp\ErpServiceProvider` |

---

## Implementation Notes

### Atomic Package Structure

```
packages/nexus-payroll/
├── src/
│   ├── Models/                     # Domain models
│   │   ├── PayrollRun.php
│   │   ├── PayrollItem.php
│   │   ├── Payslip.php
│   │   ├── StatutoryRate.php
│   │   ├── TaxRelief.php
│   │   └── ...
│   ├── Services/                   # Business logic services  
│   │   ├── PayrollProcessingService.php
│   │   ├── StatutoryCalculationService.php
│   │   ├── PayslipGenerationService.php
│   │   ├── RetroactiveCalculationService.php
│   │   └── ...
│   ├── Calculators/               # Specialized calculation engines
│   │   ├── PcbCalculator.php
│   │   ├── EpfCalculator.php
│   │   ├── SocsoCalculator.php
│   │   └── ...
│   ├── Repositories/               # Data access layer
│   │   ├── PayrollRunRepository.php
│   │   ├── PayslipRepository.php
│   │   └── ...
│   ├── Contracts/                  # External service contracts
│   │   ├── EmployeeServiceContract.php
│   │   └── AccountingServiceContract.php
│   ├── Events/                     # Domain events
│   │   ├── PayrollRunCompletedEvent.php
│   │   ├── PayslipGeneratedEvent.php
│   │   └── ...
│   ├── Data/                       # DTOs and Value Objects
│   │   ├── RunPayrollData.php
│   │   ├── PayslipData.php
│   │   ├── JournalEntryData.php
│   │   └── ...
│   ├── Enums/                      # Enumeration types
│   │   ├── PayrollRunStatus.php
│   │   ├── PayrollItemType.php
│   │   ├── StatutoryType.php
│   │   └── ...
│   └── PayrollServiceProvider.php
├── database/
│   ├── migrations/                 # Database schema
│   ├── factories/                  # Test data factories
│   └── seeders/                    # LHDN tax tables seeder
├── tests/                          # Independent test suite
│   ├── Feature/
│   ├── Unit/
│   ├── TestCase.php
│   └── bootstrap.php
├── config/
│   └── payroll.php                # Package configuration
├── composer.json                  # Package dependencies
├── phpunit.xml                   # Test configuration
└── docs/
    └── REQUIREMENTS.md (this file)
```

### Orchestration Structure

```
src/Actions/Payroll/              # In Nexus\Erp namespace
├── Processing/
│   ├── RunMonthlyPayrollAction.php
│   ├── LockPayrollRunAction.php
│   └── RollbackPayrollRunAction.php  
├── Statutory/
│   ├── UpdateTaxRatesAction.php
│   ├── GenerateEAFormAction.php
│   └── GenerateCP39Action.php
├── Payslip/
│   ├── GeneratePayslipAction.php
│   └── RegeneratePayslipAction.php
└── ...

routes/payroll.php                # In Nexus\Erp namespace
console/payroll.php               # Console commands via Actions
```

### Implementation Strategy

**Phase 1: Core Engine (Week 1)**
- Payroll run models and basic processing logic
- Recurring and variable component handling
- Pay run locking and rollback mechanism
- Independent test suite setup

**Phase 2: Statutory Calculations (Weeks 2-3)**
- PCB calculator with LHDN tax tables (YA2025-2027)
- EPF, SOCSO, EIS calculators with rate tables
- Tax relief management
- Additional remuneration handling

**Phase 3: Payslip Generation (Week 4)** 
- PDF generation with customizable templates
- JSON API for headless consumption
- Multi-language support
- YTD tracking and display

**Phase 4: Accounting Integration (Week 5)**
- GL journal entry posting
- Salary expense and liability accounts
- Event-driven architecture
- Transaction rollback support

**Phase 5: Retroactive & Reporting (Week 6)**
- Retroactive recalculation engine
- EA Form, CP39, E Form generation
- EPF/SOCSO submission files
- Audit trail and compliance reports

**Phase 6: Orchestration Layer (Week 7)**
- Laravel Actions implementation for all business operations
- HTTP/CLI/Job/Event integration via orchestration layer
- API documentation and external service contracts

### Testing Strategy

**Atomic Package Tests:**
```bash
cd packages/nexus-payroll
composer test                     # Run independent test suite
composer test-coverage           # Generate coverage reports  
composer test-isolated          # Test without dev dependencies
```

**Key Test Coverage:**
- PCB calculation accuracy (test against LHDN examples)
- EPF/SOCSO/EIS calculation verification
- Retroactive recalculation correctness
- Payslip generation integrity
- Pay run locking and rollback

**Integration Tests:**
- Performed at `Nexus\Erp` orchestration layer level
- Test Actions and external service integrations
- End-to-end payroll processing workflow
- Accounting integration verification

### Compliance Verification

To verify Maximum Atomicity compliance:

```bash
# 1. Test package independence
cd packages/nexus-payroll && composer test

# 2. Check for architectural violations
find src -name "*Controller*" -o -name "*Command*" | wc -l  # Should be 0
grep -r "Nexus\\\\" src/ | grep -v "Nexus\\Payroll"       # Should be empty

# 3. Verify contract dependencies only
composer show --tree | grep nexus                          # Should show none
```

---

**Document Maintenance:**
- Update after each development phase completion
- Update tax tables annually (LHDN changes)
- Review during architectural changes and refactoring
- Sync with [System Architectural Document](../../../docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md)

**Related Documents:**
- [System Architectural Document](../../../docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md)
- [Coding Guidelines](../../../CODING_GUIDELINES.md)
- [Package Implementation Examples](../../../packages/nexus-audit-log/TESTING.md)
- [LHDN PCB Calculation Guide](https://www.hasil.gov.my)
