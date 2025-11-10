# Package Decoupling Initiative - Summary

**Date:** November 10, 2025  
**Branch:** `design-for-decoupling`  
**Status:** Design Complete, Ready for Implementation

---

## Overview

This initiative enforces the "Package-as-a-Service" design pattern across the Laravel ERP system by abstracting all external package dependencies behind contracts/interfaces.

## Objective

**Prevent vendor lock-in and improve testability** by ensuring all external packages (Spatie, Scout, Sanctum, etc.) are accessed through our own contracts, not directly.

---

## What Was Created

### 1. Comprehensive Strategy Document

**File:** `docs/architecture/PACKAGE-DECOUPLING-STRATEGY.md`

**Contents:**
- Complete decoupling strategy for all packages
- Before/After code examples for each package
- Directory structure and file organization
- Implementation phases with task breakdown
- Testing strategies with examples
- Migration guide for existing code
- Configuration management
- Success criteria and anti-patterns

**Packages Covered:**
- Spatie Activity Log (Priority 1)
- Laravel Scout (Priority 2)
- Laravel Sanctum (Priority 3)
- Lorisleiva Actions (Analysis - keep as-is)

### 2. Updated Coding Guidelines

**File:** `CODING_GUIDELINES.md`

**Changes:**
- Added new section "Package Decoupling" (#9)
- Complete examples of wrong vs. correct approaches
- Implementation pattern checklist
- Reference to comprehensive strategy document
- Updated table of contents

### 3. Updated Copilot Instructions

**File:** `.github/copilot-instructions.md`

**Changes:**
- Added new section "Package Decoupling (CRITICAL)" (#6)
- Examples of direct package usage (wrong)
- Examples of contract-based usage (correct)
- List of critical packages requiring decoupling
- Reference to strategy document
- Pattern checklist for enforcement

### 4. GitHub Issue Created

**Issue:** [#81 - Enforce Package Decoupling Pattern](https://github.com/azaharizaman/laravel-erp/issues/81)

**Contains:**
- Complete overview and motivation
- Task breakdown by phase
- Directory structure
- Testing strategy
- Success criteria
- Example implementations
- Estimated effort (8-10 days)

---

## Design Pattern

### Architecture Flow

```
Application Code (Actions, Services, Controllers)
           ↓ (depends on)
    Our Contracts/Interfaces
           ↓ (implemented by)
    Package Adapters/Wrappers
           ↓ (uses)
    External Packages
```

### Example: Activity Logging

**Before (Direct Coupling):**
```php
// ❌ Direct package dependency
use Spatie\Activitylog\Traits\LogsActivity;

class TenantManager
{
    public function create(array $data): Tenant
    {
        $tenant = $this->repository->create($data);
        activity()->performedOn($tenant)->log('Created');
        return $tenant;
    }
}
```

**After (Decoupled):**
```php
// ✅ Using our contract
interface ActivityLoggerContract
{
    public function log(string $description, Model $subject, ?Model $causer = null): void;
}

class TenantManager
{
    public function __construct(
        private readonly ActivityLoggerContract $activityLogger
    ) {}
    
    public function create(array $data): Tenant
    {
        $tenant = $this->repository->create($data);
        $this->activityLogger->log('Tenant created', $tenant);
        return $tenant;
    }
}
```

---

## Implementation Phases

### Phase 1: Activity Logging Decoupling
**Priority:** HIGH  
**Effort:** 2-3 days

**Tasks:**
- Create `ActivityLoggerContract`
- Create `SpatieActivityLogger` adapter
- Create `LoggingServiceProvider`
- Update all services using `activity()` helper
- Add tests with mocked contract
- Remove direct Spatie usage

### Phase 2: Search Service Decoupling
**Priority:** HIGH  
**Effort:** 3-4 days

**Tasks:**
- Create `SearchServiceContract`
- Create `ScoutSearchService` adapter
- Create `DatabaseSearchService` fallback
- Update Tenant and User models
- Update all search-related code
- Add tests for both implementations

### Phase 3: Authentication Decoupling
**Priority:** HIGH  
**Effort:** 2-3 days

**Tasks:**
- Create `TokenServiceContract`
- Create `SanctumTokenService` adapter
- Update authentication actions
- Update API controllers
- Add tests with mocked contract

### Phase 4: Documentation & Standards
**Priority:** MEDIUM  
**Effort:** 1 day

**Tasks:**
- Update PR review checklist
- Create code examples
- Train team on pattern
- Enforce in code reviews

---

## Directory Structure

```
app/
├── Support/
│   ├── Contracts/                     # All package contracts
│   │   ├── ActivityLoggerContract.php
│   │   ├── SearchServiceContract.php
│   │   └── TokenServiceContract.php
│   │
│   ├── Services/                      # Package adapters
│   │   ├── Logging/
│   │   │   ├── SpatieActivityLogger.php
│   │   │   └── DatabaseActivityLogger.php (future)
│   │   ├── Search/
│   │   │   ├── ScoutSearchService.php
│   │   │   └── DatabaseSearchService.php
│   │   └── Auth/
│   │       └── SanctumTokenService.php
│   │
│   └── Traits/                        # Optional wrappers
│       ├── HasActivityLogging.php
│       └── IsSearchable.php
│
├── Providers/
│   ├── LoggingServiceProvider.php
│   ├── SearchServiceProvider.php
│   └── AuthServiceProvider.php (updated)
```

---

## Benefits

### 1. Swappability
- Replace packages without changing business logic
- Switch implementations via configuration
- Test with different backends

### 2. Testability
```php
// Easy mocking with our contracts
$mockLogger = Mockery::mock(ActivityLoggerContract::class);
$mockLogger->shouldReceive('log')->once();
$this->app->instance(ActivityLoggerContract::class, $mockLogger);
```

### 3. Maintainability
- All package-specific code isolated to adapters
- Business logic never knows about packages
- Upgrade packages by updating only adapters

### 4. No Vendor Lock-in
- Not tied to specific package APIs
- Can switch to alternative solutions
- Own our contracts, not package's contracts

---

## Code Review Enforcement

### PR Checklist (Added)

- [ ] No direct `use Spatie\Activitylog\*` in business code
- [ ] No direct `use Laravel\Scout\*` in business code
- [ ] No direct `use Laravel\Sanctum\*` in business code
- [ ] All external package usage goes through contracts
- [ ] Tests use mocked contracts, not real packages

### Mandatory for New Code

All new code MUST use contracts, never direct package dependencies.

---

## Next Steps

1. **Review & Approval**
   - Team review of strategy document
   - Approval of approach
   - Assign implementation owners

2. **Phase 1 Implementation**
   - Start with Activity Logging (most critical)
   - Create contracts and adapters
   - Update existing code domain-by-domain
   - Add comprehensive tests

3. **Progressive Rollout**
   - Complete Phase 1 before starting Phase 2
   - Test thoroughly after each phase
   - Document learnings and patterns

4. **Enforcement**
   - Update PR template
   - Train team on pattern
   - Strict code review requirements

---

## Files Modified

1. ✅ `docs/architecture/PACKAGE-DECOUPLING-STRATEGY.md` - Created
2. ✅ `CODING_GUIDELINES.md` - Updated (added section #9)
3. ✅ `.github/copilot-instructions.md` - Updated (added section #6)
4. ✅ GitHub Issue #81 - Created

---

## References

- **Strategy Document:** [PACKAGE-DECOUPLING-STRATEGY.md](PACKAGE-DECOUPLING-STRATEGY.md)
- **Coding Guidelines:** [CODING_GUIDELINES.md#package-decoupling](../../CODING_GUIDELINES.md#package-decoupling)
- **GitHub Issue:** [#81](https://github.com/azaharizaman/laravel-erp/issues/81)

---

## Timeline

**Estimated Total Effort:** 8-10 days

- Week 1: Phase 1 (Activity Logging) + Phase 2 (Search) = 5-7 days
- Week 2: Phase 3 (Auth) + Phase 4 (Docs) = 3 days

**Target Completion:** End of November 2025

---

**Status:** ✅ Design Complete - Ready for Implementation  
**Owner:** Development Team  
**Created:** November 10, 2025
