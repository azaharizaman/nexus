---
plan: Implement Multi-Tenancy API and Middleware
version: 1.0
date_created: 2025-11-11
last_updated: 2025-11-11
owner: Development Team
status: Planned
tags: [feature, multitenancy, api, middleware, authentication, impersonation, lifecycle]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan covers the API layer and middleware for the Multi-Tenancy System, building upon the core infrastructure established in PLAN01. This includes tenant resolution middleware, RESTful API endpoints for tenant management, tenant lifecycle operations (suspend, activate, archive), tenant impersonation with audit controls, and comprehensive testing. This plan focuses on the application layer that enables tenant administration and context resolution.

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-MT-003**: Implement tenant context resolution via middleware from authenticated user
- **REQ-FR-MT-005**: Implement comprehensive tenant lifecycle management (create, suspend, activate, archive, delete)
- **REQ-FR-MT-006**: Implement tenant impersonation with role-based access control and audit logging
- **REQ-FR-MT-007**: Implement tenant-scoped caching with automatic cache key prefixing (middleware integration)
- **REQ-SR-MT-002**: Tenant context resolution must fail-safe (return 403 Forbidden if cannot determine tenant)
- **REQ-SR-MT-004**: All tenant impersonation sessions must be logged with start time, end time, and reason
- **REQ-ARCH-MT-001**: Use middleware for tenant resolution before request reaches controllers
- **REQ-EV-MT-001**: Dispatch TenantCreatedEvent, TenantUpdatedEvent, TenantDeletedEvent for lifecycle changes
- **REQ-EV-MT-002**: Dispatch TenantSuspendedEvent, TenantActivatedEvent, TenantArchivedEvent for status changes
- **REQ-EV-MT-003**: Dispatch TenantImpersonationStartedEvent and TenantImpersonationEndedEvent for impersonation tracking

### Security Constraints

- **SEC-001**: Tenant impersonation must require special permission (admin or support role)
- **SEC-002**: Impersonation sessions must have configurable timeout (default 1 hour)
- **SEC-003**: API endpoints for tenant management must be protected by authentication and authorization
- **SEC-004**: Tenant resolution middleware must run after authentication middleware

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: All controllers must use Laravel Actions pattern for business logic
- **GUD-003**: API responses must use JSON:API format with proper status codes
- **GUD-004**: Middleware must document all error response scenarios in PHPDoc

### Patterns to Follow

- **PAT-001**: Use middleware for cross-cutting concerns (tenant resolution, impersonation)
- **PAT-002**: Use Form Requests for API input validation
- **PAT-003**: Use API Resources for response transformation
- **PAT-004**: Use Laravel Actions for all business operations (CreateTenantAction, SuspendTenantAction, etc.)
- **PAT-005**: Use Gates for authorization checks (can('impersonate-tenant', $tenant))

### Constraints

- **CON-001**: API endpoints must be versioned (prefix: /api/v1/)
- **CON-002**: Middleware must not make unnecessary database queries (use caching)
- **CON-003**: All lifecycle operations must be transactional (database transactions)
- **CON-004**: Impersonation must not persist beyond session/token lifetime

## 2. Implementation Steps

### GOAL-001: Implement Tenant Resolution Middleware

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MT-003, SR-MT-002, ARCH-MT-001 | Create middleware that resolves tenant from authenticated user and sets it in TenantContext. Implement fail-safe error handling. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create `packages/multitenancy/src/Http/Middleware/IdentifyTenant.php` implementing `handle(Request $request, Closure $next): Response`. Check if user is authenticated, extract tenant_id from user, load Tenant model, call TenantContext::setCurrentTenant(). | | |
| TASK-002 | In IdentifyTenant middleware, add fail-safe checks: If user not authenticated, return 401 Unauthenticated. If user has no tenant_id, return 403 Forbidden with message "User not associated with any tenant". If tenant_id exists but tenant not found in database, return 404 Not Found with message "Tenant not found". | | |
| TASK-003 | In IdentifyTenant middleware, load tenant from cache first (via TenantContext) before querying database. Log warning if cache miss occurs. Use Redis cache with 1-hour TTL. | | |
| TASK-004 | Create `packages/multitenancy/src/Http/Middleware/EnsureTenantActive.php` that checks current tenant status. If status is SUSPENDED, return 403 with message "Tenant is suspended". If ARCHIVED, return 403 with message "Tenant is archived". Only allow ACTIVE tenants to proceed. | | |
| TASK-005 | Register middleware in service provider: In MultitenancyServiceProvider::boot(), add `$this->app['router']->aliasMiddleware('tenant', IdentifyTenant::class)` and `$this->app['router']->aliasMiddleware('tenant.active', EnsureTenantActive::class)`. | | |
| TASK-006 | Add comprehensive PHPDoc to both middleware classes documenting all error response codes (401, 403, 404), parameters, and that these should run after auth middleware. Include usage examples. | | |

### GOAL-002: Create Tenant Management API Endpoints

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MT-001, FR-MT-005, EV-MT-001, EV-MT-002 | Implement RESTful API endpoints for tenant CRUD operations with proper validation, authorization, and event dispatching. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `packages/multitenancy/src/Http/Requests/StoreTenantRequest.php` extending FormRequest. Define rules: name required\|string\|max:255, domain required\|string\|unique:tenants,domain\|max:255, status nullable\|in:active,suspended,archived, configuration nullable\|array. Authorize: user must have 'create-tenant' permission. | | |
| TASK-008 | Create `packages/multitenancy/src/Http/Requests/UpdateTenantRequest.php` extending FormRequest. Define rules: name nullable\|string\|max:255, domain nullable\|string\|unique:tenants,domain,{tenant_id}\|max:255, status nullable\|in:active,suspended,archived, configuration nullable\|array. Authorize: user must have 'update-tenant' permission. | | |
| TASK-009 | Create `packages/multitenancy/src/Http/Resources/TenantResource.php` extending JsonResource. Transform tenant to JSON:API format with id, name, domain, status (as label), created_at, updated_at. Conditionally include 'configuration' only for admins. | | |
| TASK-010 | Create `packages/multitenancy/src/Http/Controllers/TenantController.php` with methods: index(Request), store(StoreTenantRequest), show(Tenant), update(UpdateTenantRequest, Tenant), destroy(Tenant). Apply middleware: ['auth:sanctum', 'tenant'] on all routes except store (tenant creation doesn't require tenant context). | | |
| TASK-011 | In TenantController::store(), use CreateTenantAction::run($request->validated()) to create tenant. Return TenantResource::make($tenant) with 201 status code. | | |
| TASK-012 | In TenantController::update(), use UpdateTenantAction::run($tenant, $request->validated()) to update tenant. Return TenantResource::make($tenant) with 200 status code. | | |
| TASK-013 | In TenantController::destroy(), use DeleteTenantAction::run($tenant) to soft-delete tenant. Return 204 No Content response. | | |
| TASK-014 | Register routes in `packages/multitenancy/routes/api.php`: Route::prefix('admin/tenants')->middleware(['auth:sanctum', 'tenant'])->group() with resourceful routes for TenantController. Publish routes in service provider. | | |

### GOAL-003: Implement Tenant Lifecycle Actions

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MT-005, EV-MT-001, EV-MT-002 | Create Laravel Actions for tenant lifecycle operations (create, update, suspend, activate, archive, delete) with event dispatching and audit logging. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-015 | Create `packages/multitenancy/src/Actions/CreateTenantAction.php` using AsAction trait. In handle(array $data): Tenant, validate data, call TenantRepository::create($data), dispatch TenantCreatedEvent, log activity, return tenant. | | |
| TASK-016 | Create `packages/multitenancy/src/Actions/UpdateTenantAction.php` using AsAction trait. In handle(Tenant $tenant, array $data): Tenant, validate changes, call TenantRepository::update($tenant, $data), dispatch TenantUpdatedEvent, log activity, return tenant. | | |
| TASK-017 | Create `packages/multitenancy/src/Actions/SuspendTenantAction.php` using AsAction trait. In handle(Tenant $tenant, string $reason): Tenant, check if tenant is ACTIVE, update status to SUSPENDED, dispatch TenantSuspendedEvent, log activity with reason, return tenant. | | |
| TASK-018 | Create `packages/multitenancy/src/Actions/ActivateTenantAction.php` using AsAction trait. In handle(Tenant $tenant): Tenant, check if tenant is SUSPENDED or ARCHIVED, update status to ACTIVE, dispatch TenantActivatedEvent, log activity, return tenant. | | |
| TASK-019 | Create `packages/multitenancy/src/Actions/ArchiveTenantAction.php` using AsAction trait. In handle(Tenant $tenant, string $reason): Tenant, check if tenant is not already ARCHIVED, update status to ARCHIVED, dispatch TenantArchivedEvent, log activity with reason, return tenant. | | |
| TASK-020 | Create `packages/multitenancy/src/Actions/DeleteTenantAction.php` using AsAction trait. In handle(Tenant $tenant): bool, soft delete tenant, dispatch TenantDeletedEvent, log activity, clear all tenant caches, return true. | | |
| TASK-021 | Add route endpoints for lifecycle actions in TenantController: POST /admin/tenants/{tenant}/suspend, POST /admin/tenants/{tenant}/activate, POST /admin/tenants/{tenant}/archive. Each calls corresponding Action with reason from request. | | |

### GOAL-004: Implement Tenant Impersonation System

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MT-006, SR-MT-001, SR-MT-004, EV-MT-003 | Create impersonation service allowing authorized users to temporarily access system as another tenant for support purposes. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-022 | Create `packages/multitenancy/src/Services/ImpersonationService.php` class with methods: startImpersonation(User $user, Tenant $tenant, string $reason): void, endImpersonation(User $user): void, isImpersonating(User $user): bool, getOriginalTenant(User $user): ?Tenant. | | |
| TASK-023 | In ImpersonationService::startImpersonation(), check Gate::allows('impersonate-tenant', $tenant). Store original tenant_id in Redis cache with key `impersonation:{$user->id}`. Set new tenant in TenantContext. Dispatch TenantImpersonationStartedEvent. Log in activity log. Set Redis TTL to config('multitenancy.impersonation_timeout', 3600) seconds. | | |
| TASK-024 | In ImpersonationService::endImpersonation(), restore original tenant_id from Redis cache, update TenantContext, dispatch TenantImpersonationEndedEvent, log activity, clear impersonation cache key. | | |
| TASK-025 | Create `packages/multitenancy/src/Http/Middleware/ImpersonationMiddleware.php` that checks for active impersonation session and ensures it hasn't timed out. If timeout exceeded, automatically call endImpersonation(). | | |
| TASK-026 | Create `packages/multitenancy/src/Actions/StartImpersonationAction.php` and `EndImpersonationAction.php` using AsAction trait. Call ImpersonationService methods respectively. Include authorization checks. | | |
| TASK-027 | Add impersonation routes: POST /admin/tenants/{tenant}/impersonate (start), POST /admin/impersonation/end (end current session). Add to TenantController or separate ImpersonationController. | | |
| TASK-028 | Create Gate definition in service provider: Gate::define('impersonate-tenant', fn(User $user, Tenant $tenant) => $user->hasRole('admin') || $user->hasRole('support')). Register in MultitenancyServiceProvider::boot(). | | |

### GOAL-005: Comprehensive Testing and Documentation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| All Requirements | Create comprehensive test suite covering middleware, API endpoints, actions, and impersonation. Update documentation with usage examples. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-029 | Create feature test `packages/multitenancy/tests/Feature/Middleware/IdentifyTenantTest.php`: Test tenant resolution from authenticated user (200), unauthenticated request (401), user without tenant_id (403), invalid tenant_id (404), tenant loaded from cache (no DB query). | | |
| TASK-030 | Create feature test `packages/multitenancy/tests/Feature/Middleware/EnsureTenantActiveTest.php`: Test ACTIVE tenant passes through (200), SUSPENDED tenant blocked (403), ARCHIVED tenant blocked (403). | | |
| TASK-031 | Create feature test `packages/multitenancy/tests/Feature/Api/TenantControllerTest.php`: Test index with pagination (200), store with valid data (201), store with duplicate domain (422), show existing tenant (200), update tenant (200), destroy tenant (204), authorization checks for each endpoint. | | |
| TASK-032 | Create feature test `packages/multitenancy/tests/Feature/Actions/TenantLifecycleActionsTest.php`: Test SuspendTenantAction dispatches event and logs activity, ActivateTenantAction changes status correctly, ArchiveTenantAction prevents further updates, DeleteTenantAction soft-deletes and clears cache. | | |
| TASK-033 | Create feature test `packages/multitenancy/tests/Feature/Services/ImpersonationServiceTest.php`: Test startImpersonation() requires permission, stores original tenant, sets new tenant context, dispatches event. Test endImpersonation() restores original tenant, clears cache. Test automatic timeout after configured duration. Test isImpersonating() returns correct boolean. | | |
| TASK-034 | Create integration test `packages/multitenancy/tests/Integration/TenantWorkflowTest.php`: Test complete workflow: Create tenant → Assign users → Suspend tenant → Users cannot access → Activate tenant → Users can access again. Test impersonation workflow: Admin starts impersonation → Sees target tenant data → Ends impersonation → Sees own tenant data. | | |
| TASK-035 | Update package README.md with: Middleware usage examples (applying tenant middleware to routes), API endpoint documentation with cURL examples, Impersonation usage guide for admins, Configuration options reference, Troubleshooting common issues (cache not working, permission denied, etc.). | | |
| TASK-036 | Create `packages/multitenancy/docs/MIDDLEWARE.md` documenting middleware execution order (must run after auth), error response codes, caching behavior, performance considerations, and best practices. | | |

## 3. Alternatives

- **ALT-001**: Use subdomain-based tenant resolution instead of authenticated user context - Rejected because it requires DNS configuration and doesn't work well for mobile apps/APIs. User-based resolution is more flexible.
- **ALT-002**: Store impersonation state in session instead of Redis cache - Rejected because sessions don't work for API authentication with Sanctum tokens. Redis cache is stateless and works across all authentication methods.
- **ALT-003**: Use Laravel Policies instead of Gates for impersonation authorization - Rejected because Gates are simpler for permission checks without specific model instances. Policies are overkill for this use case.
- **ALT-004**: Implement tenant switching UI in this package - Rejected because this is a headless backend system. Frontend should handle UI, this package only provides API endpoints.
- **ALT-005**: Allow permanent impersonation without timeout - Rejected for security reasons. All impersonation must have configurable timeout to prevent abuse and forgotten sessions.

## 4. Dependencies

### Package Dependencies

- **DEP-001**: laravel/framework ^12.0 (Core framework, routing, middleware)
- **DEP-002**: laravel/sanctum ^4.0 (API authentication for protected endpoints)
- **DEP-003**: spatie/laravel-activitylog ^4.0 (Audit logging for lifecycle events via our ActivityLoggerContract)
- **DEP-004**: lorisleiva/laravel-actions ^2.0 (Action pattern for business logic)
- **DEP-005**: spatie/laravel-permission ^6.0 (Role-based authorization via our PermissionServiceContract)

### Internal Package Dependencies

- **DEP-006**: multitenancy-core-infrastructure (PLAN01) - Tenant model, TenantContext service, BelongsToTenant trait, TenantRepository
- **DEP-007**: azaharizaman/erp-multitenancy (this package core components from PLAN01)

### Infrastructure Dependencies

- **DEP-008**: Redis 6+ (Required for impersonation state storage and tenant context caching)
- **DEP-009**: Authentication system (Users table, authentication middleware)

### Development Dependencies

- **DEP-010**: pestphp/pest ^4.0 (Testing framework)
- **DEP-011**: pestphp/pest-plugin-laravel ^4.0 (Laravel-specific Pest helpers)

## 5. Files

### Middleware

- **packages/multitenancy/src/Http/Middleware/IdentifyTenant.php**: Resolves tenant from authenticated user and sets context
- **packages/multitenancy/src/Http/Middleware/EnsureTenantActive.php**: Validates tenant status before request proceeds
- **packages/multitenancy/src/Http/Middleware/ImpersonationMiddleware.php**: Manages impersonation session timeout

### API Layer

- **packages/multitenancy/src/Http/Controllers/TenantController.php**: RESTful controller for tenant management
- **packages/multitenancy/src/Http/Requests/StoreTenantRequest.php**: Validation for tenant creation
- **packages/multitenancy/src/Http/Requests/UpdateTenantRequest.php**: Validation for tenant updates
- **packages/multitenancy/src/Http/Resources/TenantResource.php**: JSON:API resource transformation
- **packages/multitenancy/routes/api.php**: API route definitions

### Actions

- **packages/multitenancy/src/Actions/CreateTenantAction.php**: Create new tenant with validation
- **packages/multitenancy/src/Actions/UpdateTenantAction.php**: Update existing tenant
- **packages/multitenancy/src/Actions/SuspendTenantAction.php**: Suspend tenant access
- **packages/multitenancy/src/Actions/ActivateTenantAction.php**: Reactivate suspended/archived tenant
- **packages/multitenancy/src/Actions/ArchiveTenantAction.php**: Archive inactive tenant
- **packages/multitenancy/src/Actions/DeleteTenantAction.php**: Soft delete tenant
- **packages/multitenancy/src/Actions/StartImpersonationAction.php**: Begin impersonation session
- **packages/multitenancy/src/Actions/EndImpersonationAction.php**: End impersonation session

### Services

- **packages/multitenancy/src/Services/ImpersonationService.php**: Manages impersonation state and authorization

### Documentation

- **packages/multitenancy/docs/MIDDLEWARE.md**: Middleware usage guide and best practices

## 6. Testing

### Middleware Tests

- **TEST-001**: Test IdentifyTenant middleware resolves tenant from authenticated user successfully (200)
- **TEST-002**: Test IdentifyTenant middleware returns 401 for unauthenticated requests
- **TEST-003**: Test IdentifyTenant middleware returns 403 when user has no tenant_id
- **TEST-004**: Test IdentifyTenant middleware returns 404 when tenant_id is invalid
- **TEST-005**: Test IdentifyTenant middleware loads tenant from cache without DB query
- **TEST-006**: Test EnsureTenantActive middleware allows ACTIVE tenants through (200)
- **TEST-007**: Test EnsureTenantActive middleware blocks SUSPENDED tenants (403)
- **TEST-008**: Test EnsureTenantActive middleware blocks ARCHIVED tenants (403)
- **TEST-009**: Test ImpersonationMiddleware automatically ends impersonation after timeout

### API Endpoint Tests

- **TEST-010**: Test GET /admin/tenants returns paginated tenant list (200)
- **TEST-011**: Test POST /admin/tenants creates tenant with valid data (201)
- **TEST-012**: Test POST /admin/tenants rejects duplicate domain (422)
- **TEST-013**: Test GET /admin/tenants/{tenant} returns tenant details (200)
- **TEST-014**: Test PATCH /admin/tenants/{tenant} updates tenant (200)
- **TEST-015**: Test DELETE /admin/tenants/{tenant} soft-deletes tenant (204)
- **TEST-016**: Test POST /admin/tenants/{tenant}/suspend changes status to SUSPENDED (200)
- **TEST-017**: Test POST /admin/tenants/{tenant}/activate changes status to ACTIVE (200)
- **TEST-018**: Test POST /admin/tenants/{tenant}/archive changes status to ARCHIVED (200)
- **TEST-019**: Test API endpoints require authentication (401 without token)
- **TEST-020**: Test API endpoints check authorization permissions (403 without permission)

### Action Tests

- **TEST-021**: Test CreateTenantAction validates data and dispatches TenantCreatedEvent
- **TEST-022**: Test UpdateTenantAction validates changes and dispatches TenantUpdatedEvent
- **TEST-023**: Test SuspendTenantAction only suspends ACTIVE tenants and logs reason
- **TEST-024**: Test ActivateTenantAction can reactivate SUSPENDED or ARCHIVED tenants
- **TEST-025**: Test ArchiveTenantAction prevents further operations on archived tenants
- **TEST-026**: Test DeleteTenantAction soft-deletes and clears all tenant caches

### Impersonation Tests

- **TEST-027**: Test startImpersonation() requires 'impersonate-tenant' permission
- **TEST-028**: Test startImpersonation() stores original tenant in Redis cache
- **TEST-029**: Test startImpersonation() sets new tenant in TenantContext
- **TEST-030**: Test startImpersonation() dispatches TenantImpersonationStartedEvent
- **TEST-031**: Test endImpersonation() restores original tenant correctly
- **TEST-032**: Test endImpersonation() dispatches TenantImpersonationEndedEvent
- **TEST-033**: Test isImpersonating() returns correct boolean for active session
- **TEST-034**: Test impersonation automatically times out after configured duration
- **TEST-035**: Test impersonation denied for users without admin/support role

### Integration Tests

- **TEST-036**: Test complete tenant lifecycle workflow (create → activate → suspend → activate → archive → delete)
- **TEST-037**: Test tenant data isolation: User A cannot access User B's tenant data through API
- **TEST-038**: Test impersonation workflow: Admin impersonates tenant → sees their data → ends session → sees own data
- **TEST-039**: Test middleware chain: auth:sanctum → tenant → tenant.active works correctly
- **TEST-040**: Test cache invalidation: Tenant update clears relevant caches immediately

### Performance Tests

- **TEST-041**: Test middleware tenant resolution completes in < 100ms with cache hit
- **TEST-042**: Test API endpoint response times under load (100 concurrent requests)
- **TEST-043**: Test impersonation state lookup from Redis completes in < 50ms

## 7. Risks & Assumptions

### Risks

- **RISK-001**: **Middleware ordering risk** - If tenant middleware runs before auth middleware, tenant resolution will fail. Mitigation: Document required middleware order clearly, add automated tests checking middleware order.
- **RISK-002**: **Impersonation abuse risk** - Admins might impersonate tenants for unauthorized access. Mitigation: Comprehensive audit logging, require reason for impersonation, automatic timeout, monitor impersonation frequency.
- **RISK-003**: **API rate limiting** - High-frequency tenant switching or impersonation could be abused. Mitigation: Implement rate limiting on impersonation endpoints, monitor usage patterns.
- **RISK-004**: **Cache inconsistency** - Tenant updates might not immediately reflect if caches aren't properly invalidated. Mitigation: Clear all relevant caches on tenant updates, use cache tags for group invalidation.
- **RISK-005**: **Permission escalation** - Impersonation might grant unintended permissions if role checks are inconsistent. Mitigation: Impersonation only changes tenant context, not user roles. Original user's permissions still apply.

### Assumptions

- **ASSUMPTION-001**: Authentication middleware (Sanctum) is properly configured and runs before tenant middleware
- **ASSUMPTION-002**: All users belong to exactly one tenant (tenant_id is required on users table)
- **ASSUMPTION-003**: Impersonation is only used by support staff, not regular users (restricted to admin/support roles)
- **ASSUMPTION-004**: Redis is available for impersonation state storage (no fallback to database session)
- **ASSUMPTION-005**: API consumers handle 401, 403, 404 errors gracefully with proper user messages
- **ASSUMPTION-006**: Tenant suspension/archival is rare enough that cache invalidation performance impact is negligible
- **ASSUMPTION-007**: Maximum concurrent impersonation sessions per admin is < 10 (reasonable for support use case)

## 8. Related PRD / Further Reading

### Primary Documentation

- **PRD01-SUB01: Multi-Tenancy System**: [/docs/prd/prd-01/PRD01-SUB01-MULTITENANCY.md](../prd/prd-01/PRD01-SUB01-MULTITENANCY.md)
- **PLAN01: Multi-Tenancy Core Infrastructure**: [/docs/plan/PRD01-SUB01-PLAN01-implement-multitenancy-core.md](./PRD01-SUB01-PLAN01-implement-multitenancy-core.md)
- **Master PRD**: [/docs/prd/PRD01-MVP.md](../prd/PRD01-MVP.md)

### Architectural Documentation

- **Package Decoupling Strategy**: [/docs/architecture/PACKAGE-DECOUPLING-STRATEGY.md](../architecture/PACKAGE-DECOUPLING-STRATEGY.md)
- **Coding Guidelines**: [/CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- **Sanctum Authentication**: [/docs/SANCTUM_AUTHENTICATION.md](../SANCTUM_AUTHENTICATION.md)

### Laravel Documentation

- **Laravel 12 Middleware**: https://laravel.com/docs/12.x/middleware
- **Laravel 12 API Resources**: https://laravel.com/docs/12.x/eloquent-resources
- **Laravel 12 Form Requests**: https://laravel.com/docs/12.x/validation#form-request-validation
- **Laravel 12 Authorization (Gates)**: https://laravel.com/docs/12.x/authorization#gates
- **Laravel 12 API Authentication (Sanctum)**: https://laravel.com/docs/12.x/sanctum

### External Resources

- **RESTful API Design**: https://restfulapi.net/
- **JSON:API Specification**: https://jsonapi.org/
- **API Security Best Practices**: https://owasp.org/www-project-api-security/
