# Laravel ERP - Coding Guidelines

This document outlines the coding standards and best practices for the Laravel ERP project. All code contributions must adhere to these guidelines to ensure consistency, maintainability, and code quality across the codebase.

## Table of Contents

- [PHP Standards](#php-standards)
- [Modern Laravel Attributes](#modern-laravel-attributes)
- [Type Declarations](#type-declarations)
- [PHPDoc Documentation](#phpdoc-documentation)
- [Testing Standards](#testing-standards)
- [Migration Standards](#migration-standards)
- [Package Decoupling](#package-decoupling)
- [Common Mistakes and How to Avoid Them](#common-mistakes-and-how-to-avoid-them)

---

## PHP Standards

### 1. Strict Type Declarations

**✅ REQUIRED:** All PHP files MUST include `declare(strict_types=1);` immediately after the opening PHP tag.

#### ❌ Incorrect

```php
<?php

namespace App\Models;

class User extends Authenticatable
{
    // ...
}
```

#### ✅ Correct

```php
<?php

declare(strict_types=1);

namespace App\Models;

class User extends Authenticatable
{
    // ...
}
```

**Why:** Strict type declarations prevent type coercion errors and make the code more predictable by enforcing strict type checking at runtime.

**Applies to:** All PHP files including models, controllers, migrations, services, actions, enums, traits, and tests.

---

## Modern Laravel Attributes

### 1a. Prioritize PHP 8+ Attributes Over Traditional Registration

**✅ REQUIRED:** Use PHP 8+ attributes for dependency injection, route registration, and service binding instead of traditional service provider methods.

**Why:** Attributes provide better IDE support, keep configuration close to the code, reduce boilerplate, and make dependencies explicit.

#### Service Container Binding

##### ❌ Incorrect (Traditional Service Provider)

```php
// In app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(
        TenantRepositoryContract::class,
        TenantRepository::class
    );
    
    $this->app->singleton(
        ActivityLoggerContract::class,
        SpatieActivityLogger::class
    );
    
    $this->app->scoped(
        SearchServiceContract::class,
        ScoutSearchService::class
    );
}
```

##### ✅ Correct (Using Attributes)

```php
<?php

declare(strict_types=1);

namespace App\Domains\Core\Repositories;

use App\Domains\Core\Contracts\TenantRepositoryContract;
use Illuminate\Container\Attributes\Bind;

#[Bind(TenantRepositoryContract::class)]
class TenantRepository implements TenantRepositoryContract
{
    public function findById(int $id): ?Tenant
    {
        return Tenant::find($id);
    }
}
```

**Available Binding Attributes:**

```php
use Illuminate\Container\Attributes\Bind;      // Standard binding (bind)
use Illuminate\Container\Attributes\Singleton; // Singleton binding
use Illuminate\Container\Attributes\Scoped;    // Scoped binding (per request)

// Singleton example
#[Singleton(ActivityLoggerContract::class)]
class SpatieActivityLogger implements ActivityLoggerContract
{
    // ...
}

// Scoped example (fresh instance per request)
#[Scoped(SearchServiceContract::class)]
class ScoutSearchService implements SearchServiceContract
{
    // ...
}
```

#### Route Attributes

##### ❌ Incorrect (Traditional routes file)

```php
// In routes/api.php
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
    Route::patch('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');
});
```

##### ✅ Correct (Using Attributes)

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Attribute\Middleware;
use Illuminate\Routing\Attribute\Prefix;
use Illuminate\Routing\Attribute\Resource;

#[Prefix('api/v1')]
#[Middleware(['auth:sanctum', 'tenant'])]
#[Resource('tenants')]
class TenantController extends Controller
{
    // Automatically creates standard resource routes:
    // GET    /api/v1/tenants          -> index
    // POST   /api/v1/tenants          -> store
    // GET    /api/v1/tenants/{tenant} -> show
    // PATCH  /api/v1/tenants/{tenant} -> update
    // DELETE /api/v1/tenants/{tenant} -> destroy
}

// For individual routes with specific attributes:
use Illuminate\Routing\Attribute\Get;
use Illuminate\Routing\Attribute\Post;

class UserController extends Controller
{
    #[Get('/api/v1/users/{user}/profile', name: 'users.profile')]
    #[Middleware(['auth:sanctum', 'verified'])]
    public function profile(User $user): JsonResponse
    {
        return response()->json($user);
    }
    
    #[Post('/api/v1/users/{user}/avatar', name: 'users.avatar.update')]
    #[Middleware(['auth:sanctum', 'throttle:uploads'])]
    public function updateAvatar(User $user, Request $request): JsonResponse
    {
        // ...
    }
}
```

#### Event Listener Attributes

##### ❌ Incorrect (Traditional EventServiceProvider)

```php
// In app/Providers/EventServiceProvider.php
protected $listen = [
    TenantCreatedEvent::class => [
        InitializeTenantDataListener::class,
        SendWelcomeEmailListener::class,
    ],
    UserRegisteredEvent::class => [
        LogUserRegistrationListener::class,
    ],
];
```

##### ✅ Correct (Using Attributes)

```php
<?php

declare(strict_types=1);

namespace App\Domains\Core\Listeners;

use App\Domains\Core\Events\TenantCreatedEvent;
use Illuminate\Events\Attribute\Listen;

class InitializeTenantDataListener
{
    #[Listen(TenantCreatedEvent::class)]
    public function handle(TenantCreatedEvent $event): void
    {
        // Initialize default roles and permissions
        $this->createDefaultRoles($event->tenant);
    }
}

// Multiple events for same listener
class ActivityLogListener
{
    #[Listen(TenantCreatedEvent::class)]
    #[Listen(TenantUpdatedEvent::class)]
    #[Listen(TenantDeletedEvent::class)]
    public function handle(object $event): void
    {
        activity()->log($event::class);
    }
}
```

#### Schedule Attributes

##### ❌ Incorrect (Traditional Console Kernel)

```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('tenants:cleanup')
        ->daily()
        ->at('03:00');
        
    $schedule->command('cache:prune-stale-tags')
        ->hourly();
}
```

##### ✅ Correct (Using Attributes)

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Attribute\Schedule;

class CleanupTenantsCommand extends Command
{
    protected $signature = 'tenants:cleanup';
    
    #[Schedule('daily', at: '03:00')]
    public function handle(): int
    {
        // Cleanup logic
        return self::SUCCESS;
    }
}

class PruneStaleTagsCommand extends Command
{
    protected $signature = 'cache:prune-stale-tags';
    
    #[Schedule('hourly')]
    public function handle(): int
    {
        // Prune logic
        return self::SUCCESS;
    }
}
```

#### Benefits of Using Attributes

1. **Co-location:** Configuration lives next to the code it affects
2. **Type Safety:** IDE can validate attribute parameters
3. **Discoverability:** Easy to find which classes are bound/scheduled/listening
4. **Reduced Boilerplate:** No need for separate provider registration
5. **Better IDE Support:** Autocomplete and navigation work better
6. **Single Responsibility:** Each class declares its own dependencies

#### When to Use Attributes vs. Traditional Methods

**✅ Use Attributes:**
- Service container bindings for repositories, services, contracts
- Route definitions in API controllers
- Event listener registration
- Scheduled command registration
- Authorization gates and policies (Laravel 11+)

**⚠️ Use Traditional Methods:**
- Complex conditional bindings based on environment
- Dynamic route registration from database
- Third-party package service provider registration
- Legacy code that requires gradual migration

#### Migration Strategy

**For new code:** Always use attributes from the start.

**For existing code:** Migrate gradually:
1. Start with new classes (use attributes)
2. When modifying existing classes, convert to attributes
3. Update service providers to remove redundant registrations
4. Test thoroughly after each migration

**Example Migration:**

```php
// Step 1: Add attribute to existing class
#[Bind(TenantRepositoryContract::class)]
class TenantRepository implements TenantRepositoryContract
{
    // ... existing code
}

// Step 2: Remove from service provider
// public function register(): void
// {
//     $this->app->bind(
//         TenantRepositoryContract::class,
//         TenantRepository::class
//     ); // ← Remove this
// }

// Step 3: Test that dependency injection still works
// Step 4: Commit the change
```

**Important Notes:**
1. Laravel automatically discovers attributes - no manual registration needed
2. Attributes are processed during container bootstrapping
3. All attribute-based bindings are available immediately
4. No performance penalty compared to traditional methods

---

## Type Declarations

### 2. Method Parameter Type Hints

**✅ REQUIRED:** All method parameters MUST have explicit type declarations.

#### ❌ Incorrect

```php
public function scopeActive($query)
{
    return $query->where('status', TenantStatus::ACTIVE);
}
```

#### ✅ Correct

```php
public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
{
    return $query->where('status', TenantStatus::ACTIVE);
}
```

**Why:** Explicit type hints provide compile-time type checking, better IDE support, and self-documenting code.

### 3. Method Return Type Declarations

**✅ REQUIRED:** All methods MUST declare their return types.

#### ❌ Incorrect

```php
public function up()
{
    Schema::create('tenants', function (Blueprint $table) {
        // ...
    });
}

public function down()
{
    Schema::dropIfExists('tenants');
}
```

#### ✅ Correct

```php
public function up(): void
{
    Schema::create('tenants', function (Blueprint $table) {
        // ...
    });
}

public function down(): void
{
    Schema::dropIfExists('tenants');
}
```

**Why:** Return type declarations make the code more explicit, catch return type errors at compile time, and improve IDE autocomplete.

**Common return types:**
- `void` - For methods that don't return anything
- `bool` - For methods returning true/false
- `int`, `float`, `string`, `array` - For scalar types
- Class names - For methods returning objects (e.g., `User`, `Builder`)
- `?Type` - For nullable returns (e.g., `?User`)

---

## PHPDoc Documentation

### 4. Return Type Documentation

**✅ REQUIRED:** All public and protected methods MUST have PHPDoc blocks with `@return` annotations.

#### ❌ Incorrect

```php
/**
 * Get human-readable label for the status
 */
public function label(): string
{
    return match ($this) {
        self::ACTIVE => 'Active',
        self::SUSPENDED => 'Suspended',
        self::ARCHIVED => 'Archived',
    };
}
```

#### ✅ Correct

```php
/**
 * Get human-readable label for the status
 *
 * @return string
 */
public function label(): string
{
    return match ($this) {
        self::ACTIVE => 'Active',
        self::SUSPENDED => 'Suspended',
        self::ARCHIVED => 'Archived',
    };
}
```

**Why:** PHPDoc annotations provide additional context for documentation generators and IDE tooltips.

### 5. Complete PHPDoc Structure

**✅ RECOMMENDED:** Include all relevant PHPDoc tags for better documentation.

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
{
    // Implementation
}
```

### 6. Use Imported Class Names in PHPDoc

**✅ REQUIRED:** When a class is imported at the top of the file with a `use` statement, PHPDoc blocks MUST use the short class name instead of the fully qualified class name (FQCN).

#### ❌ Incorrect

```php
<?php

declare(strict_types=1);

namespace App\Domains\Core\Listeners;

use App\Domains\Core\Events\TenantCreatedEvent;
use App\Domains\Core\Models\Tenant;

class InitializeTenantDataListener
{
    /**
     * Create default roles for the tenant
     *
     * @param  \App\Domains\Core\Models\Tenant  $tenant  // ❌ Don't use FQCN when class is imported
     */
    protected function createDefaultRoles(\App\Domains\Core\Models\Tenant $tenant): void
    {
        // Implementation
    }
}
```

#### ✅ Correct

```php
<?php

declare(strict_types=1);

namespace App\Domains\Core\Listeners;

use App\Domains\Core\Events\TenantCreatedEvent;
use App\Domains\Core\Models\Tenant;

class InitializeTenantDataListener
{
    /**
     * Create default roles for the tenant
     *
     * @param  Tenant  $tenant  // ✅ Use short name when class is imported
     */
    protected function createDefaultRoles(Tenant $tenant): void
    {
        // Implementation
    }
}
```

**Why:** 
- **Consistency:** The PHPDoc should match the method signature, which uses the imported class name
- **Maintainability:** If you change the import (e.g., aliasing), you only update it in one place
- **Readability:** Shorter, cleaner documentation that's easier to read
- **IDE Support:** Better autocomplete and navigation when class names are consistent
- **Laravel/PHP Best Practices:** Following PSR standards and Laravel conventions

**Key Points:**
- Import the class at the top: `use App\Domains\Core\Models\Tenant;`
- Use short name in PHPDoc: `@param Tenant $tenant`
- Use short name in method signature: `function method(Tenant $tenant)`
- Only use FQCN when the class is NOT imported (rare cases, generally avoid)

**When to use FQCN:**
- When you need to reference a class without importing it (to avoid naming conflicts)
- In rare documentation-only scenarios where the class isn't used in the signature
- Generally, prefer importing the class instead

---

## Testing Standards

### 7. Pest Testing Framework (MANDATORY)

**✅ REQUIRED:** ALL tests MUST use Pest v4+ syntax. PHPUnit class-based tests are NOT allowed.

#### ❌ Incorrect (PHPUnit Class-Based)

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctumAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_create_token(): void
    {
        $token = $this->user->createToken('test-token');
        
        $this->assertNotNull($token->plainTextToken);
    }
}
```

#### ✅ Correct (Pest Syntax)

```php
<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can create token', function () {
    $token = $this->user->createToken('test-token');
    
    expect($token->plainTextToken)->not->toBeNull();
});
```

**Why:** Pest provides a more expressive, readable syntax that reduces boilerplate code and makes tests easier to write and understand. It's the official testing framework mandated for this project.

**Key Pest Syntax Elements:**
- Use `test()` function instead of `public function test_*()`
- Use `expect()` assertions instead of `$this->assert*()`
- Use `beforeEach()` instead of `setUp()`
- Use `uses()` to apply traits like `RefreshDatabase`
- No need for class definitions or namespace declarations

**Running Tests:**
```bash
./vendor/bin/pest                              # All tests
./vendor/bin/pest tests/Feature/               # Feature tests only
./vendor/bin/pest tests/Unit/                  # Unit tests only
./vendor/bin/pest --parallel                   # Parallel execution
./vendor/bin/pest --coverage                   # With coverage
```

**Common Pest Assertions:**
```php
// Equality
expect($value)->toBe(10);
expect($value)->toEqual($expected);
expect($value)->not->toBe(20);

// Types
expect($value)->toBeInt();
expect($value)->toBeString();
expect($value)->toBeArray();
expect($value)->toBeNull();
expect($value)->not->toBeNull();

// Collections
expect($array)->toHaveCount(5);
expect($array)->toContain('item');
expect($array)->toHaveKey('key');

// Booleans
expect($condition)->toBeTrue();
expect($condition)->toBeFalse();

// Strings
expect($string)->toContain('substring');
expect($string)->toStartWith('prefix');
expect($string)->toEndWith('suffix');
```

---

## Migration Standards

### 8. Migration Class Format

**✅ REQUIRED:** Use anonymous migration classes with `return new class extends Migration`.

#### ❌ Incorrect

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    public function up()
    {
        Schema::create('tenants', function (Blueprint $table) {
            // ...
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenants');
    }
}
```

#### ✅ Correct

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            // ...
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
```

**Why:** Anonymous migrations prevent class name conflicts, follow Laravel 12+ conventions, and are consistent with the framework's modern approach.

**Key differences:**
1. Use `return new class extends Migration` instead of named class
2. End with semicolon after closing brace (`;`)
3. Include `declare(strict_types=1);`
4. Declare return types as `: void`

---

## Common Mistakes and How to Avoid Them

### Mistake 1: Missing Strict Type Declaration

**Problem:** Files without `declare(strict_types=1);` allow implicit type conversions that can lead to bugs.

**Solution:** Always add `declare(strict_types=1);` after the opening PHP tag in every PHP file.

**Pre-commit check:**
```bash
# Check for missing strict types declarations
grep -L "declare(strict_types=1)" app/**/*.php database/**/*.php
```

### Mistake 2: Untyped Method Parameters

**Problem:** Methods without parameter type hints can accept any type, leading to runtime errors.

**Solution:** Always specify parameter types. Use IDE features or static analysis tools to identify missing type hints.

**Example of fixing:**
```php
// Before
public function process($data) { }

// After
public function process(array $data): void { }
```

### Mistake 3: Missing Return Type Declarations

**Problem:** Methods without return types can return unexpected values, causing hard-to-debug issues.

**Solution:** Always declare return types. Use `void` for methods that don't return anything.

**Example of fixing:**
```php
// Before
public function save($model) {
    return $model->save();
}

// After
public function save(Model $model): bool {
    return $model->save();
}
```

### Mistake 4: Inconsistent Class Names in PHPDoc

**Problem:** Using fully qualified class names (FQCN) in PHPDoc when the class is already imported leads to inconsistency between documentation and code, making it harder to maintain.

**Solution:** Always use the imported short class name in PHPDoc blocks when the class has a `use` statement at the top of the file.

**Example of fixing:**
```php
// Before - Inconsistent (FQCN in PHPDoc, short name in signature)
use App\Domains\Core\Models\Tenant;

/**
 * @param  \App\Domains\Core\Models\Tenant  $tenant  // ❌ FQCN doesn't match signature
 */
protected function createDefaultRoles(\App\Domains\Core\Models\Tenant $tenant): void
{
    // Implementation
}

// After - Consistent (short name everywhere)
use App\Domains\Core\Models\Tenant;

/**
 * @param  Tenant  $tenant  // ✅ Matches the import and signature
 */
protected function createDefaultRoles(Tenant $tenant): void
{
    // Implementation
}
```

**Benefits of consistency:**
- PHPDoc matches method signature
- Easier refactoring (change import once)
- Better IDE support and autocomplete
- Cleaner, more readable documentation
- Follows Laravel and PSR conventions

**Detection:**
- Code review tools will flag FQCN usage when class is imported
- IDE warnings may indicate redundant FQCN
- Laravel Pint may not catch this, so manual review is important

### Mistake 5: Incomplete PHPDoc Blocks

**Problem:** Missing `@return` or other PHPDoc tags reduce code documentation quality.

**Solution:** Write complete PHPDoc blocks for all public and protected methods.

**Template:**
```php
/**
 * Brief description of what the method does
 *
 * Longer description if needed, explaining complex logic,
 * business rules, or important considerations.
 *
 * @param Type $param Description of parameter
 * @return Type Description of return value
 * @throws ExceptionType When this exception is thrown
 */
```

### Mistake 6: Using Named Migration Classes

**Problem:** Named migration classes can cause conflicts and don't follow Laravel 12+ conventions.

**Solution:** Always use anonymous migrations with `return new class extends Migration`.

**Converting old migrations:**
```php
// Before
class CreateUsersTable extends Migration { }

// After
return new class extends Migration { };
```

### Mistake 7: Migration Ordering Issues with Foreign Keys

**Problem:** Creating a table with foreign key constraints before the referenced table exists causes migration failures.

**❌ Incorrect Order:**
```php
// File: 0001_01_01_000000_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id')->nullable();
    
    // ❌ This will fail because 'tenants' table doesn't exist yet
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->onDelete('cascade');
});

// File: 2025_11_09_023509_create_tenants_table.php (runs AFTER users)
Schema::create('tenants', function (Blueprint $table) {
    $table->uuid('id')->primary();
    // ...
});
```

**✅ Correct Order:**

Parent tables must be created before child tables that reference them.

**Option 1: Rename migrations to control order**
```php
// File: 0001_01_01_000000_create_tenants_table.php (runs FIRST)
Schema::create('tenants', function (Blueprint $table) {
    $table->uuid('id')->primary();
    // ...
});

// File: 0001_01_01_000003_create_users_table.php (runs AFTER tenants)
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id')->nullable();
    
    // ✅ Now 'tenants' table exists
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->onDelete('cascade');
});
```

**Option 2: Separate foreign key into its own migration**
```php
// File: 0001_01_01_000000_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id')->nullable(); // Column only, no FK yet
    // ...
});

// File: 0001_01_01_000001_create_tenants_table.php
Schema::create('tenants', function (Blueprint $table) {
    $table->uuid('id')->primary();
    // ...
});

// File: 0001_01_01_000002_add_tenant_foreign_key_to_users.php
Schema::table('users', function (Blueprint $table) {
    // ✅ Add FK after both tables exist
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->onDelete('cascade');
});
```

**Why:** Migration files run in alphanumeric order. Foreign key constraints require the referenced table to exist first, otherwise the migration will fail with a constraint error.

**Best Practice:** 
- Create parent/reference tables (tenants, roles, etc.) before child tables (users, posts, etc.)
- Use timestamp prefixes like `0001_01_01_000000` for base tables
- Number dependent tables sequentially: `000001`, `000002`, `000003`
- Test migrations on a fresh database: `php artisan migrate:fresh`

### Mistake 8: Race Conditions in Increment Operations

**Problem:** Using `$model->increment()` followed by checking `$model->attribute` uses stale data, causing logic errors.

**❌ Incorrect:**
```php
public function incrementFailedLoginAttempts(): void
{
    $this->increment('failed_login_attempts');
    
    // ❌ $this->failed_login_attempts still has OLD value from before increment
    if ($this->failed_login_attempts >= 5) {
        $this->locked_until = now()->addMinutes(30);
        $this->save();
    }
}
```

**Why it's wrong:**
1. `increment()` updates the database directly via SQL: `UPDATE users SET failed_login_attempts = failed_login_attempts + 1`
2. The model's in-memory attribute is NOT automatically updated
3. Checking `$this->failed_login_attempts` uses the OLD value
4. Account lockout won't trigger until the 6th attempt instead of 5th

**✅ Correct Option 1: Increment in-memory then save**
```php
public function incrementFailedLoginAttempts(): void
{
    $this->failed_login_attempts++;
    
    // Lock account after 5 failed attempts
    if ($this->failed_login_attempts >= 5) {
        $this->locked_until = now()->addMinutes(30);
    }
    
    $this->save(); // Single save with all changes
}
```

**✅ Correct Option 2: Refresh after increment**
```php
public function incrementFailedLoginAttempts(): void
{
    $this->increment('failed_login_attempts');
    $this->refresh(); // ✅ Reload from database to get updated value
    
    // Now $this->failed_login_attempts has the correct value
    if ($this->failed_login_attempts >= 5) {
        $this->locked_until = now()->addMinutes(30);
        $this->save();
    }
}
```

**When to use each approach:**
- **Option 1 (in-memory)**: Best for most cases - cleaner, single DB query
- **Option 2 (refresh)**: Use when other processes might modify the record concurrently

**Similar issues to avoid:**
- Using `decrement()` then checking the value
- Using `update()` then accessing updated attributes
- Any direct SQL modification followed by attribute access

### Mistake 9: Not Using Traits for Shared Functionality

**Problem:** Manually implementing functionality that already exists in a trait causes code duplication and missing features.

**❌ Incorrect:**
```php
use App\Domains\Core\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    // Manually defining tenant relationship
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    // Missing:
    // - Automatic tenant_id setting on creation
    // - Global scope for tenant filtering
    // - withoutTenantScope() helper
    // - withAllTenants() helper
}
```

**✅ Correct:**
```php
use App\Domains\Core\Traits\BelongsToTenant;

class User extends Authenticatable
{
    use BelongsToTenant; // ✅ Gets all tenant functionality automatically
    
    // The trait provides:
    // - tenant() relationship
    // - Automatic tenant_id on creation
    // - Global scope for filtering
    // - withoutTenantScope() method
    // - withAllTenants() method
    // - getCurrentTenantIdForModel() helper
}
```

**Benefits of using traits:**
- **DRY Principle**: Don't repeat yourself - write once, use everywhere
- **Consistency**: Same behavior across all models
- **Maintainability**: Fix bugs in one place
- **Features**: Get helper methods and automatic behaviors
- **Testing**: Trait tested once, confidence in all models

**Common Laravel traits to use:**
- `BelongsToTenant` - Multi-tenancy functionality (project-specific)
- `SoftDeletes` - Soft delete functionality
- `HasFactory` - Factory support for testing
- `HasUuids` - UUID primary keys
- `LogsActivity` - Audit logging (Spatie package)
- `HasRoles` - Role-based permissions (Spatie package)

**Before implementing manually, check if:**
1. A trait exists in the project (`app/Domains/Core/Traits/`)
2. A Laravel built-in trait exists (`Illuminate\Database\Eloquent\`)
3. A package trait exists (Spatie, etc.)

### Mistake 10: Incomplete PHPDoc for Factory State Methods

**Problem:** Factory state methods lack parameter and return type documentation, making them harder to use and understand.

**❌ Incorrect:**
```php
/**
 * Indicate that the user has failed login attempts.
 */
public function withFailedAttempts(int $attempts = 3): static
{
    return $this->state(fn (array $attributes) => [
        'failed_login_attempts' => $attempts,
    ]);
}
```

**✅ Correct:**
```php
/**
 * Indicate that the user has failed login attempts.
 *
 * @param int $attempts Number of failed login attempts
 * @return static
 */
public function withFailedAttempts(int $attempts = 3): static
{
    return $this->state(fn (array $attributes) => [
        'failed_login_attempts' => $attempts,
    ]);
}
```

**Why:**
- IDEs show parameter descriptions in autocomplete
- Documentation generators create better API docs
- Other developers understand parameters without reading code
- Follows Laravel and PHPStan standards

**Factory state method documentation template:**
```php
/**
 * Brief description of what state this creates
 *
 * @param Type $param Description of parameter (if any)
 * @return static
 */
public function stateName($param = default): static
```

---

## Code Review Checklist

Before submitting code for review, ensure:

### Type Safety & Documentation
- [ ] All PHP files have `declare(strict_types=1);`
- [ ] All method parameters have type hints
- [ ] All methods declare return types
- [ ] All public/protected methods have PHPDoc blocks with `@return` tags
- [ ] Factory state methods have `@param` and `@return` documentation
- [ ] PHPDoc uses imported class names, not FQCNs

### Architecture & Design
- [ ] Using traits instead of manual implementation (e.g., `BelongsToTenant`)
- [ ] No race conditions in increment/decrement operations
- [ ] Models use appropriate traits: `HasUuids`, `SoftDeletes`, `LogsActivity`, etc.

### Database & Migrations
- [ ] All migrations use anonymous class format
- [ ] Migration order is correct (parent tables before child tables with FKs)
- [ ] Foreign key constraints reference existing tables
- [ ] Tested with `php artisan migrate:fresh` on clean database

### Code Quality
- [ ] Code passes Laravel Pint formatting (`./vendor/bin/pint`)
- [ ] All tests pass (`php artisan test`)
- [ ] No untyped variables or parameters remain
- [ ] No code duplication - using traits where applicable

---

## Automated Tools

### Laravel Pint

Run Laravel Pint to automatically fix code style issues:

```bash
./vendor/bin/pint
```

Pint will automatically format code according to Laravel's coding standards and PSR-12.

### PHPStan (Recommended)

Install and run PHPStan for static analysis:

```bash
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse app database
```

PHPStan will catch type errors, missing return types, and other issues before runtime.

---

## IDE Configuration

### PHPStorm

1. Enable strict type inspections:
   - Settings → Editor → Inspections → PHP → Type Compatibility
   - Enable "Missing @return tag" inspection
   - Enable "Missing parameter type declaration" inspection

2. Configure code style:
   - Settings → Editor → Code Style → PHP
   - Set from: Laravel (built-in)
   - Enable "Strict types declaration" in PHP inspections

### VS Code

1. Install PHP Intelephense extension
2. Add to `settings.json`:
```json
{
    "intelephense.diagnostics.strictTypes": true,
    "intelephense.diagnostics.typeErrors": true
}
```

---

## Additional Resources

- [PSR-12: Extended Coding Style](https://www.php-fig.org/psr/psr-12/)
- [Laravel Documentation](https://laravel.com/docs)
- [PHP 8.2+ Type System](https://www.php.net/manual/en/language.types.declarations.php)
- [PHPDoc Standards](https://docs.phpdoc.org/guide/getting-started/your-first-set-of-documentation.html)

---

## Architecture Patterns

### 8. Repository Pattern

**✅ REQUIRED:** All data access operations MUST go through repository classes that implement repository contracts.

#### ❌ Incorrect

```php
class TenantManager
{
    public function create(array $data): Tenant
    {
        // Direct model access violates repository pattern
        return Tenant::create($data);
    }
}
```

#### ✅ Correct

```php
class TenantManager
{
    public function __construct(
        protected readonly TenantRepositoryContract $tenantRepository
    ) {}
    
    public function create(array $data): Tenant
    {
        // Use repository for data access
        return $this->tenantRepository->create($data);
    }
}
```

**Why:** The repository pattern abstracts data access logic, making code more testable, maintainable, and allows for easier database implementation changes.

**Steps to implement:**
1. Create a contract interface in `app/Domains/{Domain}/Contracts/`
2. Create a repository implementation in `app/Domains/{Domain}/Repositories/`
3. Bind the contract to implementation in a service provider
4. Inject the contract into services that need data access

---

## Package Decoupling

### 9. Design for Decoupling (Package-as-a-Service)

**✅ REQUIRED:** All external package dependencies MUST be abstracted behind contracts to enable easy replacement, testing, and prevent vendor lock-in.

#### Core Principle

Never directly depend on external package implementations in business logic. Always wrap external packages behind contracts/interfaces that we control.

#### ❌ Incorrect

```php
// Direct package dependency in business logic
use Spatie\Activitylog\Traits\LogsActivity;
use Laravel\Scout\Searchable;

class Tenant extends Model
{
    use LogsActivity, Searchable; // Direct package coupling
}

// Direct package usage in service
class TenantManager
{
    public function create(array $data): Tenant
    {
        $tenant = $this->repository->create($data);
        
        // Direct Spatie API usage
        activity()
            ->performedOn($tenant)
            ->causedBy(auth()->user())
            ->log('Tenant created');
        
        return $tenant;
    }
}
```

#### ✅ Correct

```php
// Our contract
interface ActivityLoggerContract
{
    public function log(string $description, Model $subject, ?Model $causer = null): void;
    public function getActivities(Model $subject): Collection;
}

// Package adapter (isolated)
class SpatieActivityLogger implements ActivityLoggerContract
{
    public function log(string $description, Model $subject, ?Model $causer = null): void
    {
        activity()
            ->performedOn($subject)
            ->causedBy($causer ?? auth()->user())
            ->log($description);
    }
}

// Business code uses our contract
class TenantManager
{
    public function __construct(
        private readonly TenantRepositoryContract $repository,
        private readonly ActivityLoggerContract $activityLogger
    ) {}
    
    public function create(array $data): Tenant
    {
        $tenant = $this->repository->create($data);
        
        // Use our contract, not package directly
        $this->activityLogger->log('Tenant created', $tenant);
        
        return $tenant;
    }
}

// Service provider binding
class LoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ActivityLoggerContract::class,
            SpatieActivityLogger::class
        );
    }
}
```

**Why:** This approach provides:
- **Swappability:** Replace packages without changing business logic
- **Testability:** Mock contracts easily without package-specific mocks
- **Maintainability:** Isolate package-specific code to adapters
- **Vendor Lock-in Prevention:** Not tied to specific package APIs

**Critical Packages to Decouple:**
1. **spatie/laravel-activitylog** - Activity logging (HIGH priority)
2. **laravel/scout** - Search functionality (HIGH priority)
3. **laravel/sanctum** - API authentication (HIGH priority)
4. **spatie/laravel-permission** - Authorization (MEDIUM priority)

**Implementation Pattern:**
1. Create contract in `app/Support/Contracts/{ServiceName}Contract.php`
   - **Note:** `{ServiceName}` should be a descriptive name representing the service functionality (e.g., `ActivityLogger`, `SearchService`, `TokenService`), not the literal package name.
   - **Example:** For activity logging, use `ActivityLoggerContract.php` (not `ActivitylogContract.php`).
2. Create adapter in `app/Support/Services/{Category}/{ServiceName}Service.php`
3. Bind in appropriate service provider
4. Update business code to inject contract
5. Add tests with mocked contract

**See:** [docs/architecture/PACKAGE-DECOUPLING-STRATEGY.md](docs/architecture/PACKAGE-DECOUPLING-STRATEGY.md) for comprehensive decoupling guide.

### 9a. Using Wrapper Traits in Models

**✅ REQUIRED:** Models MUST use our wrapper traits instead of direct package traits to maintain decoupling.

#### Available Wrapper Traits

**1. HasActivityLogging** - Activity logging with Spatie Activitylog

```php
<?php

declare(strict_types=1);

namespace App\Domains\YourDomain\Models;

use App\Support\Traits\HasActivityLogging;
use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    use HasActivityLogging;
    
    /**
     * Configure activity logging for this model
     *
     * @return array<string, mixed>
     */
    protected function configureActivityLogging(): array
    {
        return [
            'log_name' => 'your_model',  // Custom log name
            'log_attributes' => [          // Specific attributes to log
                'name',
                'status',
                'important_field',
            ],
            'log_only_dirty' => true,      // Only log changed attributes
            'dont_submit_empty_logs' => true, // Skip empty logs
        ];
    }
}
```

**Configuration Options:**
- `log_name`: Custom log name (default: table name)
- `log_attributes`: Array of attributes to log
- `log_all`: Log all attributes (default: false)
- `log_only_dirty`: Only log changed attributes (default: false)
- `dont_submit_empty_logs`: Skip empty logs (default: false)

**Helper Methods:**
- `getActivityLogs()`: Get all activities for this model
- `getLatestActivity()`: Get the most recent activity

**2. IsSearchable** - Search functionality with Laravel Scout

```php
<?php

declare(strict_types=1);

namespace App\Domains\YourDomain\Models;

use App\Support\Traits\IsSearchable;
use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    use IsSearchable;
    
    /**
     * Configure search behavior for this model
     *
     * @return array<string, mixed>
     */
    protected function configureSearchable(): array
    {
        return [
            'index_name' => 'custom_index',  // Custom search index name
            'searchable_fields' => [          // Fields to include in search
                'id',
                'name',
                'description',
                'status',
                'tenant_id',  // Always include for multi-tenancy
            ],
        ];
    }
}
```

**Configuration Options:**
- `index_name`: Custom search index name (default: table name)
- `searchable_fields`: Array of fields to include in search index (default: all fields)

**Automatic Features:**
- Tenant isolation: `tenant_id` is automatically included if present
- The trait overrides `toSearchableArray()` and `searchableAs()` methods

**3. HasTokens** - API token management with Laravel Sanctum

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Traits\HasTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasTokens;
    
    /**
     * Configure token behavior for this model
     *
     * @return array<string, mixed>
     */
    protected function configureTokens(): array
    {
        return [
            'default_abilities' => ['read', 'write'],  // Default token abilities
            'token_prefix' => 'api',                    // Token name prefix
        ];
    }
}
```

**Configuration Options:**
- `default_abilities`: Array of default abilities for new tokens (default: ['*'])
- `token_prefix`: String prefix for token names (default: none)

**Helper Methods:**
- `createApiToken($name, $abilities = ['*'])`: Create a new token
- `revokeApiToken($tokenId)`: Revoke a specific token
- `revokeAllApiTokens()`: Revoke all user tokens
- `getActiveTokens()`: Get all active tokens
- `currentTokenHasAbility($ability)`: Check current token ability

#### Complete Model Example

```php
<?php

declare(strict_types=1);

namespace App\Domains\Core\Models;

use App\Domains\Core\Enums\TenantStatus;
use App\Domains\Core\Traits\BelongsToTenant;
use App\Support\Traits\HasActivityLogging;
use App\Support\Traits\IsSearchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use BelongsToTenant, HasActivityLogging, IsSearchable, SoftDeletes;
    
    protected $fillable = [
        'name',
        'domain',
        'status',
        'configuration',
    ];
    
    protected $casts = [
        'status' => TenantStatus::class,
        'configuration' => 'encrypted:array',
    ];
    
    /**
     * Configure activity logging
     */
    protected function configureActivityLogging(): array
    {
        return [
            'log_name' => 'tenants',
            'log_attributes' => ['name', 'domain', 'status'],
            'log_only_dirty' => true,
            'dont_submit_empty_logs' => true,
        ];
    }
    
    /**
     * Configure search behavior
     */
    protected function configureSearchable(): array
    {
        return [
            'index_name' => 'tenants',
            'searchable_fields' => ['id', 'name', 'domain', 'status'],
        ];
    }
}
```

#### Using Contracts in Services

**❌ Incorrect - Direct package usage:**

```php
class YourService
{
    public function doSomething(Model $model): void
    {
        // Direct Scout usage
        $model->searchable();
        
        // Direct Spatie usage
        activity()->log('Something happened');
    }
}
```

**✅ Correct - Use contracts:**

```php
class YourService
{
    public function __construct(
        private readonly ActivityLoggerContract $activityLogger,
        private readonly SearchServiceContract $searchService,
    ) {}
    
    public function doSomething(Model $model): void
    {
        // Use search service contract
        $this->searchService->index($model);
        
        // Use activity logger contract
        $this->activityLogger->log('Something happened', $model);
    }
}
```

**Testing with Mocked Contracts:**

```php
use App\Support\Contracts\ActivityLoggerContract;
use App\Support\Contracts\SearchServiceContract;

test('service performs actions correctly', function () {
    // Mock the contracts
    $mockLogger = Mockery::mock(ActivityLoggerContract::class);
    $mockSearch = Mockery::mock(SearchServiceContract::class);
    
    $mockLogger->shouldReceive('log')->once();
    $mockSearch->shouldReceive('index')->once();
    
    // Bind mocks
    $this->app->instance(ActivityLoggerContract::class, $mockLogger);
    $this->app->instance(SearchServiceContract::class, $mockSearch);
    
    // Test your service
    $service = app(YourService::class);
    $service->doSomething($model);
});
```

**Benefits of This Approach:**
1. **Easy Testing**: Mock contracts without package-specific knowledge
2. **Swappable**: Replace Scout with Meilisearch without changing business code
3. **Maintainable**: Package upgrades isolated to adapters
4. **Consistent**: Same interface across different implementations

---

## Security Best Practices

### 10. Authentication and Authorization

**✅ REQUIRED:** All operations that require authentication MUST check for authenticated users. All privileged operations MUST check authorization.

#### ❌ Incorrect

```php
public function impersonate(Tenant $tenant, string $reason): void
{
    // No authentication or authorization check
    activity()
        ->causedBy(auth()->user()) // Will fail if not authenticated
        ->log('Impersonation started');
}
```

#### ✅ Correct

```php
public function impersonate(Tenant $tenant, string $reason): void
{
    // Check authentication
    if (! auth()->check()) {
        throw new \RuntimeException('Impersonation requires an authenticated user');
    }
    
    // Check authorization
    if (! auth()->user()->can('impersonate-tenant', $tenant)) {
        throw new AuthorizationException('Unauthorized to impersonate this tenant');
    }
    
    // Now safe to use auth()->user()
    activity()
        ->causedBy(auth()->user())
        ->log('Impersonation started');
}
```

**Why:** Prevents runtime errors and security vulnerabilities by ensuring proper authentication and authorization checks.

**Common scenarios requiring checks:**
- Impersonation and privileged operations
- Audit logging with user context
- Operations that modify data on behalf of a user
- Support and administrative functions

---

---

### 9a. Gate Closures Must Have Type Hints

**✅ REQUIRED:** All Gate closure parameters and return types MUST be explicitly declared.

#### ❌ Incorrect

```php
// In AuthServiceProvider.php
Gate::define('impersonate-tenant', function ($user, $tenant) {
    return $user->isAdmin();
});
```

#### ✅ Correct

```php
// In AuthServiceProvider.php
use App\Domains\Core\Models\Tenant;
use App\Models\User;

Gate::define('impersonate-tenant', function (User $user, Tenant $tenant): bool {
    return $user->isAdmin();
});
```

**Why:** 
- Type hints provide compile-time type checking and prevent runtime errors
- Makes the gate's expected parameters explicit and self-documenting
- Enables better IDE support with autocomplete and error detection
- Aligns with the project's strict typing standards (PHP 8.2+)

**Pattern to Follow:**

```php
use Illuminate\Support\Facades\Gate;

// In your AuthServiceProvider boot() method:
Gate::define('gate-name', function (User $user, ModelClass $model): bool {
    // Authorization logic
    return $user->hasPermission('permission-name');
});

// For gates without additional parameters:
Gate::define('gate-name', function (User $user): bool {
    return $user->isAdmin();
});

// For nullable parameters:
Gate::define('gate-name', function (?User $user, ModelClass $model): bool {
    if (!$user) {
        return false;
    }
    return $user->can('action', $model);
});
```

**Note:** Always import the model classes at the top of your service provider file to use them in type hints.


### 10. Defensive Programming with Authentication

**✅ REQUIRED:** When using `auth()->user()` in methods that can be called without authentication, use defensive checks or conditional assignment.

#### ❌ Incorrect

```php
public function create(array $data): Tenant
{
    $tenant = Tenant::create($data);
    
    // Will fail if called from CLI, queue, or seeder
    activity()
        ->performedOn($tenant)
        ->causedBy(auth()->user())
        ->log('Tenant created');
        
    return $tenant;
}
```

#### ✅ Correct

```php
public function create(array $data): Tenant
{
    $tenant = $this->repository->create($data);
    
    // Defensive approach: check authentication first
    $activity = activity()->performedOn($tenant);
    
    if (auth()->check()) {
        $activity->causedBy(auth()->user());
    }
    
    $activity->log('Tenant created');
    
    return $tenant;
}
```

**Alternative approach for optional fields:**

```php
$context = [
    'action' => 'impersonation',
    'started_at' => now(),
];

// Only add user_id if authenticated
if (auth()->check()) {
    $context['user_id'] = auth()->id();
}
```

**Why:** Allows methods to work in multiple contexts (web requests, CLI commands, queued jobs, seeders) without throwing exceptions.

---

## Validation Best Practices

### 11. Complete Validation Rules

**✅ REQUIRED:** All fields that can be provided by users MUST have validation rules, even if they have defaults or are optional.

#### ❌ Incorrect

```php
$validator = Validator::make($data, [
    'name' => ['required', 'string'],
    'email' => ['required', 'email'],
    // Missing 'status' field validation even though it's in fillable
]);

// Setting default without validation
$validatedData['status'] = $validatedData['status'] ?? TenantStatus::ACTIVE;
```

#### ✅ Correct

```php
$validator = Validator::make($data, [
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email', 'max:255'],
    'status' => ['nullable', 'string', Rule::in(TenantStatus::values())],
]);

// Now status is validated if provided
$validatedData['status'] = $validatedData['status'] ?? TenantStatus::ACTIVE;
```

**Why:** Prevents invalid data from bypassing validation and ensures data integrity at the application level.

**Best practices:**
- Validate all fillable fields
- Use `Rule::in()` for enum values
- Include `max` constraints for strings
- Use `nullable` for optional fields
- Validate arrays and nested data structures

---

## PHPDoc Standards

### 12. Accurate Exception Documentation

**✅ REQUIRED:** The `@throws` tag in PHPDoc MUST match the actual exceptions thrown by the method.

#### ❌ Incorrect

```php
/**
 * Create a new tenant
 * 
 * @throws \InvalidArgumentException If validation fails
 */
public function create(array $data): Tenant
{
    $validator = Validator::make($data, [...]);
    
    if ($validator->fails()) {
        // Actually throws ValidationException, not InvalidArgumentException
        throw new ValidationException($validator);
    }
}
```

#### ✅ Correct

```php
/**
 * Create a new tenant
 * 
 * @throws \Illuminate\Validation\ValidationException If validation fails
 */
public function create(array $data): Tenant
{
    $validator = Validator::make($data, [...]);
    
    if ($validator->fails()) {
        throw new ValidationException($validator);
    }
}
```

**Why:** Accurate documentation helps developers understand error handling and use try-catch blocks correctly.

---

## Testing Best Practices

### 13. Performance Testing

**✅ RECOMMENDED:** When testing performance constraints, test individual operations rather than averages.

#### ❌ Incorrect

```php
public function test_performance(): void
{
    $start = microtime(true);
    
    for ($i = 0; $i < 100; $i++) {
        $this->service->operation();
    }
    
    $avgTime = ((microtime(true) - $start) * 1000) / 100;
    
    // Testing average can miss slow individual calls
    $this->assertLessThan(10, $avgTime);
}
```

#### ✅ Correct

```php
public function test_performance(): void
{
    $maxAllowed = 10; // milliseconds
    
    for ($i = 0; $i < 100; $i++) {
        $start = microtime(true);
        $this->service->operation();
        $duration = (microtime(true) - $start) * 1000;
        
        // Test each individual call
        $this->assertLessThan(
            $maxAllowed,
            $duration,
            "Iteration {$i} took {$duration}ms, should be under {$maxAllowed}ms"
        );
    }
}
```

**Why:** Testing averages can hide slow individual operations. Testing each call ensures consistent performance.

**Note:** Performance tests in unit/integration suites can be unreliable due to system load. Consider dedicated performance test suites for critical paths.

---

## Common Mistakes and How to Avoid Them

### Mistake 7: Direct Model Access in Services

**Problem:** Services directly using `Model::create()` or `Model::find()` instead of repositories.

**Solution:** Always use repository pattern for data access.

```php
// Before
class TenantManager
{
    public function create(array $data): Tenant
    {
        return Tenant::create($data);
    }
}

// After
class TenantManager
{
    public function __construct(
        protected readonly TenantRepositoryContract $repository
    ) {}
    
    public function create(array $data): Tenant
    {
        return $this->repository->create($data);
    }
}
```

### Mistake 8: Missing Authentication Checks

**Problem:** Using `auth()->user()` without checking if user is authenticated.

**Solution:** Always check `auth()->check()` before accessing `auth()->user()`.

```php
// Before
$activity->causedBy(auth()->user()); // Fails if not authenticated

// After
if (auth()->check()) {
    $activity->causedBy(auth()->user());
}
```

### Mistake 9: Missing Authorization for Privileged Operations

**Problem:** Allowing any authenticated user to perform privileged operations like impersonation.

**Solution:** Add authorization checks using gates or policies.

```php
// Before
public function impersonate(Tenant $tenant): void
{
    // Anyone can impersonate!
}

// After
public function impersonate(Tenant $tenant): void
{
    if (! auth()->user()->can('impersonate-tenant', $tenant)) {
        throw new AuthorizationException('Unauthorized');
    }
}
```

### Mistake 10: Incomplete Validation Rules

**Problem:** Not validating all user-provided fields, especially optional ones.

**Solution:** Add validation for all fillable fields, using `nullable` for optional ones.

```php
// Before
'name' => ['required'],
// Missing status validation

// After
'name' => ['required', 'string', 'max:255'],
'status' => ['nullable', 'string', Rule::in(TenantStatus::values())],
```

### Mistake 11: Incorrect PHPDoc Exception Types

**Problem:** Documenting wrong exception types in `@throws` tags.

**Solution:** Match `@throws` documentation with actual thrown exceptions.

```php
// Before
/** @throws \InvalidArgumentException */
throw new ValidationException($validator);

// After
/** @throws \Illuminate\Validation\ValidationException */
throw new ValidationException($validator);
```

---

## Code Review Checklist

Before submitting code for review, ensure:

- [ ] All PHP files have `declare(strict_types=1);`
- [ ] All method parameters have type hints
- [ ] All methods declare return types
- [ ] All public/protected methods have PHPDoc blocks with `@return` tags
- [ ] All migrations use anonymous class format
- [ ] Data access uses repository pattern (no direct `Model::create()` or `Model::find()` in services)
- [ ] Authentication checks before using `auth()->user()`
- [ ] Authorization checks for privileged operations
- [ ] Complete validation rules for all fillable fields
- [ ] Accurate `@throws` tags in PHPDoc matching actual exceptions
- [ ] Code passes Laravel Pint formatting (`./vendor/bin/pint`)
- [ ] All tests pass (`php artisan test`)
- [ ] No untyped variables or parameters remain

---

## Middleware Best Practices

### 14. Avoid N+1 Queries in Middleware

**✅ REQUIRED:** Middleware should avoid triggering lazy loading that causes N+1 queries, especially for operations that run on every request.

#### ❌ Incorrect

```php
public function handle(Request $request, Closure $next): Response
{
    $user = auth()->user();
    
    // This triggers an N+1 query via lazy loading
    $tenant = $user->tenant;
    
    $this->tenantManager->setActive($tenant);
    return $next($request);
}
```

#### ✅ Correct

```php
public function handle(Request $request, Closure $next): Response
{
    $user = auth()->user();
    
    // Use direct query to avoid N+1
    $tenant = Tenant::find($user->tenant_id);
    
    $this->tenantManager->setActive($tenant);
    return $next($request);
}
```

**Why:** Middleware runs on every request. N+1 queries in middleware multiply across all requests, causing significant performance degradation. Always use direct queries or ensure relationships are eager loaded.

**Alternative approaches:**
1. Use `Tenant::find($user->tenant_id)` for direct lookup
2. Eager load on User model with `protected $with = ['tenant']` if needed globally
3. Use caching for frequently accessed tenant data

---

### 15. Middleware Ordering and Responsibilities

**✅ REQUIRED:** Middleware should not duplicate responsibilities already handled by other middleware. Document dependencies clearly.

#### ❌ Incorrect

```php
// IdentifyTenant middleware performing authentication
public function handle(Request $request, Closure $next): Response
{
    if (! auth()->check()) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
    // ... tenant resolution
}

// No comment about dependency on auth middleware
```

#### ✅ Correct

```php
// IdentifyTenant middleware with clear documentation
/**
 * This middleware should be applied after authentication middleware (e.g., auth:sanctum).
 */
public function handle(Request $request, Closure $next): Response
{
    // Note: This check is redundant if auth middleware is properly applied
    // but provides a safety net for misconfigured routes
    if (! auth()->check()) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
    // ... tenant resolution
}

// In bootstrap/app.php
$middleware->api(append: [
    IdentifyTenant::class, // Runs after auth:sanctum in API middleware stack
]);
```

**Why:** Clear middleware ordering prevents bugs and ensures each middleware has a single responsibility. Documentation helps future developers understand dependencies.

---

### 16. Complete PHPDoc for Middleware

**✅ REQUIRED:** Middleware PHPDoc must document all possible return conditions, especially error responses.

#### ❌ Incorrect

```php
/**
 * Handle an incoming request
 *
 * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
 */
public function handle(Request $request, Closure $next): Response
```

#### ✅ Correct

```php
/**
 * Handle an incoming request
 *
 * Resolves tenant from authenticated user and sets it in the TenantManager.
 * This middleware should be applied after authentication middleware (e.g., auth:sanctum).
 *
 * Returns error responses for different failure modes:
 * - 401 Unauthenticated: if no user is authenticated
 * - 403 Forbidden: if user has no tenant_id
 * - 404 Not Found: if tenant cannot be resolved from database
 *
 * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
 * @return Response
 */
public function handle(Request $request, Closure $next): Response
```

**Why:** Complete documentation helps developers understand all possible outcomes and error conditions.

---

## Testing Best Practices (Continued)

### 17. Unit vs Feature Test Classification

**✅ REQUIRED:** Unit tests should not depend on database or external systems. Tests using database should be feature/integration tests.

#### ❌ Incorrect

```php
// In tests/Unit/Support/Helpers/TenantHelperTest.php
class TenantHelperTest extends TestCase
{
    use RefreshDatabase; // Unit tests should not use database
    
    public function test_helper_returns_tenant(): void
    {
        $tenant = Tenant::factory()->create(); // Creating database records
        // ...
    }
}
```

#### ✅ Correct

Option 1: Move to feature tests
```php
// In tests/Feature/Support/Helpers/TenantHelperTest.php
class TenantHelperTest extends TestCase
{
    use RefreshDatabase; // OK for feature tests
    
    public function test_helper_returns_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        // ...
    }
}
```

Option 2: Use mocking for true unit tests
```php
// In tests/Unit/Support/Helpers/TenantHelperTest.php
class TenantHelperTest extends TestCase
{
    public function test_helper_returns_tenant(): void
    {
        $mockTenant = Mockery::mock(Tenant::class);
        $mockManager = Mockery::mock(TenantManagerContract::class);
        $mockManager->shouldReceive('current')->andReturn($mockTenant);
        // ...
    }
}
```

**Why:** Unit tests should be fast, isolated, and not depend on external state. Database dependencies make tests slower and more fragile.


---

### 17a. Policy Testing Best Practices

**✅ REQUIRED:** Policy tests should use `RefreshDatabase` trait and factory-created models to test against real Eloquent instances.

#### ❌ Incorrect

```php
// In tests/Unit/Domains/Core/Policies/TenantPolicyTest.php
class TenantPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new TenantPolicy;
        // Creating models without persisting - won't test actual behavior
        $this->adminUser = new User(['is_admin' => true]);
        $this->normalUser = new User(['is_admin' => false]);
        $this->tenant = new Tenant;
    }
}
```

#### ✅ Correct

```php
// In tests/Unit/Domains/Core/Policies/TenantPolicyTest.php
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantPolicyTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new TenantPolicy;
        // Use factories to create properly persisted models
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->normalUser = User::factory()->create(['is_admin' => false]);
        $this->tenant = Tenant::factory()->create();
    }
}
```

**Why:** 
- Policy tests verify authorization rules that often depend on model relationships and database state
- Using `RefreshDatabase` ensures test isolation and proper cleanup between tests
- Factory-created models are properly persisted with all attributes and relationships
- Testing with real Eloquent models catches issues that mocked objects might miss

**Note:** While unit tests typically avoid database dependencies, policy tests are an exception because:
1. Policies interact directly with Eloquent models
2. Authorization logic often depends on model relationships (e.g., `$user->tenant`)
3. Testing against real models ensures policies work correctly in production
4. Policy tests remain fast due to SQLite in-memory database

**Pattern to Follow:**

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class YourPolicyTest extends TestCase
{
    use RefreshDatabase;
    
    protected YourPolicy $policy;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new YourPolicy;
        // Create test fixtures using factories
        $this->user = User::factory()->create(['is_admin' => true]);
        $this->resource = YourModel::factory()->create();
    }
    
    public function test_policy_method(): void
    {
        $result = $this->policy->method($this->user, $this->resource);
        
        $this->assertTrue($result);
    }
}
```

---

### 18. Avoid Hard-Coded Values in Tests

**✅ REQUIRED:** Tests should avoid hard-coded values that might conflict with actual data or be environment-dependent.

#### ❌ Incorrect

```php
public function test_handles_missing_tenant(): void
{
    // Hard-coded ID might conflict with actual records
    $user = User::factory()->create(['tenant_id' => 99999]);
}
```

#### ✅ Correct

```php
public function test_handles_missing_tenant(): void
{
    // Generate guaranteed non-existent ID
    $nonExistentTenantId = Tenant::max('id') + 1;
    $user = User::factory()->create(['tenant_id' => $nonExistentTenantId]);
}
```

**Why:** Hard-coded values can cause test failures in different environments or with parallel test execution.

---

### 19. Performance Tests Should Be Flexible

**❌ AVOID:** Hard-coded time limits in performance tests that run in CI environments.

```php
public function test_performance(): void
{
    $maxAllowedMs = 10; // Too strict for CI
    
    for ($i = 0; $i < 100; $i++) {
        $start = microtime(true);
        $this->operation();
        $duration = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan($maxAllowedMs, $duration); // Will fail on slow CI
    }
}
```

**✅ RECOMMENDED:** Remove performance tests from unit/feature suites or make them configurable.

```php
// Option 1: Skip in CI
public function test_performance(): void
{
    if (env('CI')) {
        $this->markTestSkipped('Performance test skipped in CI environment');
    }
    // ... test code
}

// Option 2: Use environment-based thresholds
public function test_performance(): void
{
    $maxAllowedMs = env('PERF_THRESHOLD_MS', 50); // More lenient default
    // ... test code
}

// Option 3: Remove from regular test suite (best approach)
// Create dedicated performance benchmarking suite
```

**Why:** CI environments often have variable performance characteristics. Hard-coded time limits cause flaky tests that fail intermittently.

**Alternative:** Use dedicated performance testing tools like:
- Laravel Dusk for browser performance
- Apache JMeter for load testing
- Custom benchmark scripts for specific operations

---

## Documentation Standards (Continued)

### 20. Qualify Performance Claims

**✅ REQUIRED:** Performance metrics in documentation must be qualified with conditions and disclaimers.

#### ❌ Incorrect

```markdown
## Performance

- Middleware execution: < 10ms per request
- Helper function: < 5ms per call
```

#### ✅ Correct

```markdown
## Performance

- Middleware execution: typically < 10ms per request under normal load
- Helper function: typically < 5ms per call under normal load
- Single direct database query per request (Tenant::find())
- Performance may vary based on server resources and database latency
```

**Why:** Absolute performance claims are misleading. Performance varies by environment, load, and configuration.

---

## Common Mistakes and How to Avoid Them (Continued)

### Mistake 12: N+1 Queries in Middleware

**Problem:** Using lazy loading in middleware that runs on every request.

**Solution:** Use direct queries or eager loading.

```php
// Before
$tenant = $user->tenant; // N+1 query

// After
$tenant = Tenant::find($user->tenant_id); // Direct query
```

### Mistake 13: Performance Tests in Test Suites

**Problem:** Including time-sensitive performance tests in regular test suites.

**Solution:** Remove performance tests or make them optional/environment-aware.

```php
// Before
$this->assertLessThan(10, $duration); // Fails in CI

// After - Option 1: Remove the test
// After - Option 2: Skip in CI
if (env('CI')) {
    $this->markTestSkipped('Performance test skipped in CI');
}
```

### Mistake 14: Unit Tests with Database Dependencies

**Problem:** Placing tests that use database in Unit test directory.

**Solution:** Move to Feature tests or use mocking.

```php
// Before: tests/Unit/MyTest.php
use RefreshDatabase;

// After: tests/Feature/MyTest.php
use RefreshDatabase;
// OR use mocking to keep in Unit tests
```

### Mistake 15: Incomplete Middleware Documentation

**Problem:** Not documenting all error responses and dependencies in middleware PHPDoc.

**Solution:** Document all return conditions, error codes, and middleware dependencies.

```php
/**
 * Returns error responses for different failure modes:
 * - 401 Unauthenticated: if no user is authenticated
 * - 403 Forbidden: if user has no tenant_id
 * - 404 Not Found: if tenant cannot be resolved
 *
 * This middleware should be applied after authentication middleware.
 */
```

---

## PR Review Learnings: Package Decoupling Implementation

This section documents key learnings from the PR review process for the package decoupling implementation. These findings apply to contract-driven development and package abstraction patterns.

### 1. Import Statement Ordering (PSR-12 Compliance)

**Issue:** Import statements must be alphabetically ordered according to PSR-12 and Laravel Pint standards.

#### ❌ Incorrect
```php
use Illuminate\Support\ServiceProvider;
use App\Support\Contracts\SearchServiceContract;
use App\Support\Services\Search\ScoutSearchService;
```

#### ✅ Correct
```php
use App\Support\Contracts\SearchServiceContract;
use App\Support\Services\Search\ScoutSearchService;
use Illuminate\Support\ServiceProvider;
```

**Rule:** Always alphabetize imports. Laravel classes come after app classes alphabetically.

---

### 2. Union Types for Database IDs

**Issue:** Database IDs can be either `int` or `string` depending on the driver and configuration. Contracts should accept both types.

#### ❌ Incorrect
```php
public function revokeToken(User $user, string $tokenId): bool;
```

#### ✅ Correct
```php
public function revokeToken(User $user, int|string $tokenId): bool;
```

**Rule:** When accepting IDs from the database, always use `int|string` union type to support various database drivers and configurations.

---

### 3. Boolean Return Values Should Reflect Actual State

**Issue:** Methods returning boolean should accurately reflect whether an operation succeeded or had any effect.

#### ❌ Incorrect
```php
public function revokeAllTokens(User $user): bool
{
    $user->tokens()->delete();
    return true;  // Always returns true, even if no tokens were deleted
}
```

#### ✅ Correct
```php
public function revokeAllTokens(User $user): bool
{
    $deleted = $user->tokens()->delete();
    return $deleted > 0;  // Returns true only if tokens were deleted
}
```

**Rule:** Boolean returns should indicate actual state changes, not just successful execution.

---

### 4. Type Casting for Numeric Parameters

**Issue:** When accepting numeric parameters from arrays (like options or config), always cast to the expected type to prevent type errors.

#### ❌ Incorrect
```php
$perPage = $options['per_page'] ?? 15;
$page = $options['page'] ?? 1;
$paginated = $builder->paginate($perPage, ['*'], 'page', $page);
```

**Problem:** If `$options['page']` is passed as a string `"2"`, math operations will fail with `TypeError: Unsupported operand types: string - int`.

#### ✅ Correct
```php
$perPage = (int) ($options['per_page'] ?? 15);
$page = (int) ($options['page'] ?? 1);
$paginated = $builder->paginate($perPage, ['*'], 'page', $page);
```

**Rule:** Always cast numeric parameters from arrays to their expected types to prevent runtime errors.

---

### 5. Eloquent Attribute Checking

**Issue:** `hasAttribute()` method doesn't exist on Eloquent models. Use `array_key_exists()` on the `$attributes` array instead.

#### ❌ Incorrect
```php
if ($this->hasAttribute('tenant_id')) {
    $array['tenant_id'] = $this->tenant_id;
}
```

**Problem:** `hasAttribute()` is not a valid Eloquent method and will cause a fatal error.

#### ✅ Correct
```php
if (array_key_exists('tenant_id', $this->attributes)) {
    $array['tenant_id'] = $this->tenant_id;
}
```

**Alternative (checks if fillable or in casts):**
```php
if (in_array('tenant_id', $this->getFillable()) || array_key_exists('tenant_id', $this->getCasts())) {
    $array['tenant_id'] = $this->tenant_id;
}
```

**Rule:** Use `array_key_exists('attribute', $this->attributes)` to check if a model has a specific attribute.

---

### 6. Flexible Contract Parameters

**Issue:** Contracts should be flexible enough to support optional parameters that implementations may need, even if not all implementations use them.

#### Example: Adding Optional Log Name Parameter

When a test or use case needs to categorize activities by log name, the contract should support it:

```php
// Contract signature
public function log(
    string $description,
    Model $subject,
    ?Model $causer = null,
    array $properties = [],
    ?string $logName = null  // Optional parameter
): void;

// Implementation uses it
if ($logName !== null) {
    $activity->useLog($logName);
}
```

**Rule:** Design contracts to be extensible. Add optional parameters to support future use cases without breaking existing implementations.

---

### 7. Pagination Logic Should Check Multiple Keys

**Issue:** When implementing pagination, check for any pagination-related keys, not just one specific key.

#### ❌ Incorrect
```php
if (isset($options['paginate'])) {
    // Only triggers if 'paginate' key exists
}
```

**Problem:** Doesn't work if user passes `'page'` or `'per_page'` without `'paginate'` flag.

#### ✅ Correct
```php
if (isset($options['paginate']) || isset($options['page']) || isset($options['per_page'])) {
    $perPage = (int) ($options['per_page'] ?? 15);
    $page = (int) ($options['page'] ?? 1);
    // Apply pagination
}
```

**Rule:** Check for all relevant keys that indicate pagination intent, not just a single flag.

---

### 8. Test Isolation in Feature Tests

**Issue:** Feature tests may fail if they don't properly isolate data between test runs.

**False Positive Example:** Test expects 1 activity but finds 2 because previous tests left activities in the database.

**Solution Patterns:**

1. **Use RefreshDatabase trait** (already used):
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
```

2. **Use unique identifiers in tests**:
```php
$tenant = Tenant::factory()->create(['name' => 'Unique-' . Str::random(8)]);
```

3. **Clean up after tests if needed**:
```php
afterEach(function () {
    Activity::truncate();
});
```

**Rule:** Ensure tests don't depend on database state from other tests. Use factories, unique identifiers, and database refresh.

---

### Summary of Key Learnings

1. ✅ **Always alphabetize imports** (PSR-12)
2. ✅ **Use `int|string` for database IDs** in contracts
3. ✅ **Boolean returns should reflect actual state** (not just "no errors")
4. ✅ **Cast numeric parameters** from arrays to prevent type errors
5. ✅ **Use `array_key_exists()` for attribute checking** in Eloquent
6. ✅ **Design flexible contracts** with optional parameters
7. ✅ **Check multiple keys for pagination** logic
8. ✅ **Ensure proper test isolation** to avoid false positives

These patterns apply throughout the codebase, especially when implementing contracts, adapters, and service layers.

---

## Code Review Checklist (Updated)

Before submitting code for review, ensure:

### Type Safety & Documentation
- [ ] All PHP files have `declare(strict_types=1);`
- [ ] All method parameters have type hints
- [ ] All methods declare return types
- [ ] All public/protected methods have complete PHPDoc blocks with `@return` tags
- [ ] PHPDoc documents all error responses and conditions
- [ ] Factory state methods have `@param` and `@return` documentation
- [ ] PHPDoc uses imported class names, not FQCNs

### Modern Laravel Practices
- [ ] **Using attributes for service bindings** (#[Bind], #[Singleton], #[Scoped])
- [ ] **Using attributes for routes** (#[Get], #[Post], #[Resource], #[Middleware])
- [ ] **Using attributes for event listeners** (#[Listen])
- [ ] **Using attributes for scheduled commands** (#[Schedule])
- [ ] Service providers only used for complex/conditional bindings

### Architecture & Design
- [ ] Using traits instead of manual implementation (e.g., `BelongsToTenant`)
- [ ] No race conditions in increment/decrement operations
- [ ] Models use appropriate traits: `HasUuids`, `SoftDeletes`, `LogsActivity`, etc.
- [ ] Data access uses repository pattern (no direct `Model::create()` or `Model::find()` in services)

### Database & Migrations
- [ ] All migrations use anonymous class format
- [ ] Migration order is correct (parent tables before child tables with FKs)
- [ ] Foreign key constraints reference existing tables
- [ ] Tested with `php artisan migrate:fresh` on clean database

### Security & Validation
- [ ] Authentication checks before using `auth()->user()`
- [ ] Authorization checks for privileged operations
- [ ] Complete validation rules for all fillable fields
- [ ] Accurate `@throws` tags in PHPDoc matching actual exceptions
- [ ] **No N+1 queries in middleware or frequently-called code**
- [ ] **Middleware dependencies and ordering documented**

### Testing
- [ ] **Unit tests do not use database (or are in Feature directory)**
- [ ] **No hard-coded IDs or values in tests**
- [ ] **No performance tests with hard time limits in regular test suites**

### Code Quality
- [ ] **Import statements are alphabetically ordered** (PSR-12)
- [ ] **Database IDs use `int|string` union type in contracts**
- [ ] **Boolean returns reflect actual state changes**
- [ ] **Numeric parameters from arrays are cast to expected types**
- [ ] **Eloquent attribute checking uses `array_key_exists()` on `$attributes`**
- [ ] **Contracts designed with optional parameters for flexibility**
- [ ] **Performance claims in documentation are qualified**
- [ ] Code passes Laravel Pint formatting (`./vendor/bin/pint`)
- [ ] All tests pass (`php artisan test`)
- [ ] No untyped variables or parameters remain
- [ ] No code duplication - using traits where applicable

---

## PR Review Learnings: Spatie Permission Integration (November 2025)

This section documents critical learnings from the Spatie Permission integration PR review. These patterns apply to all future package integrations and demonstrate proper package decoupling implementation.

### 1. Direct Package Usage in Tests Must Be Abstracted

**Issue:** Test files directly imported and used `Spatie\Permission\Models\Permission` and `Spatie\Permission\Models\Role`, creating vendor lock-in even in tests.

#### ❌ Incorrect
```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('user can be assigned a role', function () {
    $role = Role::create(['name' => 'admin', 'team_id' => $this->tenant->id]);
    $this->user->assignRole($role);
    expect($this->user->hasRole('admin'))->toBeTrue();
});
```

**Problems:**
- Tests are tightly coupled to Spatie package
- Replacing the permission package requires rewriting all tests
- Cannot easily mock or stub package behavior
- Violates package decoupling strategy

#### ✅ Correct
```php
use App\Support\Contracts\PermissionServiceContract;

test('user can be assigned a role', function () {
    $permissionService = app(PermissionServiceContract::class);
    
    $role = $permissionService->createRole('admin', $this->tenant->id);
    $permissionService->assignRole($this->user, $role);
    
    expect($permissionService->hasRole($this->user, 'admin'))->toBeTrue();
});
```

**Benefits:**
- Tests use our abstraction layer
- Easy to mock PermissionServiceContract for unit tests
- Tests remain valid if we switch permission packages
- Follows established pattern used in ActivityLogger and SearchService tests

**Rule:** NEVER import external package classes directly in test files. Always use your contracts and service abstractions.

---

### 2. Model Traits Must Use Wrapper Pattern

**Issue:** User model directly used `Spatie\Permission\Traits\HasRoles`, creating tight coupling to the package.

#### ❌ Incorrect
```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

**Problems:**
- Model is tightly coupled to Spatie package
- Cannot easily switch to alternative permission system
- Violates package decoupling (CRITICAL) guideline
- Inconsistent with HasActivityLogging, IsSearchable, HasTokens patterns

#### ✅ Correct
```php
use App\Support\Traits\HasPermissions;

class User extends Authenticatable
{
    use HasPermissions;  // Our wrapper trait
}
```

**Wrapper Trait Implementation:**
```php
// app/Support/Traits/HasPermissions.php
namespace App\Support\Traits;

use Spatie\Permission\Traits\HasRoles;

trait HasPermissions
{
    use HasRoles;  // Internal use of Spatie trait
    
    // Add convenience methods
    public function getUserRoles(): Collection
    {
        return $this->roles;
    }
    
    public function getUserPermissions(): Collection
    {
        return $this->getAllPermissions();
    }
}
```

**Benefits:**
- Business logic depends on OUR trait, not Spatie's
- Can add custom convenience methods
- Switching packages only requires updating the wrapper
- Follows established pattern in the codebase

**Rule:** NEVER use external package traits directly in models. Always create a wrapper trait in `app/Support/Traits/` that internally uses the package trait.

---

### 3. Complete Package Decoupling Requires Three Components

**Lesson:** Full package decoupling requires creating three components:

#### Component 1: Contract Interface
```php
// app/Support/Contracts/PermissionServiceContract.php
interface PermissionServiceContract
{
    public function createRole(string $name, string|int|null $teamId = null): mixed;
    public function assignRole(Model $user, string|object $role): void;
    public function hasRole(Model $user, string $role): bool;
    // ... other methods
}
```

#### Component 2: Adapter Implementation
```php
// app/Support/Services/Permission/SpatiePermissionService.php
class SpatiePermissionService implements PermissionServiceContract
{
    public function createRole(string $name, string|int|null $teamId = null): mixed
    {
        return Role::create([
            'name' => $name,
            'team_id' => $teamId,
        ]);
    }
    
    public function assignRole(Model $user, string|object $role): void
    {
        $user->assignRole($role);
    }
    
    // ... other methods
}
```

#### Component 3: Service Provider Binding
```php
// app/Providers/PermissionServiceProvider.php
class PermissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            PermissionServiceContract::class,
            SpatiePermissionService::class
        );
    }
}
```

**Complete Integration Checklist:**
- [ ] Create contract in `app/Support/Contracts/`
- [ ] Create adapter in `app/Support/Services/{Category}/`
- [ ] Create wrapper trait in `app/Support/Traits/` (for model-level features)
- [ ] Create service provider in `app/Providers/`
- [ ] Register service provider in `bootstrap/providers.php`
- [ ] Update models to use wrapper trait (not package trait)
- [ ] Update business logic to inject contract (not use package directly)
- [ ] Update tests to use contract (not package classes)
- [ ] Add comprehensive integration tests
- [ ] Document in CODING_GUIDELINES.md

**Rule:** All three components (Contract, Adapter, Provider) are REQUIRED for proper package decoupling. Missing any one creates incomplete abstraction.

---

### 4. PHPDoc @return Tags Are Mandatory

**Issue:** Method had return type declaration but was missing required `@return` tag in PHPDoc block.

#### ❌ Incorrect
```php
/**
 * Get the team ID for permission scoping.
 *
 * This method is used by Spatie Permission to scope roles and permissions
 * to the user's tenant, ensuring multi-tenant isolation.
 */
public function getPermissionTeamId(): int|string|null
{
    return $this->tenant_id;
}
```

#### ✅ Correct
```php
/**
 * Get the team ID for permission scoping.
 *
 * This method is used by Spatie Permission to scope roles and permissions
 * to the user's tenant, ensuring multi-tenant isolation.
 *
 * @return int|string|null The tenant ID for permission scoping
 */
public function getPermissionTeamId(): int|string|null
{
    return $this->tenant_id;
}
```

**Rule:** ALL public and protected methods MUST have both:
1. Return type declaration (e.g., `: int|string|null`)
2. PHPDoc `@return` tag with description

This is required even when the return type is declared, as the PHPDoc provides additional context and documentation.

---

### 5. Comments Must Match Configuration Values

**Issue:** Configuration file had a comment stating "24 hours" but the actual value was "1 hour", causing confusion.

#### ❌ Incorrect
```php
/*
 * By default all permissions are cached for 24 hours to speed up performance.
 * When permissions or roles are updated the cache is flushed automatically.
 */
'expiration_time' => \DateInterval::createFromDateString('1 hour'),
```

#### ✅ Correct
```php
/*
 * By default all permissions are cached for 1 hour to speed up performance.
 * When permissions or roles are updated the cache is flushed automatically.
 */
'expiration_time' => \DateInterval::createFromDateString('1 hour'),
```

**Rule:** Configuration comments MUST accurately describe the actual configured value. When changing values, always update corresponding comments.

---

### 6. Missing Facade Imports Cause Fatal Errors

**Issue:** Test used `Schema::hasTable()` but forgot to import the Schema facade.

#### ❌ Incorrect
```php
<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

test('permission tables exist', function () {
    expect(Schema::hasTable('permissions'))->toBeTrue();  // Fatal error!
});
```

#### ✅ Correct
```php
<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;  // Added

test('permission tables exist', function () {
    expect(Schema::hasTable('permissions'))->toBeTrue();  // Works!
});
```

**Rule:** All facades used in tests (Schema, DB, Cache, etc.) MUST be explicitly imported. Never rely on global facade access in strict typed files.

---

### 7. Package Decoupling Priority List

Based on the PACKAGE-DECOUPLING-STRATEGY.md document, external packages should be decoupled in this priority order:

**HIGH Priority (MUST decouple immediately):**
1. `spatie/laravel-activitylog` → ActivityLoggerContract ✅ DONE
2. `laravel/scout` → SearchServiceContract ✅ DONE
3. `laravel/sanctum` → TokenServiceContract ✅ DONE
4. `spatie/laravel-permission` → PermissionServiceContract ✅ DONE (this PR)

**MEDIUM Priority (decouple when implemented):**
5. `spatie/laravel-model-status` → StatusServiceContract (when used)
6. `lorisleiva/laravel-actions` → Keep as-is (trait-based pattern, low risk)

**LOW Priority (utility libraries, no decoupling needed):**
- `brick/math` - Utility library
- `pestphp/pest` - Testing framework
- `laravel/tinker` - Development tool

**Rule:** All HIGH priority packages MUST be decoupled before using them in business logic. MEDIUM priority packages should be decoupled when first introduced. LOW priority packages do not require decoupling.

---

### 8. Decoupling Prevents Breaking Changes

**Key Insight:** The main benefit of package decoupling became clear during this PR review:

**Without Decoupling:**
```
Spatie Permission v7.0 released with breaking changes
↓
Must update 47 files across the codebase
↓
3-5 days of refactoring work
↓
High risk of introducing bugs
↓
Comprehensive testing required
```

**With Decoupling:**
```
Spatie Permission v7.0 released with breaking changes
↓
Update only SpatiePermissionService adapter
↓
30 minutes of focused work
↓
Low risk (changes isolated to one file)
↓
Existing tests verify behavior unchanged
```

**Rule:** Package decoupling is an investment that pays off when:
- Package releases breaking changes
- Need to switch to alternative package
- Want to mock package behavior in tests
- Package becomes unmaintained

The upfront cost (creating contract + adapter + provider) is recovered after the first breaking change or when writing tests.

---

## Questions or Suggestions

If you have questions about these guidelines or suggestions for improvements, please:
1. Open an issue in the repository
2. Discuss in the team chat
3. Propose changes via pull request

**Last Updated:** November 10, 2025
