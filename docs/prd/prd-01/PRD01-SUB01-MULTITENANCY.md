# PRD01-SUB01: Multi-Tenancy System

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Mandatory Feature Modules - Core Infrastructure  
**Related Sub-PRDs:** PRD01-SUB02 (Authentication), PRD01-SUB03 (Audit Logging), PRD01-SUB05 (Settings Management), PRD01-SUB22 (Notifications & Events), PRD01-SUB23 (API Gateway)  
**Composer Package:** `azaharizaman/erp-multitenancy`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Multi-Tenancy System provides **tenant isolation infrastructure enabling secure data segregation across organizations** with configurable settings and middleware-based context resolution. This is a mandatory feature module that forms the foundation of the Laravel ERP's ability to serve multiple organizations from a single application instance while maintaining complete data isolation and security boundaries.

### Purpose

The Multi-Tenancy System solves the critical problem of **secure multi-organization data isolation** in a shared infrastructure. It enables:

1. **Cost-Effective SaaS Delivery:** Multiple organizations share infrastructure while maintaining complete data privacy
2. **Resource Efficiency:** Reduced operational overhead through shared application code and optimized resource utilization
3. **Horizontal Scalability:** Support for thousands of concurrent tenants without architectural limitations
4. **Administrative Flexibility:** Centralized management with per-tenant configuration and impersonation capabilities

### Scope

**Included in this Feature Module:**

- ✅ Tenant Model and database schema
- ✅ Tenant context resolution middleware
- ✅ Automatic tenant scoping for Eloquent models
- ✅ Tenant-scoped caching mechanisms
- ✅ Tenant impersonation with audit controls
- ✅ Tenant configuration management
- ✅ Cross-tenant data exposure prevention
- ✅ Tenant lifecycle management (create, suspend, archive)

**Excluded from this Feature Module:**

- ❌ User authentication (handled by SUB02)
- ❌ Activity logging (handled by SUB03)
- ❌ Settings storage (handled by SUB05)
- ❌ Event broadcasting (handled by SUB22)
- ❌ API gateway logic (handled by SUB23)

### Dependencies

**Mandatory Dependencies:**
- Laravel Framework v12.x
- PHP ≥ 8.2
- SQL database (PostgreSQL or MySQL)
- Redis or Memcached for caching

**Feature Module Dependencies:**
- **None** - This is a foundational feature module with no dependencies on other optional feature modules

### Composer Package Information

- **Package Name:** `azaharizaman/erp-multitenancy`
- **Namespace:** `Azaharizaman\Erp\Multitenancy`
- **Monorepo Location:** `/packages/multitenancy/`
- **Installation:** `composer require azaharizaman/erp-multitenancy` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB01 (Multi-Tenancy System). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-MT-001** | Implement a **Tenant Model** to represent isolated entities with unique identifiers, names, domains, and status | High | Planned |
| **FR-MT-002** | Ensure strict **Tenant Data Isolation** by scoping all database and cache operations per tenant using global scopes and middleware | High | Planned |
| **FR-MT-003** | Establish **Tenant Context middleware** to resolve and inject active tenant context from authenticated user, subdomain, or header | High | Planned |
| **FR-MT-004** | Support **tenant-specific configuration** with cascading settings (system → tenant → user) | High | Planned |
| **FR-MT-005** | Provide **tenant lifecycle management** (create, activate, suspend, archive) with status transitions | High | Planned |
| **FR-MT-006** | Allow **Tenant Impersonation** for administrative support under strict auditing control with session management and reason tracking | Medium | Planned |
| **FR-MT-007** | Implement **tenant-scoped caching** with automatic cache key prefixing per tenant | Medium | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-MT-001** | Each tenant MUST have a unique identifier (UUID) that never changes throughout the tenant lifecycle | Planned |
| **BR-MT-002** | Tenant domain names MUST be unique across the entire system to prevent routing conflicts | Planned |
| **BR-MT-003** | Tenant status can be: ACTIVE, SUSPENDED, ARCHIVED - only ACTIVE tenants can access the system | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-MT-001** | Tenants table MUST include: id (UUID), name, domain, status (enum), configuration (encrypted JSON), timestamps, soft deletes | Planned |
| **DR-MT-002** | All tenant-scoped models MUST include `tenant_id` foreign key with composite indexes for optimal query performance | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-MT-001** | Prevent **cross-tenant data exposure** through Eloquent global scopes on all tenant-scoped models | Planned |
| **SR-MT-002** | Tenant resolution MUST fail-safe: if tenant cannot be determined, request MUST be rejected with 403 Forbidden | Planned |
| **SR-MT-003** | **Encrypt tenant-specific configurations** and secrets using Laravel's encryption facilities | Planned |
| **SR-MT-004** | Log all **tenant impersonation sessions** with impersonator identity, target tenant, reason, and timestamp for compliance auditing | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-MT-001** | Tenant resolution and context loading must complete in **< 100ms** using Redis caching | Planned |
| **PR-MT-002** | Database queries MUST automatically include `tenant_id` in WHERE clauses to leverage composite indexes | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-MT-001** | Support **10,000+ concurrent active tenants** without performance degradation | Planned |
| **SCR-MT-002** | Tenant context caching MUST distribute across Redis cluster for high availability | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-MT-001** | Use **middleware-based tenant resolution** to inject context into application lifecycle | Planned |
| **ARCH-MT-002** | Implement Eloquent **global scope trait** (`BelongsToTenant`) for automatic tenant filtering | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-MT-001** | `TenantCreatedEvent` | When new tenant is provisioned | Planned |
| **EV-MT-002** | `TenantSuspendedEvent` | When tenant status changes to SUSPENDED | Planned |
| **EV-MT-003** | `TenantImpersonationStartedEvent` | When admin begins tenant impersonation session | Planned |

---

## Technical Specifications

### Database Schema

**Tenants Table:**

```sql
CREATE TABLE tenants (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) UNIQUE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    configuration TEXT,  -- Encrypted JSON
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_tenants_domain (domain),
    INDEX idx_tenants_status (status),
    INDEX idx_tenants_created (created_at)
);
```

**Status Enum Values:**
- `active` - Tenant can access all system features
- `suspended` - Tenant access temporarily disabled
- `archived` - Tenant read-only for compliance

**Configuration JSON Structure:**
```json
{
    "settings": {
        "timezone": "UTC",
        "date_format": "Y-m-d",
        "locale": "en"
    },
    "features": {
        "inventory": true,
        "accounting": true,
        "sales": false
    },
    "limits": {
        "max_users": 100,
        "max_storage_gb": 50
    }
}
```

### API Endpoints

All endpoints follow `/api/v1/admin/tenants` pattern:

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/v1/admin/tenants` | List all tenants (paginated) | Yes - Admin |
| POST | `/api/v1/admin/tenants` | Create new tenant | Yes - Admin |
| GET | `/api/v1/admin/tenants/{id}` | Get tenant details | Yes - Admin |
| PATCH | `/api/v1/admin/tenants/{id}` | Update tenant | Yes - Admin |
| DELETE | `/api/v1/admin/tenants/{id}` | Soft delete tenant | Yes - Admin |
| POST | `/api/v1/admin/tenants/{id}/suspend` | Suspend tenant access | Yes - Admin |
| POST | `/api/v1/admin/tenants/{id}/activate` | Reactivate tenant | Yes - Admin |
| POST | `/api/v1/admin/tenants/{id}/archive` | Archive tenant | Yes - Admin |
| POST | `/api/v1/admin/tenants/{id}/impersonate` | Start impersonation session | Yes - Super Admin |
| POST | `/api/v1/admin/tenants/impersonate/stop` | End impersonation session | Yes - Super Admin |

**Request/Response Examples:**

**Create Tenant:**
```json
// POST /api/v1/admin/tenants
{
    "name": "Acme Corporation",
    "domain": "acme",
    "status": "active",
    "configuration": {
        "timezone": "America/New_York",
        "locale": "en_US"
    }
}

// Response 201 Created
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "name": "Acme Corporation",
        "domain": "acme",
        "status": "active",
        "created_at": "2025-11-11T10:00:00Z"
    }
}
```

**Impersonate Tenant:**
```json
// POST /api/v1/admin/tenants/{id}/impersonate
{
    "reason": "Customer support - investigating invoice issue #12345"
}

// Response 200 OK
{
    "data": {
        "session_id": "imp_7d8f9e0a1b2c3d4e",
        "tenant_id": "550e8400-e29b-41d4-a716-446655440000",
        "tenant_name": "Acme Corporation",
        "expires_at": "2025-11-11T11:00:00Z"
    }
}
```

### Events

**Domain Events Emitted by this Feature Module:**

| Event Class | When Fired | Payload |
|-------------|-----------|---------|
| `TenantCreatedEvent` | After new tenant is successfully created | `Tenant $tenant` |
| `TenantUpdatedEvent` | After tenant details are modified | `Tenant $tenant, array $changes` |
| `TenantSuspendedEvent` | When tenant status changes to suspended | `Tenant $tenant, string $reason` |
| `TenantActivatedEvent` | When tenant status changes to active | `Tenant $tenant` |
| `TenantArchivedEvent` | When tenant is archived | `Tenant $tenant` |
| `TenantDeletedEvent` | When tenant is soft deleted | `Tenant $tenant` |
| `TenantImpersonationStartedEvent` | When admin starts impersonation | `Tenant $tenant, User $impersonator, string $reason` |
| `TenantImpersonationEndedEvent` | When impersonation session ends | `Tenant $tenant, User $impersonator, int $duration_seconds` |

**Event Usage Example:**
```php
use Azaharizaman\Erp\Multitenancy\Events\TenantCreatedEvent;

// Emit event after tenant creation
event(new TenantCreatedEvent($tenant));

// Other modules can listen:
class InitializeTenantDataListener
{
    public function handle(TenantCreatedEvent $event): void
    {
        // Create default roles, permissions, settings for new tenant
        $this->createDefaultRoles($event->tenant);
    }
}
```

### Event Listeners

**Events this Feature Module Listens To:**

This feature module does not listen to events from other modules as it is a foundational layer.

---

## Implementation Plans

**Note:** Implementation plans follow the naming convention `PLAN{number}-{action}-{component}.md`

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN01-implement-multitenancy.md | FR-MT-001, FR-MT-002, FR-MT-003, FR-MT-006, SR-MT-001, SR-MT-002, SR-MT-003, PR-MT-001, SCR-MT-001, ARCH-MT-001, ARCH-MT-002, ARCH-MT-003, ARCH-MT-004 | MILESTONE 1 | Not Started |

**Implementation plan will be created separately using:** `.github/prompts/create-implementation-plan.prompt.md`

---

## Acceptance Criteria

### Functional Acceptance

- [ ] Tenant Model with full CRUD operations implemented
- [ ] Tenant resolution middleware automatically resolves tenant from subdomain/header/user
- [ ] All Eloquent models using `BelongsToTenant` trait automatically filter by tenant_id
- [ ] Tenant impersonation works with session management and audit logging
- [ ] Tenant status transitions (active → suspended → archived) function correctly
- [ ] API endpoints return proper error codes for cross-tenant access attempts

### Performance Acceptance

- [ ] Tenant resolution completes in < 100ms (average)
- [ ] Redis caching reduces database queries for tenant context by 95%
- [ ] Database indexes on tenant_id optimize query performance

### Security Acceptance

- [ ] Cross-tenant data access prevented by global scopes (verified in tests)
- [ ] Tenant configuration encrypted at rest
- [ ] Impersonation requires explicit permission and logs all actions
- [ ] Failed tenant resolution returns 403 Forbidden (not 404)

### Testing Acceptance

- [ ] 100% unit test coverage for Tenant model and repository
- [ ] Feature tests for all API endpoints (CRUD, suspend, activate, archive, impersonate)
- [ ] Integration tests verify cross-tenant isolation
- [ ] Performance tests validate < 100ms tenant resolution
- [ ] Security tests verify global scope protection

### Documentation Acceptance

- [ ] API documentation complete (OpenAPI/Swagger)
- [ ] Tenant resolution strategies documented with examples
- [ ] Impersonation workflow documented for support teams
- [ ] Migration guide for adding tenant_id to existing models
- [ ] PHPDoc complete for all public classes and methods

### Code Quality Acceptance

- [ ] Code passes Laravel Pint formatting
- [ ] PHPStan level 5 compliance
- [ ] Code review completed and approved
- [ ] No direct SQL queries (use Eloquent/Query Builder)
- [ ] All files include `declare(strict_types=1);`

---

## Testing Strategy

### Unit Tests

**Test Coverage Areas:**
- Tenant Model CRUD operations
- Tenant status transitions and validation
- Tenant configuration encryption/decryption
- BelongsToTenant trait functionality
- Tenant repository methods
- Helper functions (getCurrentTenant(), etc.)

**Test Examples:**
```php
test('tenant model has correct fillable attributes', function () {
    $tenant = new Tenant();
    expect($tenant->getFillable())->toContain('name', 'domain', 'status', 'configuration');
});

test('tenant configuration is encrypted when saved', function () {
    $tenant = Tenant::factory()->create([
        'configuration' => ['key' => 'value']
    ]);
    
    $raw = DB::table('tenants')->find($tenant->id);
    expect($raw->configuration)->not->toBe('{"key":"value"}'); // Should be encrypted
    expect($tenant->configuration)->toBe(['key' => 'value']); // Should decrypt on access
});

test('BelongsToTenant trait automatically adds tenant_id on create', function () {
    $tenant = Tenant::factory()->create();
    TenantContext::set($tenant);
    
    $model = new TenantScopedModel(['name' => 'Test']);
    $model->save();
    
    expect($model->tenant_id)->toBe($tenant->id);
});
```

### Feature Tests

**Test Coverage Areas:**
- API endpoint responses (status codes, data structure)
- Tenant CRUD operations via API
- Tenant lifecycle (create → active → suspended → archived)
- Impersonation flow (start, use, stop)
- Cross-tenant access prevention
- Middleware tenant resolution

**Test Examples:**
```php
test('can create tenant via API', function () {
    $admin = User::factory()->admin()->create();
    
    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/tenants', [
            'name' => 'Test Corp',
            'domain' => 'testcorp',
            'status' => 'active'
        ]);
    
    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'name', 'domain', 'status']]);
});

test('cannot access another tenant data', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $user = User::factory()->for($tenant1)->create();
    
    $item = Item::factory()->for($tenant2)->create();
    
    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/items/{$item->id}")
        ->assertForbidden();
});

test('impersonation session expires after timeout', function () {
    $admin = User::factory()->superAdmin()->create();
    $tenant = Tenant::factory()->create();
    
    // Start impersonation
    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/tenants/{$tenant->id}/impersonate", [
            'reason' => 'Testing'
        ]);
    
    $sessionId = $response->json('data.session_id');
    
    // Fast forward time
    $this->travel(2)->hours();
    
    // Session should be expired
    $this->assertDatabaseHas('impersonation_sessions', [
        'id' => $sessionId,
        'expired' => true
    ]);
});
```

### Integration Tests

**Test Coverage Areas:**
- Tenant context propagation across requests
- Event emission and listener execution
- Cache invalidation on tenant updates
- Database transaction rollbacks preserve tenant isolation
- Queue jobs maintain tenant context

**Test Examples:**
```php
test('tenant context propagates to queued jobs', function () {
    $tenant = Tenant::factory()->create();
    TenantContext::set($tenant);
    
    dispatch(new ProcessTenantDataJob());
    
    // Verify job runs with correct tenant context
    Bus::assertDispatched(ProcessTenantDataJob::class, function ($job) use ($tenant) {
        return $job->tenantId === $tenant->id;
    });
});

test('TenantCreatedEvent triggers default data initialization', function () {
    Event::fake([TenantCreatedEvent::class]);
    
    $tenant = Tenant::factory()->create();
    
    Event::assertDispatched(TenantCreatedEvent::class, function ($event) use ($tenant) {
        return $event->tenant->id === $tenant->id;
    });
});
```

### Performance Tests

**Test Coverage Areas:**
- Tenant resolution time under load
- Cache hit rates for tenant context
- Query count with tenant scoping
- Concurrent tenant access patterns

**Test Examples:**
```php
test('tenant resolution completes in under 100ms', function () {
    $tenant = Tenant::factory()->create();
    
    $iterations = 100;
    $times = [];
    
    for ($i = 0; $i < $iterations; $i++) {
        $start = microtime(true);
        $resolved = TenantResolver::resolveFromDomain($tenant->domain);
        $times[] = (microtime(true) - $start) * 1000; // Convert to ms
    }
    
    $average = array_sum($times) / count($times);
    expect($average)->toBeLessThan(100);
});

test('tenant context uses cache to reduce queries', function () {
    $tenant = Tenant::factory()->create();
    
    // First access - should query database
    DB::enableQueryLog();
    TenantContext::get();
    $firstCallQueries = count(DB::getQueryLog());
    DB::flushQueryLog();
    
    // Second access - should use cache
    TenantContext::get();
    $secondCallQueries = count(DB::getQueryLog());
    
    expect($secondCallQueries)->toBe(0); // No queries, used cache
});
```

---

## Dependencies

### Feature Module Dependencies

**Mandatory Dependencies:**
- **None** - This is a foundational feature module with zero dependencies on other feature modules

**Optional Dependencies:**
- **None** - Other feature modules depend on this one, not vice versa

### External Package Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/framework` | ^12.0 | Core framework |
| `illuminate/database` | ^12.0 | Database ORM |
| `illuminate/cache` | ^12.0 | Caching layer |
| `illuminate/support` | ^12.0 | Helper utilities |

### Infrastructure Dependencies

| Component | Requirement | Purpose |
|-----------|------------|---------|
| **Database** | PostgreSQL 14+ or MySQL 8.0+ | Tenant data storage |
| **Cache** | Redis 6+ or Memcached 1.6+ | Tenant context caching |
| **PHP Extensions** | `ext-redis`, `ext-pdo`, `ext-json` | Runtime dependencies |

---

## Feature Module Structure

### Directory Structure (in Monorepo)

```
packages/multitenancy/
├── src/
│   ├── Actions/
│   │   ├── CreateTenantAction.php
│   │   ├── UpdateTenantAction.php
│   │   ├── SuspendTenantAction.php
│   │   ├── ActivateTenantAction.php
│   │   ├── ArchiveTenantAction.php
│   │   └── StartImpersonationAction.php
│   ├── Contracts/
│   │   ├── TenantRepositoryContract.php
│   │   ├── TenantResolverContract.php
│   │   └── ImpersonationServiceContract.php
│   ├── Events/
│   │   ├── TenantCreatedEvent.php
│   │   ├── TenantUpdatedEvent.php
│   │   ├── TenantSuspendedEvent.php
│   │   ├── TenantActivatedEvent.php
│   │   ├── TenantArchivedEvent.php
│   │   ├── TenantDeletedEvent.php
│   │   ├── TenantImpersonationStartedEvent.php
│   │   └── TenantImpersonationEndedEvent.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── TenantController.php
│   │   ├── Requests/
│   │   │   ├── CreateTenantRequest.php
│   │   │   ├── UpdateTenantRequest.php
│   │   │   └── ImpersonateTenantRequest.php
│   │   ├── Resources/
│   │   │   └── TenantResource.php
│   │   └── Middleware/
│   │       ├── IdentifyTenant.php
│   │       └── EnsureTenantActive.php
│   ├── Models/
│   │   ├── Tenant.php
│   │   └── ImpersonationSession.php
│   ├── Repositories/
│   │   └── TenantRepository.php
│   ├── Services/
│   │   ├── TenantResolver.php
│   │   ├── TenantContext.php
│   │   └── ImpersonationService.php
│   ├── Traits/
│   │   └── BelongsToTenant.php
│   ├── Enums/
│   │   └── TenantStatus.php
│   └── MultitenancyServiceProvider.php
├── tests/
│   ├── Feature/
│   │   ├── TenantCrudTest.php
│   │   ├── TenantImpersonationTest.php
│   │   └── TenantIsolationTest.php
│   └── Unit/
│       ├── TenantModelTest.php
│       ├── BelongsToTenantTraitTest.php
│       └── TenantResolverTest.php
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_tenants_table.php
│   │   └── 0001_01_01_000001_create_impersonation_sessions_table.php
│   └── factories/
│       └── TenantFactory.php
├── config/
│   └── multitenancy.php
├── routes/
│   └── api.php
├── composer.json
└── README.md
```

### Key Classes

**Tenant Model:**
```php
<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Multitenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Tenant extends Model
{
    use HasUuids, SoftDeletes;
    
    protected $fillable = ['name', 'domain', 'status', 'configuration'];
    
    protected $casts = [
        'status' => TenantStatus::class,
        'configuration' => 'encrypted:array',
    ];
    
    public function isActive(): bool
    {
        return $this->status === TenantStatus::ACTIVE;
    }
    
    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::SUSPENDED;
    }
    
    public function isArchived(): bool
    {
        return $this->status === TenantStatus::ARCHIVED;
    }
}
```

**BelongsToTenant Trait:**
```php
<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Multitenancy\Traits;

use Illuminate\Database\Eloquent\Builder;
use Azaharizaman\Erp\Multitenancy\Services\TenantContext;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if (! $model->tenant_id) {
                $model->tenant_id = TenantContext::getId();
            }
        });
        
        static::addGlobalScope('tenant', function (Builder $builder) {
            $builder->where('tenant_id', TenantContext::getId());
        });
    }
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

---

## Migration Path

### From Single-Tenant to Multi-Tenant

If migrating an existing single-tenant Laravel application to use this multi-tenancy feature module:

**Step 1: Install Package**
```bash
composer require azaharizaman/erp-multitenancy
```

**Step 2: Run Migrations**
```bash
php artisan migrate
```

**Step 3: Add tenant_id to Models**
```php
// Add migration to existing tables
Schema::table('users', function (Blueprint $table) {
    $table->uuid('tenant_id')->nullable()->after('id');
    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
    $table->index('tenant_id');
});

// Update models to use trait
class User extends Authenticatable
{
    use BelongsToTenant;
}
```

**Step 4: Create Default Tenant**
```bash
php artisan tenant:create "Default Tenant" --domain=default
```

**Step 5: Associate Existing Data**
```bash
php artisan tenant:migrate-data {tenant_id}
```

---

## Success Metrics

### Technical Metrics

| Metric | Target | Measurement Method |
|--------|--------|-------------------|
| Tenant Resolution Time | < 100ms average | Performance monitoring |
| Cache Hit Rate | > 95% | Redis metrics |
| Cross-Tenant Access Attempts | 0 successful | Security audit logs |
| Test Coverage | 100% | Pest coverage report |

### Business Metrics

| Metric | Target | Measurement Method |
|--------|--------|-------------------|
| Active Tenants Supported | 1,000+ | Database count |
| Tenant Creation Time | < 30 seconds | API response time |
| System Uptime per Tenant | 99.9% | Monitoring |
| Support Impersonation Usage | < 5% of tenants/month | Audit logs |

---

## Assumptions & Constraints

### Assumptions

1. **Database Performance:** PostgreSQL or MySQL can handle thousands of concurrent tenant queries with proper indexing
2. **Cache Availability:** Redis or Memcached is available and configured for high availability
3. **Subdomain Routing:** DNS configuration allows wildcard subdomain routing (*.yourdomain.com)
4. **User Count per Tenant:** Average tenant has 10-50 users (affects cache sizing)
5. **Tenant Growth:** 10-20% monthly growth in tenant count

### Constraints

1. **Single Database:** All tenants share the same database (no per-tenant databases)
2. **Laravel Framework:** Must use Laravel v12.x (not compatible with older versions)
3. **PHP Version:** Requires PHP 8.2+ for enum and type system support
4. **Cache Requirement:** Redis/Memcached is mandatory, not optional
5. **Migration Complexity:** Adding multi-tenancy to existing apps requires careful data migration

---

## Monorepo Integration

### Development

During development in the monorepo:

1. **Location:** Lives in `/packages/multitenancy/` directory
2. **Local Requirement:** Main app uses Composer path repository:
   ```json
   {
       "repositories": [
           {
               "type": "path",
               "url": "../../packages/*"
           }
       ],
       "require": {
           "azaharizaman/erp-multitenancy": "dev-main"
       }
   }
   ```
3. **Development Flow:**
   - Edit code in `/packages/multitenancy/`
   - Changes immediately reflected in main app
   - Run tests: `./vendor/bin/pest packages/multitenancy/tests`
   - Commit changes to monorepo

### Release (v1.0)

When releasing version 1.0:

1. **Tagging:** Tag monorepo with version (e.g., `v1.0.0`)
2. **Packagist Publication:** Publish `azaharizaman/erp-multitenancy` to Packagist
3. **Independent Installation:** External Laravel apps can install:
   ```bash
   composer require azaharizaman/erp-multitenancy
   ```
4. **Versioning:** Package follows SemVer with monorepo release

---

## References

- Master PRD: [../PRD01-MVP.md](../PRD01-MVP.md)
- Monorepo Strategy: [../PRD01-MVP.md#c1-core-architectural-strategy-the-monorepo](../PRD01-MVP.md#c1-core-architectural-strategy-the-monorepo)
- Feature Module Independence: [../PRD01-MVP.md#d22-feature-module-independence-requirements](../PRD01-MVP.md#d22-feature-module-independence-requirements)
- Architecture Documentation: [../../architecture/](../../architecture/)
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- GitHub Copilot Instructions: [../../.github/copilot-instructions.md](../../.github/copilot-instructions.md)

---

## Next Steps

1. ✅ Review and approve this Sub-PRD
2. ⏳ Create implementation plan: `PLAN01-implement-multitenancy.md` in `/docs/plan/`
3. ⏳ Break down into GitHub issues using `.github/prompts/create-issue-from-implementation-plan.prompt.md`
4. ⏳ Assign to **MILESTONE 1** (Nov 30, 2025)
5. ⏳ Set up feature module structure in `/packages/multitenancy/`
6. ⏳ Implement database migrations for tenants table
7. ⏳ Develop Tenant model and repository
8. ⏳ Create BelongsToTenant trait and global scope
9. ⏳ Implement tenant resolution middleware
10. ⏳ Build tenant management API endpoints
11. ⏳ Add impersonation functionality with audit
12. ⏳ Write comprehensive tests (unit, feature, integration)
13. ⏳ Generate API documentation (OpenAPI/Swagger)
14. ⏳ Code review and QA testing

---

**Document Status:** Draft - Pending Review  
**Maintained By:** Laravel ERP Development Team  
**Last Updated:** November 11, 2025
