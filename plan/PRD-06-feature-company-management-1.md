---
goal: Implement Company Management System (Package Integration)
version: 1.0
date_created: 2025-11-09
last_updated: 2025-11-09
owner: Backoffice Domain Team
status: 'Planned'
tags: [feature, backoffice, company, package-integration, phase-1, mvp]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan details the integration of the Company Management system for the Laravel ERP using the `azaharizaman/laravel-backoffice` package. The Company Management module enables multi-level organizational hierarchy management, company registration details, tax information, and company status tracking. This module is foundational for the Backoffice domain and provides the organizational structure for the entire ERP system.

## 1. Requirements & Constraints

**Core Requirements:**
- **REQ-001**: Integrate `azaharizaman/laravel-backoffice` package for Company model
- **REQ-002**: Support parent-child company relationships for multi-level hierarchy
- **REQ-003**: Store company registration details (name, registration number, tax ID)
- **REQ-004**: Implement company status management (Active, Inactive, Suspended)
- **REQ-005**: Support unlimited depth in company hierarchy
- **REQ-006**: Extend package Company model with ERP-specific functionality if needed
- **REQ-007**: Create RESTful API endpoints for company CRUD operations
- **REQ-008**: Implement CLI commands for company management
- **REQ-009**: Apply tenant isolation to all company records
- **REQ-010**: Support company contact information storage
- **REQ-011**: Enable company logo and branding storage

**Security Requirements:**
- **SEC-001**: Apply tenant scope to all company queries to prevent cross-tenant access
- **SEC-002**: Implement authorization policies for company management operations
- **SEC-003**: Restrict company hierarchy modifications to authorized users only
- **SEC-004**: Log all company changes for audit trail compliance
- **SEC-005**: Validate company relationships to prevent circular hierarchies

**Performance Constraints:**
- **CON-001**: Company hierarchy queries must complete in under 50ms
- **CON-002**: Support minimum 100 companies per tenant
- **CON-003**: Parent-child relationship resolution must be optimized with eager loading

**Integration Guidelines:**
- **GUD-001**: Use package's Company model as base, extend only when necessary
- **GUD-002**: Apply BelongsToTenant trait to enable multi-tenancy
- **GUD-003**: Integrate with Office Management for location assignment
- **GUD-004**: Follow package documentation for model usage and configuration

**Design Patterns:**
- **PAT-001**: Use repository pattern for company data access layer
- **PAT-002**: Implement action pattern for company operations using Laravel Actions
- **PAT-003**: Apply nested set or closure table pattern for efficient hierarchy queries
- **PAT-004**: Use resource classes for API response transformation

## 2. Implementation Steps

> **Note:** This implementation has been condensed from 12 phases into 6 logical phases for better manageability while preserving all tasks and key details. Related components have been grouped together based on architectural layers and functional cohesion.

### Implementation Phase 1: Package Setup & Database

- GOAL-001: Install laravel-backoffice package and extend database schema for tenant isolation
- **Combines:** Package Installation & Configuration + Database Schema Extension

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| **Package Installation & Configuration** | | | |
| TASK-001 | Add `azaharizaman/laravel-backoffice` package to composer.json with `"dev-main"` stability | | |
| TASK-002 | Run `composer update` to install the package | | |
| TASK-003 | Publish package migrations using `php artisan vendor:publish` | | |
| TASK-004 | Review published migration files for companies table structure | | |
| TASK-005 | Publish package configuration file if available | | |
| TASK-006 | Review and customize package configuration for tenant-aware usage | | |
| **Database Schema Extension** | | | |
| TASK-007 | Create migration to add tenant_id column to companies table with foreign key constraint | | |
| TASK-008 | Create index on companies(tenant_id, status) for optimized filtering | | |
| TASK-009 | Create migration to add ERP-specific columns: registration_number, tax_id, fiscal_year_start, currency_code | | |
| TASK-010 | Add logo_path column for company branding (nullable) | | |
| TASK-011 | Add is_active boolean column with default true | | |
| TASK-012 | Run migrations to update database schema | | |

### Implementation Phase 2: Model & Repository Layer

- GOAL-002: Extend package Company model and implement repository pattern
- **Combines:** Model Extension & Trait Integration + Repository Layer

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| **Model Extension & Trait Integration** | | | |
| TASK-013 | Create ErpCompany model extending package Company model in app/Domains/Backoffice/Models/Company.php | | |
| TASK-014 | Add BelongsToTenant trait to Company model | | |
| TASK-015 | Add LogsActivity trait for audit logging | | |
| TASK-016 | Configure $fillable array to include new ERP fields | | |
| TASK-017 | Configure $casts array for proper type casting (is_active => boolean) | | |
| TASK-018 | Add $with array for eager loading common relationships | | |
| TASK-019 | Implement getChildrenAttribute() accessor for retrieving child companies | | |
| TASK-020 | Implement getAncestorsAttribute() accessor for retrieving parent hierarchy | | |
| TASK-021 | Add scopeActive() query scope to filter active companies | | |
| TASK-022 | Add scopeRootCompanies() query scope to retrieve top-level companies | | |
| **Repository Layer** | | | |
| TASK-023 | Create CompanyRepositoryInterface in app/Domains/Backoffice/Contracts/CompanyRepositoryInterface.php | | |
| TASK-024 | Define methods: findById(), findByTenantId(), create(), update(), delete(), getHierarchy() | | |
| TASK-025 | Create CompanyRepository in app/Domains/Backoffice/Repositories/CompanyRepository.php | | |
| TASK-026 | Implement findById() method with eager loading of relationships | | |
| TASK-027 | Implement findByTenantId() method with filtering and pagination | | |
| TASK-028 | Implement create() method with validation and tenant assignment | | |
| TASK-029 | Implement update() method with validation | | |
| TASK-030 | Implement delete() method with soft delete support | | |
| TASK-031 | Implement getHierarchy() method to retrieve full company tree structure | | |
| TASK-032 | Implement getChildren() method to retrieve direct children of a company | | |
| TASK-033 | Bind CompanyRepositoryInterface to CompanyRepository in BackofficeServiceProvider | | |

### Implementation Phase 3: Business Logic Layer

- GOAL-003: Implement action classes, events, and service provider configuration
- **Combines:** Action Classes + Events & Listeners + Service Provider Configuration

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| **Action Classes** | | | |
| TASK-034 | Create CreateCompanyAction in app/Domains/Backoffice/Actions/CreateCompanyAction.php | | |
| TASK-035 | Implement handle() method with validation, tenant assignment, and audit logging | | |
| TASK-036 | Add asController() method for HTTP request handling | | |
| TASK-037 | Add asJob() method for queue execution support | | |
| TASK-038 | Create UpdateCompanyAction in app/Domains/Backoffice/Actions/UpdateCompanyAction.php | | |
| TASK-039 | Implement handle() method with validation and hierarchy validation | | |
| TASK-040 | Create DeleteCompanyAction in app/Domains/Backoffice/Actions/DeleteCompanyAction.php | | |
| TASK-041 | Implement handle() method with dependency checking (child companies, offices) | | |
| TASK-042 | Create GetCompanyHierarchyAction in app/Domains/Backoffice/Actions/GetCompanyHierarchyAction.php | | |
| TASK-043 | Implement handle() method to build hierarchical tree structure | | |
| **Events & Listeners** | | | |
| TASK-044 | Create CompanyCreatedEvent in app/Domains/Backoffice/Events/CompanyCreatedEvent.php | | |
| TASK-045 | Create CompanyUpdatedEvent in app/Domains/Backoffice/Events/CompanyUpdatedEvent.php | | |
| TASK-046 | Create CompanyDeletedEvent in app/Domains/Backoffice/Events/CompanyDeletedEvent.php | | |
| TASK-047 | Dispatch events from respective action classes | | |
| TASK-048 | Create LogCompanyActivityListener for audit trail | | |
| TASK-049 | Register events and listeners in EventServiceProvider | | |
| **Service Provider Configuration** | | | |
| TASK-050 | Create BackofficeServiceProvider in app/Providers/BackofficeServiceProvider.php if not exists | | |
| TASK-051 | Register CompanyRepository binding in register() method | | |
| TASK-052 | Load routes from routes/backoffice.php in boot() method | | |
| TASK-053 | Register policies in boot() method | | |
| TASK-054 | Add BackofficeServiceProvider to config/app.php providers array | | |

### Implementation Phase 4: API Layer

- GOAL-004: Build RESTful API with controllers, validation, resources, and authorization
- **Combines:** API Controllers & Routes + Request Validation + API Resources + Authorization Policies

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| **API Controllers & Routes** | | | |
| TASK-055 | Create CompanyController in app/Http/Controllers/Api/V1/Backoffice/CompanyController.php | | |
| TASK-056 | Implement index() method with filtering, sorting, and pagination | | |
| TASK-057 | Implement store() method using CreateCompanyAction | | |
| TASK-058 | Implement show() method to retrieve single company with relationships | | |
| TASK-059 | Implement update() method using UpdateCompanyAction | | |
| TASK-060 | Implement destroy() method using DeleteCompanyAction | | |
| TASK-061 | Implement children() method to retrieve child companies | | |
| TASK-062 | Create routes in routes/api.php under /api/v1/backoffice/companies prefix | | |
| TASK-063 | Apply auth:sanctum middleware to all company routes | | |
| TASK-064 | Apply can:manage-companies middleware to modification routes | | |
| **Request Validation** | | | |
| TASK-065 | Create StoreCompanyRequest in app/Http/Requests/Backoffice/StoreCompanyRequest.php | | |
| TASK-066 | Define validation rules for required fields: name, registration_number | | |
| TASK-067 | Add validation rules for optional fields: parent_id, tax_id, logo_path | | |
| TASK-068 | Implement authorize() method with policy check | | |
| TASK-069 | Create UpdateCompanyRequest in app/Http/Requests/Backoffice/UpdateCompanyRequest.php | | |
| TASK-070 | Define validation rules allowing partial updates | | |
| TASK-071 | Add custom validation rule to prevent circular parent relationships | | |
| TASK-072 | Implement messages() method for custom validation messages | | |
| **API Resources** | | | |
| TASK-073 | Create CompanyResource in app/Http/Resources/Backoffice/CompanyResource.php | | |
| TASK-074 | Define toArray() method returning company attributes | | |
| TASK-075 | Include parent company data conditionally using whenLoaded() | | |
| TASK-076 | Include children count using $this->children()->count() | | |
| TASK-077 | Add HATEOAS links for self and related resources | | |
| TASK-078 | Create CompanyCollection resource for paginated lists | | |
| **Authorization Policies** | | | |
| TASK-079 | Create CompanyPolicy in app/Domains/Backoffice/Policies/CompanyPolicy.php | | |
| TASK-080 | Implement viewAny() method checking manage-companies permission | | |
| TASK-081 | Implement view() method checking tenant ownership | | |
| TASK-082 | Implement create() method checking manage-companies permission | | |
| TASK-083 | Implement update() method checking both permission and tenant ownership | | |
| TASK-084 | Implement delete() method with additional checks for child companies | | |
| TASK-085 | Register CompanyPolicy in AuthServiceProvider | | |

### Implementation Phase 5: CLI Commands

- GOAL-005: Create artisan commands for company management operations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-086 | Create CreateCompanyCommand in app/Console/Commands/Backoffice/CreateCompanyCommand.php | | |
| TASK-087 | Implement handle() method with interactive prompts for company data | | |
| TASK-088 | Add options for tenant-id, name, parent-id, registration-number | | |
| TASK-089 | Create ListCompaniesCommand in app/Console/Commands/Backoffice/ListCompaniesCommand.php | | |
| TASK-090 | Implement handle() method displaying companies in table format | | |
| TASK-091 | Add options for filtering by tenant, status, parent company | | |
| TASK-092 | Create CompanyHierarchyCommand showing tree structure | | |
| TASK-093 | Register commands in app/Console/Kernel.php | | |

### Implementation Phase 6: Testing & Verification


- GOAL-006: Create comprehensive test suite for company management system

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| **Unit Tests** | | | |
| TASK-094 | Create CreateCompanyActionTest in tests/Unit/Domains/Backoffice/Actions/CreateCompanyActionTest.php | | |
| TASK-095 | Test CreateCompanyAction successfully creates company with valid data | | |
| TASK-096 | Test CreateCompanyAction validates required fields (name, registration_number) | | |
| TASK-097 | Test CreateCompanyAction automatically assigns tenant_id from context | | |
| TASK-098 | Test CreateCompanyAction creates audit log entry on creation | | |
| TASK-099 | Create UpdateCompanyActionTest and test update operations | | |
| TASK-100 | Test UpdateCompanyAction prevents circular parent relationships | | |
| TASK-101 | Create DeleteCompanyActionTest and test deletion with child company checks | | |
| TASK-102 | Create CompanyRepositoryTest in tests/Unit/Domains/Backoffice/Repositories/CompanyRepositoryTest.php | | |
| TASK-103 | Test CompanyRepository getHierarchy() returns correct tree structure | | |
| TASK-104 | Test CompanyRepository findByTenantId() filters by tenant correctly | | |
| **Feature Tests - API** | | | |
| TASK-105 | Create CompanyControllerTest in tests/Feature/Api/V1/Backoffice/CompanyControllerTest.php | | |
| TASK-106 | Test POST /api/v1/backoffice/companies creates company and returns 201 | | |
| TASK-107 | Test GET /api/v1/backoffice/companies lists companies with pagination | | |
| TASK-108 | Test GET /api/v1/backoffice/companies/{id} returns single company with 200 | | |
| TASK-109 | Test PATCH /api/v1/backoffice/companies/{id} updates company and returns 200 | | |
| TASK-110 | Test DELETE /api/v1/backoffice/companies/{id} soft deletes company and returns 204 | | |
| TASK-111 | Test GET /api/v1/backoffice/companies/{id}/children returns child companies | | |
| TASK-112 | Test API enforces authentication with 401 for unauthenticated requests | | |
| TASK-113 | Test API enforces authorization with 403 for unauthorized users | | |
| TASK-114 | Test API prevents cross-tenant access returning 404 for other tenant's companies | | |
| TASK-115 | Test API validates request data returning 422 for invalid input | | |
| **Feature Tests - CLI** | | | |
| TASK-116 | Create CreateCompanyCommandTest in tests/Feature/Console/Commands/CreateCompanyCommandTest.php | | |
| TASK-117 | Test CLI command creates company successfully via artisan | | |
| **Integration Tests** | | | |
| TASK-118 | Test creating company triggers CompanyCreatedEvent | | |
| TASK-119 | Test updating company triggers CompanyUpdatedEvent | | |
| TASK-120 | Test deleting company triggers CompanyDeletedEvent | | |
| TASK-121 | Test company creation is logged in activity log | | |
| TASK-122 | Test company hierarchy query with 3 levels completes in under 50ms | | |
| TASK-123 | Test BelongsToTenant trait automatically filters companies by tenant | | |
| TASK-124 | Test package Company model can be extended without breaking functionality | | |
| TASK-125 | Test company with logo uploads and stores file correctly | | |
| TASK-126 | Test parent-child relationships maintain referential integrity | | |
| **Test Setup** | | | |
| TASK-127 | Create CompanyFactory in database/factories/CompanyFactory.php for test data generation | | |

## 3. Alternatives Considered

**ALT-001: Custom Company Model vs Package Integration**
- **Approach**: Build company management from scratch without package
- **Rejected**: Package provides well-tested hierarchy management and reduces development time
- **Rationale**: Package integration ensures consistency and leverages existing functionality

**ALT-002: Adjacency List vs Nested Set for Hierarchy**
- **Approach**: Use nested set (left/right values) for hierarchy instead of adjacency list
- **Selected**: Follow package's default approach (likely adjacency list with closure table)
- **Rationale**: Package handles hierarchy efficiently; custom optimization not needed for MVP

**ALT-003: Single Company Table vs Separate Entity Types**
- **Approach**: Separate tables for different company types (HQ, subsidiary, branch)
- **Rejected**: Single table with type field provides flexibility and simplifies queries
- **Rationale**: Polymorphic approach adds unnecessary complexity for current requirements

## 4. Dependencies

**Internal Dependencies:**
- **DEP-001**: Core.001 - Multi-Tenancy System (tenant_id foreign key, BelongsToTenant trait)
- **DEP-002**: Core.002 - Authentication & Authorization (Sanctum, Spatie Permission)
- **DEP-003**: Core.003 - Audit Logging (LogsActivity trait, activity log queries)

**External Package Dependencies:**
- **DEP-004**: `azaharizaman/laravel-backoffice: dev-main` - Base package for Company model
- **DEP-005**: `spatie/laravel-activitylog: ^4.0` - Audit logging functionality
- **DEP-006**: `lorisleiva/laravel-actions: ^2.0` - Action pattern implementation

**Why dev-main stability?**
The `azaharizaman/laravel-backoffice` package is an internal package under active development alongside this ERP system. Using `dev-main` allows us to track the latest features and fixes during the development phase. Once the package and ERP system stabilize, we'll transition to tagged releases for production deployments.

## 5. Files

**New Files to Create:**

- **FILE-001**: `app/Domains/Backoffice/Models/Company.php` - Extended Company model
- **FILE-002**: `app/Domains/Backoffice/Contracts/CompanyRepositoryInterface.php` - Repository interface
- **FILE-003**: `app/Domains/Backoffice/Repositories/CompanyRepository.php` - Repository implementation
- **FILE-004**: `app/Domains/Backoffice/Actions/CreateCompanyAction.php` - Create action
- **FILE-005**: `app/Domains/Backoffice/Actions/UpdateCompanyAction.php` - Update action
- **FILE-006**: `app/Domains/Backoffice/Actions/DeleteCompanyAction.php` - Delete action
- **FILE-007**: `app/Domains/Backoffice/Actions/GetCompanyHierarchyAction.php` - Hierarchy action
- **FILE-008**: `app/Http/Controllers/Api/V1/Backoffice/CompanyController.php` - API controller
- **FILE-009**: `app/Http/Requests/Backoffice/StoreCompanyRequest.php` - Store validation
- **FILE-010**: `app/Http/Requests/Backoffice/UpdateCompanyRequest.php` - Update validation
- **FILE-011**: `app/Http/Resources/Backoffice/CompanyResource.php` - API resource
- **FILE-012**: `app/Http/Resources/Backoffice/CompanyCollection.php` - Collection resource
- **FILE-013**: `app/Domains/Backoffice/Policies/CompanyPolicy.php` - Authorization policy
- **FILE-014**: `app/Console/Commands/Backoffice/CreateCompanyCommand.php` - Create CLI command
- **FILE-015**: `app/Console/Commands/Backoffice/ListCompaniesCommand.php` - List CLI command
- **FILE-016**: `app/Console/Commands/Backoffice/CompanyHierarchyCommand.php` - Hierarchy CLI command
- **FILE-017**: `app/Domains/Backoffice/Events/CompanyCreatedEvent.php` - Created event
- **FILE-018**: `app/Domains/Backoffice/Events/CompanyUpdatedEvent.php` - Updated event
- **FILE-019**: `app/Domains/Backoffice/Events/CompanyDeletedEvent.php` - Deleted event
- **FILE-020**: `app/Domains/Backoffice/Listeners/LogCompanyActivityListener.php` - Activity listener
- **FILE-021**: `database/migrations/yyyy_mm_dd_hhmmss_add_tenant_to_companies_table.php` - Tenant migration
- **FILE-022**: `database/migrations/yyyy_mm_dd_hhmmss_add_erp_fields_to_companies_table.php` - ERP fields migration

**Modified Files:**

- **FILE-023**: `composer.json` - Add laravel-backoffice package dependency
- **FILE-024**: `routes/api.php` - Add company API routes
- **FILE-025**: `app/Providers/AuthServiceProvider.php` - Register CompanyPolicy
- **FILE-026**: `app/Providers/BackofficeServiceProvider.php` - Register repository bindings (create if not exists)
- **FILE-027**: `config/app.php` - Register BackofficeServiceProvider
- **FILE-028**: `app/Providers/EventServiceProvider.php` - Register company events and listeners

**Test Files:**

- **FILE-029**: `tests/Unit/Domains/Backoffice/Actions/CreateCompanyActionTest.php`
- **FILE-030**: `tests/Unit/Domains/Backoffice/Actions/UpdateCompanyActionTest.php`
- **FILE-031**: `tests/Unit/Domains/Backoffice/Actions/DeleteCompanyActionTest.php`
- **FILE-032**: `tests/Unit/Domains/Backoffice/Repositories/CompanyRepositoryTest.php`
- **FILE-033**: `tests/Feature/Api/V1/Backoffice/CompanyControllerTest.php`
- **FILE-034**: `tests/Feature/Console/Commands/CreateCompanyCommandTest.php`
- **FILE-035**: `database/factories/CompanyFactory.php` - Factory for testing

## 6. Testing

**Unit Tests:**

- **TEST-001**: CreateCompanyAction successfully creates company with valid data
- **TEST-002**: CreateCompanyAction validates required fields (name, registration_number)
- **TEST-003**: CreateCompanyAction automatically assigns tenant_id from context
- **TEST-004**: CreateCompanyAction creates audit log entry on creation
- **TEST-005**: UpdateCompanyAction updates company data correctly
- **TEST-006**: UpdateCompanyAction prevents circular parent relationships
- **TEST-007**: DeleteCompanyAction prevents deletion of company with child companies
- **TEST-008**: DeleteCompanyAction soft deletes company successfully
- **TEST-009**: CompanyRepository getHierarchy() returns correct tree structure
- **TEST-010**: CompanyRepository findByTenantId() filters by tenant correctly

**Feature Tests:**

- **TEST-011**: POST /api/v1/backoffice/companies creates company and returns 201
- **TEST-012**: GET /api/v1/backoffice/companies lists companies with pagination
- **TEST-013**: GET /api/v1/backoffice/companies/{id} returns single company with 200
- **TEST-014**: PATCH /api/v1/backoffice/companies/{id} updates company and returns 200
- **TEST-015**: DELETE /api/v1/backoffice/companies/{id} soft deletes company and returns 204
- **TEST-016**: GET /api/v1/backoffice/companies/{id}/children returns child companies
- **TEST-017**: API enforces authentication with 401 for unauthenticated requests
- **TEST-018**: API enforces authorization with 403 for unauthorized users
- **TEST-019**: API prevents cross-tenant access returning 404 for other tenant's companies
- **TEST-020**: API validates request data returning 422 for invalid input

**Integration Tests:**

- **TEST-021**: Creating company triggers CompanyCreatedEvent
- **TEST-022**: Updating company triggers CompanyUpdatedEvent
- **TEST-023**: Deleting company triggers CompanyDeletedEvent
- **TEST-024**: Company creation is logged in activity log
- **TEST-025**: Company hierarchy query with 3 levels completes in under 50ms
- **TEST-026**: CLI command creates company successfully via artisan
- **TEST-027**: BelongsToTenant trait automatically filters companies by tenant
- **TEST-028**: Package Company model can be extended without breaking functionality
- **TEST-029**: Company with logo uploads and stores file correctly
- **TEST-030**: Parent-child relationships maintain referential integrity

## 7. Risks & Assumptions

**Risks:**

- **RISK-001**: Package API changes breaking compatibility
  - **Mitigation**: Pin to dev-main and monitor package releases, use integration tests
  - **Likelihood**: Medium
  - **Impact**: High

- **RISK-002**: Performance degradation with deep company hierarchies (>10 levels)
  - **Mitigation**: Implement query optimization, add indexes, consider closure table
  - **Likelihood**: Low
  - **Impact**: Medium

- **RISK-003**: Circular reference creation in parent-child relationships
  - **Mitigation**: Implement validation logic preventing circular references
  - **Likelihood**: Low
  - **Impact**: High

- **RISK-004**: Data migration complexity when transitioning from package to custom implementation
  - **Mitigation**: Keep extension minimal, follow package patterns closely
  - **Likelihood**: Low
  - **Impact**: High

**Assumptions:**

- **ASSUMPTION-001**: Package provides adequate hierarchy management for MVP requirements
- **ASSUMPTION-002**: Maximum company hierarchy depth of 5 levels is sufficient
- **ASSUMPTION-003**: Package supports soft deletes for companies
- **ASSUMPTION-004**: Tenant isolation can be added via trait without modifying package code
- **ASSUMPTION-005**: Average tenant will have fewer than 50 companies
- **ASSUMPTION-006**: Package documentation is available and up-to-date
- **ASSUMPTION-007**: Package includes migration files for database schema

## 8. Related Specifications

**Related Implementation Plans:**
- [PRD-01: Multi-Tenancy System](./PRD-01-infrastructure-multitenancy-1.md) - Tenant isolation foundation
- [PRD-02: Authentication & Authorization](./PRD-02-infrastructure-auth-1.md) - Security and permissions
- [PRD-03: Audit Logging System](./PRD-03-infrastructure-audit-1.md) - Activity logging
- [PRD-07: Office Management](./PRD-07-feature-office-management-1.md) - Office-company relationships
- [PRD-08: Department Management](./PRD-08-feature-department-management-1.md) - Department-company relationships
- [PRD-09: Staff Management](./PRD-09-feature-staff-management-1.md) - Staff-company assignments

**Source Requirements:**
- [PHASE-1-MVP.md](../docs/prd/PHASE-1-MVP.md) - Section: Backoffice.001: Company Management

**Development Guidelines:**
- [MODULE-DEVELOPMENT.md](../docs/prd/MODULE-DEVELOPMENT.md) - Module development standards
- [GitHub Copilot Instructions](../.github/copilot-instructions.md) - Coding standards and patterns

---

**Version:** 1.0  
**Status:** Planned  
**Last Updated:** 2025-11-09
