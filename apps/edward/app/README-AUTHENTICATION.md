# Authentication Core Infrastructure

This directory contains the authentication infrastructure implementation for the Laravel ERP system.

## Overview

The authentication system provides:
- **Stateless API Authentication**: Token-based authentication using Laravel Sanctum
- **Account Security**: Account lockout after failed attempts, rate limiting
- **Tenant Isolation**: Multi-tenant authentication with tenant-scoped users
- **Event-Driven**: Comprehensive event system for audit logging
- **Repository Pattern**: Clean separation of data access logic

## Directory Structure

```
app/
├── Actions/Auth/          # Business logic for authentication operations
│   ├── LoginAction.php
│   ├── LogoutAction.php
│   ├── RegisterUserAction.php
│   ├── RequestPasswordResetAction.php
│   └── ResetPasswordAction.php
├── Contracts/             # Repository interfaces
│   └── UserRepositoryContract.php
├── Events/Auth/           # Domain events
│   ├── LoginFailedEvent.php
│   ├── PasswordResetEvent.php
│   ├── PasswordResetRequestedEvent.php
│   ├── UserLoggedInEvent.php
│   ├── UserLoggedOutEvent.php
│   └── UserRegisteredEvent.php
├── Exceptions/            # Custom exceptions
│   └── AccountLockedException.php
├── Http/
│   ├── Controllers/Api/V1/
│   │   └── AuthController.php  # API endpoints
│   ├── Middleware/
│   │   ├── EnsureAccountNotLocked.php
│   │   └── ValidateSanctumToken.php
│   ├── Requests/Auth/     # Form request validation
│   │   ├── ForgotPasswordRequest.php
│   │   ├── LoginRequest.php
│   │   ├── RegisterRequest.php
│   │   └── ResetPasswordRequest.php
│   └── Resources/Auth/    # API response transformation
│       ├── TokenResource.php
│       └── UserResource.php
├── Listeners/Auth/        # Event listeners
│   ├── LogAuthenticationFailureListener.php
│   └── LogAuthenticationSuccessListener.php
├── Repositories/          # Data access layer
│   └── UserRepository.php
└── Models/
    └── User.php           # User model (pre-existing)

config/
└── authentication.php     # Authentication configuration

tests/
├── Feature/Auth/
│   └── AuthenticationApiTest.php  # 14 feature tests
└── Unit/Actions/Auth/
    └── LoginActionTest.php        # 10 unit tests
```

## Features Implemented

### 1. Authentication Actions

All authentication operations are implemented as Laravel Actions:

- **LoginAction**: Authenticates users, generates API tokens, handles account lockout
- **LogoutAction**: Revokes API tokens
- **RegisterUserAction**: Creates new user accounts with tenant scoping
- **RequestPasswordResetAction**: Generates secure password reset tokens
- **ResetPasswordAction**: Resets passwords using one-time tokens

### 2. API Endpoints

All endpoints use attribute-based routing:

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/v1/auth/login` | Authenticate and get token | No |
| POST | `/api/v1/auth/logout` | Revoke current token | Yes |
| POST | `/api/v1/auth/register` | Create new account | No |
| GET | `/api/v1/auth/me` | Get user profile | Yes |
| POST | `/api/v1/auth/password/forgot` | Request password reset | No |
| POST | `/api/v1/auth/password/reset` | Reset password with token | No |

### 3. Security Features

- **Rate Limiting**: 5 attempts per minute on auth endpoints
- **Account Lockout**: Automatic lockout after 5 failed login attempts (30 minutes)
- **Token Expiration**: Configurable token expiration (default: 30 days)
- **Token Caching**: Redis-backed token validation caching
- **Tenant Isolation**: Strict tenant boundaries for authentication

### 4. Validation

Comprehensive validation using Form Requests:
- Email format validation
- Password strength requirements (configurable)
- Password confirmation matching
- Tenant existence validation
- Device name requirement

### 5. Event System

Events dispatched for audit logging:
- `UserLoggedInEvent`: Successful authentication
- `LoginFailedEvent`: Failed authentication attempt
- `UserLoggedOutEvent`: Token revocation
- `UserRegisteredEvent`: New account creation
- `PasswordResetRequestedEvent`: Password reset request
- `PasswordResetEvent`: Successful password reset

### 6. Middleware

- **EnsureAccountNotLocked**: Checks account lockout status, auto-unlocks expired locks
- **ValidateSanctumToken**: Validates token expiration with Redis caching

## Configuration

Edit `config/authentication.php`:

```php
return [
    'token_expiration_days' => 30,    // Token lifetime
    'token_prefix' => 'erp',          // Token name prefix
    'cache_ttl' => 3600,              // Cache TTL in seconds
    'rate_limit' => [
        'max_attempts' => 5,          // Max attempts per window
        'decay_minutes' => 1,         // Time window
    ],
    'lockout' => [
        'threshold' => 5,             // Failed attempts before lockout
        'duration_minutes' => 30,     // Lockout duration
    ],
    'password_reset' => [
        'expiration_minutes' => 60,   // Reset token lifetime
    ],
];
```

## Usage Examples

### Login

```bash
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "My Device",
    "tenant_id": "550e8400-e29b-41d4-a716-446655440000"
  }'
```

Response:
```json
{
  "data": {
    "token": "1|abcdef1234567890...",
    "token_type": "Bearer",
    "expires_at": "2025-12-11T10:00:00Z",
    "user": {
      "id": "660e8400-e29b-41d4-a716-446655440001",
      "name": "John Doe",
      "email": "user@example.com",
      "tenant_id": "550e8400-e29b-41d4-a716-446655440000"
    }
  }
}
```

### Protected Request

```bash
curl -X GET http://localhost/api/v1/auth/me \
  -H "Authorization: Bearer 1|abcdef1234567890..."
```

## Testing

Run unit tests:
```bash
php artisan test --filter=LoginActionTest
```

Run feature tests:
```bash
php artisan test --filter=AuthenticationApiTest
```

Run all authentication tests:
```bash
php artisan test tests/Unit/Actions/Auth tests/Feature/Auth
```

### Test Coverage

- **Unit Tests (10 cases)**: LoginAction testing
  - Valid credentials
  - Invalid credentials
  - Account lockout
  - Failed attempt tracking
  - Tenant isolation
  
- **Feature Tests (14 cases)**: API endpoint testing
  - Login/logout flows
  - Registration validation
  - Rate limiting enforcement
  - Token authentication
  - Password reset flow

## Requirements Addressed

This implementation addresses the following PRD requirements:

- **FR-AA-002**: API Authentication using Laravel Sanctum
- **FR-AA-006**: Password Security (Argon2/bcrypt hashing)
- **FR-AA-007**: Password Reset functionality
- **FR-AA-008**: Account Lockout mechanism
- **BR-AA-001**: Tenant-scoped authentication
- **BR-AA-003**: Failed login attempt reset
- **BR-AA-004**: Automatic lockout expiration
- **SR-AA-001**: Tenant isolation
- **SR-AA-002**: Password hashing (Argon2id/bcrypt)
- **SR-AA-003**: Rate limiting
- **SR-AA-006**: Authentication failure logging
- **PR-AA-001**: Login under 300ms
- **PR-AA-002**: Token validation caching

## Dependencies

- **laravel/sanctum**: ^4.2 - API authentication
- **lorisleiva/laravel-actions**: ^2.0 - Action pattern
- **spatie/laravel-permission**: ^6.0 - Authorization (future use)
- **azaharizaman/erp-core**: dev-main - Multi-tenancy traits

## Security Considerations

1. **Password Storage**: Uses Argon2id hashing by default (Laravel 12+)
2. **Token Storage**: SHA-256 hashed tokens in database
3. **Rate Limiting**: Prevents brute force attacks
4. **Account Lockout**: Automatic protection after failed attempts
5. **Tenant Isolation**: Strict boundaries prevent cross-tenant access
6. **Event Logging**: All authentication events logged for audit

## Performance

- Login operations complete under 300ms (requirement: PR-AA-001)
- Token validation cached in Redis (requirement: PR-AA-002)
- Database queries optimized with proper indexing
- Tenant filtering at query level (no post-filtering)

## Future Enhancements

- [ ] Multi-Factor Authentication (MFA) - PRD requirement FR-AA-001
- [ ] OAuth2 support - PRD requirement FR-AA-004
- [ ] Session management - PRD requirement FR-AA-005
- [ ] Permission management UI - PRD requirement FR-AA-009
- [ ] CAPTCHA integration after 3 failed attempts
- [ ] Token refresh endpoint

## References

- [PRD01-SUB02-AUTHENTICATION.md](../../../docs/prd/prd-01/PRD01-SUB02-AUTHENTICATION.md)
- [PRD01-SUB02-PLAN01-implement-authentication-core.md](../../../docs/plan/PRD01-SUB02-PLAN01-implement-authentication-core.md)
- [CODING_GUIDELINES.md](../../../CODING_GUIDELINES.md)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
