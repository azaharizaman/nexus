# PRD01-SUB13: Human Capital Management (HCM)

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Optional Feature Modules - Human Resources  
**Related Sub-PRDs:** SUB15 (Backoffice), SUB02 (Authentication)  
**Composer Package:** `azaharizaman/erp-hcm`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Human Capital Management (HCM) module provides comprehensive employee lifecycle management, from hire to retirement, including organizational hierarchy, time and attendance tracking, leave management, and employee self-service capabilities.

### Purpose

This module solves the challenge of managing employee master data, organizational structure, and HR-related workflows in a multi-tenant environment. It serves as the foundation for payroll processing, performance management, and compliance reporting.

### Scope

**Included:**
- Employee master data management
- Organizational hierarchy and reporting relationships
- Employee lifecycle workflows (hire, transfer, promotion, termination)
- Employment history tracking
- Document management with expiry tracking
- Time and attendance integration
- Leave management system
- Benefits administration
- Employee self-service portal
- GDPR compliance for personal data

**Excluded:**
- Full payroll processing (handled by separate Payroll module)
- Performance appraisals and KPI tracking (future module)
- Recruitment and applicant tracking (future module)
- Training and development management (future module)

### Dependencies

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for employee data
- **SUB02 (Authentication & Authorization)** - Employee user accounts and access control
- **SUB03 (Audit Logging)** - Track all employee data changes
- **SUB15 (Backoffice)** - Organizational structure (departments, cost centers)

**Optional Dependencies:**
- **SUB22 (Notifications)** - Document expiry alerts, leave request notifications

### Composer Package Information

- **Package Name:** `azaharizaman/erp-hcm`
- **Namespace:** `Nexus\Erp\Hcm`
- **Monorepo Location:** `/packages/hcm/`
- **Installation:** `composer require azaharizaman/erp-hcm` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB13 (HCM). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-HCM-001** | Maintain **employee master data** including personal, job, and payroll information | High | Planned |
| **FR-HCM-002** | Support **organizational hierarchy** with reporting relationships and department structure | High | Planned |
| **FR-HCM-003** | Manage **employee lifecycle** (hire, transfer, promotion, termination) with workflow | High | Planned |
| **FR-HCM-004** | Track **employment history** including position changes, salary adjustments, and transfers | High | Planned |
| **FR-HCM-005** | Support **multiple employment types** (full-time, part-time, contract, intern) | High | Planned |
| **FR-HCM-006** | Manage **employee documents** (contracts, certifications, IDs) with expiry tracking | Medium | Planned |
| **FR-HCM-007** | Track **time and attendance** with integration to payroll | Medium | Planned |
| **FR-HCM-008** | Support **leave management** (annual, sick, unpaid) with balance tracking | Medium | Planned |
| **FR-HCM-009** | Manage **employee benefits** (health insurance, retirement, allowances) | Medium | Planned |
| **FR-HCM-010** | Provide **employee self-service portal** for data updates and leave requests | Low | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-HCM-001** | Disallow **deletion of employee records** with existing payroll or leave history | Planned |
| **BR-HCM-002** | Employee IDs must be **unique within tenant** | Planned |
| **BR-HCM-003** | Terminated employees cannot have **active leave or payroll** processing | Planned |
| **BR-HCM-004** | Manager cannot approve their **own leave requests** | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-HCM-001** | Store **sensitive personal data** (SSN, ID numbers) with AES-256 encryption | Planned |
| **DR-HCM-002** | Maintain **complete audit trail** of all employee data changes | Planned |
| **DR-HCM-003** | Store **document metadata** with expiry dates and renewal reminders | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-HCM-001** | Integrate with **Payroll module** (future) for salary and deduction processing | Planned |
| **IR-HCM-002** | Integrate with **SUB15 (Backoffice)** for department and position management | Planned |
| **IR-HCM-003** | Integrate with **SUB02 (Authentication)** for employee user account management | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-HCM-001** | Implement **role-based access** to employee personal information | Planned |
| **SR-HCM-002** | **Encrypt sensitive fields** (salary, SSN, bank account) at rest using Laravel encryption | Planned |
| **SR-HCM-003** | Log all **access to employee records** for compliance auditing | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-HCM-001** | Employee record retrieval must complete under **200ms** | Planned |
| **PR-HCM-002** | Organizational hierarchy query must return in **< 100ms** for 10k employees | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-HCM-001** | Support **100,000+ employee records** per tenant with efficient indexing | Planned |

### Compliance Requirements (CR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **CR-HCM-001** | Comply with **GDPR** for employee personal data protection (right to access, rectification, erasure) | Planned |
| **CR-HCM-002** | Support **right to erasure** for terminated employees after retention period | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-HCM-001** | Use **soft deletes** for employee records to maintain referential integrity | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-HCM-001** | `EmployeeHiredEvent` | When new employee is onboarded | Planned |
| **EV-HCM-002** | `EmployeeTerminatedEvent` | When employment ends | Planned |
| **EV-HCM-003** | `EmployeeTransferredEvent` | When employee changes department/position | Planned |
| **EV-HCM-004** | `DocumentExpiringEvent` | When employee document approaches expiry (30 days before) | Planned |

---

## Technical Specifications

### Database Schema

**Employees Table:**

```sql
CREATE TABLE employees (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    employee_number VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    date_of_birth DATE NULL,
    gender VARCHAR(20) NULL,
    ssn_encrypted TEXT NULL,  -- Encrypted
    hire_date DATE NOT NULL,
    termination_date DATE NULL,
    employment_type VARCHAR(50) NOT NULL,  -- 'full-time', 'part-time', 'contract', 'intern'
    status VARCHAR(20) NOT NULL DEFAULT 'active',  -- 'active', 'terminated', 'suspended'
    department_id BIGINT NULL REFERENCES departments(id),
    position_id BIGINT NULL REFERENCES positions(id),
    manager_id UUID NULL REFERENCES employees(id),
    user_id BIGINT NULL REFERENCES users(id),
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE (tenant_id, employee_number),
    INDEX idx_employees_tenant (tenant_id),
    INDEX idx_employees_status (status),
    INDEX idx_employees_manager (manager_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Employee Employment History Table:**

```sql
CREATE TABLE employee_employment_history (
    id BIGSERIAL PRIMARY KEY,
    employee_id UUID NOT NULL REFERENCES employees(id),
    effective_date DATE NOT NULL,
    event_type VARCHAR(50) NOT NULL,  -- 'hire', 'transfer', 'promotion', 'termination'
    department_id BIGINT NULL,
    position_id BIGINT NULL,
    salary_encrypted TEXT NULL,  -- Encrypted
    notes TEXT NULL,
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_emp_history_employee (employee_id),
    INDEX idx_emp_history_date (effective_date)
);
```

**Employee Documents Table:**

```sql
CREATE TABLE employee_documents (
    id BIGSERIAL PRIMARY KEY,
    employee_id UUID NOT NULL REFERENCES employees(id),
    document_type VARCHAR(100) NOT NULL,  -- 'contract', 'certificate', 'id', 'passport'
    document_number VARCHAR(100) NULL,
    issue_date DATE NULL,
    expiry_date DATE NULL,
    file_path VARCHAR(500) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_emp_docs_employee (employee_id),
    INDEX idx_emp_docs_expiry (expiry_date)
);
```

**Leave Requests Table:**

```sql
CREATE TABLE leave_requests (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    employee_id UUID NOT NULL REFERENCES employees(id),
    leave_type VARCHAR(50) NOT NULL,  -- 'annual', 'sick', 'unpaid', 'maternity'
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days DECIMAL(5, 2) NOT NULL,
    reason TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',  -- 'pending', 'approved', 'rejected', 'cancelled'
    approved_by BIGINT NULL REFERENCES users(id),
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_leave_tenant (tenant_id),
    INDEX idx_leave_employee (employee_id),
    INDEX idx_leave_status (status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

### API Endpoints

All endpoints follow the RESTful pattern under `/api/v1/hcm/`:

**Employee Management:**
- `GET /api/v1/hcm/employees` - List employees with filtering and pagination
- `POST /api/v1/hcm/employees` - Create new employee
- `GET /api/v1/hcm/employees/{id}` - Get employee details
- `PATCH /api/v1/hcm/employees/{id}` - Update employee information
- `DELETE /api/v1/hcm/employees/{id}` - Soft delete employee (requires authorization)
- `GET /api/v1/hcm/employees/{id}/history` - Get employment history
- `POST /api/v1/hcm/employees/{id}/terminate` - Terminate employment

**Document Management:**
- `GET /api/v1/hcm/employees/{id}/documents` - List employee documents
- `POST /api/v1/hcm/employees/{id}/documents` - Upload document
- `GET /api/v1/hcm/documents/{id}` - Download document
- `DELETE /api/v1/hcm/documents/{id}` - Delete document

**Leave Management:**
- `GET /api/v1/hcm/leave-requests` - List leave requests (filtered by user role)
- `POST /api/v1/hcm/leave-requests` - Create leave request
- `PATCH /api/v1/hcm/leave-requests/{id}` - Update leave request
- `POST /api/v1/hcm/leave-requests/{id}/approve` - Approve leave request
- `POST /api/v1/hcm/leave-requests/{id}/reject` - Reject leave request

**Organizational Hierarchy:**
- `GET /api/v1/hcm/hierarchy` - Get organizational hierarchy tree
- `GET /api/v1/hcm/employees/{id}/subordinates` - Get direct reports

### Events

**Domain Events Emitted:**

```php
namespace Nexus\Erp\Hcm\Events;

class EmployeeHiredEvent
{
    public function __construct(
        public readonly Employee $employee,
        public readonly Carbon $hireDate
    ) {}
}

class EmployeeTerminatedEvent
{
    public function __construct(
        public readonly Employee $employee,
        public readonly Carbon $terminationDate,
        public readonly string $reason
    ) {}
}

class EmployeeTransferredEvent
{
    public function __construct(
        public readonly Employee $employee,
        public readonly int $fromDepartmentId,
        public readonly int $toDepartmentId
    ) {}
}

class DocumentExpiringEvent
{
    public function __construct(
        public readonly EmployeeDocument $document,
        public readonly Carbon $expiryDate,
        public readonly int $daysUntilExpiry
    ) {}
}
```

### Event Listeners

**Events from Other Modules:**

This module listens to:
- `TenantCreatedEvent` (SUB01) - Initialize default departments/positions
- `UserCreatedEvent` (SUB02) - Link user account to employee record

---

## Implementation Plans

**Note:** Implementation plans follow the naming convention `PLAN{number}-implement-{component}.md`

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN13-implement-hcm.md | FR-HCM-001 to FR-HCM-010, BR-HCM-001 to BR-HCM-004 | MILESTONE 4 | Not Started |

**Implementation plan will be created separately using:** `.github/prompts/create-implementation-plan.prompt.md`

---

## Acceptance Criteria

### Functional Acceptance

- [ ] Can create, update, and retrieve employee master data
- [ ] Organizational hierarchy displays correctly with reporting relationships
- [ ] Employee lifecycle workflows (hire, transfer, termination) function properly
- [ ] Employment history tracks all position and salary changes
- [ ] Document management with expiry alerts operational
- [ ] Leave request workflow with approval process working
- [ ] Employee self-service portal accessible and functional

### Technical Acceptance

- [ ] All API endpoints return correct responses per OpenAPI spec
- [ ] Sensitive data (SSN, salary) encrypted at rest
- [ ] Soft deletes prevent hard deletion of historical records
- [ ] Performance benchmarks met (< 200ms record retrieval)
- [ ] Organizational hierarchy queries complete in < 100ms for 10k employees

### Security Acceptance

- [ ] Role-based access control enforced for employee data
- [ ] Audit logs capture all employee data access and modifications
- [ ] GDPR compliance verified (right to access, rectification, erasure)
- [ ] Managers cannot approve their own leave requests

### Integration Acceptance

- [ ] Events emitted correctly to SUB03 (Audit Logging)
- [ ] Integration with SUB15 (Backoffice) for departments functional
- [ ] Integration with SUB02 (Authentication) for user accounts working

---

## Testing Strategy

### Unit Tests

**Test Coverage Requirements:** Minimum 80% code coverage

**Key Test Areas:**
- Employee entity business logic
- Leave balance calculations
- Hierarchy traversal algorithms
- Document expiry date calculations
- Encryption/decryption of sensitive fields

**Example Tests:**
```php
test('employee number is unique within tenant', function () {
    $tenant = Tenant::factory()->create();
    Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'EMP001',
    ]);
    
    expect(fn () => Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'EMP001',
    ]))->toThrow(QueryException::class);
});

test('manager cannot approve their own leave request', function () {
    $manager = Employee::factory()->create();
    $leaveRequest = LeaveRequest::factory()->create([
        'employee_id' => $manager->id,
    ]);
    
    $result = ApproveLeaveRequestAction::run(
        $leaveRequest,
        $manager->user
    );
    
    expect($result)->toBeFalse();
});
```

### Feature Tests

**API Integration Tests:**
- Complete CRUD operations for employees via API
- Leave request workflow (create, approve, reject)
- Document upload and retrieval
- Organizational hierarchy API responses

**Example Tests:**
```php
test('can create employee via API', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/hcm/employees', [
            'employee_number' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'hire_date' => '2025-01-15',
            'employment_type' => 'full-time',
        ]);
    
    $response->assertCreated();
    expect($response->json('data.employee_number'))->toBe('EMP001');
});
```

### Integration Tests

**Cross-Module Integration:**
- Employee creation triggers user account creation (SUB02)
- Employee termination triggers audit log entry (SUB03)
- Department changes reflect in Backoffice hierarchy (SUB15)

### Performance Tests

**Load Testing Scenarios:**
- Retrieve employee record: < 200ms (PR-HCM-001)
- Hierarchy query for 10k employees: < 100ms (PR-HCM-002)
- Bulk employee import: 1000 records in < 10 seconds

---

## Dependencies

### Feature Module Dependencies

**From Master PRD Section D.2.1:**

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for all employee data
- **SUB02 (Authentication & Authorization)** - User accounts and RBAC
- **SUB03 (Audit Logging)** - Track all employee data changes
- **SUB05 (Settings Management)** - Leave policies, document expiry thresholds
- **SUB15 (Backoffice)** - Organizational structure (departments, positions, cost centers)

**Optional Dependencies:**
- **SUB22 (Notifications)** - Document expiry alerts, leave request notifications
- **SUB06 (UOM)** - If tracking leave days with fractional precision

### External Package Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "azaharizaman/erp-core": "^1.0",
    "azaharizaman/erp-backoffice": "^1.0",
    "lorisleiva/laravel-actions": "^2.0",
    "spatie/laravel-model-status": "^2.0"
  },
  "require-dev": {
    "pestphp/pest": "^4.0"
  }
}
```

### Infrastructure Dependencies

- **Database:** PostgreSQL 14+ (for UUID support and JSONB)
- **Cache:** Redis 6+ (for organizational hierarchy caching)
- **Queue:** Redis or database queue driver
- **Storage:** Local or S3-compatible storage for employee documents

---

## Feature Module Structure

### Directory Structure (in Monorepo)

```
packages/hcm/
├── src/
│   ├── Actions/
│   │   ├── CreateEmployeeAction.php
│   │   ├── TerminateEmployeeAction.php
│   │   ├── ApproveLeaveRequestAction.php
│   │   └── UploadEmployeeDocumentAction.php
│   ├── Contracts/
│   │   ├── EmployeeRepositoryContract.php
│   │   └── LeaveRequestRepositoryContract.php
│   ├── Events/
│   │   ├── EmployeeHiredEvent.php
│   │   ├── EmployeeTerminatedEvent.php
│   │   ├── EmployeeTransferredEvent.php
│   │   └── DocumentExpiringEvent.php
│   ├── Listeners/
│   │   ├── InitializeEmployeeTenantListener.php
│   │   └── SendDocumentExpiryNotificationListener.php
│   ├── Models/
│   │   ├── Employee.php
│   │   ├── EmployeeEmploymentHistory.php
│   │   ├── EmployeeDocument.php
│   │   └── LeaveRequest.php
│   ├── Observers/
│   │   └── EmployeeObserver.php
│   ├── Policies/
│   │   ├── EmployeePolicy.php
│   │   └── LeaveRequestPolicy.php
│   ├── Repositories/
│   │   ├── EmployeeRepository.php
│   │   └── LeaveRequestRepository.php
│   ├── Services/
│   │   ├── EmployeeLifecycleService.php
│   │   ├── LeaveManagementService.php
│   │   └── OrganizationalHierarchyService.php
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Resources/
│   └── HcmServiceProvider.php
├── tests/
│   ├── Feature/
│   │   ├── EmployeeManagementTest.php
│   │   └── LeaveRequestTest.php
│   └── Unit/
│       ├── EmployeeTest.php
│       └── LeaveBalanceTest.php
├── database/
│   ├── migrations/
│   │   ├── 2025_01_01_000001_create_employees_table.php
│   │   ├── 2025_01_01_000002_create_employee_employment_history_table.php
│   │   ├── 2025_01_01_000003_create_employee_documents_table.php
│   │   └── 2025_01_01_000004_create_leave_requests_table.php
│   └── factories/
│       ├── EmployeeFactory.php
│       └── LeaveRequestFactory.php
├── routes/
│   └── api.php
├── config/
│   └── hcm.php
├── composer.json
└── README.md
```

---

## Migration Path

This is a new module with no existing functionality to migrate from.

**Initial Setup:**
1. Install package via Composer
2. Publish migrations and run `php artisan migrate`
3. Seed default leave types and employment statuses
4. Configure leave policies in Settings module (SUB05)
5. Set up organizational structure in Backoffice (SUB15)

---

## Success Metrics

From Master PRD Section B.3:

**Adoption Metrics:**
- Employee self-service portal adoption rate > 70% within 3 months
- Average time to onboard new employee < 15 minutes

**Performance Metrics:**
- Employee record retrieval < 200ms (PR-HCM-001)
- Organizational hierarchy query < 100ms for 10k employees (PR-HCM-002)

**Compliance Metrics:**
- Zero GDPR compliance violations
- 100% audit trail coverage for employee data changes

**Operational Metrics:**
- Leave request approval turnaround time < 24 hours
- Document expiry reminders sent 30 days before expiration

---

## Assumptions & Constraints

### Assumptions

1. Payroll processing handled by separate module (not included)
2. Employee documents stored in tenant-scoped storage
3. Leave policies configured via Settings module (SUB05)
4. Organizational structure (departments, positions) managed via Backoffice (SUB15)
5. Time and attendance tracking interface provided by external system or future module

### Constraints

1. Cannot delete employees with payroll or leave history
2. Employee numbers must be unique within tenant
3. Manager approval required for all leave requests
4. Sensitive data encryption uses Laravel's built-in encryption
5. Maximum document upload size: 10MB per file

---

## Monorepo Integration

### Development

- Lives in `/packages/hcm/` during development
- Main app uses Composer path repository to require locally:
  ```json
  {
    "repositories": [
      {
        "type": "path",
        "url": "./packages/hcm"
      }
    ],
    "require": {
      "azaharizaman/erp-hcm": "@dev"
    }
  }
  ```
- All changes committed to monorepo

### Release (v1.0)

- Tagged with monorepo version (e.g., v1.0.0)
- Published to Packagist as `azaharizaman/erp-hcm`
- Can be installed independently in external Laravel apps
- Semantic versioning: MAJOR.MINOR.PATCH

---

## References

- Master PRD: [../PRD01-MVP.md](../PRD01-MVP.md)
- Monorepo Strategy: [../PRD01-MVP.md#C.1](../PRD01-MVP.md#section-c1-core-architectural-strategy-the-monorepo)
- Feature Module Independence: [../PRD01-MVP.md#D.2.2](../PRD01-MVP.md#d22-feature-module-independence-requirements)
- Architecture Documentation: [../../architecture/](../../architecture/)
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- GitHub Copilot Instructions: [../../.github/copilot-instructions.md](../../.github/copilot-instructions.md)

---

**Next Steps:**
1. Review and approve this Sub-PRD
2. Create implementation plan: `PLAN13-implement-hcm.md` in `/docs/plan/`
3. Break down into GitHub issues
4. Assign to MILESTONE 4 from Master PRD Section F.2.4
5. Set up feature module structure in `/packages/hcm/`
