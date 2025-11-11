---
plan: Implement Authentication Core Infrastructure
version: 1.0
date_created: 2025-11-11
last_updated: 2025-11-11
owner: Development Team
status: Planned
tags: [feature, authentication, sanctum, security, core-infrastructure, user-management]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan covers the core authentication infrastructure for the Laravel ERP system, implementing stateless API authentication using Laravel Sanctum with personal access tokens, secure password management, account security features (lockout, rate limiting), and user management. This plan establishes the foundational authentication layer that all other API interactions will depend on.

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-AA-002**: Implement API Authentication using token-based access control (Laravel Sanctum) supporting personal access tokens
- **REQ-FR-AA-005**: Implement Session Management with secure session handling and automatic timeout
- **REQ-FR-AA-006**: Enforce Password Security through salted hashing using Argon2 or bcrypt with configurable complexity requirements
- **REQ-FR-AA-007**: Provide Password Reset functionality with secure token-based email verification
- **REQ-FR-AA-008**: Enable Account Lockout after repeated failed login attempts with configurable threshold and lockout duration
- **REQ-BR-AA-001**: User authentication MUST be tenant-scoped - users can only authenticate within their assigned tenant
- **REQ-BR-AA-002**: Tokens MUST include user ID, tenant ID, and expiration timestamp in encrypted payload
- **REQ-BR-AA-003**: Failed login attempts MUST reset to zero after successful authentication
- **REQ-BR-AA-004**: Account lockout MUST expire automatically after configured duration (default: 30 minutes)
- **REQ-DR-AA-001**: Users table MUST include: id (UUID), tenant_id, name, email (unique per tenant), password (hashed), failed_login_attempts, locked_until, timestamps
- **REQ-DR-AA-004**: Personal access tokens table MUST include: id, tokenable_id, name, token (hashed), abilities, last_used_at, expires_at
- **REQ-DR-AA-005**: Email MUST be indexed per tenant for fast lookup: UNIQUE(tenant_id, email)
- **REQ-SR-AA-001**: Ensure Tenant-Scoped Authentication - users cannot authenticate across tenant boundaries
- **REQ-SR-AA-002**: Passwords MUST be hashed using Argon2id or bcrypt with minimum 12 rounds
- **REQ-SR-AA-003**: Enforce API Rate Limiting on authentication endpoints (default: 5 attempts per minute)
- **REQ-SR-AA-004**: Tokens MUST have configurable expiration (default: 30 days for API tokens)
- **REQ-SR-AA-005**: Password reset tokens MUST expire after 1 hour and be single-use
- **REQ-SR-AA-006**: All authentication failures MUST be logged for security audit
- **REQ-PR-AA-001**: Login and token validation operations must complete under 300ms on average
- **REQ-PR-AA-002**: Token validation MUST use caching (Redis) to minimize database queries
- **REQ-ARCH-AA-001**: Use Laravel Sanctum for API token management with database token storage
- **REQ-ARCH-AA-003**: Store tokens using SHA-256 hashing for security
- **REQ-ARCH-AA-004**: Implement middleware for token validation and permission checks

### Security Constraints

- **SEC-001**: All passwords must use Argon2id or bcrypt with minimum 12 rounds
- **SEC-002**: Password reset tokens must be cryptographically secure random strings (minimum 64 characters)
- **SEC-003**: Failed login attempts must increment atomically to prevent race conditions
- **SEC-004**: Token validation must check expiration before database query to prevent unnecessary load

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: All models must have complete PHPDoc blocks with @property annotations
- **GUD-003**: Use Laravel 12+ conventions (anonymous migrations, modern factory syntax)
- **GUD-004**: Follow PSR-12 coding standards, enforced by Laravel Pint

### Patterns to Follow

- **PAT-001**: Use Laravel Actions pattern for all authentication operations (LoginAction, RegisterAction, etc.)
- **PAT-002**: Use Form Requests for input validation in all API endpoints
- **PAT-003**: Use API Resources for response transformation
- **PAT-004**: Use Repository pattern with contracts for user data access
- **PAT-005**: Use Service Provider for Sanctum and package configuration

### Constraints

- **CON-001**: Must support PostgreSQL 14+ and MySQL 8.0+
- **CON-002**: Redis 6+ is mandatory for token caching (not optional)
- **CON-003**: Package must be installable independently via Composer
- **CON-004**: Must maintain backward compatibility with Laravel Sanctum v4.x

## 2. Implementation Steps

### GOAL-001: Create User Model with Security Features

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AA-006, BR-AA-001, BR-AA-003, BR-AA-004, DR-AA-001, DR-AA-005, SR-AA-001, SR-AA-002 | Implement User model with tenant scoping, password hashing, failed login tracking, and account lockout features. Create database migration with proper indexes and constraints. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create `packages/authentication/database/migrations/0001_01_01_000003_create_users_table.php` anonymous migration with: id (UUID primary key), tenant_id (UUID foreign key to tenants), name (VARCHAR 255), email (VARCHAR 255), password (VARCHAR 255), failed_login_attempts (INT DEFAULT 0), locked_until (TIMESTAMP NULL), email_verified_at, remember_token, timestamps, deleted_at. Add UNIQUE KEY (tenant_id, email), INDEX (tenant_id), INDEX (email), INDEX (locked_until). | | |
| TASK-002 | Create `packages/authentication/src/Models/User.php` extending `Illuminate\Foundation\Auth\User as Authenticatable`. Use `HasUuids`, `SoftDeletes`, `BelongsToTenant` traits. Set $fillable = ['name', 'email', 'password', 'tenant_id']. Cast 'email_verified_at' to datetime, 'locked_until' to datetime. Add $hidden = ['password', 'remember_token']. | | |
| TASK-003 | Add to User model: `incrementFailedLoginAttempts(): void` method that increments `failed_login_attempts` atomically and sets `locked_until` to now() + 30 minutes if attempts reach 5. Use `$this->failed_login_attempts++; if ($this->failed_login_attempts >= 5) { $this->locked_until = now()->addMinutes(30); } $this->save();` pattern. | | |
| TASK-004 | Add to User model: `resetFailedLoginAttempts(): void` method that sets `failed_login_attempts = 0` and `locked_until = null`. Add `isLocked(): bool` method returning `$this->locked_until && $this->locked_until->isFuture()`. Add `unlockAccount(): void` method that sets `locked_until = null, failed_login_attempts = 0`. | | |
| TASK-005 | Create `packages/authentication/database/factories/UserFactory.php` extending `Illuminate\Database\Eloquent\Factories\Factory`. Define default state with fake name, unique email, Argon2id hashed password. Add state methods: `locked()` (locked_until = now + 30 min, failed_login_attempts = 5), `withFailedAttempts(int $count)` (failed_login_attempts = $count). | | |
| TASK-006 | Add PHPDoc to User model with @property annotations for id, tenant_id, name, email, password, failed_login_attempts, locked_until, email_verified_at, created_at, updated_at, deleted_at. Include @method annotations for isLocked(), resetFailedLoginAttempts(), incrementFailedLoginAttempts(). | | |

### GOAL-002: Integrate Laravel Sanctum for Token Authentication

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AA-002, BR-AA-002, DR-AA-004, SR-AA-004, PR-AA-002, ARCH-AA-001, ARCH-AA-003 | Install and configure Laravel Sanctum for stateless API token authentication with database storage, token expiration, and caching. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Install Laravel Sanctum: Add `"laravel/sanctum": "^4.0"` to `packages/authentication/composer.json` require section. Run `composer require laravel/sanctum` in package directory. | | |
| TASK-008 | Publish Sanctum migrations: Run `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` to publish personal_access_tokens migration. Move migration to `packages/authentication/database/migrations/`. Ensure migration uses anonymous class format. | | |
| TASK-009 | Add `HasApiTokens` trait to User model: `use Laravel\Sanctum\HasApiTokens;` in User.php. This provides `createToken()`, `tokens()` relationship, and `currentAccessToken()` methods. | | |
| TASK-010 | Create `packages/authentication/config/authentication.php` config file with: token_expiration (default: 30 days in minutes = 43200), token_prefix (default: 'erp'), cache_ttl (default: 3600), rate_limit (default: 5), lockout_threshold (default: 5), lockout_duration (default: 30 minutes), password_reset_expiration (default: 60 minutes). | | |
| TASK-011 | Configure Sanctum middleware in `packages/authentication/src/AuthenticationServiceProvider.php`: Register `EnsureFrontendRequestsAreStateful` middleware if needed, configure token abilities. Bind Sanctum guard as default API guard. | | |
| TASK-012 | Add token helper methods to User model: `createApiToken(string $name, array $abilities = ['*']): string` that creates token with configured expiration: `return $this->createToken($name, $abilities)->plainTextToken;`. Add `revokeApiToken(int $tokenId): bool` and `revokeAllApiTokens(): void` methods. | | |

### GOAL-003: Implement Authentication Actions and API Endpoints

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AA-002, FR-AA-007, BR-AA-003, SR-AA-003, SR-AA-006, PR-AA-001 | Create Laravel Actions for authentication operations (login, logout, register, password reset) and RESTful API endpoints with validation, rate limiting, and event dispatching. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-013 | Create `packages/authentication/src/Actions/LoginAction.php` using AsAction trait. In `handle(string $email, string $password, string $deviceName, string $tenantId): array`, validate credentials, check if account locked, verify tenant match, reset failed attempts on success or increment on failure, create token, dispatch `UserLoggedInEvent`, return ['token' => $token, 'user' => $user, 'expires_at' => Carbon]. Throw `AccountLockedException` if locked. | | |
| TASK-014 | Create `packages/authentication/src/Actions/LogoutAction.php` using AsAction trait. In `handle(User $user, ?int $tokenId = null): void`, revoke specified token or current token, dispatch `UserLoggedOutEvent`, log activity. | | |
| TASK-015 | Create `packages/authentication/src/Actions/RegisterUserAction.php` using AsAction trait. In `handle(array $data): User`, validate email uniqueness per tenant, hash password with Argon2id, create user, dispatch `UserRegisteredEvent`, return user. Use UserRepository for creation. | | |
| TASK-016 | Create `packages/authentication/src/Actions/RequestPasswordResetAction.php` using AsAction trait. In `handle(string $email, string $tenantId): void`, find user by email in tenant, generate secure reset token (64 char random), store in password_reset_tokens table with 1-hour expiration, dispatch `PasswordResetRequestedEvent` (for email notification). | | |
| TASK-017 | Create `packages/authentication/src/Actions/ResetPasswordAction.php` using AsAction trait. In `handle(string $email, string $token, string $newPassword): bool`, validate token exists and not expired, hash new password, update user password, delete reset token (single-use), dispatch `PasswordChangedEvent`, return true. | | |
| TASK-018 | Create `packages/authentication/src/Http/Controllers/AuthController.php` with methods: `login(LoginRequest)` calls LoginAction, `logout()` calls LogoutAction, `me()` returns authenticated user, `register(RegisterRequest)` calls RegisterUserAction, `forgotPassword(ForgotPasswordRequest)` calls RequestPasswordResetAction, `resetPassword(ResetPasswordRequest)` calls ResetPasswordAction. Apply rate limiting middleware. | | |
| TASK-019 | Create Form Requests: `LoginRequest` (email required|email, password required|string, device_name required|string), `RegisterRequest` (name required|string|max:255, email required|email|unique per tenant, password required|min:8|confirmed), `ForgotPasswordRequest` (email required|email), `ResetPasswordRequest` (email, token, password, password_confirmation). | | |
| TASK-020 | Create API Resources: `UserResource` transforming user to JSON:API format with id, name, email, tenant info, roles, permissions (conditional). `TokenResource` with token string, user, expires_at. | | |

### GOAL-004: Implement Security Middleware and Rate Limiting

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AA-008, SR-AA-003, SR-AA-006, PR-AA-002, ARCH-AA-004 | Create middleware for token validation, account lockout checks, rate limiting on auth endpoints, and audit logging of authentication events. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-021 | Create `packages/authentication/src/Http/Middleware/EnsureAccountNotLocked.php` implementing `handle(Request $request, Closure $next): Response`. Check if authenticated user `isLocked()`. If locked and lock expired, call `unlockAccount()` and allow through. If locked and lock active, return 423 Locked with message and locked_until timestamp. | | |
| TASK-022 | Create `packages/authentication/src/Http/Middleware/ValidateSanctumToken.php` implementing `handle(Request $request, Closure $next): Response`. Check token expiration from cache first (Redis). If cached and valid, proceed. If not cached, query database, cache result for configured TTL, proceed. Return 401 if invalid/expired. Log failed validation attempts. | | |
| TASK-023 | Register middleware in `AuthenticationServiceProvider::boot()`: `$this->app['router']->aliasMiddleware('auth.locked', EnsureAccountNotLocked::class)`. Add to API middleware group after `auth:sanctum`. | | |
| TASK-024 | Apply rate limiting to auth endpoints: In `routes/api.php`, wrap auth routes in `Route::middleware('throttle:auth')` group. Configure 'auth' rate limiter in `AuthenticationServiceProvider::boot()`: `RateLimiter::for('auth', fn () => Limit::perMinute(5)->by(request()->input('email', request()->ip())))`. | | |
| TASK-025 | Create `packages/authentication/src/Listeners/LogAuthenticationFailureListener.php` listening to `LoginFailedEvent`. In `handle()`, log to activity log with email, IP address, attempts remaining, timestamp. Use ActivityLoggerContract. | | |
| TASK-026 | Create `packages/authentication/src/Listeners/LogAuthenticationSuccessListener.php` listening to `UserLoggedInEvent`. In `handle()`, log to activity log with user, device name, IP address, timestamp. | | |

### GOAL-005: Create User Repository and Service Provider

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-AA-001, DR-AA-005, CON-003 | Implement repository pattern for user data access with contract interface, configure service provider for package registration and binding. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-027 | Create `packages/authentication/src/Contracts/UserRepositoryContract.php` interface with methods: `findById(string $id): ?User`, `findByEmail(string $email, string $tenantId): ?User`, `create(array $data): User`, `update(User $user, array $data): User`, `delete(User $user): bool`, `getByTenant(string $tenantId, array $filters = [], int $perPage = 15)`, `countByTenant(string $tenantId): int`. | | |
| TASK-028 | Create `packages/authentication/src/Repositories/UserRepository.php` implementing UserRepositoryContract. Inject User model. Implement all interface methods using Eloquent query builder. Ensure tenant scoping on all queries. In `findByEmail()`, query: `User::where('tenant_id', $tenantId)->where('email', $email)->first()`. | | |
| TASK-029 | In UserRepository::create(), validate email uniqueness per tenant using `User::where('tenant_id', $data['tenant_id'])->where('email', $data['email'])->exists()`. Hash password with Argon2id. Set tenant_id from current context. Dispatch UserRegisteredEvent. Return created user. | | |
| TASK-030 | Create `packages/authentication/src/AuthenticationServiceProvider.php` extending ServiceProvider. In `register()`: bind UserRepositoryContract to UserRepository as singleton. Merge config from `config/authentication.php`. In `boot()`: publish migrations, publish config, register middleware, configure rate limiters, register event listeners. | | |
| TASK-031 | Create `packages/authentication/composer.json` with package metadata: name "azaharizaman/erp-authentication", type "library", require PHP ^8.2, laravel/framework ^12.0, laravel/sanctum ^4.0, autoload PSR-4 namespace, extra.laravel.providers array with AuthenticationServiceProvider. | | |
| TASK-032 | Create `packages/authentication/README.md` with: installation instructions, basic usage (login, token management), configuration options reference, API endpoints documentation with cURL examples, troubleshooting section (common errors like rate limit, account locked, invalid token). | | |

## 3. Alternatives

- **ALT-001**: Use Laravel Passport instead of Sanctum - Rejected because Passport is overkill for first-party API authentication and adds OAuth2 complexity unnecessary for this use case. Sanctum is simpler and sufficient.
- **ALT-002**: Store tokens in Redis instead of database - Rejected because database provides better audit trail and token management features. Redis is used for caching only to improve performance.
- **ALT-003**: Use JWT tokens instead of Sanctum personal access tokens - Rejected because JWT requires additional libraries, has no native Laravel support, and doesn't provide built-in revocation mechanism.
- **ALT-004**: Implement custom authentication system without Sanctum - Rejected because reinventing the wheel increases maintenance burden and security risk. Laravel Sanctum is battle-tested and actively maintained.
- **ALT-005**: Use session-based authentication - Rejected because sessions don't work for stateless API authentication required by SPAs and mobile apps.

## 4. Dependencies

### Package Dependencies

- **DEP-001**: laravel/framework ^12.0 (Core framework, Eloquent, validation)
- **DEP-002**: laravel/sanctum ^4.0 (API token authentication)
- **DEP-003**: illuminate/hashing ^12.0 (Password hashing with Argon2id)
- **DEP-004**: illuminate/cache ^12.0 (Redis cache for token validation)
- **DEP-005**: lorisleiva/laravel-actions ^2.0 (Action pattern for business logic)

### Internal Package Dependencies

- **DEP-006**: azaharizaman/erp-multitenancy (SUB01) - Required for tenant context and BelongsToTenant trait

### Infrastructure Dependencies

- **DEP-007**: PostgreSQL 14+ or MySQL 8.0+ (User and token data storage)
- **DEP-008**: Redis 6+ (Required for token caching and rate limiting)
- **DEP-009**: PHP 8.2+ (Required for enums, readonly properties)

### Development Dependencies

- **DEP-010**: pestphp/pest ^4.0 (Testing framework)
- **DEP-011**: laravel/pint ^1.0 (Code formatting)

## 5. Files

### Core Models and Database

- **packages/authentication/src/Models/User.php**: User model with security features (lockout, failed attempts)
- **packages/authentication/database/migrations/0001_01_01_000003_create_users_table.php**: Users table migration with tenant scoping
- **packages/authentication/database/factories/UserFactory.php**: Factory for test user generation

### Actions

- **packages/authentication/src/Actions/LoginAction.php**: Authenticate user and generate token
- **packages/authentication/src/Actions/LogoutAction.php**: Revoke token(s)
- **packages/authentication/src/Actions/RegisterUserAction.php**: Create new user account
- **packages/authentication/src/Actions/RequestPasswordResetAction.php**: Generate password reset token
- **packages/authentication/src/Actions/ResetPasswordAction.php**: Reset password with token

### API Layer

- **packages/authentication/src/Http/Controllers/AuthController.php**: RESTful authentication endpoints
- **packages/authentication/src/Http/Requests/LoginRequest.php**: Login validation
- **packages/authentication/src/Http/Requests/RegisterRequest.php**: Registration validation
- **packages/authentication/src/Http/Requests/ForgotPasswordRequest.php**: Password reset request validation
- **packages/authentication/src/Http/Requests/ResetPasswordRequest.php**: Password reset validation
- **packages/authentication/src/Http/Resources/UserResource.php**: User JSON:API transformation
- **packages/authentication/src/Http/Resources/TokenResource.php**: Token response transformation

### Middleware

- **packages/authentication/src/Http/Middleware/EnsureAccountNotLocked.php**: Check account lockout status
- **packages/authentication/src/Http/Middleware/ValidateSanctumToken.php**: Token validation with caching

### Services and Repositories

- **packages/authentication/src/Contracts/UserRepositoryContract.php**: User data access interface
- **packages/authentication/src/Repositories/UserRepository.php**: User repository implementation

### Event Listeners

- **packages/authentication/src/Listeners/LogAuthenticationFailureListener.php**: Log failed login attempts
- **packages/authentication/src/Listeners/LogAuthenticationSuccessListener.php**: Log successful logins

### Package Infrastructure

- **packages/authentication/src/AuthenticationServiceProvider.php**: Service provider for package registration
- **packages/authentication/config/authentication.php**: Package configuration
- **packages/authentication/composer.json**: Composer package definition
- **packages/authentication/README.md**: Package documentation
- **packages/authentication/routes/api.php**: API route definitions

## 6. Testing

### Unit Tests

- **TEST-001**: Test User model password hashing on creation using Argon2id
- **TEST-002**: Test User model `incrementFailedLoginAttempts()` increments counter
- **TEST-003**: Test User model locks account after 5 failed attempts
- **TEST-004**: Test User model `isLocked()` returns true when locked_until is future
- **TEST-005**: Test User model `isLocked()` returns false when locked_until is past
- **TEST-006**: Test User model `resetFailedLoginAttempts()` resets counter to 0
- **TEST-007**: Test User model `unlockAccount()` clears locked_until and resets counter
- **TEST-008**: Test UserFactory generates valid users with hashed passwords
- **TEST-009**: Test UserFactory `locked()` state creates locked user
- **TEST-010**: Test LoginAction validates credentials correctly
- **TEST-011**: Test LoginAction increments failed attempts on wrong password
- **TEST-012**: Test LoginAction resets failed attempts on successful login
- **TEST-013**: Test LoginAction throws AccountLockedException when account locked
- **TEST-014**: Test RegisterUserAction validates email uniqueness per tenant
- **TEST-015**: Test UserRepository::findByEmail() scopes by tenant correctly

### Feature Tests

- **TEST-016**: Test POST /api/v1/auth/login returns token with valid credentials (200)
- **TEST-017**: Test POST /api/v1/auth/login returns 401 with invalid credentials
- **TEST-018**: Test POST /api/v1/auth/login returns 423 when account locked
- **TEST-019**: Test POST /api/v1/auth/login locks account after 5 failed attempts
- **TEST-020**: Test POST /api/v1/auth/login resets failed attempts on success
- **TEST-021**: Test POST /api/v1/auth/logout revokes token (204)
- **TEST-022**: Test GET /api/v1/auth/me returns authenticated user (200)
- **TEST-023**: Test GET /api/v1/auth/me returns 401 without token
- **TEST-024**: Test POST /api/v1/auth/register creates user (201)
- **TEST-025**: Test POST /api/v1/auth/register returns 422 with duplicate email in same tenant
- **TEST-026**: Test POST /api/v1/auth/register allows duplicate email in different tenants
- **TEST-027**: Test POST /api/v1/auth/password/forgot sends reset email (200)
- **TEST-028**: Test POST /api/v1/auth/password/reset resets password with valid token (200)
- **TEST-029**: Test POST /api/v1/auth/password/reset returns 422 with expired token
- **TEST-030**: Test rate limiting on login endpoint (429 after 5 attempts)
- **TEST-031**: Test rate limiting on password reset endpoint (429 after 5 attempts)

### Integration Tests

- **TEST-032**: Test tenant-scoped authentication: User in tenant A cannot see user in tenant B with same email
- **TEST-033**: Test LoginAction dispatches UserLoggedInEvent
- **TEST-034**: Test failed login dispatches LoginFailedEvent
- **TEST-035**: Test account lockout dispatches AccountLockedEvent
- **TEST-036**: Test password reset request dispatches PasswordResetRequestedEvent
- **TEST-037**: Test LogAuthenticationSuccessListener logs to activity log
- **TEST-038**: Test LogAuthenticationFailureListener logs to activity log

### Performance Tests

- **TEST-039**: Test login completes in < 300ms (average over 100 requests)
- **TEST-040**: Test token validation with cache hit completes in < 50ms
- **TEST-041**: Test password hashing completes in < 100ms (Argon2id)

## 7. Risks & Assumptions

### Risks

- **RISK-001**: **Password hashing performance** - Argon2id is CPU-intensive and might slow down login on high traffic. Mitigation: Use bcrypt as fallback, implement horizontal scaling, cache authenticated sessions.
- **RISK-002**: **Account lockout abuse** - Attackers could lock legitimate user accounts by failing login attempts. Mitigation: Implement CAPTCHA after 3 failed attempts, monitor lockout patterns, allow admin unlock.
- **RISK-003**: **Token storage growth** - Personal access tokens accumulate in database over time. Mitigation: Implement automatic token cleanup job (delete expired tokens older than 90 days).
- **RISK-004**: **Race condition in failed attempts** - Concurrent login attempts might not increment counter correctly. Mitigation: Use atomic increment operation (`$user->increment('failed_login_attempts')`) instead of read-modify-write.
- **RISK-005**: **Cache invalidation** - Token revocation might not reflect immediately if cached. Mitigation: Use cache tags to invalidate user-specific caches on token revocation.

### Assumptions

- **ASSUMPTION-001**: Redis is available and properly configured in all environments
- **ASSUMPTION-002**: Email service is configured for password reset emails (handled by SUB22)
- **ASSUMPTION-003**: All users belong to exactly one tenant (tenant_id is required)
- **ASSUMPTION-004**: Token expiration of 30 days is acceptable for most use cases
- **ASSUMPTION-005**: Rate limit of 5 login attempts per minute per email is sufficient
- **ASSUMPTION-006**: Account lockout duration of 30 minutes is acceptable to users
- **ASSUMPTION-007**: Argon2id is available on target servers (PHP 7.2+)

## 8. Related PRD / Further Reading

### Primary Documentation

- **PRD01-SUB02: Authentication & Authorization System**: [/docs/prd/prd-01/PRD01-SUB02-AUTHENTICATION.md](../prd/prd-01/PRD01-SUB02-AUTHENTICATION.md)
- **Master PRD**: [/docs/prd/PRD01-MVP.md](../prd/PRD01-MVP.md)

### Related Plans

- **PLAN01: Multi-Tenancy Core Infrastructure**: [/docs/plan/PRD01-SUB01-PLAN01-implement-multitenancy-core.md](./PRD01-SUB01-PLAN01-implement-multitenancy-core.md)
- **PLAN02: Multi-Tenancy API and Middleware**: [/docs/plan/PRD01-SUB01-PLAN02-implement-multitenancy-api.md](./PRD01-SUB01-PLAN02-implement-multitenancy-api.md)

### Architectural Documentation

- **Package Decoupling Strategy**: [/docs/architecture/PACKAGE-DECOUPLING-STRATEGY.md](../architecture/PACKAGE-DECOUPLING-STRATEGY.md)
- **Coding Guidelines**: [/CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- **Sanctum Authentication**: [/docs/SANCTUM_AUTHENTICATION.md](../SANCTUM_AUTHENTICATION.md)

### Laravel Documentation

- **Laravel 12 Sanctum**: https://laravel.com/docs/12.x/sanctum
- **Laravel 12 Hashing**: https://laravel.com/docs/12.x/hashing
- **Laravel 12 Password Reset**: https://laravel.com/docs/12.x/passwords
- **Laravel 12 Rate Limiting**: https://laravel.com/docs/12.x/routing#rate-limiting
- **Laravel 12 Middleware**: https://laravel.com/docs/12.x/middleware

### External Resources

- **Argon2 Password Hashing**: https://en.wikipedia.org/wiki/Argon2
- **OWASP Authentication Cheat Sheet**: https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html
- **API Security Best Practices**: https://owasp.org/www-project-api-security/
