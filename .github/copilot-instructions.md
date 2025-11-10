# Laravel ERP System - GitHub Copilot Instructions

**Version:** 2.0.0  
**Last Updated:** November 10, 2025  
**Project:** Laravel Headless ERP Backend System

> **📖 Important:** Before starting any development, read the [CODING_GUIDELINES.md](../CODING_GUIDELINES.md) file in the repository root. It contains critical coding standards and common mistakes to avoid.

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Technology Stack](#technology-stack)
3. [Core Architecture Patterns](#core-architecture-patterns)
4. [Mandatory Tool Integration](#mandatory-tool-integration)
5. [Development Standards](#development-standards)
6. [Domain Organization](#domain-organization)
7. [Quick Reference](#quick-reference)

---

## Project Overview

This is an **enterprise-grade, headless ERP backend system** built with Laravel 12+ and PHP 8.2+. The system is designed to rival SAP, Odoo, and Microsoft Dynamics while maintaining superior modularity, extensibility, and agentic capabilities.

### Key Characteristics

- **Architecture:** Headless backend-only system (NO UI components, NO views, NO frontend assets)
- **Integration:** RESTful APIs and CLI commands only
- **Design Philosophy:** Contract-driven, Domain-driven, Event-driven
- **Target Users:** AI agents, custom frontends, and automated systems
- **Modularity:** Enable/disable modules without system-wide impact
- **Security:** Zero-trust model with blockchain verification for critical operations

### System Boundaries

**✅ This system includes:**
- RESTful API endpoints (versioned as `/api/v1/`)
- Artisan CLI commands (prefixed with `erp:`)
- Background queue jobs
- Event-driven integrations
- Database schema and migrations
- Business logic (Actions, Services, Repositories)

**❌ This system excludes:**
- Web views (Blade templates)
- Frontend JavaScript frameworks
- HTML/CSS assets
- Server-side rendering
- Traditional web forms

---

## Technology Stack

### Core Requirements

| Component | Version | Purpose |
|-----------|---------|---------|
| **PHP** | ≥ 8.2 | Use latest PHP 8.2+ features (readonly, enums, etc.) |
| **Laravel** | ≥ 12.x | Latest stable version with streamlined structure |
| **Database** | Agnostic | Design for MySQL, PostgreSQL, SQLite, SQL Server |
| **Composer** | Latest | Use `dev-main` stability for internal packages |

### Required Packages

All packages below are **required dependencies** for this project:

#### Core Business Packages (dev-main)
```json
{
  "azaharizaman/laravel-uom-management": "dev-main",
  "azaharizaman/laravel-inventory-management": "dev-main",
  "azaharizaman/laravel-backoffice": "dev-main",
  "azaharizaman/laravel-serial-numbering": "dev-main",
  "azaharizaman/php-blockchain": "dev-main"
}
```

#### Development Tools (MANDATORY)
```json
{
  "laravel/scout": "^10.0",          // MANDATORY: Search on all models
  "laravel/pulse": "^1.0",           // Optional: Performance monitoring
  "pestphp/pest": "^4.0",            // MANDATORY: Primary testing framework
  "laravel/pint": "^1.0"             // MANDATORY: Code style enforcement
}
```

#### Architecture Support
```json
{
  "lorisleiva/laravel-actions": "^2.0",
  "spatie/laravel-permission": "^6.0",
  "spatie/laravel-model-status": "^2.0",
  "spatie/laravel-activitylog": "^4.0",
  "brick/math": "^0.12"
}
```

---

## Core Architecture Patterns

### 1. Contract-Driven Development

**ALWAYS define interfaces before implementation:**

```php
// 1. Define contract in app/Domains/{Domain}/Contracts/
interface TenantRepositoryContract
{
    public function findById(int $id): ?Tenant;
    public function create(array $data): Tenant;
}

// 2. Implement in app/Domains/{Domain}/Repositories/
class TenantRepository implements TenantRepositoryContract
{
    public function findById(int $id): ?Tenant
    {
        return Tenant::find($id);
    }
}

// 3. Bind in service provider
$this->app->bind(TenantRepositoryContract::class, TenantRepository::class);

// 4. Inject via constructor
public function __construct(
    private readonly TenantRepositoryContract $repository
) {}
```

### 2. Domain-Driven Design

**Strict domain boundaries:**

```
app/Domains/
├── Core/              # Multi-tenancy, auth, settings (NO business logic)
├── Backoffice/        # Organization structure, staff
├── Inventory/         # Items, warehouses, stock movements
├── Sales/             # Customers, orders, pricing
├── Purchasing/        # Vendors, POs, goods receipt
└── Accounting/        # GL, AP/AR, reporting
```

Each domain contains:
```
{DomainName}/
├── Actions/          # Business operations (Laravel Actions)
├── Contracts/        # Interfaces
├── Events/           # Domain events
├── Listeners/        # Event handlers
├── Models/           # Eloquent models
├── Observers/        # Model observers
├── Policies/         # Authorization
├── Repositories/     # Data access
└── Services/         # Business logic
```

**Cross-domain communication:** ONLY via events. No direct dependencies between business domains.

### 3. Event-Driven Architecture

```php
// Dispatch domain events
event(new StockAdjustedEvent($item, $quantity, $reason));

// Listen in other domains
class UpdateInventoryBalanceListener
{
    public function handle(StockAdjustedEvent $event): void
    {
        // Update accounting balances
    }
}
```

### 4. Repository Pattern

**NEVER use direct Model access in services:**

```php
// ❌ WRONG
class TenantManager
{
    public function create(array $data): Tenant
    {
        return Tenant::create($data);  // Direct model access
    }
}

// ✅ CORRECT
class TenantManager
{
    public function __construct(
        private readonly TenantRepositoryContract $repository
    ) {}
    
    public function create(array $data): Tenant
    {
        return $this->repository->create($data);
    }
}
```

### 5. Action Pattern

Use `lorisleiva/laravel-actions` for all business operations:

```php
use Lorisleiva\Actions\Concerns\AsAction;

class CreatePurchaseOrderAction
{
    use AsAction;
    
    public function handle(array $data): PurchaseOrder
    {
        // Validation, business logic, audit logging, event dispatching
        return $order;
    }
    
    // Automatically available as:
    // - Job: CreatePurchaseOrderAction::dispatch($data)
    // - Command: php artisan erp:create-purchase-order
    // - Controller: CreatePurchaseOrderAction::run($data)
}
```

---

## Mandatory Tool Integration

### 1. Laravel Scout (MANDATORY)

**ALL Eloquent models MUST implement Scout:**

```php
namespace App\Domains\Inventory\Models;

use Laravel\Scout\Searchable;

class InventoryItem extends Model
{
    use Searchable;
    
    public function searchableAs(): string
    {
        return 'inventory_items';
    }
    
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'tenant_id' => $this->tenant_id,  // Required for multi-tenancy
        ];
    }
}
```

**Requirements:**
- ✅ Use `Searchable` trait on all models
- ✅ Implement `searchableAs()` for index naming
- ✅ Implement `toSearchableArray()` for search data
- ✅ Include `tenant_id` for tenant isolation
- ✅ Test search in feature tests

### 2. Pest Testing (MANDATORY)

**ALL tests MUST use Pest v4+ syntax:**

```php
// Feature Test
test('can create inventory item', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/inventory-items', [
            'code' => 'ITEM-001',
            'name' => 'Test Item',
            'quantity' => 100,
        ]);
    
    $response->assertCreated();
    expect($response->json('data.code'))->toBe('ITEM-001');
});

// Unit Test
test('can increase stock quantity', function () {
    $item = InventoryItem::factory()->create(['quantity' => 100]);
    
    $result = AdjustStockAction::run($item, 50, 'Purchase receipt');
    
    expect($result)->toBeTrue();
    expect($item->fresh()->quantity)->toBe(150.0);
});
```

**Run tests:**
```bash
./vendor/bin/pest                              # All tests
./vendor/bin/pest tests/Feature/               # Feature tests only
./vendor/bin/pest --parallel                   # Parallel execution
./vendor/bin/pest --coverage                   # With coverage
```

### 3. Laravel Pint (MANDATORY)

**Run before every commit:**

```bash
./vendor/bin/pint                              # Fix all issues
./vendor/bin/pint --test                       # Check only (CI)
./vendor/bin/pint app/Models/User.php          # Fix specific file
```

**Configuration:** PSR-12 with Laravel-specific rules

### 4. Laravel Pulse (OPTIONAL)

Performance monitoring for production:
- Dashboard: `/pulse`
- Metrics: Application performance, cache hits, queue jobs, slow queries
- Authorization: Admin-only access

---

## Development Standards

### Type Safety (MANDATORY)

All PHP files MUST follow strict typing:

```php
<?php

declare(strict_types=1);  // ← REQUIRED

namespace App\Domains\Core\Models;

class Tenant extends Model
{
    // All methods MUST have:
    // 1. Parameter type hints
    // 2. Return type declarations
    // 3. PHPDoc with @return tags
    
    /**
     * Get active tenants
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TenantStatus::ACTIVE);
    }
}
```

### Multi-Tenancy (MANDATORY)

All tenant-aware models MUST use the trait:

```php
use App\Domains\Core\Traits\BelongsToTenant;

class InventoryItem extends Model
{
    use BelongsToTenant;  // Automatic tenant_id handling
}
```

**Benefits:**
- Automatic `tenant_id` on creation
- Global scope for filtering
- Helper methods: `withoutTenantScope()`, `withAllTenants()`

### Security (MANDATORY)

```php
// 1. Authentication checks
if (! auth()->check()) {
    throw new RuntimeException('Requires authenticated user');
}

// 2. Authorization checks
if (! auth()->user()->can('impersonate-tenant', $tenant)) {
    throw new AuthorizationException('Unauthorized');
}

// 3. Input validation
$validator = Validator::make($data, [
    'name' => ['required', 'string', 'max:255'],
    'status' => ['nullable', 'string', Rule::in(TenantStatus::values())],
]);

// 4. Audit logging
use Spatie\Activitylog\Traits\LogsActivity;

class PurchaseOrder extends Model
{
    use LogsActivity;
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_amount'])
            ->logOnlyDirty();
    }
}
```

### Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Classes | PascalCase | `InventoryItemController`, `CreatePurchaseOrderAction` |
| Methods | camelCase | `createPurchaseOrder()`, `calculateTotal()` |
| Variables | camelCase | `$itemQuantity`, `$totalAmount` |
| Tables | snake_case (plural) | `inventory_items`, `purchase_orders` |
| Columns | snake_case | `created_at`, `unit_price`, `tenant_id` |
| Constants | UPPER_SNAKE_CASE | `MAX_QUANTITY`, `DEFAULT_CURRENCY` |

### Database Best Practices

```php
Schema::create('inventory_items', function (Blueprint $table) {
    $table->id();
    
    // Foreign keys with explicit naming
    $table->foreignId('tenant_id')
        ->constrained('tenants')
        ->onDelete('cascade');
    
    // Always index tenant_id
    $table->index(['tenant_id', 'is_active']);
    
    // Decimal precision for financial data
    $table->decimal('quantity', 15, 4)->default(0);
    $table->decimal('unit_cost', 15, 2)->default(0);
    
    $table->timestamps();
    $table->softDeletes();
});
```

---

## Domain Organization

### Core Domain
- **Purpose:** Multi-tenancy foundation, authentication, audit logging
- **Packages:** Laravel Sanctum, Spatie Permission
- **Rule:** NO business logic here

### Backoffice Domain
- **Purpose:** Organization structure (Company, Office, Department)
- **Package:** `azaharizaman/laravel-backoffice`
- **Depends on:** Core

### Inventory Domain
- **Purpose:** Item master, warehouse, stock movements
- **Packages:** `azaharizaman/laravel-inventory-management`, `laravel-uom-management`
- **Depends on:** Core, Backoffice

### Sales Domain
- **Purpose:** Customers, quotations, sales orders
- **Depends on:** Core, Inventory

### Purchasing Domain
- **Purpose:** Vendors, purchase orders, goods receipt
- **Depends on:** Core, Inventory

### Accounting Domain
- **Purpose:** General ledger, AP/AR, financial reporting
- **Depends on:** Core, Sales, Purchasing

---

## Quick Reference

### Pre-Commit Checklist

Before committing code:

- [ ] Read [CODING_GUIDELINES.md](../CODING_GUIDELINES.md)
- [ ] All files have `declare(strict_types=1);`
- [ ] All methods have parameter types and return types
- [ ] All public methods have PHPDoc blocks
- [ ] Using repository pattern (no direct Model access in services)
- [ ] Authentication and authorization checks in place
- [ ] Complete validation rules for all fillable fields
- [ ] Tests written (Feature + Unit) using Pest
- [ ] Run `./vendor/bin/pint` to fix code style
- [ ] Run `./vendor/bin/pest` to verify tests pass

### Common Commands

```bash
# Testing
./vendor/bin/pest                              # Run all tests
./vendor/bin/pest --filter=testName            # Run specific test
./vendor/bin/pest --parallel                   # Parallel execution

# Code Quality
./vendor/bin/pint                              # Fix code style
./vendor/bin/pint --test                       # Check only (CI)

# Artisan
php artisan make:model {Domain}/Models/{Name}  # Create model
php artisan make:action {Domain}/Actions/{Name} # Create action
php artisan make:test {Name} --pest            # Create Pest test
```

### API Development Pattern

```php
// 1. Form Request (validation)
class StoreInventoryItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'unique:inventory_items'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}

// 2. Action (business logic)
class CreateInventoryItemAction
{
    use AsAction;
    
    public function handle(array $data): InventoryItem
    {
        // Business logic here
    }
}

// 3. Resource (transformation)
class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
        ];
    }
}

// 4. Controller (thin layer)
class InventoryItemController extends Controller
{
    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $item = CreateInventoryItemAction::run($request->validated());
        
        return InventoryItemResource::make($item)
            ->response()
            ->setStatusCode(201);
    }
}
```

### Performance Optimization

```php
// ✅ GOOD: Eager loading
$items = InventoryItem::with(['uom', 'category'])->get();

// ❌ BAD: N+1 query
foreach ($items as $item) {
    echo $item->uom->name; // Separate query each iteration
}

// ✅ GOOD: Chunk large datasets
InventoryItem::chunk(1000, function ($items) {
    foreach ($items as $item) {
        // Process item
    }
});
```

---

## Important Reminders

1. **📖 Read CODING_GUIDELINES.md first** - Contains common mistakes and how to avoid them
2. **🚫 No UI components** - This is a headless API-only system
3. **✅ Contract-first** - Define interfaces before implementation
4. **🔍 Scout everywhere** - All models must be searchable
5. **🧪 Test with Pest** - All tests use Pest v4+ syntax
6. **✨ Pint before commit** - Run code formatter before committing
7. **🏢 Multi-tenant aware** - Use BelongsToTenant trait on all tenant models
8. **🔒 Security first** - Always check authentication and authorization
9. **📝 Audit everything** - Use LogsActivity trait for important models
10. **🎯 Domain boundaries** - Communicate between domains via events only

---

## Additional Resources

- **[CODING_GUIDELINES.md](../CODING_GUIDELINES.md)** - Detailed coding standards and common mistakes
- **[PSR-12 Standard](https://www.php-fig.org/psr/psr-12/)** - PHP coding style guide
- **[Laravel Documentation](https://laravel.com/docs)** - Framework documentation
- **[Pest Documentation](https://pestphp.com)** - Testing framework guide
- **[Laravel Scout Documentation](https://laravel.com/docs/scout)** - Search integration

---

**Version:** 2.0.0  
**Maintained By:** Laravel ERP Development Team  
**Last Updated:** November 10, 2025
