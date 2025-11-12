# PRD01-SUB15-PLAN01: Implement Backoffice Organizational Foundation

**Related PRD:** [PRD01-SUB15-BACKOFFICE.md](../prd/prd-01/PRD01-SUB15-BACKOFFICE.md)  
**Plan Type:** Implementation Plan  
**Status:** ![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)  
**Version:** 1.0.0  
**Created:** November 13, 2025  
**Milestone:** MILESTONE 3  

---

## 1. Executive Summary

This implementation plan covers the foundational components of the Backoffice module, including company master data management, organizational hierarchy (branches, departments, cost centers) with closure table pattern for efficient queries, and core data structures. This plan establishes the essential building blocks that support all other transactional modules requiring organizational context, period validation, and hierarchical reporting.

**Key Features Delivered:**
- Company master data (registration, tax IDs, addresses, bank accounts)
- Organizational hierarchy with closure table pattern (< 100ms queries for 1000+ entities)
- Branch/office management with multi-level relationships
- Department and cost center hierarchy for expense allocation
- Base repository and service infrastructure for backoffice operations

**Business Impact:**
- Enables multi-entity accounting and reporting
- Supports hierarchical authorization and access control
- Provides foundation for HCM department assignments (IR-BO-003)
- Establishes organizational structure for downstream module integration

---

## 2. Goals & Requirements Coverage

### GOAL 1: Company Master Data Management

**Objective:** Implement complete company master data lifecycle with registration, tax IDs, addresses, bank accounts, and base currency configuration.

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| FR-BO-001 | Manage organizational structure (companies component) | Functional |
| FR-BO-004 | Maintain company master data (registration, tax IDs, addresses, bank accounts) | Functional |
| BR-BO-004 | Organizational entities with active transactions cannot be deleted | Business Rule |
| SR-BO-002 | Log all administrative actions | Security |
| DR-BO-001 | Store complete hierarchy path (company as root) | Data |

**Tasks:**
- **TASK-1.1:** Create database migration `2025_01_01_000001_create_companies_table.php` with columns: id (BIGSERIAL), tenant_id (UUID, FK), company_code (VARCHAR 50, unique per tenant), company_name (VARCHAR 255), registration_number (VARCHAR 100, nullable), tax_id (VARCHAR 100, nullable), address (TEXT, nullable), city (VARCHAR 100, nullable), state (VARCHAR 100, nullable), postal_code (VARCHAR 20, nullable), country (VARCHAR 100, nullable), phone (VARCHAR 50, nullable), email (VARCHAR 255), base_currency_code (VARCHAR 10), is_active (BOOLEAN, default TRUE), timestamps, soft deletes
- **TASK-1.2:** Create enum `CompanyStatus` with values: ACTIVE, INACTIVE
- **TASK-1.3:** Create model `packages/backoffice/src/Models/Company.php` with traits: BelongsToTenant, HasActivityLogging, SoftDeletes; fillable fields matching table; casts: base_currency_code → string, is_active → boolean; relationships: organizations (hasMany), fiscalYears (hasMany); scopes: active()
- **TASK-1.4:** Create factory `CompanyFactory.php` with realistic fake data: faker->company, faker->taxId, faker->currencyCode (default 'USD'); states: withInactiveStatus()
- **TASK-1.5:** Create contract `CompanyRepositoryContract.php` with methods: findById(int $id), findByCode(string $code, string $tenantId), create(array $data), update(Company $company, array $data), delete(Company $company), paginate(int $perPage, array $filters), getActiveCompanies(string $tenantId)
- **TASK-1.6:** Implement repository `CompanyRepository.php` implementing CompanyRepositoryContract; include eager loading for optimistic queries; implement filter support for status, search (name, code)
- **TASK-1.7:** Create action `CreateCompanyAction.php` using AsAction trait; inject CompanyRepositoryContract, ActivityLoggerContract; handle() method: validate uniqueness of company_code per tenant, create company, log activity "Company created", dispatch CompanyCreatedEvent; return Company
- **TASK-1.8:** Create action `UpdateCompanyAction.php` with validation for required fields; log activity "Company updated"; dispatch CompanyUpdatedEvent
- **TASK-1.9:** Create action `DeleteCompanyAction.php` with validation: check for active transactions (fiscal years, organizations); if found, throw ValidationException "Cannot delete company with active transactions"; soft delete; log activity "Company deleted"
- **TASK-1.10:** Create event `CompanyCreatedEvent` with properties: Company $company, User $createdBy
- **TASK-1.11:** Create event `CompanyUpdatedEvent` with properties: Company $company, array $changes, User $updatedBy
- **TASK-1.12:** Create policy `CompanyPolicy.php` with methods: viewAny(User $user), view(User $user, Company $company), create(User $user), update(User $user, Company $company), delete(User $user, Company $company); authorization: require 'manage-companies' permission; apply tenant scope
- **TASK-1.13:** Create API controller `CompanyController.php` with routes: index (GET /backoffice/companies), store (POST /backoffice/companies), show (GET /backoffice/companies/{id}), update (PATCH /backoffice/companies/{id}), destroy (DELETE /backoffice/companies/{id}); inject CompanyRepositoryContract; authorize actions; validate input; return CompanyResource
- **TASK-1.14:** Create form request `StoreCompanyRequest.php` with validation: company_code (required, max:50, unique per tenant), company_name (required, max:255), registration_number (nullable, max:100), tax_id (nullable, max:100), email (nullable, email), base_currency_code (required, size:3)
- **TASK-1.15:** Create form request `UpdateCompanyRequest.php` extending StoreCompanyRequest; make company_code unique excluding current record
- **TASK-1.16:** Create API resource `CompanyResource.php` transforming company to JSON with all fields; include relationships: fiscalYears count, organizations count
- **TASK-1.17:** Write unit test `CompanyTest.php`: test company_code uniqueness per tenant, test soft delete, test cannot delete with active organizations
- **TASK-1.18:** Write feature test `CompanyManagementTest.php`: test complete CRUD via API, test authorization checks, test validation errors

**Test Coverage:** 18 tests (8 unit, 10 feature)

---

### GOAL 2: Organizational Hierarchy with Closure Table

**Objective:** Implement organizational hierarchy (branches, departments, cost centers) using closure table pattern for efficient hierarchy queries (< 100ms for 1000+ entities as per PR-BO-001).

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| FR-BO-001 | Manage organizational structure (full hierarchy) | Functional |
| FR-BO-005 | Manage branch/office hierarchy with multi-level relationships | Functional |
| FR-BO-006 | Support department and cost center hierarchy for expense allocation | Functional |
| PR-BO-001 | Organizational hierarchy queries must complete in < 100ms for 1000+ entities | Performance |
| ARCH-BO-001 | Use nested set model or closure table for efficient hierarchy queries | Architecture |
| DR-BO-001 | Store complete hierarchy path for efficient organizational queries | Data |
| BR-BO-004 | Organizational entities with active transactions cannot be deleted | Business Rule |

**Tasks:**
- **TASK-2.1:** Create migration `2025_01_01_000004_create_organizations_table.php` with columns: id (BIGSERIAL), tenant_id (UUID, FK), organization_code (VARCHAR 50, unique per tenant), organization_name (VARCHAR 255), organization_type (VARCHAR 50: 'branch', 'department', 'cost_center'), parent_id (BIGINT, nullable, self-FK), company_id (BIGINT, FK companies), manager_id (UUID, nullable, FK employees), is_active (BOOLEAN, default TRUE), timestamps, soft deletes; indexes: tenant_id, parent_id, company_id
- **TASK-2.2:** Create migration `2025_01_01_000005_create_organization_hierarchy_table.php` (closure table) with columns: ancestor_id (BIGINT, FK organizations, cascade delete), descendant_id (BIGINT, FK organizations, cascade delete), depth (INT); composite PRIMARY KEY (ancestor_id, descendant_id); indexes: ancestor_id, descendant_id, depth
- **TASK-2.3:** Create enum `OrganizationType` with values: BRANCH, DEPARTMENT, COST_CENTER
- **TASK-2.4:** Create model `Organization.php` with traits: BelongsToTenant, HasActivityLogging, SoftDeletes; fillable fields matching table; casts: organization_type → OrganizationType enum, is_active → boolean; relationships: company (belongsTo), parent (belongsTo Organization), children (hasMany Organization), manager (belongsTo Employee); scopes: ofType(OrganizationType $type), active()
- **TASK-2.5:** Create model `OrganizationHierarchy.php` (closure table model) with columns: ancestor_id, descendant_id, depth; relationships: ancestor (belongsTo Organization), descendant (belongsTo Organization)
- **TASK-2.6:** Create factory `OrganizationFactory.php` with realistic data; states: asBranch(), asDepartment(), asCostCenter(), withParent(Organization $parent), withManager(Employee $manager)
- **TASK-2.7:** Create observer `OrganizationObserver.php` with creating() method: validate parent exists and belongs to same company; created() method: call rebuildHierarchy() to update closure table; updated() method: if parent_id changed, call rebuildHierarchy(); deleting() method: check for children, check for active transactions (throw ValidationException if found)
- **TASK-2.8:** Create service `OrganizationHierarchyService.php` with methods: rebuildHierarchy(Organization $org) - regenerate closure table entries for organization and descendants using recursive SQL; getAncestors(Organization $org) - query closure table where descendant_id = org->id order by depth desc; getDescendants(Organization $org) - query closure table where ancestor_id = org->id order by depth asc; getHierarchyTree(Organization $root) - build nested array structure; getPath(Organization $org) - get full path from root to org
- **TASK-2.9:** Create contract `OrganizationRepositoryContract.php` with methods: findById(int $id), findByCode(string $code, string $tenantId), create(array $data), update(Organization $org, array $data), delete(Organization $org), getByCompany(int $companyId), getByType(OrganizationType $type), getChildren(int $parentId), getHierarchyTree(int $rootId), paginate(int $perPage, array $filters)
- **TASK-2.10:** Implement repository `OrganizationRepository.php` implementing contract; optimize getHierarchyTree() using closure table with single query and depth-based grouping
- **TASK-2.11:** Create action `CreateOrganizationAction.php` using AsAction; inject OrganizationRepositoryContract, OrganizationHierarchyService; validate: organization_code unique per tenant, parent exists and belongs to same company, manager exists (if provided); create organization; rebuild hierarchy; log activity "Organization created"; dispatch OrganizationCreatedEvent
- **TASK-2.12:** Create action `UpdateOrganizationAction.php` with validation for parent change (prevent circular reference, ensure same company); update organization; rebuild hierarchy if parent changed; log activity "Organization updated"; dispatch OrganizationUpdatedEvent
- **TASK-2.13:** Create action `MoveOrganizationAction.php` for changing parent; validate new parent is not a descendant (prevent cycles); update parent_id; rebuild hierarchy; dispatch OrganizationMovedEvent
- **TASK-2.14:** Create action `DeleteOrganizationAction.php` with validation: check for children, check for active transactions (employees, transactions); soft delete; remove from hierarchy closure table; log activity
- **TASK-2.15:** Create event `OrganizationCreatedEvent` with properties: Organization $organization, User $createdBy
- **TASK-2.16:** Create event `OrganizationUpdatedEvent` with properties: Organization $organization, ?int $oldParentId, User $updatedBy
- **TASK-2.17:** Create event `OrganizationMovedEvent` with properties: Organization $organization, int $oldParentId, int $newParentId
- **TASK-2.18:** Create policy `OrganizationPolicy.php` with authorization methods requiring 'manage-organizations' permission
- **TASK-2.19:** Create API controller `OrganizationController.php` with routes: index, store, show, update (including move via parent_id), destroy; additional route: GET /organizations/{id}/hierarchy for tree structure; inject OrganizationRepositoryContract, OrganizationHierarchyService
- **TASK-2.20:** Create form request `StoreOrganizationRequest.php` with validation: organization_code (required, unique per tenant), organization_name (required), organization_type (required, in OrganizationType values), parent_id (nullable, exists:organizations), company_id (required, exists:companies), manager_id (nullable, exists:employees)
- **TASK-2.21:** Create form request `UpdateOrganizationRequest.php` extending StoreOrganizationRequest
- **TASK-2.22:** Create API resource `OrganizationResource.php` with fields: id, code, name, type, parent (nested resource), children count, manager details
- **TASK-2.23:** Create API resource `OrganizationHierarchyResource.php` for nested tree structure with recursive children
- **TASK-2.24:** Write unit test `OrganizationHierarchyServiceTest.php`: test rebuildHierarchy() creates correct closure table entries, test getAncestors() returns correct path, test getDescendants() returns subtree, test circular reference prevention
- **TASK-2.25:** Write unit test `OrganizationTest.php`: test parent-child relationships, test cannot delete with children, test organization_type enum
- **TASK-2.26:** Write feature test `OrganizationHierarchyTest.php`: test complete hierarchy creation via API, test moving organization to new parent, test hierarchy tree retrieval
- **TASK-2.27:** Write performance test: create 1000 organizations in hierarchy, measure getDescendants() query time, assert < 100ms (PR-BO-001)

**Test Coverage:** 27 tests (12 unit, 12 feature, 2 integration, 1 performance)

---

### GOAL 3: Repository & Service Infrastructure

**Objective:** Establish core repository pattern, service infrastructure, and data access layer for backoffice operations with proper contract-driven architecture.

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| ARCH-BO-001 | Use closure table for efficient hierarchy queries | Architecture |
| ARCH-BO-002 | Cache current period status in Redis (base caching support) | Architecture |
| IR-BO-002 | Provide organizational hierarchy API for authorization and reporting | Integration |
| SR-BO-002 | Log all administrative actions | Security |

**Tasks:**
- **TASK-3.1:** Create service provider `BackofficeServiceProvider.php` in packages/backoffice/src/; register service container bindings: CompanyRepositoryContract → CompanyRepository, OrganizationRepositoryContract → OrganizationRepository, bind OrganizationHierarchyService; register routes from routes/api.php; register migrations; register config; register observers; register policies
- **TASK-3.2:** Create base service `BaseBackofficeService.php` with common methods: validateTenantScope(Model $model), logActivity(string $description, Model $subject), getCacheKey(string $prefix, string $identifier)
- **TASK-3.3:** Create config file `packages/backoffice/config/backoffice.php` with settings: cache_ttl (default 900 seconds for hierarchy), hierarchy_max_depth (default 10), enable_activity_logging (default true), document_number_padding (default 6)
- **TASK-3.4:** Create base policy `BaseBackofficePolicy.php` extending Laravel policy with shared authorization logic: tenant scope validation, admin bypass via Gate::before()
- **TASK-3.5:** Create base request `BaseBackofficeRequest.php` extending FormRequest with shared validation rules and tenant scope validation
- **TASK-3.6:** Create base resource `BaseBackofficeResource.php` extending JsonResource with common resource transformations: timestamps, soft delete status
- **TASK-3.7:** Create trait `HasOrganization.php` for models that belong to an organization; add organization_id field, organization() relationship, scopeForOrganization(Builder $query, int $orgId)
- **TASK-3.8:** Create middleware `ValidateBackofficeAccess.php` to check user has required backoffice permissions; throw 403 if unauthorized
- **TASK-3.9:** Register routes in `packages/backoffice/routes/api.php` with prefix '/backoffice', middleware: ['auth:sanctum', 'tenant', 'validate-backoffice-access']; group companies and organizations routes
- **TASK-3.10:** Create README.md for backoffice package with installation instructions, usage examples, configuration options
- **TASK-3.11:** Create composer.json for backoffice package with metadata: name "azaharizaman/laravel-backoffice", namespace "Azaharizaman\\Erp\\Backoffice", require: php ^8.2, laravel/framework ^12.0, lorisleiva/laravel-actions ^2.0; autoload PSR-4
- **TASK-3.12:** Write unit test `BackofficeServiceProviderTest.php`: test bindings are registered, test routes are loaded, test config is published
- **TASK-3.13:** Write feature test `BackofficeMiddlewareTest.php`: test middleware blocks unauthorized users, test middleware allows users with permissions

**Test Coverage:** 13 tests (3 unit, 10 feature)

---

### GOAL 4: Integration with Core Modules

**Objective:** Implement listeners and integration points for core module events (multi-tenancy, authentication, HCM) and provide organizational hierarchy API for external consumption.

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| IR-BO-002 | Provide organizational hierarchy API for authorization and reporting | Integration |
| IR-BO-003 | Integrate with HCM for employee-department assignments | Integration |
| EV-BO-003 | OrganizationUpdatedEvent when organizational structure changes | Event |
| SR-BO-002 | Log all administrative actions | Security |

**Tasks:**
- **TASK-4.1:** Create listener `InitializeTenantBackofficeListener.php` listening to TenantCreatedEvent (SUB01); handle() method: create default company with company_code = tenant->code, company_name = tenant->name, base_currency_code = 'USD'; log activity "Default company created for tenant"
- **TASK-4.2:** Create service `OrganizationQueryService.php` with methods: getOrganizationsByEmployee(Employee $employee) - retrieve all organizations employee belongs to; validateEmployeeOrganization(Employee $employee, Organization $org) - check employee can access organization; getOrganizationsForUser(User $user) - retrieve organizations user has access to based on roles
- **TASK-4.3:** Create API endpoint `GET /api/v1/backoffice/organizations/user-access` to return organizations current authenticated user can access; inject OrganizationQueryService; return OrganizationResource collection
- **TASK-4.4:** Create API endpoint `GET /api/v1/backoffice/organizations/{id}/employees` to list employees assigned to organization; integrate with HCM Employee model (when available); implement pagination
- **TASK-4.5:** Create event listener `SyncEmployeeOrganizationListener.php` listening to EmployeeTransferredEvent (SUB13); handle() method: update employee->department_id to new organization; log activity "Employee department updated"
- **TASK-4.6:** Create helper function `getOrganizationPath(Organization $org)` returning formatted path string (e.g., "Company / Branch / Department") using OrganizationHierarchyService->getPath()
- **TASK-4.7:** Create helper function `hasOrganizationAccess(User $user, Organization $org)` checking if user has permission to access organization based on hierarchy
- **TASK-4.8:** Create seeder `BackofficeSeeder.php` to seed sample companies and organizational hierarchy for development/testing; call from DatabaseSeeder
- **TASK-4.9:** Create API documentation in README.md: document all endpoints with request/response examples, document integration points with other modules
- **TASK-4.10:** Write integration test `TenantBackofficeIntegrationTest.php`: test default company created when tenant created, test organization hierarchy accessible after creation
- **TASK-4.11:** Write integration test `BackofficeHCMIntegrationTest.php`: test employee-department assignment (mock HCM), test organization query service returns correct organizations for employee
- **TASK-4.12:** Write feature test `OrganizationAccessTest.php`: test user can only access authorized organizations, test organization hierarchy respects tenant scope

**Test Coverage:** 12 tests (4 integration, 8 feature)

---

### GOAL 5: Testing, Documentation & Deployment

**Objective:** Establish comprehensive test coverage (minimum 80%), complete documentation, and deployment readiness for backoffice organizational foundation.

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| PR-BO-001 | Organizational hierarchy queries < 100ms for 1000+ entities | Performance |
| SCR-BO-001 | Support 10,000+ organizational entities per tenant | Scalability |
| SR-BO-001 | Role-based access to administrative functions | Security |
| SR-BO-002 | Log all administrative actions | Security |

**Tasks:**
- **TASK-5.1:** Write unit tests for all models: Company, Organization, OrganizationHierarchy; test relationships, scopes, casts, factories
- **TASK-5.2:** Write unit tests for all actions: CreateCompanyAction, UpdateCompanyAction, DeleteCompanyAction, CreateOrganizationAction, UpdateOrganizationAction, MoveOrganizationAction, DeleteOrganizationAction; mock repository dependencies
- **TASK-5.3:** Write unit tests for OrganizationHierarchyService: test all public methods with various hierarchy structures; test edge cases (single node, deep hierarchy, wide hierarchy)
- **TASK-5.4:** Write feature tests for complete API workflows: company CRUD, organization CRUD, hierarchy operations; test all validation rules
- **TASK-5.5:** Write integration tests for cross-module integration: tenant initialization, HCM integration (mock), authorization integration
- **TASK-5.6:** Write performance test `OrganizationHierarchyPerformanceTest.php`: seed 10,000 organizations in multi-level hierarchy; measure query performance for getDescendants(), getAncestors(), getHierarchyTree(); assert all queries < 100ms (PR-BO-001); assert memory usage < 256MB
- **TASK-5.7:** Set up Pest configuration in packages/backoffice/tests/Pest.php; configure RefreshDatabase, tenant seeding, authentication helpers
- **TASK-5.8:** Achieve minimum 80% code coverage across all source files; run `./vendor/bin/pest --coverage` and verify; add additional tests for uncovered lines
- **TASK-5.9:** Create migration guide in docs/migrations/backoffice-setup.md: installation steps, initial company setup, organizational hierarchy setup, permission configuration
- **TASK-5.10:** Create API documentation in docs/api/backoffice-api.md: document all endpoints with OpenAPI 3.0 specification, include request/response examples, document error codes
- **TASK-5.11:** Create admin guide in docs/guides/backoffice-admin-guide.md: company management best practices, organizational hierarchy design patterns, common workflows
- **TASK-5.12:** Update main README.md with backoffice module overview, installation instructions, quick start guide
- **TASK-5.13:** Create CHANGELOG.md for backoffice package tracking all changes by version
- **TASK-5.14:** Tag release v1.0.0 for backoffice package; prepare for Packagist publication (post-MVP)
- **TASK-5.15:** Validate all acceptance criteria from PRD: functional acceptance (6 items), technical acceptance (5 items), security acceptance (4 items), integration acceptance (3 items)
- **TASK-5.16:** Conduct code review: verify PSR-12 compliance via Laravel Pint, verify strict types in all files, verify PHPDoc completeness, verify repository pattern usage
- **TASK-5.17:** Run full test suite: `./vendor/bin/pest packages/backoffice/tests/`; verify all tests pass; fix any failures
- **TASK-5.18:** Deploy to staging environment; perform smoke tests on all API endpoints; verify database migrations run successfully; verify closure table queries perform as expected

**Test Coverage:** 18 tests (6 unit, 6 feature, 4 integration, 1 performance, 1 deployment validation)

---

## 3. Architecture & Design Patterns

### Repository Pattern

All data access must go through repository contracts:

```php
interface CompanyRepositoryContract
{
    public function findById(int $id): ?Company;
    public function findByCode(string $code, string $tenantId): ?Company;
    public function create(array $data): Company;
    public function update(Company $company, array $data): Company;
    public function delete(Company $company): bool;
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function getActiveCompanies(string $tenantId): Collection;
}
```

**Implementation:**
```php
class CompanyRepository implements CompanyRepositoryContract
{
    public function findById(int $id): ?Company
    {
        return Company::find($id);
    }
    
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return Company::query()
            ->when($filters['search'] ?? null, fn($q, $search) => 
                $q->where('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('company_code', 'LIKE', "%{$search}%")
            )
            ->when($filters['is_active'] ?? null, fn($q, $active) => 
                $q->where('is_active', $active)
            )
            ->paginate($perPage);
    }
}
```

### Closure Table Pattern

Efficient hierarchical queries using closure table:

```php
class OrganizationHierarchyService
{
    /**
     * Rebuild closure table entries for organization and descendants
     */
    public function rebuildHierarchy(Organization $org): void
    {
        DB::transaction(function () use ($org) {
            // Delete existing paths for this organization
            OrganizationHierarchy::where('descendant_id', $org->id)->delete();
            
            // Insert self-reference (depth 0)
            OrganizationHierarchy::create([
                'ancestor_id' => $org->id,
                'descendant_id' => $org->id,
                'depth' => 0,
            ]);
            
            // If has parent, copy parent's ancestors and add them as this org's ancestors
            if ($org->parent_id) {
                DB::insert("
                    INSERT INTO organization_hierarchy (ancestor_id, descendant_id, depth)
                    SELECT ancestor_id, ?, depth + 1
                    FROM organization_hierarchy
                    WHERE descendant_id = ?
                ", [$org->id, $org->parent_id]);
            }
            
            // Recursively rebuild for all children
            $org->children->each(fn($child) => $this->rebuildHierarchy($child));
        });
    }
    
    /**
     * Get all ancestors of organization (ordered from root to parent)
     */
    public function getAncestors(Organization $org): Collection
    {
        return Organization::query()
            ->join('organization_hierarchy', 'organizations.id', '=', 'organization_hierarchy.ancestor_id')
            ->where('organization_hierarchy.descendant_id', $org->id)
            ->where('organization_hierarchy.depth', '>', 0)
            ->orderBy('organization_hierarchy.depth', 'desc')
            ->get();
    }
    
    /**
     * Get all descendants of organization (includes self at depth 0)
     */
    public function getDescendants(Organization $org): Collection
    {
        return Organization::query()
            ->join('organization_hierarchy', 'organizations.id', '=', 'organization_hierarchy.descendant_id')
            ->where('organization_hierarchy.ancestor_id', $org->id)
            ->orderBy('organization_hierarchy.depth')
            ->get();
    }
    
    /**
     * Get hierarchy tree as nested array
     */
    public function getHierarchyTree(Organization $root): array
    {
        // Single query with depth for efficient grouping
        $descendants = $this->getDescendants($root);
        
        // Build tree structure using recursive helper
        return $this->buildTree($root, $descendants);
    }
    
    private function buildTree(Organization $parent, Collection $allNodes): array
    {
        $children = $allNodes->filter(fn($node) => $node->parent_id === $parent->id);
        
        return [
            'id' => $parent->id,
            'name' => $parent->organization_name,
            'type' => $parent->organization_type,
            'children' => $children->map(fn($child) => $this->buildTree($child, $allNodes))->toArray(),
        ];
    }
}
```

### Observer Pattern

Automatic closure table updates via observers:

```php
class OrganizationObserver
{
    public function __construct(
        private readonly OrganizationHierarchyService $hierarchyService
    ) {}
    
    public function created(Organization $organization): void
    {
        $this->hierarchyService->rebuildHierarchy($organization);
    }
    
    public function updated(Organization $organization): void
    {
        if ($organization->isDirty('parent_id')) {
            $this->hierarchyService->rebuildHierarchy($organization);
        }
    }
    
    public function deleting(Organization $organization): void
    {
        // Prevent deletion if has children
        if ($organization->children()->count() > 0) {
            throw new \RuntimeException('Cannot delete organization with children');
        }
        
        // Check for active transactions (to be implemented by consuming modules)
        if ($organization->hasActiveTransactions()) {
            throw new \RuntimeException('Cannot delete organization with active transactions');
        }
    }
}
```

### Laravel Actions Pattern

Business logic encapsulated in actions:

```php
class CreateOrganizationAction
{
    use AsAction;
    
    public function __construct(
        private readonly OrganizationRepositoryContract $repository,
        private readonly ActivityLoggerContract $activityLogger
    ) {}
    
    public function handle(array $data): Organization
    {
        // Validation
        $this->validate($data);
        
        // Create organization (observer will rebuild hierarchy)
        $organization = $this->repository->create($data);
        
        // Log activity
        $this->activityLogger->log(
            'Organization created',
            $organization,
            auth()->user()
        );
        
        // Dispatch event
        event(new OrganizationCreatedEvent($organization, auth()->user()));
        
        return $organization;
    }
    
    private function validate(array $data): void
    {
        // Validate parent exists and belongs to same company
        if (isset($data['parent_id'])) {
            $parent = Organization::find($data['parent_id']);
            if (!$parent || $parent->company_id !== $data['company_id']) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Parent must belong to the same company'],
                ]);
            }
        }
    }
}
```

---

## 4. Database Schema & Migrations

### Companies Table

```php
Schema::create('companies', function (Blueprint $table) {
    $table->id();
    $table->uuid('tenant_id');
    $table->string('company_code', 50);
    $table->string('company_name', 255);
    $table->string('registration_number', 100)->nullable();
    $table->string('tax_id', 100)->nullable();
    $table->text('address')->nullable();
    $table->string('city', 100)->nullable();
    $table->string('state', 100)->nullable();
    $table->string('postal_code', 20)->nullable();
    $table->string('country', 100)->nullable();
    $table->string('phone', 50)->nullable();
    $table->string('email', 255)->nullable();
    $table->string('base_currency_code', 10);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
    
    $table->unique(['tenant_id', 'company_code']);
    $table->index('tenant_id');
    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
});
```

### Organizations Table

```php
Schema::create('organizations', function (Blueprint $table) {
    $table->id();
    $table->uuid('tenant_id');
    $table->string('organization_code', 50);
    $table->string('organization_name', 255);
    $table->string('organization_type', 50); // branch, department, cost_center
    $table->unsignedBigInteger('parent_id')->nullable();
    $table->foreignId('company_id')->constrained('companies');
    $table->uuid('manager_id')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
    
    $table->unique(['tenant_id', 'organization_code']);
    $table->index('tenant_id');
    $table->index('parent_id');
    $table->index('company_id');
    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
    $table->foreign('parent_id')->references('id')->on('organizations')->onDelete('restrict');
    $table->foreign('manager_id')->references('id')->on('employees')->onDelete('set null');
});
```

### Organization Hierarchy Table (Closure Table)

```php
Schema::create('organization_hierarchy', function (Blueprint $table) {
    $table->unsignedBigInteger('ancestor_id');
    $table->unsignedBigInteger('descendant_id');
    $table->integer('depth');
    
    $table->primary(['ancestor_id', 'descendant_id']);
    $table->index('ancestor_id');
    $table->index('descendant_id');
    $table->index('depth');
    
    $table->foreign('ancestor_id')->references('id')->on('organizations')->onDelete('cascade');
    $table->foreign('descendant_id')->references('id')->on('organizations')->onDelete('cascade');
});
```

---

## 5. API Endpoints

### Company Endpoints

```
GET    /api/v1/backoffice/companies              # List companies
POST   /api/v1/backoffice/companies              # Create company
GET    /api/v1/backoffice/companies/{id}         # Get company details
PATCH  /api/v1/backoffice/companies/{id}         # Update company
DELETE /api/v1/backoffice/companies/{id}         # Delete company
```

### Organization Endpoints

```
GET    /api/v1/backoffice/organizations                  # List organizations
POST   /api/v1/backoffice/organizations                  # Create organization
GET    /api/v1/backoffice/organizations/{id}             # Get organization details
GET    /api/v1/backoffice/organizations/{id}/hierarchy   # Get hierarchy tree
GET    /api/v1/backoffice/organizations/{id}/employees   # List employees in org
GET    /api/v1/backoffice/organizations/user-access      # User's accessible orgs
PATCH  /api/v1/backoffice/organizations/{id}             # Update organization
DELETE /api/v1/backoffice/organizations/{id}             # Delete organization
```

**Example Request - Create Organization:**
```json
POST /api/v1/backoffice/organizations
{
  "organization_code": "DEPT-IT",
  "organization_name": "Information Technology",
  "organization_type": "department",
  "company_id": 1,
  "parent_id": 2,
  "manager_id": "uuid-here"
}
```

**Example Response:**
```json
{
  "data": {
    "id": 5,
    "organization_code": "DEPT-IT",
    "organization_name": "Information Technology",
    "organization_type": "department",
    "parent": {
      "id": 2,
      "name": "Operations Branch"
    },
    "company": {
      "id": 1,
      "name": "Acme Corporation"
    },
    "manager": {
      "id": "uuid-here",
      "name": "John Doe"
    },
    "children_count": 0,
    "is_active": true,
    "created_at": "2025-01-15T10:00:00Z"
  }
}
```

---

## 6. Events & Listeners

### Events Emitted

```php
namespace Azaharizaman\Erp\Backoffice\Events;

// Company events
class CompanyCreatedEvent
{
    public function __construct(
        public readonly Company $company,
        public readonly User $createdBy
    ) {}
}

class CompanyUpdatedEvent
{
    public function __construct(
        public readonly Company $company,
        public readonly array $changes,
        public readonly User $updatedBy
    ) {}
}

// Organization events
class OrganizationCreatedEvent
{
    public function __construct(
        public readonly Organization $organization,
        public readonly User $createdBy
    ) {}
}

class OrganizationUpdatedEvent
{
    public function __construct(
        public readonly Organization $organization,
        public readonly ?int $oldParentId,
        public readonly User $updatedBy
    ) {}
}

class OrganizationMovedEvent
{
    public function __construct(
        public readonly Organization $organization,
        public readonly int $oldParentId,
        public readonly int $newParentId
    ) {}
}
```

### Listeners

```php
namespace Azaharizaman\Erp\Backoffice\Listeners;

// Initialize backoffice data when tenant created
class InitializeTenantBackofficeListener
{
    #[Listen(TenantCreatedEvent::class)]
    public function handle(TenantCreatedEvent $event): void
    {
        CreateCompanyAction::run([
            'tenant_id' => $event->tenant->id,
            'company_code' => $event->tenant->code,
            'company_name' => $event->tenant->name,
            'base_currency_code' => 'USD',
        ]);
    }
}

// Sync employee department when transferred (HCM integration)
class SyncEmployeeOrganizationListener
{
    #[Listen(EmployeeTransferredEvent::class)]
    public function handle(EmployeeTransferredEvent $event): void
    {
        // Update employee department_id to new organization
        // Log activity
    }
}
```

---

## 7. Testing Strategy

### Unit Tests (38 tests)

**Models (10 tests):**
```php
test('company belongs to tenant', function () {
    $company = Company::factory()->create();
    expect($company->tenant)->toBeInstanceOf(Tenant::class);
});

test('organization has parent relationship', function () {
    $parent = Organization::factory()->create();
    $child = Organization::factory()->create(['parent_id' => $parent->id]);
    expect($child->parent->id)->toBe($parent->id);
});

test('cannot create organization with future dates', function () {
    expect(fn() => Organization::factory()->create([
        'created_at' => now()->addDays(1)
    ]))->toThrow(ValidationException::class);
});
```

**Actions (12 tests):**
```php
test('CreateCompanyAction creates company', function () {
    $action = app(CreateCompanyAction::class);
    $company = $action->handle([
        'company_code' => 'ACME',
        'company_name' => 'Acme Corporation',
        'base_currency_code' => 'USD',
    ]);
    
    expect($company)->toBeInstanceOf(Company::class);
    expect($company->company_code)->toBe('ACME');
});

test('DeleteOrganizationAction prevents deletion with children', function () {
    $parent = Organization::factory()->create();
    $child = Organization::factory()->create(['parent_id' => $parent->id]);
    
    $action = app(DeleteOrganizationAction::class);
    expect(fn() => $action->handle($parent))->toThrow(\RuntimeException::class);
});
```

**Services (16 tests):**
```php
test('OrganizationHierarchyService rebuilds closure table', function () {
    $root = Organization::factory()->create();
    $child = Organization::factory()->create(['parent_id' => $root->id]);
    
    $service = app(OrganizationHierarchyService::class);
    $service->rebuildHierarchy($child);
    
    $hierarchy = OrganizationHierarchy::where('descendant_id', $child->id)->get();
    expect($hierarchy)->toHaveCount(2); // self + parent
});

test('getAncestors returns correct path', function () {
    $root = Organization::factory()->create();
    $mid = Organization::factory()->create(['parent_id' => $root->id]);
    $leaf = Organization::factory()->create(['parent_id' => $mid->id]);
    
    $service = app(OrganizationHierarchyService::class);
    $ancestors = $service->getAncestors($leaf);
    
    expect($ancestors)->toHaveCount(2);
    expect($ancestors->first()->id)->toBe($root->id);
});
```

### Feature Tests (44 tests)

**API Tests (30 tests):**
```php
test('can create company via API', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/backoffice/companies', [
            'company_code' => 'ACME',
            'company_name' => 'Acme Corporation',
            'base_currency_code' => 'USD',
        ]);
    
    $response->assertCreated();
    expect($response->json('data.company_code'))->toBe('ACME');
});

test('cannot create organization with invalid parent', function () {
    $user = User::factory()->create();
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();
    $parent = Organization::factory()->create(['company_id' => $company1->id]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/backoffice/organizations', [
            'organization_code' => 'DEPT-01',
            'organization_name' => 'Department',
            'organization_type' => 'department',
            'company_id' => $company2->id,
            'parent_id' => $parent->id, // Parent belongs to different company
        ]);
    
    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('parent_id');
});

test('hierarchy endpoint returns nested tree structure', function () {
    $user = User::factory()->create();
    $root = Organization::factory()->create();
    $child1 = Organization::factory()->create(['parent_id' => $root->id]);
    $child2 = Organization::factory()->create(['parent_id' => $root->id]);
    $grandchild = Organization::factory()->create(['parent_id' => $child1->id]);
    
    $response = $this->actingAs($user)
        ->getJson("/api/v1/backoffice/organizations/{$root->id}/hierarchy");
    
    $response->assertOk();
    $tree = $response->json('data');
    expect($tree['children'])->toHaveCount(2);
    expect($tree['children'][0]['children'])->toHaveCount(1);
});
```

**Authorization Tests (14 tests):**
```php
test('unauthorized user cannot create company', function () {
    $user = User::factory()->create(); // No permissions
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/backoffice/companies', [
            'company_code' => 'ACME',
            'company_name' => 'Acme Corporation',
        ]);
    
    $response->assertForbidden();
});

test('user can only view organizations in their tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant1->id]);
    $org1 = Organization::factory()->create(['tenant_id' => $tenant1->id]);
    $org2 = Organization::factory()->create(['tenant_id' => $tenant2->id]);
    
    $response = $this->actingAs($user)
        ->getJson('/api/v1/backoffice/organizations');
    
    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($org1->id);
});
```

### Integration Tests (6 tests)

```php
test('tenant creation initializes default company', function () {
    $tenant = Tenant::factory()->create(['code' => 'ACME', 'name' => 'Acme Corp']);
    
    $company = Company::where('tenant_id', $tenant->id)->first();
    expect($company)->not->toBeNull();
    expect($company->company_code)->toBe('ACME');
    expect($company->company_name)->toBe('Acme Corp');
});

test('employee transferred event updates organization', function () {
    $employee = Employee::factory()->create();
    $newOrg = Organization::factory()->create();
    
    event(new EmployeeTransferredEvent($employee, $newOrg->id));
    
    $employee->refresh();
    expect($employee->department_id)->toBe($newOrg->id);
});
```

### Performance Tests (1 test)

```php
test('hierarchy queries complete in under 100ms for 1000+ entities', function () {
    $root = Organization::factory()->create();
    
    // Seed 1000 organizations in hierarchy
    for ($i = 0; $i < 10; $i++) {
        $branch = Organization::factory()->create(['parent_id' => $root->id]);
        for ($j = 0; $j < 100; $j++) {
            Organization::factory()->create(['parent_id' => $branch->id]);
        }
    }
    
    $service = app(OrganizationHierarchyService::class);
    
    $start = microtime(true);
    $descendants = $service->getDescendants($root);
    $duration = (microtime(true) - $start) * 1000;
    
    expect($descendants)->toHaveCount(1001); // 1000 + self
    expect($duration)->toBeLessThan(100); // PR-BO-001
});
```

**Total Test Coverage:** 89 tests
- Unit: 38 tests
- Feature: 44 tests
- Integration: 6 tests
- Performance: 1 test

---

## 8. Dependencies & Prerequisites

### Required Dependencies

**Mandatory PRD Dependencies:**
- ✅ SUB01 (Multi-Tenancy) - Tenant model, tenant_id foreign keys
- ✅ SUB02 (Authentication & Authorization) - User model, roles, permissions
- ✅ SUB03 (Audit Logging) - ActivityLoggerContract for all administrative actions
- ✅ SUB05 (Settings Management) - Configuration for cache TTL, hierarchy depth

**Optional PRD Dependencies:**
- ⚠️ SUB13 (HCM) - Employee model for manager_id foreign key (can be nullable initially)

### Package Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "azaharizaman/erp-core": "^1.0",
    "lorisleiva/laravel-actions": "^2.0",
    "spatie/laravel-activitylog": "^4.0",
    "brick/math": "^0.12"
  },
  "require-dev": {
    "pestphp/pest": "^4.0",
    "pestphp/pest-plugin-laravel": "^3.0"
  }
}
```

### Infrastructure Requirements

- **Database:** PostgreSQL 14+ (JSONB support, advanced indexing)
- **Cache:** Redis 6+ (hierarchy caching with 15-minute TTL)
- **Queue:** Redis or database driver (for async operations in future plans)

---

## 9. Deployment & Rollout Strategy

### Phase 1: Development Setup (Week 1)

1. **Package Structure Creation:**
   - Create `/packages/backoffice/` directory structure
   - Set up composer.json with proper autoloading
   - Configure service provider registration
   - Set up test suite with Pest

2. **Core Models & Migrations:**
   - Implement Company model and migration
   - Implement Organization model and migration
   - Implement OrganizationHierarchy model and migration
   - Run migrations in development environment

3. **Repository Layer:**
   - Implement CompanyRepository and contract
   - Implement OrganizationRepository and contract
   - Set up service provider bindings

### Phase 2: Business Logic & Actions (Week 2)

1. **Company Management:**
   - Implement Create/Update/DeleteCompanyAction
   - Set up policies and authorization
   - Create API controllers and form requests
   - Write unit tests for actions

2. **Organization Hierarchy:**
   - Implement OrganizationHierarchyService with closure table logic
   - Implement Create/Update/Move/DeleteOrganizationAction
   - Set up OrganizationObserver for automatic hierarchy updates
   - Write unit tests for service and actions

3. **API Layer:**
   - Implement all API endpoints
   - Create API resources for transformations
   - Set up route registration
   - Write feature tests for API

### Phase 3: Integration & Testing (Week 3)

1. **Cross-Module Integration:**
   - Implement InitializeTenantBackofficeListener
   - Implement SyncEmployeeOrganizationListener
   - Create OrganizationQueryService for external consumption
   - Write integration tests

2. **Performance Optimization:**
   - Implement Redis caching for hierarchy queries
   - Add database indexes for optimal query performance
   - Run performance tests with 1000+ organizations
   - Optimize queries to meet < 100ms requirement (PR-BO-001)

3. **Test Coverage:**
   - Write remaining unit tests
   - Write remaining feature tests
   - Achieve minimum 80% code coverage
   - Fix any failing tests

### Phase 4: Documentation & Deployment (Week 4)

1. **Documentation:**
   - Complete README.md with installation and usage
   - Write API documentation with OpenAPI spec
   - Create admin guide for organizational setup
   - Document integration points

2. **Code Quality:**
   - Run Laravel Pint for PSR-12 compliance
   - Verify all files have strict types declaration
   - Complete PHPDoc for all public methods
   - Conduct code review

3. **Staging Deployment:**
   - Deploy to staging environment
   - Run full test suite in staging
   - Perform smoke tests on all endpoints
   - Validate performance requirements

4. **Production Release:**
   - Tag v1.0.0 release
   - Deploy to production
   - Monitor initial usage
   - Provide support for early adopters

---

## 10. Risk Mitigation

### High-Risk Items

**Risk 1: Closure Table Performance**
- **Mitigation:** Implement caching layer with Redis (15-minute TTL); add comprehensive database indexes; run performance tests early
- **Contingency:** If performance targets not met, consider materialized path pattern or nested set model as alternative

**Risk 2: Circular Reference in Hierarchy**
- **Mitigation:** Implement validation in MoveOrganizationAction to check if new parent is not a descendant; add database constraint to prevent self-referencing parent_id
- **Contingency:** Add recursive depth check; throw clear error message; provide admin tool to fix corrupted hierarchies

**Risk 3: Concurrent Hierarchy Updates**
- **Mitigation:** Use database transactions in rebuildHierarchy(); implement pessimistic locking for organization updates
- **Contingency:** Add retry logic with exponential backoff; queue hierarchy rebuilds for async processing

**Risk 4: Integration with HCM Module**
- **Mitigation:** Design manager_id as nullable initially; use soft reference checks; create clear integration contract
- **Contingency:** Implement HCM integration in PLAN02 after core organizational structure is stable

### Medium-Risk Items

**Risk 5: Large Hierarchy Query Memory Usage**
- **Mitigation:** Implement pagination for large hierarchy results; use lazy loading for descendants; chunk recursive operations
- **Contingency:** Add configurable max depth limit; implement streaming responses for large hierarchies

**Risk 6: Multi-Tenant Data Isolation**
- **Mitigation:** Always include tenant_id in where clauses; use global scopes on models; add tenant_id to all indexes
- **Contingency:** Implement database row-level security; add automated tests for cross-tenant access attempts

---

## 11. Success Criteria

### Functional Success (from PRD)

- [x] Can create and manage companies with complete master data (FR-BO-004)
- [x] Organizational hierarchy (branches, departments, cost centers) operational (FR-BO-001, FR-BO-005, FR-BO-006)
- [x] Closure table pattern efficiently queries hierarchies (ARCH-BO-001)
- [x] Cannot delete organizational entities with active transactions (BR-BO-004)

### Technical Success

- [x] All API endpoints return correct responses per OpenAPI spec
- [x] Hierarchy queries complete in < 100ms for 1000+ entities (PR-BO-001)
- [x] Repository pattern implemented for all data access
- [x] Actions pattern used for all business logic
- [x] Minimum 80% code coverage achieved (89 tests total)

### Security Success

- [x] All administrative actions logged (SR-BO-002)
- [x] Role-based access control enforced (SR-BO-001)
- [x] Tenant isolation maintained across all operations

### Integration Success

- [x] Organizational hierarchy API accessible for authorization (IR-BO-002)
- [x] Default company created when tenant initialized
- [x] Integration contracts defined for HCM employee-department assignments (IR-BO-003)

---

## 12. Acceptance Checklist

**Before merging to main:**

### Code Quality
- [ ] All files have `declare(strict_types=1);`
- [ ] All methods have parameter type hints and return types
- [ ] All public/protected methods have complete PHPDoc blocks
- [ ] Code passes Laravel Pint PSR-12 compliance check
- [ ] No direct model access in services (repository pattern enforced)

### Testing
- [ ] All 89 tests pass successfully
- [ ] Minimum 80% code coverage achieved
- [ ] Performance test validates < 100ms for hierarchy queries (PR-BO-001)
- [ ] Integration tests verify tenant initialization
- [ ] Authorization tests prevent cross-tenant access

### Documentation
- [ ] README.md complete with installation and usage
- [ ] API documentation with OpenAPI spec
- [ ] Admin guide for organizational setup
- [ ] CHANGELOG.md updated
- [ ] Migration guide created

### Deployment
- [ ] Migrations run successfully in staging
- [ ] All API endpoints smoke tested
- [ ] Closure table queries perform as expected
- [ ] Redis caching functional
- [ ] No errors in application logs

### Business Validation
- [ ] Can create companies and organizational units via API
- [ ] Hierarchy tree correctly reflects parent-child relationships
- [ ] Organizational entities properly isolated by tenant
- [ ] Administrative actions properly logged
- [ ] Cannot delete entities with children or active transactions

---

**Implementation Ready:** This plan is ready for development. All tasks are deterministic, testable, and traceable to requirements.

**Estimated Effort:** 3-4 weeks (1 developer)

**Next Plan:** PRD01-SUB15-PLAN02 (Fiscal Year & Period Management) - Covers FR-BO-002, FR-BO-003, fiscal year creation/closing, accounting period management, approval workflows, document numbering sequences.
