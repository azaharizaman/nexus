# Multi-Tenancy Middleware Guide

## Overview

This package provides three middleware components for multi-tenancy management in the Laravel ERP system:

1. **IdentifyTenant** - Resolves tenant from authenticated user with cache-first loading
2. **EnsureTenantActive** - Blocks access for suspended or archived tenants
3. **ImpersonationMiddleware** - Manages impersonation session timeout

---

## Middleware: IdentifyTenant

### Purpose

Automatically resolves the current tenant from the authenticated user's `tenant_id` and sets it in the `TenantManager` for use throughout the application request lifecycle.

### Middleware Ordering ⚠️

**CRITICAL:** This middleware MUST be applied after authentication middleware.

```php
// ✅ CORRECT
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// ❌ WRONG - tenant middleware runs before auth
Route::middleware(['tenant', 'auth:sanctum'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

### Error Response Codes

| Code | Condition | Response Message |
|------|-----------|------------------|
| 401  | User not authenticated | `"Unauthenticated."` |
| 403  | User has no `tenant_id` | `"User does not belong to any tenant."` |
| 404  | Tenant not found in database | `"Tenant not found."` |

### Cache-First Loading

The middleware implements a cache-first strategy to reduce database queries:

1. **Cache Hit**: Returns tenant from Redis cache (default TTL: 1 hour)
2. **Cache Miss**: Loads from database and stores in cache for future requests
3. **Cache Miss Warning**: Logs warning when cache miss occurs for monitoring

**Configuration:**

```php
// config/erp-core.php
'tenant_cache_ttl' => env('ERP_TENANT_CACHE_TTL', 3600), // seconds
```

### Performance Considerations

- **Average Response Time**: < 5ms (cache hit), < 50ms (cache miss)
- **Cache Key Pattern**: `tenant:{tenant_id}`
- **Cache Invalidation**: Automatically cleared on tenant updates/deletes

### Skipped Routes

The middleware automatically skips tenant identification for:

- `/api/v1/tenants/*` - Tenant management routes (admin-only, no tenant context needed)

---

## Middleware: EnsureTenantActive

### Purpose

Verifies that the current tenant is in an `ACTIVE` state. Blocks requests for `SUSPENDED` or `ARCHIVED` tenants.

### Middleware Ordering ⚠️

This middleware should be applied AFTER `IdentifyTenant` to ensure tenant context exists:

```php
// ✅ CORRECT
Route::middleware(['auth:sanctum', 'tenant', 'tenant.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

### Error Response Codes

| Code | Condition | Response Message |
|------|-----------|------------------|
| 403  | Tenant is `SUSPENDED` | `"Tenant is suspended."` |
| 403  | Tenant is `ARCHIVED` | `"Tenant is archived."` |

### Use Cases

Apply this middleware to routes that should only be accessible by active tenants:

```php
// Dashboard, reports, and operational features
Route::middleware(['auth:sanctum', 'tenant', 'tenant.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
});

// Admin routes for managing tenants (no tenant.active check needed)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/tenants/{tenant}/activate', [TenantController::class, 'activate']);
    Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend']);
});
```

---

## Middleware: ImpersonationMiddleware

### Purpose

Monitors active impersonation sessions and ensures they haven't exceeded the configured timeout. Uses Redis TTL for automatic expiration.

### How It Works

1. Checks if the authenticated user is currently impersonating a tenant
2. Redis cache automatically handles timeout via TTL (default: 1 hour)
3. When TTL expires, `isImpersonating()` returns `false` and impersonation ends
4. No manual timeout checking needed - handled by Redis

### Configuration

```php
// config/erp-core.php
'impersonation_timeout' => env('ERP_IMPERSONATION_TIMEOUT', 3600), // seconds (1 hour)
```

### Usage

```php
Route::middleware(['auth:sanctum', 'tenant', 'impersonation'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

### Security Features

- Automatic timeout enforcement
- Comprehensive audit logging (start/end events)
- Original tenant restoration on timeout
- Role-based authorization (admin/support only)

---

## Complete Middleware Stack Example

### Standard Application Routes

```php
// Most application routes
Route::middleware(['auth:sanctum', 'tenant', 'tenant.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('orders', OrderController::class);
});
```

### Admin Routes with Impersonation

```php
// Admin routes with impersonation support
Route::middleware(['auth:sanctum', 'impersonation'])->group(function () {
    Route::post('/tenants/{tenant}/impersonate', [TenantController::class, 'impersonate']);
    Route::post('/impersonation/end', [TenantController::class, 'endImpersonation']);
});
```

### Tenant Management Routes (No Tenant Context)

```php
// System-level tenant management
Route::middleware(['auth:sanctum'])->prefix('tenants')->group(function () {
    Route::get('/', [TenantController::class, 'index']);
    Route::post('/', [TenantController::class, 'store']);
    Route::get('/{tenant}', [TenantController::class, 'show']);
    Route::patch('/{tenant}', [TenantController::class, 'update']);
    Route::delete('/{tenant}', [TenantController::class, 'destroy']);
    
    Route::post('/{tenant}/suspend', [TenantController::class, 'suspend']);
    Route::post('/{tenant}/activate', [TenantController::class, 'activate']);
    Route::post('/{tenant}/archive', [TenantController::class, 'archive']);
});
```

---

## Troubleshooting

### Issue: "User does not belong to any tenant" (403)

**Cause:** User's `tenant_id` is `null`.

**Solution:**
1. Assign tenant to user: `$user->update(['tenant_id' => $tenant->id]);`
2. Ensure user creation includes `tenant_id`
3. Check if user was created before tenant assignment

### Issue: "Tenant not found" (404)

**Cause:** User's `tenant_id` references a non-existent or soft-deleted tenant.

**Solution:**
1. Verify tenant exists: `Tenant::find($user->tenant_id)`
2. Check if tenant was soft-deleted: `Tenant::withTrashed()->find($user->tenant_id)`
3. Reassign user to valid tenant

### Issue: Cache not working / always cache miss

**Cause:** Redis not configured or connection failed.

**Solution:**
1. Check Redis connection: `php artisan tinker` → `Cache::put('test', 'value', 60)`
2. Verify `CACHE_DRIVER=redis` in `.env`
3. Check Redis server is running: `redis-cli ping` (should return `PONG`)

### Issue: Impersonation automatically ends too quickly

**Cause:** Impersonation timeout is too short.

**Solution:**
1. Increase timeout: `ERP_IMPERSONATION_TIMEOUT=7200` in `.env` (2 hours)
2. Restart application to load new config
3. Verify config: `php artisan tinker` → `config('erp-core.impersonation_timeout')`

### Issue: Middleware execution order errors

**Cause:** Middleware applied in wrong order.

**Solution:**
1. Always use: `['auth:sanctum', 'tenant', 'tenant.active']` in that order
2. Never put `tenant` before `auth:sanctum`
3. Check route group middleware inheritance

---

## Performance Best Practices

### 1. Enable Redis for Caching

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 2. Monitor Cache Hit Rate

```php
// Log cache hits for monitoring
Log::info('Tenant loaded', [
    'tenant_id' => $tenant->id,
    'cache_hit' => Cache::has("tenant:{$tenant->id}"),
]);
```

### 3. Use Cache Tags (Redis only)

```php
// Group tenant caches for efficient invalidation
Cache::tags(['tenants', "tenant:{$tenant->id}"])->put($key, $value, $ttl);
```

### 4. Avoid Middleware on Static Routes

```php
// Don't apply tenant middleware to static assets or health checks
Route::get('/health', [HealthController::class, 'check']); // No middleware needed
```

---

## Testing Middleware

### Unit Test Example

```php
test('identify tenant middleware resolves tenant from user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->getJson('/api/v1/dashboard')
        ->assertOk();

    expect(app(TenantManagerContract::class)->current()->id)->toBe($tenant->id);
});
```

### Integration Test Example

```php
test('suspended tenant cannot access application', function () {
    $tenant = Tenant::factory()->create(['status' => TenantStatus::SUSPENDED]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->getJson('/api/v1/dashboard')
        ->assertForbidden()
        ->assertJson(['message' => 'Tenant is suspended.']);
});
```

---

## Related Documentation

- [API Endpoints](../README.md#api-endpoints)
- [Impersonation Guide](../README.md#impersonation)
- [Multi-Tenancy Architecture](../../../../docs/architecture/MULTITENANCY.md)
- [Security Guidelines](../../../../docs/SECURITY.md)

---

**Last Updated:** November 11, 2025  
**Package Version:** 1.0.0
