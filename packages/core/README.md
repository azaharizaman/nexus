# ERP Core Package

Core functionality for the Laravel ERP system, providing:

- **Multi-tenancy**: Complete tenant isolation at the database and application level
- **Tenant Management API**: RESTful endpoints for tenant CRUD and lifecycle management
- **Tenant Impersonation**: Support/admin access to any tenant with comprehensive audit logging
- **Authentication**: Laravel Sanctum-based API authentication with security features
- **Audit Logging**: Comprehensive activity logging using Spatie Activity Log
- **Authorization**: Role-based access control (RBAC) using Spatie Permission
- **Base Models**: Tenant-aware Eloquent models with common functionality
- **Middleware**: Security and tenant resolution middleware with cache-first loading
- **Events & Listeners**: Core system events for extensibility

## Installation

```bash
composer require azaharizaman/erp-core
```

## Configuration

Publish configuration files:

```bash
php artisan vendor:publish --tag=erp-core-config
```

Run migrations:

```bash
php artisan migrate
```

Configure environment variables in `.env`:

```env
# Tenant Cache TTL (seconds)
ERP_TENANT_CACHE_TTL=3600

# Impersonation Timeout (seconds)
ERP_IMPERSONATION_TIMEOUT=3600

# Redis for caching (recommended)
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## Usage

### Multi-Tenancy

#### 1. Apply Tenant-Aware Trait to Models

All models that should be scoped to tenants must use the `BelongsToTenant` trait:

```php
use Nexus\Erp\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToTenant;
    
    // Model will automatically be scoped to the current tenant
    // tenant_id will be auto-populated on creation
}
```

#### 2. Apply Middleware to Routes

Protect your routes with tenant middleware to ensure proper tenant context:

```php
// Standard application routes
Route::middleware(['auth:sanctum', 'tenant', 'tenant.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('orders', OrderController::class);
});
```

**Middleware Options:**
- `tenant` - Resolves tenant from authenticated user (required for tenant-scoped routes)
- `tenant.active` - Blocks suspended/archived tenants
- `impersonation` - Enforces impersonation session timeout

**See:** [MIDDLEWARE.md](docs/MIDDLEWARE.md) for complete middleware documentation.

### Tenant Management API

The package provides RESTful API endpoints for managing tenants:

#### List Tenants

```bash
GET /api/v1/tenants?page=1&per_page=15&status=active&search=acme
Authorization: Bearer {token}
```

#### Create Tenant

```bash
POST /api/v1/tenants
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Acme Corporation",
  "domain": "acme.example.com",
  "contact_email": "admin@acme.com",
  "status": "active"
}
```

#### Update Tenant

```bash
PATCH /api/v1/tenants/{tenant}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Acme Corp (Updated)",
  "status": "active"
}
```

#### Suspend Tenant

```bash
POST /api/v1/tenants/{tenant}/suspend
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Payment overdue"
}
```

#### Activate Tenant

```bash
POST /api/v1/tenants/{tenant}/activate
Authorization: Bearer {token}
```

#### Archive Tenant

```bash
POST /api/v1/tenants/{tenant}/archive
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Company dissolved"
}
```

#### Delete Tenant (Soft Delete)

```bash
DELETE /api/v1/tenants/{tenant}
Authorization: Bearer {token}
```

### Tenant Impersonation

Admins and support staff can impersonate tenants for support purposes:

#### Start Impersonation

```bash
POST /api/v1/tenants/{tenant}/impersonate
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Customer support - troubleshooting issue #1234"
}
```

**Response:**
```json
{
  "message": "Impersonation started successfully.",
  "tenant": {
    "id": "9d3e4f5a-b6c7-8d9e-0f1a-2b3c4d5e6f7a",
    "name": "Acme Corporation",
    "status": {
      "value": "active",
      "label": "Active"
    }
  }
}
```

#### End Impersonation

```bash
POST /api/v1/impersonation/end
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Impersonation ended successfully."
}
```

**Security Features:**
- Requires `admin` or `support` role
- Automatic timeout after configured duration (default: 1 hour)
- Comprehensive audit logging (start/end times, reason, duration)
- Original tenant restored after timeout or manual end

**See:** [MIDDLEWARE.md](docs/MIDDLEWARE.md#impersonationmiddleware) for implementation details.

### Programmatic Usage

#### Using Actions

```php
use Nexus\Erp\Core\Actions\CreateTenantAction;
use Nexus\Erp\Core\Actions\SuspendTenantAction;

// Create tenant
$tenant = CreateTenantAction::run([
    'name' => 'Acme Corporation',
    'domain' => 'acme.example.com',
    'contact_email' => 'admin@acme.com',
]);

// Suspend tenant
$suspendedTenant = SuspendTenantAction::run($tenant, 'Payment overdue');
```

#### Using TenantManager

```php
use Nexus\Erp\Core\Contracts\TenantManagerContract;

$tenantManager = app(TenantManagerContract::class);

// Create tenant
$tenant = $tenantManager->create([
    'name' => 'Acme Corporation',
    'domain' => 'acme.example.com',
    'contact_email' => 'admin@acme.com',
]);

// Get current tenant
$currentTenant = $tenantManager->current();

// Set active tenant
$tenantManager->setActive($tenant);
```

#### Using ImpersonationService

```php
use Nexus\Erp\Core\Services\ImpersonationService;

$impersonationService = app(ImpersonationService::class);

// Start impersonation
$impersonationService->startImpersonation(
    auth()->user(),
    $tenant,
    'Customer support - issue #1234'
);

// Check if impersonating
if ($impersonationService->isImpersonating(auth()->user())) {
    // User is currently impersonating
}

// Get original tenant
$originalTenant = $impersonationService->getOriginalTenant(auth()->user());

// End impersonation
$impersonationService->endImpersonation(auth()->user());
```

### Events

The package dispatches events for key tenant lifecycle changes:

```php
use Nexus\Erp\Core\Events\TenantCreatedEvent;
use Nexus\Erp\Core\Events\TenantSuspendedEvent;
use Nexus\Erp\Core\Events\TenantImpersonationStartedEvent;

// Listen to tenant events
Event::listen(TenantCreatedEvent::class, function ($event) {
    // Send welcome email, initialize default data, etc.
});

Event::listen(TenantSuspendedEvent::class, function ($event) {
    // Notify tenant admin, log to monitoring system, etc.
});

Event::listen(TenantImpersonationStartedEvent::class, function ($event) {
    // Alert security team, update support ticket, etc.
});
```

**Available Events:**
- `TenantCreatedEvent` - New tenant created
- `TenantUpdatedEvent` - Tenant information updated
- `TenantSuspendedEvent` - Tenant suspended
- `TenantActivatedEvent` - Tenant activated
- `TenantArchivedEvent` - Tenant archived
- `TenantDeletedEvent` - Tenant soft-deleted
- `TenantImpersonationStartedEvent` - Admin started impersonating tenant
- `TenantImpersonationEndedEvent` - Admin ended impersonation

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test -- --coverage
```

## Documentation

- [Middleware Guide](docs/MIDDLEWARE.md) - Complete middleware documentation
- [API Reference](docs/API.md) - Full API endpoint documentation (coming soon)
- [Architecture](../../../../docs/architecture/MULTITENANCY.md) - Multi-tenancy architecture

## Troubleshooting

### Common Issues

1. **"User does not belong to any tenant" (403)**
   - Assign tenant to user: `$user->update(['tenant_id' => $tenant->id]);`

2. **Cache not working**
   - Verify Redis is running: `redis-cli ping`
   - Check `.env`: `CACHE_DRIVER=redis`

3. **Impersonation ends too quickly**
   - Increase timeout: `ERP_IMPERSONATION_TIMEOUT=7200` (2 hours)

**See:** [MIDDLEWARE.md](docs/MIDDLEWARE.md#troubleshooting) for complete troubleshooting guide.

## License

MIT License. See LICENSE for details.
