# Laravel ERP - Coding Guidelines

This document outlines the coding standards and best practices for the Laravel ERP project. All code contributions must adhere to these guidelines to ensure consistency, maintainability, and code quality across the codebase.

## Table of Contents

- [PHP Standards](#php-standards)
- [Type Declarations](#type-declarations)
- [PHPDoc Documentation](#phpdoc-documentation)
- [Migration Standards](#migration-standards)
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

---

## Migration Standards

### 6. Migration Class Format

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

### Mistake 4: Incomplete PHPDoc Blocks

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

### Mistake 5: Using Named Migration Classes

**Problem:** Named migration classes can cause conflicts and don't follow Laravel 12+ conventions.

**Solution:** Always use anonymous migrations with `return new class extends Migration`.

**Converting old migrations:**
```php
// Before
class CreateUsersTable extends Migration { }

// After
return new class extends Migration { };
```

---

## Code Review Checklist

Before submitting code for review, ensure:

- [ ] All PHP files have `declare(strict_types=1);`
- [ ] All method parameters have type hints
- [ ] All methods declare return types
- [ ] All public/protected methods have PHPDoc blocks with `@return` tags
- [ ] All migrations use anonymous class format
- [ ] Code passes Laravel Pint formatting (`./vendor/bin/pint`)
- [ ] All tests pass (`php artisan test`)
- [ ] No untyped variables or parameters remain

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

## Questions or Suggestions

If you have questions about these guidelines or suggestions for improvements, please:
1. Open an issue in the repository
2. Discuss in the team chat
3. Propose changes via pull request

**Last Updated:** November 9, 2025
