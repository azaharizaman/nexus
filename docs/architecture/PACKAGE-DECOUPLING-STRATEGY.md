# Package Decoupling Strategy

**Version:** 1.0  
**Created:** November 10, 2025  
**Status:** Design Phase

## Overview

This document outlines the "Package-as-a-Service" approach for the Laravel ERP system, ensuring all external package dependencies are abstracted behind contracts to enable easy replacement, testing, and maintainability.

## Design Principle

**Core Concept:** Never directly depend on external package implementations in business logic. Always wrap external packages behind contracts/interfaces that we control.

**Benefits:**
- **Swappable:** Replace packages without changing business logic
- **Testable:** Mock interfaces easily without package-specific mocks
- **Maintainable:** Isolate package-specific code to adapters
- **Evolvable:** Change implementations as requirements evolve
- **Vendor Lock-in Prevention:** Not tied to specific package APIs

## Architecture Pattern

```
Application Code (Actions, Services, Controllers)
           ↓ (depends on)
    Our Contracts/Interfaces
           ↓ (implemented by)
    Package Adapters/Wrappers
           ↓ (uses)
    External Packages
```

---

## Current External Dependencies

### Critical Business Packages

| Package | Current Usage | Decoupling Priority | Status |
|---------|--------------|---------------------|--------|
| **spatie/laravel-activitylog** | Audit logging | HIGH | ❌ Not Decoupled |
| **lorisleiva/laravel-actions** | Action pattern | MEDIUM | ⚠️ Trait-based |
| **laravel/scout** | Search functionality | HIGH | ❌ Not Decoupled |
| **laravel/sanctum** | API authentication | HIGH | ❌ Not Decoupled |
| **spatie/laravel-permission** | Authorization | MEDIUM | Not Yet Implemented |
| **spatie/laravel-model-status** | Status management | MEDIUM | Not Yet Implemented |

### Infrastructure Packages

| Package | Usage | Notes |
|---------|-------|-------|
| **brick/math** | Decimal precision | Low priority - utility library |
| **laravel/tinker** | Development tool | No decoupling needed |
| **pestphp/pest** | Testing framework | No decoupling needed |

---

## Decoupling Strategy by Package

### 1. Activity Logging (Spatie Activitylog)

**Current State:**
```php
// ❌ Direct dependency in models
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Tenant extends Model
{
    use LogsActivity;
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status']);
    }
}

// ❌ Direct query in services
Activity::where('subject_type', Tenant::class)->get();
```

**Target State:**
```php
// ✅ Our contract
interface ActivityLoggerContract
{
    public function log(string $description, Model $subject, ?Model $causer = null): void;
    public function getActivities(Model $subject): Collection;
    public function getByDateRange(Carbon $from, Carbon $to, ?string $logName = null): Collection;
    public function getStatistics(array $filters = []): array;
}

// ✅ Spatie adapter implementation
class SpatieActivityLogger implements ActivityLoggerContract
{
    public function log(string $description, Model $subject, ?Model $causer = null): void
    {
        activity()
            ->performedOn($subject)
            ->causedBy($causer ?? auth()->user())
            ->log($description);
    }
    
    public function getActivities(Model $subject): Collection
    {
        return Activity::forSubject($subject)->get();
    }
    
    // ... other methods
}

// ✅ Usage in business code
class TenantManager
{
    public function __construct(
        private readonly ActivityLoggerContract $activityLogger
    ) {}
    
    public function create(array $data): Tenant
    {
        $tenant = $this->repository->create($data);
        
        $this->activityLogger->log(
            'Tenant created',
            $tenant
        );
        
        return $tenant;
    }
}
```

**Implementation Files:**
- `app/Support/Contracts/ActivityLoggerContract.php` - Interface
- `app/Support/Services/Logging/SpatieActivityLogger.php` - Spatie adapter
- `app/Providers/LoggingServiceProvider.php` - Binding
- `app/Support/Traits/HasActivityLogging.php` - Optional trait wrapper

---

### 2. Search (Laravel Scout)

**Current State:**
```php
// ❌ Direct Scout dependency
use Laravel\Scout\Searchable;

class Tenant extends Model
{
    use Searchable;
    
    public function searchableAs(): string
    {
        return 'tenants';
    }
}

// ❌ Direct Scout query in controllers
$results = Tenant::search($query)->get();
```

**Target State:**
```php
// ✅ Our contract
interface SearchServiceContract
{
    public function search(string $modelClass, string $query, array $options = []): Collection;
    public function searchRaw(string $index, string $query): array;
    public function index(Model $model): void;
    public function removeFromIndex(Model $model): void;
    public function flush(string $modelClass): void;
}

// ✅ Scout adapter
class ScoutSearchService implements SearchServiceContract
{
    public function search(string $modelClass, string $query, array $options = []): Collection
    {
        $builder = $modelClass::search($query);
        
        if (isset($options['filters'])) {
            foreach ($options['filters'] as $key => $value) {
                $builder->where($key, $value);
            }
        }
        
        return $builder->get();
    }
    
    // ... other methods
}

// ✅ Usage in business code
class SearchTenantsAction
{
    use AsAction;
    
    public function __construct(
        private readonly SearchServiceContract $searchService
    ) {}
    
    public function handle(string $query): Collection
    {
        return $this->searchService->search(
            Tenant::class,
            $query,
            ['filters' => ['status' => TenantStatus::ACTIVE]]
        );
    }
}
```

**Implementation Files:**
- `app/Support/Contracts/SearchServiceContract.php` - Interface
- `app/Support/Services/Search/ScoutSearchService.php` - Scout adapter
- `app/Support/Services/Search/DatabaseSearchService.php` - Fallback implementation
- `app/Providers/SearchServiceProvider.php` - Binding with config
- `app/Support/Traits/IsSearchable.php` - Optional trait wrapper

---

### 3. Authentication (Laravel Sanctum)

**Current State:**
```php
// ❌ Direct Sanctum dependency
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
}

// ❌ Direct token creation
$token = $user->createToken('api-token')->plainTextToken;
```

**Target State:**
```php
// ✅ Our contract
interface TokenServiceContract
{
    public function createToken(User $user, string $name, array $abilities = []): string;
    public function revokeToken(User $user, string $tokenId): bool;
    public function revokeAllTokens(User $user): bool;
    public function getActiveTokens(User $user): Collection;
    public function validateToken(string $token): ?User;
}

// ✅ Sanctum adapter
class SanctumTokenService implements TokenServiceContract
{
    public function createToken(User $user, string $name, array $abilities = []): string
    {
        return $user->createToken($name, $abilities)->plainTextToken;
    }
    
    public function revokeToken(User $user, string $tokenId): bool
    {
        return $user->tokens()->where('id', $tokenId)->delete() > 0;
    }
    
    // ... other methods
}

// ✅ Usage in business code
class AuthenticateUserAction
{
    use AsAction;
    
    public function __construct(
        private readonly TokenServiceContract $tokenService
    ) {}
    
    public function handle(User $user, string $deviceName): string
    {
        return $this->tokenService->createToken(
            $user,
            $deviceName,
            ['*'] // All abilities
        );
    }
}
```

**Implementation Files:**
- `app/Support/Contracts/TokenServiceContract.php` - Interface
- `app/Support/Services/Auth/SanctumTokenService.php` - Sanctum adapter
- `app/Support/Services/Auth/SessionTokenService.php` - Alternative for web
- `app/Providers/AuthServiceProvider.php` - Binding (update existing)

---

### 4. Actions (Lorisleiva Laravel Actions)

**Current State:**
```php
// ⚠️ Trait-based approach - harder to decouple
use Lorisleiva\Actions\Concerns\AsAction;

class CreateTenantAction
{
    use AsAction;
    
    public function handle(array $data): Tenant
    {
        // Business logic
    }
}
```

**Analysis:**
Laravel Actions uses traits, which is harder to decouple. However, we can:

**Option 1: Keep as-is** (Recommended)
- Actions are a pattern, not business logic dependency
- Trait adds no business logic, only invocation methods
- Low risk of needing replacement
- Focus decoupling efforts on data-handling packages

**Option 2: Define Action Base Contract** (If strict decoupling required)
```php
// ✅ Our action contract
interface ActionContract
{
    public function handle(...$arguments): mixed;
    public static function run(...$arguments): mixed;
    public static function dispatch(...$arguments): void;
}

// ✅ Abstract base using Laravel Actions
abstract class BaseAction implements ActionContract
{
    use AsAction;
    
    abstract public function handle(...$arguments): mixed;
}

// ✅ Usage
class CreateTenantAction extends BaseAction
{
    public function handle(array $data): Tenant
    {
        // Business logic
    }
}
```

**Recommendation:** Keep Laravel Actions as-is. It's a pattern library with minimal business logic coupling.

---

## Implementation Phases

### Phase 1: Activity Logging Decoupling (PRIORITY 1)

**Why First:** Most critical for audit requirements, used extensively across domains.

**Tasks:**
1. Create `ActivityLoggerContract` interface
2. Create `SpatieActivityLogger` adapter
3. Create `HasActivityLogging` trait (optional wrapper)
4. Update `TenantManager` to use contract
5. Update all existing services using `activity()` helper
6. Add tests with mocked contract
7. Document usage in coding guidelines

**Estimated Effort:** 2-3 days

---

### Phase 2: Search Service Decoupling (PRIORITY 2)

**Why Second:** Used in multiple domains, critical for user experience.

**Tasks:**
1. Create `SearchServiceContract` interface
2. Create `ScoutSearchService` adapter
3. Create `DatabaseSearchService` fallback (for testing/small deployments)
4. Create `IsSearchable` trait wrapper
5. Update all models using Scout directly
6. Update search-related actions/services
7. Add configuration for switching implementations
8. Add tests with both implementations

**Estimated Effort:** 3-4 days

---

### Phase 3: Authentication Decoupling (PRIORITY 3)

**Why Third:** Affects API layer, less frequent change than logging/search.

**Tasks:**
1. Create `TokenServiceContract` interface
2. Create `SanctumTokenService` adapter
3. Update authentication actions
4. Update API controllers
5. Add tests with mocked contract
6. Document token management patterns

**Estimated Effort:** 2-3 days

---

### Phase 4: Future Packages (PRIORITY 4)

**Apply pattern to:**
- Spatie Permission (when implemented)
- Spatie Model Status (when implemented)
- Any new package dependencies

**Standard Process:**
1. Create contract in `app/Support/Contracts/`
2. Create adapter in `app/Support/Services/{Category}/`
3. Bind in appropriate service provider
4. Update business code to use contract
5. Add tests
6. Document in guidelines

---

## Directory Structure

```
app/
├── Support/
│   ├── Contracts/                     # All package contracts
│   │   ├── ActivityLoggerContract.php
│   │   ├── SearchServiceContract.php
│   │   ├── TokenServiceContract.php
│   │   ├── PermissionServiceContract.php
│   │   └── CacheServiceContract.php
│   │
│   ├── Services/                      # Package adapters
│   │   ├── Logging/
│   │   │   ├── SpatieActivityLogger.php
│   │   │   └── DatabaseActivityLogger.php (future)
│   │   │
│   │   ├── Search/
│   │   │   ├── ScoutSearchService.php
│   │   │   ├── DatabaseSearchService.php
│   │   │   └── MeilisearchSearchService.php (future)
│   │   │
│   │   ├── Auth/
│   │   │   ├── SanctumTokenService.php
│   │   │   └── SessionTokenService.php
│   │   │
│   │   └── Permission/
│   │       └── SpatiePermissionService.php
│   │
│   └── Traits/                        # Optional wrapper traits
│       ├── HasActivityLogging.php
│       ├── IsSearchable.php
│       └── HasTokens.php
│
├── Providers/
│   ├── LoggingServiceProvider.php     # Activity logging bindings
│   ├── SearchServiceProvider.php      # Search bindings
│   └── AuthServiceProvider.php        # Token service bindings (updated)
```

---

## Service Provider Bindings

### LoggingServiceProvider

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Contracts\ActivityLoggerContract;
use App\Support\Services\Logging\SpatieActivityLogger;
use Illuminate\Support\ServiceProvider;

class LoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActivityLoggerContract::class, function ($app) {
            // Could switch based on config
            return new SpatieActivityLogger();
        });
    }
}
```

### SearchServiceProvider

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Contracts\SearchServiceContract;
use App\Support\Services\Search\ScoutSearchService;
use App\Support\Services\Search\DatabaseSearchService;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SearchServiceContract::class, function ($app) {
            $driver = config('search.driver', 'scout');
            
            return match ($driver) {
                'scout' => new ScoutSearchService(),
                'database' => new DatabaseSearchService(),
                default => new ScoutSearchService(),
            };
        });
    }
}
```

---

## Testing Strategy

### Mocking Package Contracts

```php
// ✅ Easy to mock our contracts
class TenantManagerTest extends TestCase
{
    public function test_create_logs_activity(): void
    {
        // Mock our contract, not Spatie's
        $mockLogger = Mockery::mock(ActivityLoggerContract::class);
        $mockLogger->shouldReceive('log')
            ->once()
            ->with('Tenant created', Mockery::type(Tenant::class));
        
        $this->app->instance(ActivityLoggerContract::class, $mockLogger);
        
        $manager = app(TenantManagerContract::class);
        $tenant = $manager->create(['name' => 'Test']);
        
        // Assert tenant created, activity logged
    }
}
```

### Testing Multiple Implementations

```php
// Test search works with both Scout and Database
dataset('search_implementations', [
    'scout' => [ScoutSearchService::class],
    'database' => [DatabaseSearchService::class],
]);

test('can search tenants', function (string $implementation) {
    $this->app->bind(SearchServiceContract::class, $implementation);
    
    $service = app(SearchServiceContract::class);
    $results = $service->search(Tenant::class, 'acme');
    
    expect($results)->toHaveCount(1);
})->with('search_implementations');
```

---

## Migration Strategy

### For Existing Code

**Step 1: Create contract and adapter**
- Define interface with all needed methods
- Implement adapter wrapping existing package
- Bind in service provider

**Step 2: Update one domain at a time**
- Start with Core domain
- Update services to inject contract
- Update tests to use mocks
- Verify functionality unchanged

**Step 3: Repeat for other domains**
- Backoffice → Inventory → Sales → Purchasing → Accounting

**Step 4: Remove direct package usage**
- Search for direct `use` statements
- Replace with contract usage
- Run full test suite

### For New Code

**MANDATORY RULE:** All new code MUST use contracts, never direct package dependencies.

**PR Review Checklist:**
- [ ] No direct `use Spatie\Activitylog\*` in business code
- [ ] No direct `use Laravel\Scout\*` in business code
- [ ] No direct `use Laravel\Sanctum\*` in business code
- [ ] All external package usage goes through contracts
- [ ] Tests use mocked contracts, not real packages

---

## Configuration

### config/packages.php (New File)

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Activity Logging Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "spatie", "database", "null"
    */
    'activity_logger' => env('ACTIVITY_LOGGER', 'spatie'),

    /*
    |--------------------------------------------------------------------------
    | Search Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "scout", "database", "null"
    */
    'search_driver' => env('SEARCH_DRIVER', 'scout'),

    /*
    |--------------------------------------------------------------------------
    | Token Service Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "sanctum", "session"
    */
    'token_service' => env('TOKEN_SERVICE', 'sanctum'),
];
```

---

## Benefits Realized

### 1. **Testability**
```php
// Before: Hard to test without Spatie
$this->mockSpatie(); // Complex package-specific mocking

// After: Easy to test with our contract
$this->mock(ActivityLoggerContract::class);
```

### 2. **Flexibility**
```php
// Can switch implementations via config
// Development: Use database logging (faster)
// Production: Use Spatie (more features)
// Testing: Use null logger (no writes)
```

### 3. **Maintainability**
```php
// All Spatie-specific code in ONE adapter file
// Business logic never knows about Spatie
// Upgrade Spatie? Update only the adapter
```

### 4. **Documentation**
```php
// Our contracts are self-documenting
// Clear API that we control
// PHPDoc explains our business needs
```

---

## Anti-Patterns to Avoid

### ❌ Leaky Abstractions
```php
// DON'T expose package-specific types
interface ActivityLoggerContract
{
    // ❌ Returns Spatie's LogOptions
    public function getOptions(): LogOptions;
}

// ✅ Use our own types
interface ActivityLoggerContract
{
    // ✅ Returns generic array
    public function getOptions(): array;
}
```

### ❌ Over-Engineering
```php
// DON'T create abstractions for everything
// Utilities and helpers are OK to use directly

use Illuminate\Support\Str; // ✅ OK - framework helper
use Carbon\Carbon; // ✅ OK - standard library

// Only abstract packages that:
// 1. Handle business-critical operations
// 2. Might need replacement
// 3. Are hard to test
```

### ❌ Premature Abstraction
```php
// DON'T abstract packages you haven't used yet
// Wait until you actually use the package
// Then wrap it immediately before use spreads
```

---

## Success Criteria

### Definition of Done (Per Package)

- [ ] Contract interface created with complete PHPDoc
- [ ] At least one adapter implementation
- [ ] Service provider binding with config support
- [ ] All business code updated to use contract
- [ ] No direct package usage in `app/Domains/` or `app/Actions/`
- [ ] Unit tests using mocked contracts pass
- [ ] Feature tests with real implementation pass
- [ ] Documentation updated in coding guidelines
- [ ] PR reviewed and approved

### Overall Project Success

- [ ] All critical packages (Activity Log, Scout, Sanctum) decoupled
- [ ] Zero direct package usage in business logic
- [ ] All tests use mocked contracts
- [ ] Configuration allows switching implementations
- [ ] Documentation complete
- [ ] Team trained on pattern

---

## Resources

### Related Documentation
- [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md) - Repository pattern
- [.github/copilot-instructions.md](../../.github/copilot-instructions.md) - Contract-driven development

### Package Documentation
- [Spatie Activitylog](https://spatie.be/docs/laravel-activitylog)
- [Laravel Scout](https://laravel.com/docs/scout)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Lorisleiva Actions](https://laravelactions.com/)

---

**Next Steps:**
1. Review this document with team
2. Get approval for approach
3. Start Phase 1: Activity Logging Decoupling
4. Update coding guidelines with new patterns
5. Create first PR with Activity Logger contract

**Document Owner:** Development Team  
**Last Updated:** November 10, 2025
