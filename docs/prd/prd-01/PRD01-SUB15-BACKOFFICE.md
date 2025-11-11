# PRD01-SUB15: Backoffice

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Optional Feature Modules - Core Operations  
**Related Sub-PRDs:** SUB13 (HCM), SUB08 (General Ledger), SUB16 (Purchasing), SUB17 (Sales)  
**Composer Package:** `azaharizaman/laravel-backoffice`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Backoffice module provides foundational organizational and operational structures including company master data, fiscal year management, accounting periods, organizational hierarchy (companies, branches, departments, cost centers), approval workflows, and document numbering sequences.

### Purpose

This module solves the challenge of managing enterprise-wide organizational structures and operational frameworks that support all transactional modules. It serves as the foundation for multi-entity accounting, hierarchical reporting, and workflow-based approvals.

### Scope

**Included:**
- Organizational structure management (companies, branches, departments, cost centers)
- Fiscal year management (creation, closing, reopening)
- Accounting period management with open/closed status
- Company master data (registration, tax IDs, addresses, bank accounts)
- Branch/office hierarchy with multi-level relationships
- Department and cost center hierarchy for expense allocation
- Approval workflow configuration with multi-level approvers
- Document numbering sequences per organizational entity

**Excluded:**
- User role management (handled by SUB02 Authentication & Authorization)
- Payroll and HCM-specific workflows (handled by SUB13 HCM)
- Warehouse management (handled by SUB14 Inventory Management)
- GL account configuration (handled by SUB07 Chart of Accounts)

### Dependencies

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for organizational data
- **SUB02 (Authentication & Authorization)** - Role-based access control
- **SUB03 (Audit Logging)** - Track all administrative changes
- **SUB05 (Settings Management)** - System-wide configuration settings

**Optional Dependencies:**
- **SUB13 (HCM)** - Employee-department assignments
- **SUB07-SUB12 (Accounting Modules)** - Fiscal year and period validation

### Composer Package Information

- **Package Name:** `azaharizaman/laravel-backoffice`
- **Namespace:** `Azaharizaman\Erp\Backoffice`
- **Monorepo Location:** `/packages/backoffice/`
- **Installation:** `composer require azaharizaman/laravel-backoffice` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB15 (Backoffice). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-BO-001** | Manage **organizational structure** (companies, branches, departments, cost centers) | High | Planned |
| **FR-BO-002** | Support **fiscal year management** including creation, closing, and reopening | High | Planned |
| **FR-BO-003** | Define **accounting periods** with open/closed status per module | High | Planned |
| **FR-BO-004** | Maintain **company master data** (registration, tax IDs, addresses, bank accounts) | High | Planned |
| **FR-BO-005** | Manage **branch/office hierarchy** with multi-level relationships | Medium | Planned |
| **FR-BO-006** | Support **department and cost center** hierarchy for expense allocation | Medium | Planned |
| **FR-BO-007** | Define **approval workflows** with multi-level approvers and delegation rules | Medium | Planned |
| **FR-BO-008** | Manage **document numbering sequences** per entity (company, branch, department) | Low | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-BO-001** | Only **system administrators** can create or modify fiscal years | Planned |
| **BR-BO-002** | Closed accounting periods **cannot accept new transactions** without reopening | Planned |
| **BR-BO-003** | **Fiscal year end date** must be after start date and cannot overlap existing years | Planned |
| **BR-BO-004** | Organizational entities with **active transactions** cannot be deleted | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-BO-001** | Store **complete hierarchy path** for efficient organizational queries | Planned |
| **DR-BO-002** | Maintain **period lock history** for compliance and audit trail | Planned |
| **DR-BO-003** | Record **approval workflow history** with timestamps and approver details | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-BO-001** | Integrate with **all transactional modules** for period validation | Planned |
| **IR-BO-002** | Provide **organizational hierarchy API** for authorization and reporting | Planned |
| **IR-BO-003** | Integrate with **HCM** for employee-department assignments | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-BO-001** | Implement **role-based access** to fiscal year and period management | Planned |
| **SR-BO-002** | Log all **administrative actions** (fiscal year closing, period locking) | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-BO-001** | Organizational hierarchy queries must complete in **< 100ms** for 1000+ entities | Planned |
| **PR-BO-002** | Period validation check must complete in **< 10ms** | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-BO-001** | Support **10,000+ organizational entities** per tenant | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-BO-001** | Use **nested set model** or **closure table** for efficient hierarchy queries | Planned |
| **ARCH-BO-002** | Cache **current period status** in Redis for fast validation | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-BO-001** | `FiscalYearClosedEvent` | When fiscal year is closed | Planned |
| **EV-BO-002** | `PeriodLockedEvent` | When accounting period is locked | Planned |
| **EV-BO-003** | `OrganizationUpdatedEvent` | When organizational structure changes | Planned |

---

## Technical Specifications

### Database Schema

**Companies Table:**

```sql
CREATE TABLE companies (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    company_code VARCHAR(50) NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    registration_number VARCHAR(100) NULL,
    tax_id VARCHAR(100) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(100) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    base_currency_code VARCHAR(10) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE (tenant_id, company_code),
    INDEX idx_companies_tenant (tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Fiscal Years Table:**

```sql
CREATE TABLE fiscal_years (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    year_code VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',  -- 'open', 'closed'
    closed_by BIGINT NULL REFERENCES users(id),
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, company_id, year_code),
    INDEX idx_fiscal_years_tenant (tenant_id),
    INDEX idx_fiscal_years_company (company_id),
    INDEX idx_fiscal_years_status (status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CHECK (end_date > start_date)
);
```

**Accounting Periods Table:**

```sql
CREATE TABLE accounting_periods (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    fiscal_year_id BIGINT NOT NULL REFERENCES fiscal_years(id),
    period_number INT NOT NULL,
    period_name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',  -- 'open', 'closed'
    locked_modules JSONB NULL,  -- JSON array of module names that are locked
    closed_by BIGINT NULL REFERENCES users(id),
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (fiscal_year_id, period_number),
    INDEX idx_periods_tenant (tenant_id),
    INDEX idx_periods_fiscal_year (fiscal_year_id),
    INDEX idx_periods_status (status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CHECK (end_date > start_date)
);
```

**Organizational Hierarchy Table (Closure Table Pattern):**

```sql
CREATE TABLE organizations (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    organization_code VARCHAR(50) NOT NULL,
    organization_name VARCHAR(255) NOT NULL,
    organization_type VARCHAR(50) NOT NULL,  -- 'branch', 'department', 'cost_center'
    parent_id BIGINT NULL REFERENCES organizations(id),
    company_id BIGINT NOT NULL REFERENCES companies(id),
    manager_id UUID NULL REFERENCES employees(id),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE (tenant_id, organization_code),
    INDEX idx_orgs_tenant (tenant_id),
    INDEX idx_orgs_parent (parent_id),
    INDEX idx_orgs_company (company_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Closure table for efficient hierarchy queries
CREATE TABLE organization_hierarchy (
    ancestor_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    descendant_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    depth INT NOT NULL,
    
    PRIMARY KEY (ancestor_id, descendant_id),
    INDEX idx_hierarchy_ancestor (ancestor_id),
    INDEX idx_hierarchy_descendant (descendant_id),
    INDEX idx_hierarchy_depth (depth)
);
```

**Approval Workflows Table:**

```sql
CREATE TABLE approval_workflows (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    workflow_name VARCHAR(255) NOT NULL,
    document_type VARCHAR(100) NOT NULL,  -- 'purchase_order', 'sales_order', 'expense_claim'
    approval_levels INT NOT NULL,
    rules JSONB NOT NULL,  -- JSON configuration for approval thresholds and rules
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_workflows_tenant (tenant_id),
    INDEX idx_workflows_doc_type (document_type),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Document Number Sequences Table:**

```sql
CREATE TABLE document_number_sequences (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    entity_type VARCHAR(50) NOT NULL,  -- 'company', 'branch', 'department'
    entity_id BIGINT NOT NULL,
    document_type VARCHAR(100) NOT NULL,  -- 'invoice', 'purchase_order', 'payment'
    prefix VARCHAR(20) NOT NULL,
    next_number INT NOT NULL DEFAULT 1,
    padding_length INT NOT NULL DEFAULT 6,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, entity_type, entity_id, document_type),
    INDEX idx_doc_seq_tenant (tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

### API Endpoints

All endpoints follow the RESTful pattern under `/api/v1/backoffice/`:

**Company Management:**
- `GET /api/v1/backoffice/companies` - List companies
- `POST /api/v1/backoffice/companies` - Create new company
- `GET /api/v1/backoffice/companies/{id}` - Get company details
- `PATCH /api/v1/backoffice/companies/{id}` - Update company
- `DELETE /api/v1/backoffice/companies/{id}` - Soft delete company

**Fiscal Year Management:**
- `GET /api/v1/backoffice/fiscal-years` - List fiscal years
- `POST /api/v1/backoffice/fiscal-years` - Create fiscal year
- `GET /api/v1/backoffice/fiscal-years/{id}` - Get fiscal year details
- `POST /api/v1/backoffice/fiscal-years/{id}/close` - Close fiscal year
- `POST /api/v1/backoffice/fiscal-years/{id}/reopen` - Reopen fiscal year

**Accounting Period Management:**
- `GET /api/v1/backoffice/periods` - List accounting periods
- `POST /api/v1/backoffice/periods/{id}/lock` - Lock period for specific module
- `POST /api/v1/backoffice/periods/{id}/unlock` - Unlock period

**Organizational Hierarchy:**
- `GET /api/v1/backoffice/organizations` - List organizational units
- `POST /api/v1/backoffice/organizations` - Create organizational unit
- `GET /api/v1/backoffice/organizations/{id}` - Get unit details
- `GET /api/v1/backoffice/organizations/{id}/hierarchy` - Get hierarchy tree
- `PATCH /api/v1/backoffice/organizations/{id}` - Update unit
- `DELETE /api/v1/backoffice/organizations/{id}` - Soft delete unit

**Approval Workflows:**
- `GET /api/v1/backoffice/workflows` - List approval workflows
- `POST /api/v1/backoffice/workflows` - Create workflow
- `PATCH /api/v1/backoffice/workflows/{id}` - Update workflow
- `DELETE /api/v1/backoffice/workflows/{id}` - Delete workflow

**Document Numbering:**
- `GET /api/v1/backoffice/document-sequences` - List number sequences
- `POST /api/v1/backoffice/document-sequences` - Create sequence
- `POST /api/v1/backoffice/document-sequences/generate` - Generate next number

### Events

**Domain Events Emitted:**

```php
namespace Azaharizaman\Erp\Backoffice\Events;

class FiscalYearClosedEvent
{
    public function __construct(
        public readonly FiscalYear $fiscalYear,
        public readonly User $closedBy,
        public readonly Carbon $closedAt
    ) {}
}

class PeriodLockedEvent
{
    public function __construct(
        public readonly AccountingPeriod $period,
        public readonly array $lockedModules,
        public readonly User $lockedBy
    ) {}
}

class OrganizationUpdatedEvent
{
    public function __construct(
        public readonly Organization $organization,
        public readonly string $changeType,  // 'created', 'updated', 'moved'
        public readonly ?int $oldParentId
    ) {}
}
```

### Event Listeners

**Events from Other Modules:**

This module listens to:
- `TenantCreatedEvent` (SUB01) - Initialize default company and fiscal year
- `TransactionPostedEvent` (SUB07-SUB12) - Validate period status before posting

---

## Implementation Plans

**Note:** Implementation plans follow the naming convention `PLAN{number}-implement-{component}.md`

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN15-implement-backoffice.md | FR-BO-001 to FR-BO-008, BR-BO-001 to BR-BO-004 | MILESTONE 3 | Not Started |

**Implementation plan will be created separately using:** `.github/prompts/create-implementation-plan.prompt.md`

---

## Acceptance Criteria

### Functional Acceptance

- [ ] Can create and manage companies with complete master data
- [ ] Fiscal year creation, closing, and reopening functional
- [ ] Accounting periods can be opened, closed, and locked per module
- [ ] Organizational hierarchy (branches, departments, cost centers) operational
- [ ] Approval workflows configurable with multi-level approvers
- [ ] Document numbering sequences generate unique numbers per entity

### Technical Acceptance

- [ ] All API endpoints return correct responses per OpenAPI spec
- [ ] Hierarchy queries complete in < 100ms for 1000+ entities (PR-BO-001)
- [ ] Period validation completes in < 10ms (PR-BO-002)
- [ ] Closure table pattern efficiently queries hierarchies (ARCH-BO-001)
- [ ] Redis caching improves period validation performance (ARCH-BO-002)

### Security Acceptance

- [ ] Only system administrators can modify fiscal years (BR-BO-001)
- [ ] Closed periods reject new transactions (BR-BO-002)
- [ ] All administrative actions logged (SR-BO-002)
- [ ] Role-based access control enforced (SR-BO-001)

### Integration Acceptance

- [ ] Period validation integrated with all transactional modules (IR-BO-001)
- [ ] Organizational hierarchy API accessible for authorization (IR-BO-002)
- [ ] Employee-department assignments sync with HCM (IR-BO-003)

---

## Testing Strategy

### Unit Tests

**Test Coverage Requirements:** Minimum 80% code coverage

**Key Test Areas:**
- Fiscal year date validation
- Period overlap detection
- Organizational hierarchy path calculations
- Document number sequence generation
- Approval workflow rule evaluation

**Example Tests:**
```php
test('fiscal year end date must be after start date', function () {
    expect(fn () => FiscalYear::factory()->create([
        'start_date' => '2025-12-31',
        'end_date' => '2025-01-01',
    ]))->toThrow(ValidationException::class);
});

test('closed period rejects new transactions', function () {
    $period = AccountingPeriod::factory()->create([
        'status' => 'closed',
    ]);
    
    $result = ValidatePeriodAction::run($period, 'general_ledger');
    
    expect($result)->toBeFalse();
});
```

### Feature Tests

**API Integration Tests:**
- Complete CRUD operations for companies via API
- Fiscal year lifecycle (create, close, reopen) via API
- Organizational hierarchy queries via API
- Document number sequence generation

**Example Tests:**
```php
test('can close fiscal year via API', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->admin()->create(['tenant_id' => $tenant->id]);
    $fiscalYear = FiscalYear::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => 'open',
    ]);
    
    $response = $this->actingAs($user)
        ->postJson("/api/v1/backoffice/fiscal-years/{$fiscalYear->id}/close");
    
    $response->assertOk();
    expect($fiscalYear->fresh()->status)->toBe('closed');
});
```

### Integration Tests

**Cross-Module Integration:**
- Period validation called from GL posting (SUB08)
- Organizational hierarchy used for expense allocation (SUB10)
- Employee-department assignments sync (SUB13)

### Performance Tests

**Load Testing Scenarios:**
- Hierarchy query for 1000+ entities: < 100ms (PR-BO-001)
- Period validation: < 10ms (PR-BO-002)
- Document number generation under concurrent load

---

## Dependencies

### Feature Module Dependencies

**From Master PRD Section D.2.1:**

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for all organizational data
- **SUB02 (Authentication & Authorization)** - Role-based access control
- **SUB03 (Audit Logging)** - Track administrative changes
- **SUB05 (Settings Management)** - System-wide configuration

**Optional Dependencies:**
- **SUB07-SUB12 (Accounting Modules)** - Period validation integration
- **SUB13 (HCM)** - Employee-department assignments
- **SUB16 (Purchasing)** - Approval workflow enforcement
- **SUB17 (Sales)** - Approval workflow enforcement

### External Package Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "azaharizaman/erp-core": "^1.0",
    "lorisleiva/laravel-actions": "^2.0"
  },
  "require-dev": {
    "pestphp/pest": "^4.0"
  }
}
```

### Infrastructure Dependencies

- **Database:** PostgreSQL 14+ (for JSONB and advanced indexing)
- **Cache:** Redis 6+ (for period status caching)
- **Queue:** Redis or database queue driver

---

## Feature Module Structure

### Directory Structure (in Monorepo)

```
packages/backoffice/
├── src/
│   ├── Actions/
│   │   ├── CreateCompanyAction.php
│   │   ├── CloseFiscalYearAction.php
│   │   ├── LockPeriodAction.php
│   │   └── CreateOrganizationAction.php
│   ├── Contracts/
│   │   ├── CompanyRepositoryContract.php
│   │   ├── FiscalYearRepositoryContract.php
│   │   └── OrganizationRepositoryContract.php
│   ├── Events/
│   │   ├── FiscalYearClosedEvent.php
│   │   ├── PeriodLockedEvent.php
│   │   └── OrganizationUpdatedEvent.php
│   ├── Listeners/
│   │   ├── InitializeTenantBackofficeListener.php
│   │   └── ValidatePeriodBeforePostingListener.php
│   ├── Models/
│   │   ├── Company.php
│   │   ├── FiscalYear.php
│   │   ├── AccountingPeriod.php
│   │   ├── Organization.php
│   │   └── OrganizationHierarchy.php
│   ├── Observers/
│   │   └── OrganizationObserver.php
│   ├── Policies/
│   │   ├── CompanyPolicy.php
│   │   └── FiscalYearPolicy.php
│   ├── Repositories/
│   │   ├── CompanyRepository.php
│   │   ├── FiscalYearRepository.php
│   │   └── OrganizationRepository.php
│   ├── Services/
│   │   ├── FiscalYearService.php
│   │   ├── PeriodValidationService.php
│   │   └── OrganizationHierarchyService.php
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Resources/
│   └── BackofficeServiceProvider.php
├── tests/
│   ├── Feature/
│   │   ├── CompanyManagementTest.php
│   │   ├── FiscalYearTest.php
│   │   └── OrganizationHierarchyTest.php
│   └── Unit/
│       ├── FiscalYearTest.php
│       └── PeriodValidationTest.php
├── database/
│   ├── migrations/
│   │   ├── 2025_01_01_000001_create_companies_table.php
│   │   ├── 2025_01_01_000002_create_fiscal_years_table.php
│   │   ├── 2025_01_01_000003_create_accounting_periods_table.php
│   │   └── 2025_01_01_000004_create_organizations_table.php
│   └── factories/
│       ├── CompanyFactory.php
│       └── FiscalYearFactory.php
├── routes/
│   └── api.php
├── config/
│   └── backoffice.php
├── composer.json
└── README.md
```

---

## Migration Path

This is a new module with no existing functionality to migrate from.

**Initial Setup:**
1. Install package via Composer
2. Publish migrations and run `php artisan migrate`
3. Create initial company master data
4. Configure first fiscal year
5. Set up organizational hierarchy (branches, departments)

---

## Success Metrics

From Master PRD Section B.3:

**Adoption Metrics:**
- Organizational hierarchy usage in reporting > 90%
- Approval workflow automation > 70% of transactions

**Performance Metrics:**
- Hierarchy queries < 100ms for 1000+ entities (PR-BO-001)
- Period validation < 10ms (PR-BO-002)

**Compliance Metrics:**
- Zero transactions posted to closed periods
- 100% administrative action audit coverage

**Operational Metrics:**
- Fiscal year closing time < 2 hours
- Average approval workflow processing time < 4 hours

---

## Assumptions & Constraints

### Assumptions

1. Each tenant has at least one company
2. Only one active fiscal year per company at a time
3. Fiscal years follow calendar year or fiscal year conventions
4. Organizational hierarchy limited to reasonable depth (< 10 levels)
5. Document numbering sequences are tenant-scoped

### Constraints

1. Only system administrators can modify fiscal years
2. Closed periods cannot accept new transactions without reopening
3. Fiscal year dates cannot overlap
4. Organizational entities with active transactions cannot be deleted
5. Document number sequences must be unique per entity and document type

---

## Monorepo Integration

### Development

- Lives in `/packages/backoffice/` during development
- Main app uses Composer path repository to require locally:
  ```json
  {
    "repositories": [
      {
        "type": "path",
        "url": "./packages/backoffice"
      }
    ],
    "require": {
      "azaharizaman/laravel-backoffice": "@dev"
    }
  }
  ```
- All changes committed to monorepo

### Release (v1.0)

- Tagged with monorepo version (e.g., v1.0.0)
- Published to Packagist as `azaharizaman/laravel-backoffice`
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
2. Create implementation plan: `PLAN15-implement-backoffice.md` in `/docs/plan/`
3. Break down into GitHub issues
4. Assign to MILESTONE 3 from Master PRD Section F.2.4
5. Set up feature module structure in `/packages/backoffice/`
