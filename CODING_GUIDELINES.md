# Laravel ERP - Coding Guidelines

This document outlines the coding standards and best practices for the Laravel ERP project. All code contributions must adhere to these guidelines to ensure consistency, maintainability, and code quality across the codebase.

## Table of Contents

- [PHP Standards](#php-standards)
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

## Code Review Checklist (Updated)

Before submitting code for review, ensure:

- [ ] All PHP files have `declare(strict_types=1);`
- [ ] All method parameters have type hints
- [ ] All methods declare return types
- [ ] All public/protected methods have complete PHPDoc blocks with `@return` tags
- [ ] PHPDoc documents all error responses and conditions
- [ ] All migrations use anonymous class format
- [ ] Data access uses repository pattern (no direct `Model::create()` or `Model::find()` in services)
- [ ] Authentication checks before using `auth()->user()`
- [ ] Authorization checks for privileged operations
- [ ] Complete validation rules for all fillable fields
- [ ] Accurate `@throws` tags in PHPDoc matching actual exceptions
- [ ] **No N+1 queries in middleware or frequently-called code**
- [ ] **Middleware dependencies and ordering documented**
- [ ] **Unit tests do not use database (or are in Feature directory)**
- [ ] **No hard-coded IDs or values in tests**
- [ ] **No performance tests with hard time limits in regular test suites**
- [ ] **Performance claims in documentation are qualified**
- [ ] Code passes Laravel Pint formatting (`./vendor/bin/pint`)
- [ ] All tests pass (`php artisan test`)
- [ ] No untyped variables or parameters remain

---

## Questions or Suggestions

If you have questions about these guidelines or suggestions for improvements, please:
1. Open an issue in the repository
2. Discuss in the team chat
3. Propose changes via pull request

**Last Updated:** November 9, 2025
