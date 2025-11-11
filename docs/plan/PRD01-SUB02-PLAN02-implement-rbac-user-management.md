---
plan: Implement RBAC and User Management
version: 1.0
date_created: 2025-11-11
last_updated: 2025-11-11
owner: Development Team
status: Planned
tags: [feature, authentication, rbac, permissions, authorization, user-management, admin]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan covers the Role-Based Access Control (RBAC) system and user management features for the Laravel ERP authentication module. This includes integration with Spatie Laravel Permission for roles and permissions, tenant-scoped authorization, permission caching, admin user management API endpoints, and comprehensive event-driven architecture. This plan builds upon the core authentication infrastructure from PLAN01.

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-AA-003**: Develop a Role-Based Access Control (RBAC) system with roles, permissions, and role hierarchy
- **REQ-FR-AA-009**: Support Permission Management interface for admins to assign/revoke permissions
- **REQ-BR-AA-005**: Users with `super-admin` role can access all tenants (for support purposes)
- **REQ-BR-AA-006**: Permissions are inherited from roles - users can have multiple roles
- **REQ-BR-AA-007**: Role hierarchy enforced: `super-admin` > `tenant-admin` > `manager` > `user`
- **REQ-DR-AA-002**: Roles table MUST include: id, name, guard_name, team_id (tenant_id for tenant-scoped roles), permissions array
- **REQ-DR-AA-003**: Permissions table MUST include: id, name, guard_name, description, category
- **REQ-IR-AA-001**: Emit events to SUB03 (Audit Logging) for all authentication activities
- **REQ-IR-AA-002**: Emit events to SUB22 (Notifications) for account lockout alerts
- **REQ-IR-AA-003**: Use SUB01 (Multi-Tenancy) for tenant context resolution during login
- **REQ-PR-AA-003**: Permission checks MUST be cached per user session to avoid repeated queries
- **REQ-ARCH-AA-002**: Use Spatie Laravel Permission for RBAC with team_id scoping for multi-tenancy
- **REQ-ARCH-AA-003**: Implement Redis caching for permission checks and token validation
- **REQ-EV-AA-001**: Dispatch UserAuthenticatedEvent when user successfully logs in
- **REQ-EV-AA-002**: Dispatch AuthenticationFailedEvent when login attempt fails
- **REQ-EV-AA-003**: Dispatch AccountLockedEvent when account is locked due to failed attempts
- **REQ-EV-AA-004**: Dispatch PasswordResetRequestedEvent when user requests password reset
- **REQ-EV-AA-005**: Dispatch PasswordChangedEvent when user changes password

### Security Constraints

- **SEC-001**: Permission checks must be cached with user-specific cache keys to prevent permission escalation
- **SEC-002**: Role assignment must verify tenant match to prevent cross-tenant role assignment
- **SEC-003**: Super-admin role must be carefully controlled and logged for audit compliance
- **SEC-004**: Permission cache must be invalidated immediately when roles or permissions change

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: All authorization checks must use Gates or Policies, never direct database queries
- **GUD-003**: Use Laravel 12+ conventions (anonymous migrations, modern factory syntax)
- **GUD-004**: Follow PSR-12 coding standards, enforced by Laravel Pint

### Patterns to Follow

- **PAT-001**: Use Laravel Policies for model-specific authorization (UserPolicy, RolePolicy)
- **PAT-002**: Use Gates for general authorization checks (can-manage-roles, can-assign-permissions)
- **PAT-003**: Use Form Requests for input validation in admin endpoints
- **PAT-004**: Use API Resources for response transformation (RoleResource, PermissionResource)
- **PAT-005**: Use Service Provider for Spatie Permission configuration and cache warming

### Constraints

- **CON-001**: Must support PostgreSQL 14+ and MySQL 8.0+
- **CON-002**: Redis 6+ is mandatory for permission caching (not optional)
- **CON-003**: Package must maintain compatibility with Spatie Permission v6.x
- **CON-004**: Permission cache TTL must be configurable (default: 1 hour)

## 2. Implementation Steps

### GOAL-001: Integrate Spatie Laravel Permission with Tenant Scoping

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AA-003, DR-AA-002, DR-AA-003, ARCH-AA-002 | Install and configure Spatie Laravel Permission package with team_id scoping for multi-tenancy support. Publish migrations and configure for tenant-scoped roles and permissions. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Install Spatie Laravel Permission: Add `"spatie/laravel-permission": "^6.0"` to `packages/authentication/composer.json` require section. Run `composer require spatie/laravel-permission`. | | |
| TASK-002 | Publish Spatie Permission migrations: Run `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"` to publish migrations (roles, permissions, model_has_roles, model_has_permissions, role_has_permissions tables). Move migrations to `packages/authentication/database/migrations/`. Convert to anonymous class format. | | |
| TASK-003 | Configure team_id scoping in `packages/authentication/config/permission.php`: Publish Spatie config, set `teams` => true, set `column_names.team_foreign_key` => 'team_id' (maps to tenant_id). Ensure cache store uses Redis. | | |
| TASK-004 | Add `HasRoles` trait to User model: `use Spatie\Permission\Traits\HasRoles;` in User.php. This provides `assignRole()`, `hasRole()`, `givePermissionTo()`, `hasPermissionTo()` methods. Add `getPermissionTeamId()` method returning `$this->tenant_id` for team scoping. | | |
| TASK-005 | Create `packages/authentication/database/seeders/RolePermissionSeeder.php` to seed default roles (super-admin, tenant-admin, manager, user) and common permissions (view-users, create-users, update-users, delete-users, manage-roles, manage-permissions). Use tenant context for tenant-specific roles. | | |
| TASK-006 | Configure permission caching in AuthenticationServiceProvider: Set cache expiration from config, implement cache warming strategy (preload permissions for authenticated user on login), clear cache on role/permission changes. | | |

### GOAL-002: Implement Permission Management Actions

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AA-009, BR-AA-006, BR-AA-007, PR-AA-003 | Create Laravel Actions for role and permission management (create roles, assign roles, grant permissions) with authorization checks and event dispatching. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `packages/authentication/src/Actions/CreateRoleAction.php` using AsAction trait. In `handle(string $name, array $permissions, ?string $tenantId): Role`, validate name uniqueness per tenant (team_id), create role with Spatie `Role::create(['name' => $name, 'team_id' => $tenantId])`, sync permissions using `$role->syncPermissions($permissions)`, dispatch `RoleCreatedEvent`, return role. | | |
| TASK-008 | Create `packages/authentication/src/Actions/AssignRoleToUserAction.php` using AsAction trait. In `handle(User $user, string|Role $role): void`, verify tenant match between user and role (if role has team_id, must match user tenant_id), use `$user->assignRole($role)`, dispatch `RoleAssignedEvent`, clear user permission cache, log activity. | | |
| TASK-009 | Create `packages/authentication/src/Actions/RevokeRoleFromUserAction.php` using AsAction trait. In `handle(User $user, string|Role $role): void`, verify user has role, use `$user->removeRole($role)`, dispatch `RoleRevokedEvent`, clear user permission cache, log activity. | | |
| TASK-010 | Create `packages/authentication/src/Actions/GrantPermissionToRoleAction.php` using AsAction trait. In `handle(Role $role, string|Permission $permission): void`, use `$role->givePermissionTo($permission)`, dispatch `PermissionGrantedEvent`, clear cache for all users with this role, log activity. | | |
| TASK-011 | Create `packages/authentication/src/Actions/CreatePermissionAction.php` using AsAction trait. In `handle(string $name, ?string $category, ?string $description): Permission`, create permission with `Permission::create(['name' => $name, 'category' => $category, 'description' => $description])`, dispatch `PermissionCreatedEvent`, return permission. | | |
| TASK-012 | Create helper service `packages/authentication/src/Services/PermissionCacheService.php` with methods: `cacheUserPermissions(User $user): void`, `clearUserPermissions(User $user): void`, `warmPermissionsCache(User $user): void`, `getCacheKey(User $user): string`. Use Redis cache with TTL from config. | | |

### GOAL-003: Create Admin User Management API Endpoints

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AA-009, BR-AA-001, BR-AA-007 | Implement RESTful API endpoints for admin user management (CRUD operations, suspend, unlock) with proper authorization, validation, and event dispatching. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-013 | Create `packages/authentication/src/Http/Controllers/UserManagementController.php` with methods: `index(Request)` lists users in current tenant with pagination and filters, `store(CreateUserRequest)` creates user in current tenant, `show(User $user)` returns user details, `update(UpdateUserRequest, User $user)` updates user, `destroy(User $user)` soft-deletes user, `suspend(User $user, Request $request)` suspends user, `unlock(User $user)` unlocks account. Apply `auth:sanctum` and authorization middleware. | | |
| TASK-014 | Create Form Requests: `CreateUserRequest` (name required|string|max:255, email required|email|unique per tenant, password required|min:8, roles optional|array), `UpdateUserRequest` (name, email, password all optional with validation), authorization checks user has 'manage-users' permission. | | |
| TASK-015 | Create `packages/authentication/src/Actions/CreateUserAction.php` using AsAction trait (admin version). In `handle(array $data, string $tenantId): User`, validate email uniqueness per tenant, hash password, create user with tenant_id, assign roles if provided, dispatch `UserCreatedByAdminEvent`, return user. | | |
| TASK-016 | Create `packages/authentication/src/Actions/SuspendUserAction.php` using AsAction trait. In `handle(User $user, string $reason): User`, set user status to suspended (add `status` column to users table or use separate suspended_at timestamp), revoke all tokens, dispatch `UserSuspendedEvent`, log activity with reason, return user. | | |
| TASK-017 | Add API routes in `packages/authentication/routes/api.php`: Group under `/api/v1/admin/users` with `auth:sanctum` middleware. Define resourceful routes for UserManagementController. Add extra routes for POST suspend, POST unlock actions. | | |
| TASK-018 | Create API Resource `packages/authentication/src/Http/Resources/UserResource.php` with comprehensive transformation including id, name, email, tenant info, roles (as array of names), permissions (as array of names), failed_login_attempts, locked_until, created_at, updated_at. Conditionally include sensitive fields only for admins. | | |

### GOAL-004: Implement Authorization Policies and Gates

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-AA-005, BR-AA-007 | Create Laravel Policies and Gates for authorization checks with role hierarchy support and super-admin bypass. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-019 | Create `packages/authentication/src/Policies/UserPolicy.php` with methods: `viewAny(User $user): bool` checks 'view-users' permission, `view(User $user, User $model): bool` checks same tenant or super-admin, `create(User $user): bool` checks 'create-users', `update(User $user, User $model): bool` checks 'update-users' and same tenant, `delete(User $user, User $model): bool` checks 'delete-users' and same tenant, `restore(User $user, User $model): bool` checks 'delete-users', `forceDelete(User $user, User $model): bool` super-admin only. | | |
| TASK-020 | Create `packages/authentication/src/Policies/RolePolicy.php` with methods: `viewAny(User $user): bool` checks 'view-roles', `create(User $user): bool` checks 'manage-roles', `update(User $user, Role $role): bool` checks 'manage-roles' and same tenant, `delete(User $user, Role $role): bool` checks 'manage-roles', prevent deleting super-admin role. | | |
| TASK-021 | Register policies in AuthenticationServiceProvider: In `boot()`, add `Gate::policy(User::class, UserPolicy::class)` and `Gate::policy(Role::class, RolePolicy::class)`. | | |
| TASK-022 | Define Gates in AuthenticationServiceProvider: `Gate::define('manage-roles', fn(User $user) => $user->hasPermissionTo('manage-roles'))`, `Gate::define('manage-permissions', fn(User $user) => $user->hasPermissionTo('manage-permissions'))`, `Gate::define('impersonate-user', fn(User $user) => $user->hasRole('super-admin'))`, `Gate::before()` to allow super-admin all permissions. | | |
| TASK-023 | Implement `Gate::before()` callback for super-admin bypass: In AuthenticationServiceProvider::boot(), add `Gate::before(function (User $user, string $ability) { if ($user->hasRole('super-admin')) { return true; } })`. This grants super-admin all permissions without explicit checks. | | |
| TASK-024 | Create authorization middleware `packages/authentication/src/Http/Middleware/CheckPermission.php` implementing `handle(Request $request, Closure $next, string $permission): Response`. Check `auth()->user()->can($permission)`. Return 403 Forbidden if fails. Use cached permissions. | | |

### GOAL-005: Implement Event System and Comprehensive Testing

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| EV-AA-001, EV-AA-002, EV-AA-003, EV-AA-004, EV-AA-005, IR-AA-001, IR-AA-002 | Create domain events for authentication activities, implement event listeners for audit logging and notifications, and comprehensive test suite for RBAC and user management. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-025 | Create domain events in `packages/authentication/src/Events/`: `UserLoggedInEvent` (User $user, string $token, string $deviceName), `LoginFailedEvent` (string $email, int $attemptsRemaining), `AccountLockedEvent` (User $user, Carbon $lockedUntil), `PasswordResetRequestedEvent` (User $user, string $resetToken), `PasswordChangedEvent` (User $user), `UserSuspendedEvent` (User $user, string $reason), `RoleAssignedEvent` (User $user, Role $role), `RoleRevokedEvent` (User $user, Role $role), `PermissionGrantedEvent` (Role $role, Permission $permission). | | |
| TASK-026 | Create event listeners in `packages/authentication/src/Listeners/`: `NotifyAccountLockedListener` (listens to AccountLockedEvent, dispatches notification to SUB22), `ClearUserCacheOnRoleChangeListener` (listens to RoleAssignedEvent and RoleRevokedEvent, clears permission cache), `LogAuthenticationEventsListener` (listens to all auth events, logs to SUB03 via ActivityLoggerContract). | | |
| TASK-027 | Register events and listeners in AuthenticationServiceProvider: In `boot()`, use `Event::listen()` to register all listeners. Alternatively, use `$listen` array in EventServiceProvider if using traditional approach. | | |
| TASK-028 | Create feature tests for RBAC in `packages/authentication/tests/Feature/RbacTest.php`: Test role creation, role assignment, permission granting, permission checking, role hierarchy (super-admin > tenant-admin > manager > user), tenant isolation (cannot assign cross-tenant roles), permission caching, cache invalidation on role changes. | | |
| TASK-029 | Create feature tests for admin endpoints in `packages/authentication/tests/Feature/Admin/UserManagementTest.php`: Test GET /admin/users (200, pagination), POST /admin/users (201, validation), GET /admin/users/{id} (200, 404), PATCH /admin/users/{id} (200, 403 for cross-tenant), DELETE /admin/users/{id} (204), POST /admin/users/{id}/suspend (200), POST /admin/users/{id}/unlock (200), authorization checks for each endpoint. | | |
| TASK-030 | Create integration tests in `packages/authentication/tests/Integration/AuthenticationWorkflowTest.php`: Test complete workflow (register → login → access protected resource → assign role → check permission → logout), test tenant isolation (User A cannot manage User B from different tenant), test super-admin can access all tenants, test event emission to audit logging and notifications. | | |
| TASK-031 | Create performance tests in `packages/authentication/tests/Performance/PermissionCheckPerformanceTest.php`: Test permission check with cache hit completes < 10ms, test permission check with cache miss completes < 100ms, test loading 100 permissions for user completes < 200ms, verify cache effectiveness (cache hit rate > 95% after warmup). | | |
| TASK-032 | Update package README.md with: RBAC usage guide (creating roles, assigning permissions), API endpoint documentation for admin user management, authorization examples (using policies and gates), permission caching explanation, troubleshooting permission issues. | | |

## 3. Alternatives

- **ALT-001**: Build custom RBAC system instead of using Spatie Permission - Rejected because Spatie Permission is battle-tested, actively maintained, and provides all required features including team scoping. Building custom would increase development time and security risk.
- **ALT-002**: Use Laravel's built-in Gate system without Spatie - Rejected because managing role-permission relationships manually is error-prone and lacks caching optimization that Spatie provides.
- **ALT-003**: Store permissions in Redis only (no database) - Rejected because database provides better audit trail and permission management. Redis is for caching only.
- **ALT-004**: Use attribute-based access control (ABAC) instead of RBAC - Rejected because ABAC is more complex and overkill for this use case. RBAC is sufficient for most ERP scenarios.
- **ALT-005**: Implement permission checks in middleware only - Rejected because policies provide better encapsulation and reusability. Middleware is only for high-level checks.

## 4. Dependencies

### Package Dependencies

- **DEP-001**: laravel/framework ^12.0 (Core framework, Gates, Policies)
- **DEP-002**: spatie/laravel-permission ^6.0 (RBAC implementation)
- **DEP-003**: illuminate/cache ^12.0 (Redis cache for permissions)
- **DEP-004**: lorisleiva/laravel-actions ^2.0 (Action pattern for business logic)

### Internal Package Dependencies

- **DEP-005**: azaharizaman/erp-multitenancy (SUB01) - Required for tenant context
- **DEP-006**: azaharizaman/erp-authentication/PLAN01 - Core authentication infrastructure (User model, Actions, API endpoints)

### Infrastructure Dependencies

- **DEP-007**: PostgreSQL 14+ or MySQL 8.0+ (Roles and permissions data)
- **DEP-008**: Redis 6+ (Required for permission caching)

### Development Dependencies

- **DEP-009**: pestphp/pest ^4.0 (Testing framework)
- **DEP-010**: laravel/pint ^1.0 (Code formatting)

## 5. Files

### Models and Database

- **packages/authentication/database/migrations/create_permission_tables.php**: Spatie Permission tables (roles, permissions, pivot tables)
- **packages/authentication/database/seeders/RolePermissionSeeder.php**: Default roles and permissions seeder

### Actions

- **packages/authentication/src/Actions/CreateRoleAction.php**: Create new role with permissions
- **packages/authentication/src/Actions/AssignRoleToUserAction.php**: Assign role to user
- **packages/authentication/src/Actions/RevokeRoleFromUserAction.php**: Remove role from user
- **packages/authentication/src/Actions/GrantPermissionToRoleAction.php**: Grant permission to role
- **packages/authentication/src/Actions/CreatePermissionAction.php**: Create new permission
- **packages/authentication/src/Actions/CreateUserAction.php**: Admin user creation
- **packages/authentication/src/Actions/SuspendUserAction.php**: Suspend user access

### API Layer

- **packages/authentication/src/Http/Controllers/UserManagementController.php**: Admin user management endpoints
- **packages/authentication/src/Http/Controllers/RoleController.php**: Role management endpoints
- **packages/authentication/src/Http/Controllers/PermissionController.php**: Permission management endpoints
- **packages/authentication/src/Http/Requests/CreateUserRequest.php**: User creation validation
- **packages/authentication/src/Http/Requests/UpdateUserRequest.php**: User update validation
- **packages/authentication/src/Http/Requests/CreateRoleRequest.php**: Role creation validation
- **packages/authentication/src/Http/Resources/UserResource.php**: User JSON:API transformation (enhanced)
- **packages/authentication/src/Http/Resources/RoleResource.php**: Role JSON:API transformation
- **packages/authentication/src/Http/Resources/PermissionResource.php**: Permission JSON:API transformation

### Policies and Middleware

- **packages/authentication/src/Policies/UserPolicy.php**: User authorization policy
- **packages/authentication/src/Policies/RolePolicy.php**: Role authorization policy
- **packages/authentication/src/Http/Middleware/CheckPermission.php**: Permission checking middleware

### Services

- **packages/authentication/src/Services/PermissionCacheService.php**: Permission caching management

### Events and Listeners

- **packages/authentication/src/Events/UserLoggedInEvent.php**: User login event
- **packages/authentication/src/Events/LoginFailedEvent.php**: Failed login event
- **packages/authentication/src/Events/AccountLockedEvent.php**: Account lockout event
- **packages/authentication/src/Events/PasswordResetRequestedEvent.php**: Password reset request event
- **packages/authentication/src/Events/PasswordChangedEvent.php**: Password change event
- **packages/authentication/src/Events/UserSuspendedEvent.php**: User suspension event
- **packages/authentication/src/Events/RoleAssignedEvent.php**: Role assignment event
- **packages/authentication/src/Events/RoleRevokedEvent.php**: Role revocation event
- **packages/authentication/src/Events/PermissionGrantedEvent.php**: Permission grant event
- **packages/authentication/src/Listeners/NotifyAccountLockedListener.php**: Account lockout notification
- **packages/authentication/src/Listeners/ClearUserCacheOnRoleChangeListener.php**: Cache invalidation
- **packages/authentication/src/Listeners/LogAuthenticationEventsListener.php**: Audit logging

## 6. Testing

### Unit Tests

- **TEST-001**: Test User model `hasRole()` returns true when user has role
- **TEST-002**: Test User model `hasPermissionTo()` returns true when user has permission via role
- **TEST-003**: Test User model `getPermissionTeamId()` returns tenant_id
- **TEST-004**: Test CreateRoleAction validates name uniqueness per tenant
- **TEST-005**: Test AssignRoleToUserAction verifies tenant match
- **TEST-006**: Test RevokeRoleFromUserAction clears permission cache
- **TEST-007**: Test GrantPermissionToRoleAction syncs permissions correctly
- **TEST-008**: Test PermissionCacheService caches user permissions
- **TEST-009**: Test PermissionCacheService clears user permissions
- **TEST-010**: Test UserPolicy::update() prevents cross-tenant updates
- **TEST-011**: Test RolePolicy::create() checks 'manage-roles' permission
- **TEST-012**: Test super-admin bypasses all permission checks via Gate::before()

### Feature Tests

- **TEST-013**: Test GET /api/v1/admin/users returns tenant-scoped users (200)
- **TEST-014**: Test POST /api/v1/admin/users creates user with roles (201)
- **TEST-015**: Test POST /api/v1/admin/users validates email uniqueness per tenant (422)
- **TEST-016**: Test PATCH /api/v1/admin/users/{id} updates user (200)
- **TEST-017**: Test PATCH /api/v1/admin/users/{id} returns 403 for cross-tenant update
- **TEST-018**: Test DELETE /api/v1/admin/users/{id} soft-deletes user (204)
- **TEST-019**: Test POST /api/v1/admin/users/{id}/suspend suspends user and revokes tokens (200)
- **TEST-020**: Test POST /api/v1/admin/users/{id}/unlock unlocks account (200)
- **TEST-021**: Test GET /api/v1/admin/roles returns roles (200)
- **TEST-022**: Test POST /api/v1/admin/roles creates role with permissions (201)
- **TEST-023**: Test POST /api/v1/admin/users/{id}/roles assigns role (200)
- **TEST-024**: Test DELETE /api/v1/admin/users/{id}/roles/{role} revokes role (204)
- **TEST-025**: Test authorization: user without 'manage-users' permission gets 403
- **TEST-026**: Test super-admin can access all endpoints regardless of tenant

### Integration Tests

- **TEST-027**: Test role assignment emits RoleAssignedEvent
- **TEST-028**: Test RoleAssignedEvent triggers cache clearing
- **TEST-029**: Test AccountLockedEvent triggers notification to SUB22
- **TEST-030**: Test authentication events logged to SUB03 audit log
- **TEST-031**: Test permission cache invalidation propagates immediately
- **TEST-032**: Test tenant isolation: Admin in Tenant A cannot manage users in Tenant B
- **TEST-033**: Test super-admin can manage users across all tenants
- **TEST-034**: Test role hierarchy: tenant-admin has more permissions than manager

### Performance Tests

- **TEST-035**: Test permission check with cached permissions completes < 10ms
- **TEST-036**: Test permission check with cache miss completes < 100ms
- **TEST-037**: Test loading 100 permissions for user completes < 200ms
- **TEST-038**: Test cache hit rate > 95% after warmup period

## 7. Risks & Assumptions

### Risks

- **RISK-001**: **Permission cache staleness** - Cached permissions might not reflect recent changes immediately. Mitigation: Implement cache tags for group invalidation, clear cache on any role/permission change, use short TTL (1 hour).
- **RISK-002**: **Super-admin abuse** - Super-admin role has unlimited access which could be abused. Mitigation: Comprehensive audit logging of super-admin actions, require MFA for super-admin (future), limit number of super-admin accounts.
- **RISK-003**: **Role proliferation** - Too many roles created leading to management complexity. Mitigation: Provide role templates, documentation on role design best practices, periodic role audit.
- **RISK-004**: **Cross-tenant permission leakage** - Bug in team_id scoping could allow cross-tenant permission checks. Mitigation: Comprehensive integration tests, code review focus on tenant isolation, security audit.
- **RISK-005**: **Performance degradation** - Permission checks on every request might slow down API. Mitigation: Aggressive caching, permission warming on login, use cache for 95%+ of checks.

### Assumptions

- **ASSUMPTION-001**: Redis is available for permission caching in all environments
- **ASSUMPTION-002**: Role hierarchy is relatively flat (max 5 levels)
- **ASSUMPTION-003**: Most users have 1-3 roles, not dozens
- **ASSUMPTION-004**: Permission changes are infrequent (not real-time requirement)
- **ASSUMPTION-005**: Super-admin role will be tightly controlled (< 5 accounts per tenant)
- **ASSUMPTION-006**: Spatie Permission v6.x will maintain backward compatibility
- **ASSUMPTION-007**: Cache invalidation latency < 1 second is acceptable

## 8. Related PRD / Further Reading

### Primary Documentation

- **PRD01-SUB02: Authentication & Authorization System**: [/docs/prd/prd-01/PRD01-SUB02-AUTHENTICATION.md](../prd/prd-01/PRD01-SUB02-AUTHENTICATION.md)
- **Master PRD**: [/docs/prd/PRD01-MVP.md](../prd/PRD01-MVP.md)

### Related Plans

- **PLAN01: Authentication Core Infrastructure**: [/docs/plan/PRD01-SUB02-PLAN01-implement-authentication-core.md](./PRD01-SUB02-PLAN01-implement-authentication-core.md)
- **PLAN01: Multi-Tenancy Core Infrastructure**: [/docs/plan/PRD01-SUB01-PLAN01-implement-multitenancy-core.md](./PRD01-SUB01-PLAN01-implement-multitenancy-core.md)

### Architectural Documentation

- **Package Decoupling Strategy**: [/docs/architecture/PACKAGE-DECOUPLING-STRATEGY.md](../architecture/PACKAGE-DECOUPLING-STRATEGY.md)
- **Coding Guidelines**: [/CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)

### Laravel Documentation

- **Laravel 12 Authorization (Gates)**: https://laravel.com/docs/12.x/authorization#gates
- **Laravel 12 Authorization (Policies)**: https://laravel.com/docs/12.x/authorization#creating-policies
- **Laravel 12 Caching**: https://laravel.com/docs/12.x/cache

### Spatie Documentation

- **Spatie Laravel Permission**: https://spatie.be/docs/laravel-permission/v6/introduction
- **Spatie Permission Teams**: https://spatie.be/docs/laravel-permission/v6/advanced-usage/teams

### External Resources

- **RBAC Best Practices**: https://en.wikipedia.org/wiki/Role-based_access_control
- **OWASP Authorization Cheat Sheet**: https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html
