# Laravel ERP System - GitHub Copilot Instructions

**Version:** 1.0.0  
**Last Updated:** November 8, 2025  
**Project:** Laravel Headless ERP Backend System

---

## Project Overview

This is an enterprise-grade, headless ERP backend system built with Laravel 12+ and PHP 8.2+. The system is designed to rival SAP, Odoo, and Microsoft Dynamics while maintaining superior modularity, extensibility, and agentic capabilities.

### Key Characteristics

- **Architecture:** Headless backend-only system (no UI components)
- **Integration:** RESTful APIs and CLI commands only
- **Design Philosophy:** Contract-driven, domain-driven, event-driven
- **Target:** AI agents, custom frontends, and automated systems
- **Modularity:** Enable/disable modules without system-wide impact
- **Security:** Zero-trust model with blockchain verification

---

## Technology Stack

### Core Requirements

- **PHP:** ≥ 8.2 (use latest PHP 8.2+ features)
- **Laravel:** ≥ 12.x (latest stable version)
- **Database:** Agnostic design (MySQL, PostgreSQL, SQLite, SQL Server)
- **Composer Stability:** `dev` for internal packages

### Required Packages

```json
{
  "azaharizaman/laravel-uom-management": "dev-main",
  "azaharizaman/laravel-inventory-management": "dev-main",
  "azaharizaman/laravel-backoffice": "dev-main",
  "azaharizaman/laravel-serial-numbering": "dev-main",
  "azaharizaman/php-blockchain": "dev-main",
  "lorisleiva/laravel-actions": "^2.0",
  "spatie/laravel-permission": "^6.0",
  "spatie/laravel-model-status": "^2.0",
  "spatie/laravel-activitylog": "^4.0",
  "brick/math": "^0.12"
}
```

### Architecture Patterns

- **Contract-Driven Development:** All functionality defined by interfaces
- **Domain-Driven Design:** Business logic organized by domain boundaries
- **Event-Driven Architecture:** Module communication via Laravel events
- **Repository Pattern:** Data access abstraction layer
- **Service Layer Pattern:** Business logic encapsulation
- **Action Pattern:** Discrete business operations using `lorisleiva/laravel-actions`
- **SOLID Principles:** Single responsibility, dependency injection throughout

---

## Code Standards

### PHP Standards

#### Version Features
- Use PHP 8.2+ features: typed properties, constructor property promotion, enums, readonly properties
- Use strict types: `declare(strict_types=1);` in all PHP files
- Use null coalescing operator `??` and null safe operator `?->`
- Use match expressions instead of switch statements where appropriate
- Use array spread operator `...` for array merging
- Use named arguments for better readability in complex function calls

#### Type Declarations
```php
// ALWAYS use strict type declarations
declare(strict_types=1);

namespace App\Domains\Inventory\Actions;

use App\Domains\Inventory\Contracts\InventoryItemInterface;
use App\Domains\Inventory\Models\InventoryItem;
use Illuminate\Support\Collection;

// Use typed properties
class AdjustStockAction
{
    public function __construct(
        private readonly InventoryItemInterface $inventoryRepository,
        private readonly AuditLogService $auditLog
    ) {}
    
    // Always specify return types
    public function execute(InventoryItem $item, float $quantity, string $reason): bool
    {
        // Implementation
    }
}
```

#### Naming Conventions
- **Classes:** PascalCase - `InventoryItemController`, `PurchaseOrderService`
- **Methods:** camelCase - `createPurchaseOrder()`, `getActiveItems()`
- **Variables:** camelCase - `$purchaseOrder`, `$itemQuantity`
- **Constants:** UPPER_SNAKE_CASE - `MAX_QUANTITY`, `DEFAULT_CURRENCY`
- **Database Tables:** snake_case, plural - `inventory_items`, `purchase_orders`
- **Database Columns:** snake_case - `created_at`, `unit_price`
- **Contracts/Interfaces:** Suffix with `Interface` - `InventoryItemInterface`
- **Actions:** Suffix with `Action` - `CreatePurchaseOrderAction`
- **Events:** Suffix with `Event` - `StockAdjustedEvent`
- **Listeners:** Suffix with `Listener` - `SendLowStockNotificationListener`
- **Policies:** Suffix with `Policy` - `InventoryItemPolicy`
- **Repositories:** Suffix with `Repository` - `InventoryItemRepository`
- **Services:** Suffix with `Service` - `SerialNumberService`

#### Code Style
- Follow PSR-12 coding standards
- Use Laravel best practices and conventions
- Keep methods focused and small (≤ 50 lines)
- Maximum cyclomatic complexity: 10
- Use early returns and guard clauses
- Avoid nested conditionals (max depth: 3)

### Documentation Standards

#### PHPDoc Blocks
```php
/**
 * Adjust inventory stock level with audit trail
 *
 * @param InventoryItem $item The inventory item to adjust
 * @param float $quantity The adjustment quantity (positive or negative)
 * @param string $reason The reason for adjustment
 * @return bool True if adjustment successful
 * @throws InsufficientStockException If adjustment would result in negative stock
 * @throws InvalidQuantityException If quantity is zero or invalid
 */
public function execute(InventoryItem $item, float $quantity, string $reason): bool
```

#### Comments
- Write self-documenting code (clear naming reduces comment needs)
- Comment "why" not "what"
- Use TODO, FIXME, NOTE markers with issue numbers
- Document complex business logic and calculations
- Explain non-obvious design decisions

---

## Project Structure

### Domain Organization

```
app/
├── Domains/                         # Business domains (DDD)
│   ├── Core/                        # Foundation (tenancy, auth, audit)
│   │   ├── Actions/
│   │   ├── Contracts/
│   │   ├── Events/
│   │   ├── Listeners/
│   │   ├── Models/
│   │   ├── Observers/
│   │   ├── Policies/
│   │   ├── Repositories/
│   │   └── Services/
│   ├── Backoffice/                  # Organization structure
│   ├── Inventory/                   # Inventory management
│   ├── Sales/                       # Sales operations
│   ├── Purchasing/                  # Procurement
│   ├── Manufacturing/               # Production
│   ├── HumanResources/              # HR operations
│   ├── Accounting/                  # Financial accounting
│   ├── SupplyChain/                 # Supply chain
│   ├── Quality/                     # Quality management
│   ├── Maintenance/                 # CMMS (optional)
│   └── Analytics/                   # Business intelligence
│
├── Http/
│   ├── Controllers/                 # API controllers only
│   │   └── Api/
│   │       └── V1/
│   │           ├── CoreController.php
│   │           ├── InventoryController.php
│   │           └── ...
│   ├── Middleware/
│   ├── Requests/                    # Form request validation
│   └── Resources/                   # API resources (transformers)
│
├── Console/
│   └── Commands/                    # CLI commands for each domain
│
└── Support/                         # Shared utilities
    ├── Enums/
    ├── Helpers/
    └── Traits/
```

### File Naming Patterns

- **Models:** Singular noun - `InventoryItem.php`, `PurchaseOrder.php`
- **Controllers:** Resource name + `Controller.php` - `InventoryItemController.php`
- **Actions:** Verb + noun + `Action.php` - `CreatePurchaseOrderAction.php`
- **Services:** Purpose + `Service.php` - `SerialNumberService.php`
- **Repositories:** Model + `Repository.php` - `InventoryItemRepository.php`
- **Contracts:** Model + `Interface.php` - `InventoryItemInterface.php`
- **Events:** Past tense + `Event.php` - `StockAdjustedEvent.php`
- **Listeners:** Action description + `Listener.php` - `UpdateInventoryBalanceListener.php`
- **Policies:** Model + `Policy.php` - `InventoryItemPolicy.php`
- **Requests:** Action + Model + `Request.php` - `StoreInventoryItemRequest.php`
- **Resources:** Model + `Resource.php` - `InventoryItemResource.php`
- **Migrations:** `yyyy_mm_dd_hhmmss_action_table_name.php`

---

## Development Guidelines

### Contract-Driven Development

Always define contracts (interfaces) before implementation:

```php
namespace App\Domains\Inventory\Contracts;

use App\Domains\Inventory\Models\InventoryItem;
use Illuminate\Support\Collection;

interface InventoryItemInterface
{
    public function findById(int $id): ?InventoryItem;
    
    public function findByCode(string $code): ?InventoryItem;
    
    public function getActiveItems(): Collection;
    
    public function create(array $data): InventoryItem;
    
    public function update(InventoryItem $item, array $data): bool;
    
    public function delete(InventoryItem $item): bool;
}
```

### Repository Pattern

Implement repositories for all data access:

```php
namespace App\Domains\Inventory\Repositories;

use App\Domains\Inventory\Contracts\InventoryItemInterface;
use App\Domains\Inventory\Models\InventoryItem;
use Illuminate\Support\Collection;

class InventoryItemRepository implements InventoryItemInterface
{
    public function findById(int $id): ?InventoryItem
    {
        return InventoryItem::find($id);
    }
    
    public function getActiveItems(): Collection
    {
        return InventoryItem::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }
    
    // Implement all interface methods
}
```

### Action Pattern

Use Actions for business operations:

```php
namespace App\Domains\Inventory\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use App\Domains\Inventory\Models\InventoryItem;
use App\Domains\Inventory\Events\StockAdjustedEvent;

class AdjustStockAction
{
    use AsAction;
    
    public function __construct(
        private readonly AuditLogService $auditLog
    ) {}
    
    public function handle(InventoryItem $item, float $quantity, string $reason): bool
    {
        // Validate
        if ($quantity === 0.0) {
            throw new InvalidQuantityException('Quantity cannot be zero');
        }
        
        $newQuantity = $item->quantity + $quantity;
        
        if ($newQuantity < 0) {
            throw new InsufficientStockException('Insufficient stock for adjustment');
        }
        
        // Update
        $item->quantity = $newQuantity;
        $item->save();
        
        // Audit
        $this->auditLog->log('stock_adjusted', $item, [
            'old_quantity' => $item->quantity - $quantity,
            'new_quantity' => $newQuantity,
            'adjustment' => $quantity,
            'reason' => $reason,
        ]);
        
        // Event
        event(new StockAdjustedEvent($item, $quantity, $reason));
        
        return true;
    }
    
    // Make action available as job
    public function asJob(InventoryItem $item, float $quantity, string $reason): void
    {
        $this->handle($item, $quantity, $reason);
    }
    
    // Make action available via CLI
    public function asCommand(Command $command): void
    {
        $itemId = $command->argument('item_id');
        $quantity = (float) $command->argument('quantity');
        $reason = $command->argument('reason');
        
        $item = InventoryItem::findOrFail($itemId);
        
        $this->handle($item, $quantity, $reason);
        
        $command->info("Stock adjusted successfully for {$item->code}");
    }
}
```

### Event-Driven Communication

Use events for module communication:

```php
namespace App\Domains\Inventory\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Domains\Inventory\Models\InventoryItem;

class StockAdjustedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public readonly InventoryItem $item,
        public readonly float $adjustment,
        public readonly string $reason
    ) {}
}
```

### Service Layer

Encapsulate complex business logic in services:

```php
namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Contracts\InventoryItemInterface;
use App\Domains\Inventory\Models\InventoryItem;
use Illuminate\Support\Collection;

class InventoryValuationService
{
    public function __construct(
        private readonly InventoryItemInterface $repository
    ) {}
    
    public function calculateTotalValue(): float
    {
        return $this->repository
            ->getActiveItems()
            ->sum(fn (InventoryItem $item) => $item->quantity * $item->unit_cost);
    }
    
    public function getItemsByValuation(float $minValue): Collection
    {
        return $this->repository
            ->getActiveItems()
            ->filter(fn (InventoryItem $item) => 
                ($item->quantity * $item->unit_cost) >= $minValue
            );
    }
}
```

---

## API Development

### API Standards

- **Version:** Always version APIs (`/api/v1/`)
- **Format:** JSON:API specification compliance
- **Authentication:** Laravel Sanctum (stateless tokens)
- **Rate Limiting:** Apply per endpoint
- **Status Codes:** Use proper HTTP status codes
- **Pagination:** Always paginate list endpoints
- **Filtering:** Support query parameter filtering
- **Sorting:** Support `sort` parameter
- **Field Selection:** Support `fields` parameter (sparse fieldsets)

### Controller Structure

```php
namespace App\Http\Controllers\Api\V1;

use App\Domains\Inventory\Actions\CreateInventoryItemAction;
use App\Domains\Inventory\Actions\UpdateInventoryItemAction;
use App\Domains\Inventory\Contracts\InventoryItemInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryItemController extends Controller
{
    public function __construct(
        private readonly InventoryItemInterface $repository
    ) {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(InventoryItem::class, 'item');
    }
    
    public function index(): AnonymousResourceCollection
    {
        $items = $this->repository
            ->query()
            ->filter(request()->only(['search', 'category', 'is_active']))
            ->sort(request('sort', 'code'))
            ->paginate(request('per_page', 15));
        
        return InventoryItemResource::collection($items);
    }
    
    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $item = CreateInventoryItemAction::run($request->validated());
        
        return InventoryItemResource::make($item)
            ->response()
            ->setStatusCode(201);
    }
    
    public function show(int $id): InventoryItemResource
    {
        $item = $this->repository->findById($id);
        
        abort_if(!$item, 404, 'Inventory item not found');
        
        return InventoryItemResource::make($item);
    }
    
    public function update(
        UpdateInventoryItemRequest $request,
        int $id
    ): InventoryItemResource {
        $item = $this->repository->findById($id);
        
        abort_if(!$item, 404, 'Inventory item not found');
        
        $updated = UpdateInventoryItemAction::run($item, $request->validated());
        
        return InventoryItemResource::make($updated);
    }
    
    public function destroy(int $id): JsonResponse
    {
        $item = $this->repository->findById($id);
        
        abort_if(!$item, 404, 'Inventory item not found');
        
        $this->repository->delete($item);
        
        return response()->json(null, 204);
    }
}
```

### API Resource Structure

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'uom' => $this->whenLoaded('uom', fn () => UomResource::make($this->uom)),
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'unit_price' => $this->unit_price,
            'reorder_level' => $this->reorder_level,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'links' => [
                'self' => route('api.v1.inventory-items.show', $this->id),
            ],
        ];
    }
}
```

---

## Database Guidelines

### Migration Standards

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Foreign keys (with explicit naming)
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade');
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->onDelete('set null');
            $table->foreignId('uom_id')
                ->constrained('units_of_measure')
                ->onDelete('restrict');
            
            // Unique constraints
            $table->string('code')->unique();
            
            // Required fields
            $table->string('name');
            $table->text('description')->nullable();
            
            // Numeric fields with precision
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('reorder_level', 15, 4)->default(0);
            
            // Status/flags
            $table->boolean('is_active')->default(true);
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
            $table->index('code');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
```

### Model Standards

```php
namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'tenant_id',
        'category_id',
        'uom_id',
        'code',
        'name',
        'description',
        'quantity',
        'unit_cost',
        'unit_price',
        'reorder_level',
        'is_active',
    ];
    
    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'reorder_level' => 'decimal:4',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
    
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level');
    }
    
    // Activity log options
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'quantity', 'unit_cost', 'unit_price'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

---

## Testing Standards

### Test Structure

```
tests/
├── Feature/                         # Integration tests
│   └── Api/
│       └── V1/
│           ├── InventoryItemTest.php
│           └── PurchaseOrderTest.php
├── Unit/                            # Unit tests
│   └── Domains/
│       └── Inventory/
│           ├── Actions/
│           │   └── AdjustStockActionTest.php
│           └── Services/
│               └── InventoryValuationServiceTest.php
└── TestCase.php
```

### Feature Test Example

```php
namespace Tests\Feature\Api\V1;

use App\Domains\Inventory\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryItemTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }
    
    public function test_can_list_inventory_items(): void
    {
        InventoryItem::factory()->count(3)->create();
        
        $response = $this->getJson('/api/v1/inventory-items');
        
        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'quantity'],
                ],
                'links',
                'meta',
            ]);
    }
    
    public function test_can_create_inventory_item(): void
    {
        $data = [
            'code' => 'ITEM-001',
            'name' => 'Test Item',
            'quantity' => 100,
            'unit_cost' => 10.50,
            'unit_price' => 15.00,
        ];
        
        $response = $this->postJson('/api/v1/inventory-items', $data);
        
        $response
            ->assertCreated()
            ->assertJsonFragment(['code' => 'ITEM-001']);
        
        $this->assertDatabaseHas('inventory_items', ['code' => 'ITEM-001']);
    }
    
    public function test_cannot_create_duplicate_item_code(): void
    {
        InventoryItem::factory()->create(['code' => 'ITEM-001']);
        
        $data = ['code' => 'ITEM-001', 'name' => 'Duplicate'];
        
        $response = $this->postJson('/api/v1/inventory-items', $data);
        
        $response->assertUnprocessable();
    }
}
```

### Unit Test Example

```php
namespace Tests\Unit\Domains\Inventory\Actions;

use App\Domains\Inventory\Actions\AdjustStockAction;
use App\Domains\Inventory\Exceptions\InsufficientStockException;
use App\Domains\Inventory\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdjustStockActionTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_increase_stock(): void
    {
        $item = InventoryItem::factory()->create(['quantity' => 100]);
        
        $result = AdjustStockAction::run($item, 50, 'Purchase receipt');
        
        $this->assertTrue($result);
        $this->assertEquals(150, $item->fresh()->quantity);
    }
    
    public function test_can_decrease_stock(): void
    {
        $item = InventoryItem::factory()->create(['quantity' => 100]);
        
        $result = AdjustStockAction::run($item, -30, 'Sales order');
        
        $this->assertTrue($result);
        $this->assertEquals(70, $item->fresh()->quantity);
    }
    
    public function test_throws_exception_on_insufficient_stock(): void
    {
        $item = InventoryItem::factory()->create(['quantity' => 10]);
        
        $this->expectException(InsufficientStockException::class);
        
        AdjustStockAction::run($item, -50, 'Sales order');
    }
}
```

---

## CLI Commands

### Command Structure

```php
namespace App\Console\Commands;

use App\Domains\Inventory\Actions\GenerateStockReportAction;
use Illuminate\Console\Command;

class GenerateStockReportCommand extends Command
{
    protected $signature = 'inventory:stock-report
                            {--format=json : Output format (json|csv|pdf)}
                            {--email= : Email address to send report}
                            {--category= : Filter by category ID}';
    
    protected $description = 'Generate inventory stock report';
    
    public function handle(): int
    {
        $format = $this->option('format');
        $email = $this->option('email');
        $categoryId = $this->option('category');
        
        $this->info('Generating stock report...');
        
        $report = GenerateStockReportAction::run($format, $categoryId);
        
        if ($email) {
            // Send email
            $this->info("Report sent to {$email}");
        } else {
            $this->line($report);
        }
        
        $this->info('Report generated successfully');
        
        return self::SUCCESS;
    }
}
```

---

## Security Guidelines

### Authentication & Authorization

- Use Laravel Sanctum for API authentication
- Implement policies for all models
- Use gates for complex authorization logic
- Always authorize controller actions
- Use middleware for route protection

### Input Validation

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', InventoryItem::class);
    }
    
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:inventory_items,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'uom_id' => ['required', 'exists:units_of_measure,id'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
```

### Audit Logging

- Log all data modifications using `spatie/laravel-activitylog`
- Include user, timestamp, old/new values
- Log failed authorization attempts
- Log all authentication events
- Consider blockchain verification for critical operations

---

## Module Development Workflow

When creating a new module or domain:

1. **Define Contracts** - Create interfaces in `Contracts/` directory
2. **Create Models** - Define Eloquent models with relationships
3. **Write Migrations** - Create database schema
4. **Build Repositories** - Implement contract interfaces
5. **Create Actions** - Build discrete business operations
6. **Define Events** - Create events for module communication
7. **Build Services** - Encapsulate complex business logic
8. **Add Policies** - Implement authorization rules
9. **Create Controllers** - Build API endpoints
10. **Write Tests** - Feature and unit tests
11. **Add CLI Commands** - Create console commands
12. **Document APIs** - API documentation

---

## Best Practices Summary

### DO

- ✅ Use strict types in all PHP files
- ✅ Type-hint all parameters and return types
- ✅ Define contracts/interfaces before implementation
- ✅ Use repository pattern for data access
- ✅ Implement action pattern for business operations
- ✅ Use events for module communication
- ✅ Write comprehensive tests (Feature + Unit)
- ✅ Log all data modifications with audit trail
- ✅ Validate all inputs using Form Requests
- ✅ Use resource classes for API responses
- ✅ Apply authorization policies
- ✅ Document complex business logic
- ✅ Use database transactions for atomic operations
- ✅ Implement soft deletes where appropriate
- ✅ Use eager loading to prevent N+1 queries
- ✅ Follow PSR-12 coding standards

### DON'T

- ❌ Create UI components or views (headless system only)
- ❌ Use static Eloquent methods in business logic (use repositories)
- ❌ Put business logic in controllers (use actions/services)
- ❌ Skip input validation
- ❌ Bypass authorization checks
- ❌ Forget audit logging
- ❌ Create tight coupling between modules
- ❌ Use magic numbers (define constants)
- ❌ Ignore database indexing
- ❌ Skip writing tests
- ❌ Use raw SQL queries without parameterization
- ❌ Expose sensitive data in API responses
- ❌ Skip error handling
- ❌ Create god classes or methods

---

## Reference Documentation

### Required Reading

- [Laravel 12.x Documentation](https://laravel.com/docs/12.x)
- [PHP 8.2+ Features](https://www.php.net/releases/8.2/en.php)
- [PSR-12 Coding Style](https://www.php-fig.org/psr/psr-12/)
- [JSON:API Specification](https://jsonapi.org/format/)
- [Domain-Driven Design Patterns](https://martinfowler.com/tags/domain%20driven%20design.html)

### Internal Documentation

- [Product Requirements Document](/docs/prd/PRD.md)
- [Phase 1 MVP Specifications](/docs/prd/PHASE-1-MVP.md)
- [Phase 2-4 Progressive Features](/docs/prd/PHASE-2-4-PROGRESSIVE.md)
- [Module Development Guide](/docs/prd/MODULE-DEVELOPMENT.md)
- [Implementation Checklist](/docs/prd/IMPLEMENTATION-CHECKLIST.md)

### Package Documentation

- [Laravel UOM Management](https://github.com/azaharizaman/laravel-uom-management)
- [Laravel Inventory Management](https://github.com/azaharizaman/laravel-inventory-management)
- [Laravel Backoffice](https://github.com/azaharizaman/laravel-backoffice)
- [Laravel Serial Numbering](https://github.com/azaharizaman/laravel-serial-numbering)
- [PHP Blockchain](https://github.com/azaharizaman/php-blockchain)
- [Laravel Actions](https://laravelactions.com/)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [Spatie Laravel Model Status](https://github.com/spatie/laravel-model-status)
- [Spatie Laravel Activity Log](https://spatie.be/docs/laravel-activitylog)

---

## Copilot-Specific Instructions

### When Generating Code

1. **Always** check existing patterns in the codebase first
2. **Follow** the domain structure strictly
3. **Use** the appropriate design patterns (Repository, Action, Service)
4. **Include** proper type hints and return types
5. **Add** PHPDoc blocks for complex methods
6. **Implement** proper error handling
7. **Write** corresponding tests
8. **Consider** event-driven communication between modules
9. **Apply** authorization and validation
10. **Log** important operations

### When Refactoring

1. **Maintain** backward compatibility
2. **Preserve** existing contracts/interfaces
3. **Update** tests accordingly
4. **Document** breaking changes
5. **Use** Laravel's migration system for database changes

### When Debugging

1. **Check** Laravel logs (`storage/logs/laravel.log`)
2. **Review** query logs for N+1 issues
3. **Verify** authorization policies
4. **Validate** input data
5. **Check** event listeners

---

**Last Updated:** November 8, 2025  
**Maintained By:** Laravel ERP Development Team  
**Questions?** Refer to `/docs/prd/` folder for detailed specifications
