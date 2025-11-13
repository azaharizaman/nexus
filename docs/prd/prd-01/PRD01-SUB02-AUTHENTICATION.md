# PRD01-SUB02: Authentication & Authorization System

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Mandatory Feature Modules - Core Infrastructure  
**Related Sub-PRDs:** PRD01-SUB01 (Multi-Tenancy), PRD01-SUB03 (Audit Logging), PRD01-SUB22 (Notifications & Events), PRD01-SUB23 (API Gateway)  
**Composer Package:** `azaharizaman/erp-authentication`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Authentication & Authorization System provides **stateless API authentication using Laravel Sanctum with personal access tokens, role-based permissions, and security controls**. This mandatory feature module ensures secure access to the ERP system through token-based authentication, granular permission management, and comprehensive security features including account lockout and rate limiting.

### Purpose

The Authentication & Authorization System solves the critical problem of **secure API access control** in a headless architecture. It enables:

1. **Stateless API Security:** Token-based authentication perfect for SPAs, mobile apps, and third-party integrations
2. **Granular Access Control:** Role-Based Access Control (RBAC) with fine-grained permissions
3. **Multi-Factor Security:** Password security, account lockout, and rate limiting prevent unauthorized access
4. **Tenant Isolation:** Authentication scoped to tenants preventing cross-tenant access
5. **Developer-Friendly:** Simple token management with clear API endpoints

### Scope

**Included in this Feature Module:**

- ✅ Laravel Sanctum integration for API token authentication
- ✅ Personal access token generation and management
- ✅ Role-Based Access Control (RBAC) system
- ✅ Permission management and authorization checks
- ✅ Password security (Argon2/bcrypt hashing)
- ✅ Account lockout after failed login attempts
- ✅ API rate limiting on authentication endpoints
- ✅ Tenant-scoped authentication
- ✅ Token revocation and refresh mechanisms
- ✅ User management (create, update, suspend, delete)

**Excluded from this Feature Module:**

- ❌ Multi-tenancy infrastructure (handled by SUB01)
- ❌ Activity logging (handled by SUB03)
- ❌ Email notifications (handled by SUB22)
- ❌ OAuth2/Social authentication (future enhancement)
- ❌ Two-factor authentication (future enhancement)

### Dependencies

**Mandatory Dependencies:**
- Laravel Framework v12.x
- PHP ≥ 8.2
- Laravel Sanctum
- Spatie Laravel Permission
- PRD01-SUB01 (Multi-Tenancy System)

**Feature Module Dependencies:**
- **Mandatory:** SUB01 (Multi-Tenancy) - Required for tenant-scoped authentication

### Composer Package Information

- **Package Name:** `azaharizaman/erp-authentication`
- **Namespace:** `Nexus\Erp\Authentication`
- **Monorepo Location:** `/packages/authentication/`
- **Installation:** `composer require azaharizaman/erp-authentication` (post v1.0 release)

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB02 (Authentication & Authorization). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-AA-001** | Implement **Multi-Factor Authentication (MFA)** supporting TOTP (Time-based One-Time Password) using authenticator apps | High | Planned |
| **FR-AA-002** | Implement **API Authentication** using token-based access control (Laravel Sanctum) supporting personal access tokens | High | Planned |
| **FR-AA-003** | Develop a **Role-Based Access Control (RBAC)** system with roles, permissions, and role hierarchy | High | Planned |
| **FR-AA-004** | Support **OAuth2 Authentication** for third-party integrations with token-based access | Medium | Planned |
| **FR-AA-005** | Implement **Session Management** with secure session handling and automatic timeout | High | Planned |
| **FR-AA-006** | Enforce **Password Security** through salted hashing using Argon2 or bcrypt with configurable complexity requirements | High | Planned |
| **FR-AA-007** | Provide **Password Reset** functionality with secure token-based email verification | High | Planned |
| **FR-AA-008** | Enable **Account Lockout** after repeated failed login attempts with configurable threshold and lockout duration | High | Planned |
| **FR-AA-009** | Support **Permission Management** interface for admins to assign/revoke permissions | Medium | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-AA-001** | User authentication MUST be **tenant-scoped** - users can only authenticate within their assigned tenant | Planned |
| **BR-AA-002** | Tokens MUST include **user ID, tenant ID, and expiration timestamp** in encrypted payload | Planned |
| **BR-AA-003** | Failed login attempts MUST **reset to zero** after successful authentication | Planned |
| **BR-AA-004** | Account lockout MUST **expire automatically** after configured duration (default: 30 minutes) | Planned |
| **BR-AA-005** | Users with `super-admin` role can **access all tenants** (for support purposes) | Planned |
| **BR-AA-006** | Permissions are **inherited from roles** - users can have multiple roles | Planned |
| **BR-AA-007** | Role hierarchy enforced: `super-admin` > `tenant-admin` > `manager` > `user` | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-AA-001** | Users table MUST include: id (UUID), tenant_id, name, email (unique per tenant), password (hashed), failed_login_attempts, locked_until, timestamps | Planned |
| **DR-AA-002** | Roles table MUST include: id, name, guard_name, team_id (tenant_id for tenant-scoped roles), permissions array | Planned |
| **DR-AA-003** | Permissions table MUST include: id, name, guard_name, description, category | Planned |
| **DR-AA-004** | Personal access tokens table MUST include: id, tokenable_id, name, token (hashed), abilities, last_used_at, expires_at | Planned |
| **DR-AA-005** | Email MUST be indexed per tenant for fast lookup: **UNIQUE(tenant_id, email)** | Planned |

### Integration Requirements (IR)

| Requirement ID | Integration Point | Status |
|----------------|------------------|--------|
| **IR-AA-001** | Emit events to **SUB03 (Audit Logging)** for all authentication activities | Planned |
| **IR-AA-002** | Emit events to **SUB22 (Notifications)** for account lockout alerts | Planned |
| **IR-AA-003** | Use **SUB01 (Multi-Tenancy)** for tenant context resolution during login | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-AA-001** | Login and token validation operations must complete under **300ms** on average | Planned |
| **PR-AA-002** | Token validation MUST use **caching (Redis)** to minimize database queries | Planned |
| **PR-AA-003** | Permission checks MUST be **cached per user session** to avoid repeated queries | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-AA-001** | Ensure **Tenant-Scoped Authentication** - users cannot authenticate across tenant boundaries | Planned |
| **SR-AA-002** | Passwords MUST be hashed using **Argon2id or bcrypt** with minimum 12 rounds | Planned |
| **SR-AA-003** | Enforce **API Rate Limiting** on authentication endpoints (default: 5 attempts per minute) | Planned |
| **SR-AA-004** | Tokens MUST have **configurable expiration** (default: 30 days for API tokens) | Planned |
| **SR-AA-005** | Password reset tokens MUST **expire after 1 hour** and be single-use | Planned |
| **SR-AA-006** | All authentication failures MUST be **logged for security audit** | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-AA-001** | Support **10,000+ concurrent authenticated users** per tenant with Redis session cache | Planned |

### Compliance Requirements (CR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **CR-AA-001** | Maintain **audit trail of all login attempts** for compliance (SOX, PCI-DSS) | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-AA-001** | Use **Laravel Sanctum** for API token management with database token storage | Planned |
| **ARCH-AA-002** | Use **Spatie Laravel Permission** for RBAC with team_id scoping for multi-tenancy | Planned |
| **ARCH-AA-003** | Implement **Redis caching** for permission checks and token validation | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-AA-001** | `UserAuthenticatedEvent` | When user successfully logs in | Planned |
| **EV-AA-002** | `AuthenticationFailedEvent` | When login attempt fails | Planned |
| **EV-AA-003** | `AccountLockedEvent` | When account is locked due to failed attempts | Planned |
| **EV-AA-004** | `PasswordResetRequestedEvent` | When user requests password reset | Planned |
| **EV-AA-005** | `PasswordChangedEvent` | When user changes password | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| ARCH-AA-001 | Use Laravel Sanctum for stateless token authentication | Planned |
| ARCH-AA-002 | Use Spatie Laravel Permission for RBAC implementation | Planned |
| ARCH-AA-003 | Store tokens using SHA-256 hashing for security | Planned |
| ARCH-AA-004 | Implement middleware for token validation and permission checks | Planned |

---

## Technical Specifications

### Database Schema

**Users Table:**

```sql
CREATE TABLE users (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_email_per_tenant (tenant_id, email),
    INDEX idx_users_tenant (tenant_id),
    INDEX idx_users_email (email),
    INDEX idx_users_locked (locked_until)
);
```

**Roles Table (Spatie Permission):**

```sql
CREATE TABLE roles (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    team_id UUID NULL,  -- Maps to tenant_id for tenant-specific roles
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (team_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_name (team_id, name, guard_name),
    INDEX idx_roles_team (team_id)
);
```

**Permissions Table (Spatie Permission):**

```sql
CREATE TABLE permissions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(50) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY unique_permission_name (name, guard_name),
    INDEX idx_permissions_category (category)
);
```

**Personal Access Tokens Table (Sanctum):**

```sql
CREATE TABLE personal_access_tokens (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_tokenable (tokenable_type, tokenable_id),
    INDEX idx_token (token)
);
```

### API Endpoints

All endpoints follow `/api/v1/auth` pattern:

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| POST | `/api/v1/auth/register` | Register new user account | No |
| POST | `/api/v1/auth/login` | Authenticate and receive token | No |
| POST | `/api/v1/auth/logout` | Revoke current token | Yes |
| POST | `/api/v1/auth/refresh` | Refresh token expiration | Yes |
| GET | `/api/v1/auth/me` | Get current user profile | Yes |
| PATCH | `/api/v1/auth/me` | Update current user profile | Yes |
| POST | `/api/v1/auth/password/forgot` | Request password reset | No |
| POST | `/api/v1/auth/password/reset` | Reset password with token | No |
| POST | `/api/v1/auth/password/change` | Change password (authenticated) | Yes |
| GET | `/api/v1/auth/tokens` | List user's active tokens | Yes |
| DELETE | `/api/v1/auth/tokens/{id}` | Revoke specific token | Yes |
| DELETE | `/api/v1/auth/tokens/all` | Revoke all tokens | Yes |

**Admin Endpoints:**

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/v1/admin/users` | List all users (tenant-scoped) | Yes - Admin |
| POST | `/api/v1/admin/users` | Create new user | Yes - Admin |
| GET | `/api/v1/admin/users/{id}` | Get user details | Yes - Admin |
| PATCH | `/api/v1/admin/users/{id}` | Update user | Yes - Admin |
| DELETE | `/api/v1/admin/users/{id}` | Soft delete user | Yes - Admin |
| POST | `/api/v1/admin/users/{id}/suspend` | Suspend user access | Yes - Admin |
| POST | `/api/v1/admin/users/{id}/unlock` | Unlock locked account | Yes - Admin |
| GET | `/api/v1/admin/roles` | List all roles | Yes - Admin |
| POST | `/api/v1/admin/roles` | Create new role | Yes - Admin |
| GET | `/api/v1/admin/permissions` | List all permissions | Yes - Admin |
| POST | `/api/v1/admin/users/{id}/roles` | Assign role to user | Yes - Admin |
| POST | `/api/v1/admin/roles/{id}/permissions` | Assign permissions to role | Yes - Admin |

**Request/Response Examples:**

**Login:**
```json
// POST /api/v1/auth/login
{
    "email": "john@acme.com",
    "password": "SecurePassword123!",
    "device_name": "iPhone 14 Pro"
}

// Response 200 OK
{
    "data": {
        "token": "1|abcdef1234567890...",
        "user": {
            "id": "550e8400-e29b-41d4-a716-446655440000",
            "name": "John Doe",
            "email": "john@acme.com",
            "tenant": {
                "id": "660e8400-e29b-41d4-a716-446655440001",
                "name": "Acme Corporation"
            },
            "roles": ["manager", "sales-user"],
            "permissions": ["view-orders", "create-orders", "view-customers"]
        },
        "expires_at": "2025-12-11T10:00:00Z"
    }
}

// Response 401 Unauthorized (Failed Login)
{
    "message": "Invalid credentials",
    "attempts_remaining": 3
}

// Response 423 Locked (Account Locked)
{
    "message": "Account locked due to too many failed attempts",
    "locked_until": "2025-11-11T10:30:00Z"
}
```

**Create User (Admin):**
```json
// POST /api/v1/admin/users
{
    "name": "Jane Smith",
    "email": "jane@acme.com",
    "password": "SecurePassword123!",
    "roles": ["user"]
}

// Response 201 Created
{
    "data": {
        "id": "770e8400-e29b-41d4-a716-446655440002",
        "name": "Jane Smith",
        "email": "jane@acme.com",
        "tenant_id": "660e8400-e29b-41d4-a716-446655440001",
        "roles": ["user"],
        "created_at": "2025-11-11T10:00:00Z"
    }
}
```

### Events

**Domain Events Emitted by this Feature Module:**

| Event Class | When Fired | Payload |
|-------------|-----------|---------|
| `UserRegisteredEvent` | After new user account created | `User $user` |
| `UserLoggedInEvent` | After successful authentication | `User $user, string $token, string $deviceName` |
| `UserLoggedOutEvent` | After token revocation | `User $user, string $tokenId` |
| `UserPasswordChangedEvent` | After password updated | `User $user` |
| `UserPasswordResetRequestedEvent` | When password reset requested | `User $user, string $resetToken` |
| `UserPasswordResetEvent` | After password successfully reset | `User $user` |
| `LoginFailedEvent` | After failed login attempt | `string $email, int $attemptsRemaining` |
| `AccountLockedEvent` | When account locked due to failed attempts | `User $user, Carbon $lockedUntil` |
| `AccountUnlockedEvent` | When account unlocked by admin or timeout | `User $user` |
| `UserSuspendedEvent` | When user suspended by admin | `User $user, string $reason` |
| `RoleAssignedEvent` | When role assigned to user | `User $user, Role $role` |
| `RoleRevokedEvent` | When role removed from user | `User $user, Role $role` |
| `PermissionGrantedEvent` | When permission assigned to role | `Role $role, Permission $permission` |
| `TokenRevokedEvent` | When token revoked | `User $user, string $tokenId` |

**Event Usage Example:**
```php
use Nexus\Erp\Authentication\Events\UserLoggedInEvent;

// Emit event after successful login
event(new UserLoggedInEvent($user, $token, $deviceName));

// Other modules can listen:
class LogUserLoginListener
{
    public function handle(UserLoggedInEvent $event): void
    {
        // Log to audit trail
        activity()
            ->causedBy($event->user)
            ->withProperties(['device' => $event->deviceName])
            ->log('User logged in');
    }
}
```

### Event Listeners

**Events this Feature Module Listens To:**

| Event Source | Event Class | Action |
|--------------|-------------|--------|
| SUB01 (Multi-Tenancy) | `TenantSuspendedEvent` | Revoke all tokens for users in suspended tenant |
| SUB01 (Multi-Tenancy) | `TenantDeletedEvent` | Soft delete all users belonging to deleted tenant |

---

## Implementation Plans

**Note:** Implementation plans follow the naming convention `PLAN{number}-{action}-{component}.md`

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN02-implement-authentication.md | FR-AA-002, FR-AA-003, FR-AA-006, FR-AA-008, SR-AA-001, SR-AA-002, SR-AA-003, PR-AA-001, ARCH-AA-001, ARCH-AA-002, ARCH-AA-003, ARCH-AA-004 | MILESTONE 1 | Not Started |

**Implementation plan will be created separately using:** `.github/prompts/create-implementation-plan.prompt.md`

---

## Acceptance Criteria

### Functional Acceptance

- [ ] User registration with validation implemented
- [ ] Login returns valid Sanctum token
- [ ] Token validation middleware works correctly
- [ ] Role and permission assignment functional
- [ ] Account lockout after 5 failed attempts
- [ ] Password hashing uses Argon2id or bcrypt
- [ ] Password reset flow complete with email
- [ ] Token revocation works (single and all tokens)
- [ ] Tenant-scoped authentication enforced
- [ ] Admin user management endpoints functional

### Performance Acceptance

- [ ] Login completes in < 300ms (average)
- [ ] Token validation completes in < 50ms (cached)
- [ ] Permission checks use caching effectively

### Security Acceptance

- [ ] Passwords never stored in plain text
- [ ] Tokens hashed with SHA-256
- [ ] Rate limiting prevents brute force attacks
- [ ] Cross-tenant authentication prevented
- [ ] Failed login attempts logged
- [ ] Account lockout logs sent to audit system

### Testing Acceptance

- [ ] 100% unit test coverage for authentication logic
- [ ] Feature tests for all API endpoints
- [ ] Security tests verify tenant isolation
- [ ] Performance tests validate < 300ms login time
- [ ] Integration tests with SUB01 (Multi-Tenancy)

### Documentation Acceptance

- [ ] API documentation complete (OpenAPI/Swagger)
- [ ] Authentication flow documented with diagrams
- [ ] RBAC configuration guide created
- [ ] Password policy documentation complete
- [ ] PHPDoc complete for all public classes

### Code Quality Acceptance

- [ ] Code passes Laravel Pint formatting
- [ ] PHPStan level 5 compliance
- [ ] No direct package usage (use contracts)
- [ ] All files include `declare(strict_types=1);`

---

## Testing Strategy

### Unit Tests

**Test Coverage Areas:**
- User model validation and relationships
- Password hashing and verification
- Failed login attempt tracking
- Account lockout logic
- Token generation and validation
- Role and permission assignment
- Helper functions

**Test Examples:**
```php
test('user password is hashed on creation', function () {
    $user = User::factory()->create(['password' => 'password']);
    
    expect($user->password)->not->toBe('password');
    expect(Hash::check('password', $user->password))->toBeTrue();
});

test('increments failed login attempts on failed login', function () {
    $user = User::factory()->create(['failed_login_attempts' => 2]);
    
    $user->incrementFailedAttempts();
    
    expect($user->failed_login_attempts)->toBe(3);
});

test('locks account after 5 failed attempts', function () {
    $user = User::factory()->create(['failed_login_attempts' => 4]);
    
    $user->incrementFailedAttempts();
    
    expect($user->isLocked())->toBeTrue();
    expect($user->locked_until)->not->toBeNull();
});

test('resets failed attempts on successful login', function () {
    $user = User::factory()->create(['failed_login_attempts' => 3]);
    
    $user->resetFailedAttempts();
    
    expect($user->failed_login_attempts)->toBe(0);
});
```

### Feature Tests

**Test Coverage Areas:**
- Login endpoint with valid/invalid credentials
- Token generation and format
- Token-based API access
- Account lockout after failed attempts
- Password reset flow
- User CRUD operations via admin endpoints
- Role and permission assignment
- Rate limiting on auth endpoints

**Test Examples:**
```php
test('can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password')
    ]);
    
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
        'device_name' => 'Test Device'
    ]);
    
    $response->assertOk()
        ->assertJsonStructure(['data' => ['token', 'user', 'expires_at']]);
});

test('cannot login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password')
    ]);
    
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
        'device_name' => 'Test Device'
    ]);
    
    $response->assertUnauthorized()
        ->assertJson(['message' => 'Invalid credentials']);
});

test('locks account after 5 failed login attempts', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password')
    ]);
    
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
            'device_name' => 'Test Device'
        ]);
    }
    
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
        'device_name' => 'Test Device'
    ]);
    
    $response->assertStatus(423)
        ->assertJson(['message' => 'Account locked due to too many failed attempts']);
});

test('can access protected endpoint with valid token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    
    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me');
    
    $response->assertOk()
        ->assertJson(['data' => ['email' => $user->email]]);
});

test('cannot access protected endpoint without token', function () {
    $response = $this->getJson('/api/v1/auth/me');
    
    $response->assertUnauthorized();
});
```

### Integration Tests

**Test Coverage Areas:**
- Tenant isolation in authentication
- Event emission to audit logging
- Event emission to notification system
- Role and permission caching
- Token expiration and cleanup

**Test Examples:**
```php
test('users can only login to their own tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $user1 = User::factory()->for($tenant1)->create(['email' => 'test@example.com']);
    $user2 = User::factory()->for($tenant2)->create(['email' => 'test@example.com']);
    
    TenantContext::set($tenant1);
    
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
        'device_name' => 'Test'
    ]);
    
    $response->assertOk();
    expect($response->json('data.user.id'))->toBe($user1->id);
});

test('login event triggers audit log', function () {
    Event::fake([UserLoggedInEvent::class]);
    
    $user = User::factory()->create();
    
    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Test'
    ]);
    
    Event::assertDispatched(UserLoggedInEvent::class);
});
```

### Performance Tests

**Test Coverage Areas:**
- Login response time
- Token validation speed
- Permission check caching
- Database query optimization

---

## Dependencies

### Feature Module Dependencies

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy):** Required for tenant context and isolation

**Optional Dependencies:**
- **None**

### External Package Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/framework` | ^12.0 | Core framework |
| `laravel/sanctum` | ^4.0 | API token authentication |
| `spatie/laravel-permission` | ^6.0 | RBAC implementation |
| `illuminate/hashing` | ^12.0 | Password hashing |

### Infrastructure Dependencies

| Component | Requirement | Purpose |
|-----------|------------|---------|
| **Database** | PostgreSQL 14+ or MySQL 8.0+ | User and permission data |
| **Cache** | Redis 6+ or Memcached 1.6+ | Token and permission caching |
| **PHP Extensions** | `ext-redis`, `ext-pdo`, `ext-hash` | Runtime dependencies |

---

## Feature Module Structure

### Directory Structure (in Monorepo)

```
packages/authentication/
├── src/
│   ├── Actions/
│   │   ├── LoginAction.php
│   │   ├── RegisterAction.php
│   │   ├── LogoutAction.php
│   │   ├── RefreshTokenAction.php
│   │   ├── ForgotPasswordAction.php
│   │   ├── ResetPasswordAction.php
│   │   ├── ChangePasswordAction.php
│   │   └── RevokeTokenAction.php
│   ├── Contracts/
│   │   ├── UserRepositoryContract.php
│   │   ├── AuthenticationServiceContract.php
│   │   └── PermissionServiceContract.php
│   ├── Events/
│   │   ├── UserRegisteredEvent.php
│   │   ├── UserLoggedInEvent.php
│   │   ├── UserLoggedOutEvent.php
│   │   ├── LoginFailedEvent.php
│   │   ├── AccountLockedEvent.php
│   │   ├── AccountUnlockedEvent.php
│   │   ├── UserPasswordChangedEvent.php
│   │   └── RoleAssignedEvent.php
│   ├── Listeners/
│   │   ├── LogLoginAttemptListener.php
│   │   ├── SendAccountLockedNotificationListener.php
│   │   └── RevokeTenantTokensListener.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   └── UserController.php
│   │   ├── Requests/
│   │   │   ├── LoginRequest.php
│   │   │   ├── RegisterRequest.php
│   │   │   ├── ForgotPasswordRequest.php
│   │   │   ├── ResetPasswordRequest.php
│   │   │   └── CreateUserRequest.php
│   │   ├── Resources/
│   │   │   └── UserResource.php
│   │   └── Middleware/
│   │       ├── EnsureTokenIsValid.php
│   │       ├── CheckPermission.php
│   │       └── ThrottleAuthentication.php
│   ├── Models/
│   │   └── User.php
│   ├── Repositories/
│   │   └── UserRepository.php
│   ├── Services/
│   │   ├── AuthenticationService.php
│   │   └── PermissionService.php
│   ├── Traits/
│   │   └── HasApiTokens.php
│   └── AuthenticationServiceProvider.php
├── tests/
│   ├── Feature/
│   │   ├── AuthenticationTest.php
│   │   ├── UserManagementTest.php
│   │   ├── PermissionTest.php
│   │   └── AccountLockoutTest.php
│   └── Unit/
│       ├── UserModelTest.php
│       ├── PasswordHashingTest.php
│       └── FailedAttemptTrackingTest.php
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000003_create_users_table.php
│   │   ├── 0001_01_01_000004_create_permission_tables.php
│   │   └── 0001_01_01_000005_create_personal_access_tokens_table.php
│   └── factories/
│       └── UserFactory.php
├── config/
│   └── authentication.php
├── routes/
│   └── api.php
├── composer.json
└── README.md
```

---

## Migration Path

### Adding Authentication to Existing Laravel App

**Step 1: Install Package**
```bash
composer require azaharizaman/erp-authentication
```

**Step 2: Publish Configuration**
```bash
php artisan vendor:publish --provider="Nexus\Erp\Authentication\AuthenticationServiceProvider"
```

**Step 3: Run Migrations**
```bash
php artisan migrate
```

**Step 4: Configure Guards**
```php
// config/auth.php
'guards' => [
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],
```

**Step 5: Create Initial Admin User**
```bash
php artisan auth:create-admin
```

---

## Success Metrics

### Technical Metrics

| Metric | Target | Measurement Method |
|--------|--------|-------------------|
| Login Response Time | < 300ms average | Performance monitoring |
| Token Validation Time | < 50ms average | Redis cache metrics |
| Authentication Failure Rate | < 1% | Log analysis |
| Account Lockout Rate | < 0.5% | Security metrics |

### Business Metrics

| Metric | Target | Measurement Method |
|--------|--------|-------------------|
| Active User Sessions | Track daily/monthly | Token usage |
| Password Reset Requests | < 5% of users/month | Audit logs |
| Security Incidents | 0 breaches | Security audit |
| API Token Usage | Track per tenant | Analytics |

---

## Assumptions & Constraints

### Assumptions

1. **Token Expiration:** 30-day token expiration is acceptable for API integrations
2. **Rate Limiting:** 5 login attempts per minute per IP is sufficient
3. **Account Lockout:** 30-minute lockout duration balances security and UX
4. **Redis Availability:** Redis is available for caching
5. **Email Service:** Email service available for password resets

### Constraints

1. **Stateless Architecture:** Must remain stateless - no server-side sessions
2. **Sanctum Only:** OAuth2/Social auth out of scope for MVP
3. **Single Guard:** Only 'api' guard supported (no 'web' guard)
4. **Laravel Version:** Requires Laravel 12.x
5. **PHP Version:** Requires PHP 8.2+ for enum support

---

## Monorepo Integration

### Development

- Lives in `/packages/authentication/` during development
- Main app uses Composer path repository to require locally
- All changes committed to monorepo

### Release (v1.0)

- Tagged with monorepo version (e.g., v1.0.0)
- Published to Packagist as `azaharizaman/erp-authentication`
- Can be installed independently in external Laravel apps

---

## References

- Master PRD: [../PRD01-MVP.md](../PRD01-MVP.md)
- Laravel Sanctum Documentation: https://laravel.com/docs/12.x/sanctum
- Spatie Permission Documentation: https://spatie.be/docs/laravel-permission
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)

---

## Next Steps

1. ✅ Review and approve this Sub-PRD
2. ⏳ Create implementation plan: `PLAN02-implement-authentication.md`
3. ⏳ Break down into GitHub issues
4. ⏳ Assign to **MILESTONE 1** (Nov 30, 2025)
5. ⏳ Set up feature module structure in `/packages/authentication/`
6. ⏳ Implement User model with authentication traits
7. ⏳ Integrate Laravel Sanctum
8. ⏳ Integrate Spatie Permission
9. ⏳ Build authentication API endpoints
10. ⏳ Implement account lockout mechanism
11. ⏳ Add rate limiting middleware
12. ⏳ Write comprehensive tests
13. ⏳ Generate API documentation

---

**Document Status:** Draft - Pending Review  
**Maintained By:** Laravel ERP Development Team  
**Last Updated:** November 11, 2025
