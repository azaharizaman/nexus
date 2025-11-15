# nexus-hrm Package Requirements

**Package Name:** `nexus/hrm`  
**Namespace:** `Nexus\Hrm`  
**Version:** 1.0.0  
**Status:** Design Phase  
**Created:** November 15, 2025  
**Updated:** November 15, 2025

---

## Executive Summary

Complete Human Resource Management atomic package implementing Employee Lifecycle Management, Leave & Attendance, Performance Management, Disciplinary Tracking, Training & Certifications, and HR Analytics following the **Maximum Atomicity** architectural principles.

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
Human Resource Management components are consolidated because they:
1. **Share tight domain coupling** - Leave balances depend on employee contracts, performance reviews reference disciplinary records
2. **Have constant data flow** - Employee lifecycle changes trigger leave balance recalculations, attendance affects performance reviews
3. **Cannot function independently** - Employee master data required by all HR submodules
4. **Share organizational semantics** - All use same organizational structure from nexus-backoffice
5. **Violate atomicity if separated** - Would require complex cross-package choreography for simple leave approvals

**Internal Modularity Maintained:**
- Namespace separation (`Nexus\Hrm\EmployeeManagement`, `Nexus\Hrm\LeaveManagement`)
- Bounded contexts with clear internal interfaces  
- Domain events for internal component communication
- Separate repositories and services per subdomain

---

## Architectural Compliance

### Maximum Atomicity Requirements

| Requirement | Status | Implementation Notes |
|-------------|---------|---------------------|
| **No HTTP Controllers** | ✅ Must Comply | Controllers moved to `Nexus\Erp\Actions\Hrm\*` |
| **No CLI Commands** | ✅ Must Comply | Commands converted to Actions in orchestration layer |
| **No Routes Definition** | ✅ Must Comply | Routes handled by `Nexus\Erp` service provider |
| **Independent Testability** | ✅ Must Comply | Complete test suite with Orchestra Testbench |
| **Zero Package Dependencies** | ✅ Must Comply | Communication via contracts and events only |
| **Contract-Based Integration** | ✅ Must Comply | Define interfaces for external dependencies |

### Package Dependencies

**Allowed Dependencies:**
- `laravel/framework` (framework core)
- Testing packages (`orchestra/testbench`, `pestphp/pest`)

**Forbidden Dependencies:**
- Other `nexus/*` packages (violates atomicity)
- HTTP presentation packages (`inertiajs/inertia-laravel`)
- Package-specific external services (must be abstracted)

**External Integration:**
```php
// ✅ CORRECT: Define contracts for external dependencies
interface OrganizationServiceContract {
    public function getDepartment(string $departmentId): ?Department;
}

interface WorkflowServiceContract {
    public function submitForApproval(string $type, array $data): WorkflowInstance;
}

// ✅ CORRECT: Register in Nexus\Erp orchestration layer  
$this->app->bind(OrganizationServiceContract::class, BackofficeIntegrationService::class);
$this->app->bind(WorkflowServiceContract::class, WorkflowEngineService::class);
```

---

## Functional Requirements

### 1. Employee Lifecycle Management

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-HRM-EMP-001** | Manage **employee master data** with personal information, emergency contacts, and dependents | High |
| **FR-HRM-EMP-002** | Track **employment contracts** with start date, probation period, position, and employment type | High |
| **FR-HRM-EMP-003** | Implement **employee lifecycle states** (prospect → active → probation → permanent → notice → terminated) | High |
| **FR-HRM-EMP-004** | Support **automatic org hierarchy** integration via nexus-backoffice (manager, subordinates, department queries) | High |
| **FR-HRM-EMP-005** | Track **employment history** with position changes, transfers, and promotions | Medium |
| **FR-HRM-EMP-006** | Manage **employee documents** with secure storage, version control, and expiry tracking | Medium |

### 2. Leave Management

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-HRM-LEAVE-001** | Define **leave types** (annual, sick, maternity, unpaid, custom) with entitlement rules | High |
| **FR-HRM-LEAVE-002** | Calculate **automatic leave entitlements** with pro-rata, carry-forward, and seniority-based rules | High |
| **FR-HRM-LEAVE-003** | Process **leave requests** with workflow integration for approval routing | High |
| **FR-HRM-LEAVE-004** | Track **leave balances** in real-time with YTD tracking and negative balance handling | High |
| **FR-HRM-LEAVE-005** | Support **leave adjustments** with audit trail and reason tracking | Medium |
| **FR-HRM-LEAVE-006** | Generate **leave reports** (balance summary, usage patterns, departmental analytics) | Medium |

### 3. Attendance & Time Tracking

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-HRM-ATT-001** | Record **clock-in/clock-out** events with timestamp and optional geolocation | High |
| **FR-HRM-ATT-002** | Track **break times** and **overtime hours** with automatic calculation | High |
| **FR-HRM-ATT-003** | Manage **shift schedules** with recurring patterns and shift swapping | High |
| **FR-HRM-ATT-004** | Handle **roster management** with team assignments and coverage tracking | Medium |
| **FR-HRM-ATT-005** | Support **flexible work arrangements** (remote work, flexible hours, compressed weeks) | Medium |
| **FR-HRM-ATT-006** | Generate **attendance reports** (monthly summary, absenteeism, tardiness analytics) | High |

### 4. Performance Management

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-HRM-PERF-001** | Define **review cycles** with scheduled periods (annual, bi-annual, quarterly) | High |
| **FR-HRM-PERF-002** | Support **customizable review templates** with weighted KPIs and competency frameworks | High |
| **FR-HRM-PERF-003** | Enable **360-degree feedback** with peer, manager, and self-assessment capabilities | High |
| **FR-HRM-PERF-004** | Track **goal setting and OKRs** with progress monitoring and milestone tracking | Medium |
| **FR-HRM-PERF-005** | Support **performance calibration** sessions with comparison across departments | Medium |
| **FR-HRM-PERF-006** | Generate **performance analytics** (top performers, improvement areas, distribution curves) | Medium |

### 5. Disciplinary & Grievance Management

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-HRM-DISC-001** | Track **disciplinary cases** with severity levels (verbal → written → suspension → termination) | High |
| **FR-HRM-DISC-002** | Integrate with **workflow engine** for escalation and approval routing | High |
| **FR-HRM-DISC-003** | Record **grievances** with investigation tracking and resolution status | Medium |
| **FR-HRM-DISC-004** | Generate **case reports** with timeline visualization and audit trail | Medium |
| **FR-HRM-DISC-005** | Support **document attachments** for evidence, witness statements, and resolutions | Medium |

### 6. Training & Development

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-HRM-TRN-001** | Maintain **training catalog** with course details, duration, and providers | High |
| **FR-HRM-TRN-002** | Track **training enrollments** and **completion records** with certification generation | High |
| **FR-HRM-TRN-003** | Manage **certification expiry** with automatic reminders and renewal workflows | High |
| **FR-HRM-TRN-004** | Calculate **training budgets** per employee with departmental allocation tracking | Medium |
| **FR-HRM-TRN-005** | Generate **skills matrix** showing competency levels across the organization | Medium |

### 7. Recruitment Integration (Phase 2)

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-HRM-REC-001** | Manage **job vacancies** with position requirements and approval workflow | Low |
| **FR-HRM-REC-002** | Track **candidate pipeline** with stages (screening → interview → offer → onboarding) | Low |
| **FR-HRM-REC-003** | Support **interview scheduling** with panel coordination and feedback collection | Low |
| **FR-HRM-REC-004** | Automate **offer letter generation** with template management | Low |

---

## Business Rules

| Rule ID | Description | Scope |
|---------|-------------|-------|
| **BR-HRM-001** | Employees MUST have **active contract** before leave accrual begins | Leave |
| **BR-HRM-002** | Leave requests CANNOT exceed **available balance** unless negative balance policy enabled | Leave |
| **BR-HRM-003** | **Probation completion** required before permanent leave entitlements activate | Employee, Leave |
| **BR-HRM-004** | Attendance records MUST NOT overlap for same employee (prevent duplicate clock-ins) | Attendance |
| **BR-HRM-005** | Performance reviews MUST be conducted by **employee's direct manager** or authorized delegate | Performance |
| **BR-HRM-006** | Disciplinary actions require **documented evidence** and approval workflow completion | Disciplinary |
| **BR-HRM-007** | Training certifications with expiry dates trigger **automatic reminders 30 days before expiry** | Training |
| **BR-HRM-008** | Employee termination MUST trigger **automatic leave balance calculation** and final settlement | Employee |

---

## Data Requirements

| Requirement ID | Description | Scope |
|----------------|-------------|-------|
| **DR-HRM-001** | Employee master table: personal data, emergency contacts, dependents (JSON), employment status | Employee |
| **DR-HRM-002** | Employment contracts table: contract type, start/end dates, position, probation period, work schedule | Employee |
| **DR-HRM-003** | Leave entitlements table: employee_id, leave_type_id, year, entitled_days, used_days, carried_forward | Leave |
| **DR-HRM-004** | Leave requests table: employee_id, leave_type_id, dates, status, approval_chain, workflow_instance_id | Leave |
| **DR-HRM-005** | Attendance records table: employee_id, clock_in, clock_out, break_duration, overtime, location | Attendance |
| **DR-HRM-006** | Performance reviews table: employee_id, review_cycle_id, reviewer_id, scores, comments, status | Performance |
| **DR-HRM-007** | Disciplinary cases table: employee_id, case_type, severity, status, resolution, case_handler_id | Disciplinary |
| **DR-HRM-008** | Training records table: employee_id, training_id, completion_date, certification_number, expiry_date | Training |

---

## Integration Requirements

### Internal Package Communication

| Component | Integration Method | Implementation |
|-----------|-------------------|----------------|
| **Nexus\Backoffice** | Event-driven & Contract | Listen to `EmployeeCreatedEvent` from backoffice; use `OrganizationServiceContract` for hierarchy queries |
| **Nexus\Workflow** | Service contract | Use `WorkflowServiceContract` for leave/disciplinary approvals, performance review routing |
| **Nexus\Tenancy** | Event-driven | Listen to `TenantCreatedEvent` for default leave policy setup |
| **Nexus\AuditLog** | Service contract | Use `ActivityLoggerContract` for all employee lifecycle changes |
| **Nexus\Settings** | Service contract | Use `SettingsServiceContract` for HR policies, leave rules, working hour configurations |

### API Contracts Definition

```php
// Package defines contracts for external services
interface OrganizationServiceContract {
    public function getDepartment(string $departmentId): ?Department;
    public function getManager(string $employeeId): ?Employee;
    public function getSubordinates(string $employeeId): Collection;
}

interface WorkflowServiceContract {
    public function submitForApproval(string $type, array $data): WorkflowInstance;
    public function getApprovalStatus(string $instanceId): WorkflowStatus;
}

// Orchestration layer binds implementations
class ErpServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(OrganizationServiceContract::class, BackofficeIntegrationService::class);
        $this->app->bind(WorkflowServiceContract::class, WorkflowEngineService::class);
    }
}
```

---

## Orchestration Layer Integration

### Laravel Actions Implementation

The presentation layer is handled by `Nexus\Erp\Actions\Hrm\*` following the Laravel Actions pattern:

```php
// Atomic package provides business logic
namespace Nexus\Hrm\Services;
class LeaveManagementService {
    public function submitLeaveRequest(SubmitLeaveData $data): LeaveRequest {
        // Domain logic implementation
        // - Validate balance
        // - Create request
        // - Submit to workflow
    }
}

// Orchestration layer exposes via Actions  
namespace Nexus\Erp\Actions\Hrm;
class SubmitLeaveRequestAction {
    use AsAction;
    
    public function handle(SubmitLeaveRequestRequest $request): LeaveRequest {
        return app(LeaveManagementService::class)->submitLeaveRequest(
            SubmitLeaveData::fromRequest($request)
        );
    }
    
    // Can be invoked as HTTP, CLI, Job, Event Listener
    public function asController(SubmitLeaveRequestRequest $request) { 
        return $this->handle($request); 
    }
}
```

### Event-Driven Architecture

```php
// Package publishes domain events
namespace Nexus\Hrm\Events;
class LeaveRequestSubmittedEvent {
    public function __construct(
        public LeaveRequest $leaveRequest,
        public Employee $employee
    ) {}
}

// External packages subscribe via orchestration layer
class ErpEventServiceProvider extends EventServiceProvider {
    protected $listen = [
        LeaveRequestSubmittedEvent::class => [
            NotifyManagerListener::class,
            UpdateLeaveBalanceListener::class,
        ],
    ];
}
```

---

## Performance Requirements

| Requirement ID | Description | Target |
|----------------|-------------|--------|
| **PR-HRM-001** | Employee search across 100K records | < 500ms |
| **PR-HRM-002** | Leave balance calculation with complex rules | < 200ms |
| **PR-HRM-003** | Monthly attendance report generation (1000 employees) | < 5 seconds |
| **PR-HRM-004** | Performance review data aggregation (department-level) | < 2 seconds |
| **PR-HRM-005** | Real-time leave balance check during request submission | < 100ms |

---

## Security Requirements

| Requirement ID | Description |
|----------------|-------------|
| **SR-HRM-001** | Implement audit logging for all employee data changes using `ActivityLoggerContract` |
| **SR-HRM-002** | Enforce tenant isolation for all HR data via tenant scoping |
| **SR-HRM-003** | Support authorization policies through contract-based permission system |
| **SR-HRM-004** | Encrypt sensitive employee data (personal information, salary details) at rest |
| **SR-HRM-005** | Implement field-level access control (HR managers see salary, line managers don't) |

---

## Dependencies

### Framework Dependencies
```json
{
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0"
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
| Organization Structure | `OrganizationServiceContract` | `Nexus\Erp\ErpServiceProvider` |
| Workflow Engine | `WorkflowServiceContract` | `Nexus\Erp\ErpServiceProvider` |
| Settings Management | `SettingsServiceContract` | `Nexus\Erp\ErpServiceProvider` |

---

## Implementation Notes

### Atomic Package Structure

```
packages/nexus-hrm/
├── src/
│   ├── Models/                     # Domain models
│   │   ├── Employee.php
│   │   ├── EmploymentContract.php
│   │   ├── LeaveRequest.php
│   │   ├── LeaveEntitlement.php
│   │   ├── AttendanceRecord.php
│   │   ├── PerformanceReview.php
│   │   └── ...
│   ├── Services/                   # Business logic services  
│   │   ├── EmployeeManagementService.php
│   │   ├── LeaveManagementService.php
│   │   ├── AttendanceService.php
│   │   └── ...
│   ├── Repositories/               # Data access layer
│   │   ├── EmployeeRepository.php
│   │   ├── LeaveRequestRepository.php
│   │   └── ...
│   ├── Contracts/                  # External service contracts
│   │   ├── OrganizationServiceContract.php
│   │   └── WorkflowServiceContract.php
│   ├── Events/                     # Domain events
│   │   ├── EmployeeCreatedEvent.php
│   │   ├── LeaveRequestSubmittedEvent.php
│   │   └── ...
│   ├── Data/                       # DTOs and Value Objects
│   │   ├── SubmitLeaveData.php
│   │   ├── ClockInData.php
│   │   └── ...
│   ├── Enums/                      # Enumeration types
│   │   ├── EmploymentStatus.php
│   │   ├── LeaveStatus.php
│   │   └── ...
│   └── HrmServiceProvider.php
├── database/
│   ├── migrations/                 # Database schema
│   └── factories/                  # Test data factories
├── tests/                          # Independent test suite
│   ├── Feature/
│   ├── Unit/
│   ├── TestCase.php
│   └── bootstrap.php
├── config/
│   └── hrm.php                    # Package configuration
├── composer.json                  # Package dependencies
├── phpunit.xml                   # Test configuration
└── docs/
    └── REQUIREMENTS.md (this file)
```

### Orchestration Structure

```
src/Actions/Hrm/                  # In Nexus\Erp namespace
├── Employee/
│   ├── CreateEmployeeAction.php
│   ├── UpdateEmployeeAction.php
│   └── TerminateEmployeeAction.php  
├── Leave/
│   ├── SubmitLeaveRequestAction.php
│   ├── ApproveLeaveRequestAction.php
│   └── AdjustLeaveBalanceAction.php
├── Attendance/
│   ├── ClockInAction.php
│   └── GenerateAttendanceReportAction.php
└── ...

routes/hrm.php                    # In Nexus\Erp namespace
console/hrm.php                   # Console commands via Actions
```

### Implementation Strategy

**Phase 1: Employee Foundation (Week 1)**
- Employee models and contract tracking
- Integration with nexus-backoffice for org hierarchy
- Basic CRUD operations with business rule validation
- Independent test suite setup

**Phase 2: Leave Management (Week 2)**
- Leave types, entitlements, and balance calculations
- Leave request workflow integration
- Pro-rata and carry-forward logic
- Event-driven notifications

**Phase 3: Attendance Tracking (Week 3)** 
- Clock-in/clock-out functionality
- Shift and roster management
- Break time and overtime calculations
- Geolocation support

**Phase 4: Performance Management (Week 4)**
- Review cycles and templates
- 360-degree feedback support
- Goal tracking (OKRs)
- Performance analytics

**Phase 5: Disciplinary & Training (Week 5)**
- Disciplinary case management
- Training catalog and enrollments
- Certification tracking with expiry
- Document management

**Phase 6: Orchestration Layer (Week 6)**
- Laravel Actions implementation for all business operations
- HTTP/CLI/Job/Event integration via orchestration layer
- API documentation and external service contracts

### Testing Strategy

**Atomic Package Tests:**
```bash
cd packages/nexus-hrm
composer test                     # Run independent test suite
composer test-coverage           # Generate coverage reports  
composer test-isolated          # Test without dev dependencies
```

**Integration Tests:**
- Performed at `Nexus\Erp` orchestration layer level
- Test Actions and external service integrations
- End-to-end testing in Edward demo application

### Compliance Verification

To verify Maximum Atomicity compliance:

```bash
# 1. Test package independence
cd packages/nexus-hrm && composer test

# 2. Check for architectural violations
find src -name "*Controller*" -o -name "*Command*" | wc -l  # Should be 0
grep -r "Nexus\\\\" src/ | grep -v "Nexus\\Hrm"           # Should be empty

# 3. Verify contract dependencies only
composer show --tree | grep nexus                          # Should show none
```

---

**Document Maintenance:**
- Update after each development phase completion
- Review during architectural changes and refactoring
- Sync with [System Architectural Document](../../../docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md)

**Related Documents:**
- [System Architectural Document](../../../docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md)
- [Coding Guidelines](../../../CODING_GUIDELINES.md)
- [Package Implementation Examples](../../../packages/nexus-audit-log/TESTING.md)
