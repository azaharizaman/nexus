---
plan: Implement Backoffice Fiscal Year & Period Management
version: 1.0.0
date_created: 2025-11-13
last_updated: 2025-11-13
owner: Development Team
status: Planned
tags: [feature, backoffice, accounting, fiscal-year, period-management, business-logic]
---

# PRD01-SUB15-PLAN02: Implement Backoffice Fiscal Year & Period Management

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

**Related PRD:** [PRD01-SUB15-BACKOFFICE.md](../prd/prd-01/PRD01-SUB15-BACKOFFICE.md)  
**Previous Plan:** [PRD01-SUB15-PLAN01-implement-organizational-foundation.md](./PRD01-SUB15-PLAN01-implement-organizational-foundation.md)  
**Plan Type:** Implementation Plan  
**Version:** 1.0.0  
**Created:** November 13, 2025  
**Milestone:** MILESTONE 3

---

## Introduction

This implementation plan focuses on fiscal year and accounting period management, which are critical for financial operations across all accounting modules. The plan covers fiscal year lifecycle (creation, closing, reopening), accounting period management with module-specific locking, period validation services with Redis caching, and integration with transactional modules for posting validation.

**Key Features Delivered:**
- Fiscal year creation, closing, and reopening with audit trails
- Accounting period management with open/closed status per module
- Period validation service with < 10ms response time (PR-BO-002)
- Redis caching for period status with automatic invalidation
- Integration with all transactional modules for posting validation
- Period lock history for compliance and audit requirements

**Business Impact:**
- Ensures financial data integrity through period controls
- Supports month-end and year-end closing processes
- Prevents posting to closed periods (BR-BO-002)
- Provides audit trail for all period status changes
- Enables module-specific period locking for flexible financial controls

---

## 1. Requirements & Constraints

### Functional Requirements

- **FR-BO-002**: Support fiscal year management including creation, closing, and reopening
- **FR-BO-003**: Define accounting periods with open/closed status per module

### Business Rules

- **BR-BO-001**: Only system administrators can create or modify fiscal years
- **BR-BO-002**: Closed accounting periods cannot accept new transactions without reopening
- **BR-BO-003**: Fiscal year end date must be after start date and cannot overlap existing years

### Data Requirements

- **DR-BO-002**: Maintain period lock history for compliance and audit trail

### Integration Requirements

- **IR-BO-001**: Integrate with all transactional modules for period validation

### Security Requirements

- **SR-BO-001**: Implement role-based access to fiscal year and period management
- **SR-BO-002**: Log all administrative actions (fiscal year closing, period locking)

### Performance Requirements

- **PR-BO-002**: Period validation check must complete in < 10ms

### Architecture Requirements

- **ARCH-BO-002**: Cache current period status in Redis for fast validation

### Event Requirements

- **EV-BO-001**: FiscalYearClosedEvent when fiscal year is closed
- **EV-BO-002**: PeriodLockedEvent when accounting period is locked

### Coding Guidelines

- **GUD-001**: All PHP files must have `declare(strict_types=1);`
- **GUD-002**: All methods must have parameter type hints and return type declarations
- **GUD-003**: Use repository pattern for all data access (no direct Model queries in services)
- **GUD-004**: Use Laravel Actions pattern for all business operations
- **GUD-005**: All public/protected methods must have complete PHPDoc blocks

### Design Patterns

- **PAT-001**: Repository pattern with contracts for all data access
- **PAT-002**: Laravel Actions (AsAction trait) for all business logic
- **PAT-003**: Observer pattern for model lifecycle events
- **PAT-004**: Event-driven architecture for cross-module communication
- **PAT-005**: Service layer with Redis caching for high-frequency operations

### Constraints

- **CON-001**: Fiscal years cannot overlap for the same company
- **CON-002**: Cannot close fiscal year if any period is still open
- **CON-003**: Cannot delete fiscal year if it has transactions
- **CON-004**: Period validation must use cached data to meet < 10ms requirement
- **CON-005**: Only system administrators can modify fiscal year status

---

## 2. Implementation Steps

### GOAL-001: Fiscal Year Foundation & Lifecycle Management

**Objective:** Implement fiscal year data model, repository, and core lifecycle actions (create, close, reopen) with comprehensive validation and audit logging.

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| FR-BO-002 | Support fiscal year management including creation, closing, reopening | Functional |
| BR-BO-001 | Only system administrators can create or modify fiscal years | Business Rule |
| BR-BO-003 | Fiscal year end date after start date, no overlaps | Business Rule |
| SR-BO-001 | Role-based access to fiscal year management | Security |
| SR-BO-002 | Log all administrative actions | Security |
| EV-BO-001 | FiscalYearClosedEvent when fiscal year closed | Event |

**Tasks:**

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-1.1 | Create database migration `2025_01_01_000002_create_fiscal_years_table.php` with columns: id (BIGSERIAL), tenant_id (UUID, FK tenants), company_id (BIGINT, FK companies), year_code (VARCHAR 20, e.g., 'FY2025'), start_date (DATE), end_date (DATE), status (VARCHAR 20: 'open', 'closed'), closed_by (BIGINT nullable, FK users), closed_at (TIMESTAMP nullable), timestamps; UNIQUE constraint (tenant_id, company_id, year_code); indexes: tenant_id, company_id, status; CHECK constraint (end_date > start_date) | | |
| TASK-1.2 | Create enum `FiscalYearStatus` with values: OPEN, CLOSED | | |
| TASK-1.3 | Create model `packages/backoffice/src/Models/FiscalYear.php` with traits: BelongsToTenant, HasActivityLogging, SoftDeletes; fillable: year_code, start_date, end_date, status, closed_by, closed_at; casts: status → FiscalYearStatus enum, start_date → date, end_date → date, closed_at → datetime; relationships: company (belongsTo), closedByUser (belongsTo User), periods (hasMany AccountingPeriod); scopes: open(), closed(), forCompany(int $companyId), current() | | |
| TASK-1.4 | Create factory `FiscalYearFactory.php` with faker data: year_code = 'FY' . faker->year, start_date = Jan 1, end_date = Dec 31, status = OPEN; states: closed(User $user), withPeriods(int $count = 12) | | |
| TASK-1.5 | Create contract `FiscalYearRepositoryContract.php` with methods: findById(int $id): ?FiscalYear, findByCode(string $code, int $companyId): ?FiscalYear, create(array $data): FiscalYear, update(FiscalYear $fiscalYear, array $data): FiscalYear, delete(FiscalYear $fiscalYear): bool, getByCompany(int $companyId): Collection, getCurrentFiscalYear(int $companyId): ?FiscalYear, checkOverlap(int $companyId, Carbon $startDate, Carbon $endDate, ?int $excludeId = null): bool, paginate(int $perPage, array $filters): LengthAwarePaginator | | |
| TASK-1.6 | Implement repository `FiscalYearRepository.php` implementing FiscalYearRepositoryContract; implement checkOverlap() using WHERE clause with date range check: `(start_date <= $endDate AND end_date >= $startDate)`; include eager loading for company, periods count | | |
| TASK-1.7 | Create action `CreateFiscalYearAction.php` using AsAction; inject FiscalYearRepositoryContract, ActivityLoggerContract; handle(array $data): FiscalYear; validation: check user hasRole('admin') or throw AuthorizationException; validate end_date > start_date; check for overlapping fiscal years using repository->checkOverlap(); create fiscal year; log activity "Fiscal year created"; dispatch FiscalYearCreatedEvent; return fiscal year | | |
| TASK-1.8 | Create action `CloseFiscalYearAction.php` using AsAction; inject FiscalYearRepositoryContract, AccountingPeriodRepositoryContract, ActivityLoggerContract; handle(FiscalYear $fiscalYear): FiscalYear; validation: check user hasRole('admin'); verify all periods are closed (query periods where status != 'closed'); if any open, throw ValidationException "Cannot close fiscal year with open periods"; update status = CLOSED, closed_by = auth()->id(), closed_at = now(); invalidate Redis cache; log activity "Fiscal year closed"; dispatch FiscalYearClosedEvent; return fiscal year | | |
| TASK-1.9 | Create action `ReopenFiscalYearAction.php` using AsAction; inject FiscalYearRepositoryContract, ActivityLoggerContract; handle(FiscalYear $fiscalYear): FiscalYear; validation: check user hasRole('admin'); verify status is CLOSED; update status = OPEN, closed_by = null, closed_at = null; invalidate Redis cache; log activity "Fiscal year reopened"; dispatch FiscalYearReopenedEvent; return fiscal year | | |
| TASK-1.10 | Create event `FiscalYearCreatedEvent` with properties: FiscalYear $fiscalYear, User $createdBy | | |
| TASK-1.11 | Create event `FiscalYearClosedEvent` with properties: FiscalYear $fiscalYear, User $closedBy, Carbon $closedAt | | |
| TASK-1.12 | Create event `FiscalYearReopenedEvent` with properties: FiscalYear $fiscalYear, User $reopenedBy | | |
| TASK-1.13 | Create policy `FiscalYearPolicy.php` with methods: viewAny(User $user): bool - require 'view-fiscal-years' permission; view(User $user, FiscalYear $fiscalYear): bool - require permission + tenant scope; create(User $user): bool - require 'manage-fiscal-years' + admin role; update(User $user, FiscalYear $fiscalYear): bool - require permission + admin; delete(User $user, FiscalYear $fiscalYear): bool - require permission + admin; close(User $user, FiscalYear $fiscalYear): bool - require admin; reopen(User $user, FiscalYear $fiscalYear): bool - require admin | | |
| TASK-1.14 | Create API controller `FiscalYearController.php` with routes: index (GET /backoffice/fiscal-years), store (POST /backoffice/fiscal-years), show (GET /backoffice/fiscal-years/{id}), update (PATCH /backoffice/fiscal-years/{id}), destroy (DELETE /backoffice/fiscal-years/{id}), close (POST /backoffice/fiscal-years/{id}/close), reopen (POST /backoffice/fiscal-years/{id}/reopen); inject FiscalYearRepositoryContract; authorize all actions via policy; return FiscalYearResource | | |
| TASK-1.15 | Create form request `StoreFiscalYearRequest.php` with validation: company_id (required, exists:companies), year_code (required, max:20, unique per company), start_date (required, date), end_date (required, date, after:start_date); custom validation: check no overlapping fiscal years | | |
| TASK-1.16 | Create form request `UpdateFiscalYearRequest.php` extending StoreFiscalYearRequest; make year_code unique excluding current record | | |
| TASK-1.17 | Create API resource `FiscalYearResource.php` with fields: id, year_code, start_date, end_date, status, company (nested resource), periods_count, closed_by (user resource if closed), closed_at, timestamps | | |
| TASK-1.18 | Write unit test `FiscalYearTest.php`: test fiscal year factory, test status enum, test relationships (company, periods), test scopes (open, closed, current), test cannot create with end_date < start_date, test checkOverlap() detects overlapping years | | |
| TASK-1.19 | Write unit test `CreateFiscalYearActionTest.php`: mock repository; test creates fiscal year with valid data; test throws ValidationException for overlapping years; test throws AuthorizationException for non-admin users | | |
| TASK-1.20 | Write unit test `CloseFiscalYearActionTest.php`: mock repositories; test closes fiscal year when all periods closed; test throws ValidationException if any period open; test updates closed_by and closed_at; test dispatches FiscalYearClosedEvent | | |
| TASK-1.21 | Write feature test `FiscalYearManagementTest.php`: test complete CRUD via API; test close fiscal year endpoint; test reopen fiscal year endpoint; test authorization (non-admin cannot close); test validation errors (overlapping years) | | |

**Test Coverage:** 21 tests (10 unit, 11 feature)

---

### GOAL-002: Accounting Period Management & Locking

**Objective:** Implement accounting period data model with module-specific locking capability, period lifecycle management, and period lock history for audit compliance.

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| FR-BO-003 | Define accounting periods with open/closed status per module | Functional |
| BR-BO-002 | Closed accounting periods cannot accept new transactions | Business Rule |
| DR-BO-002 | Maintain period lock history for compliance and audit trail | Data |
| SR-BO-002 | Log all administrative actions (period locking) | Security |
| EV-BO-002 | PeriodLockedEvent when accounting period locked | Event |

**Tasks:**

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-2.1 | Create migration `2025_01_01_000003_create_accounting_periods_table.php` with columns: id (BIGSERIAL), tenant_id (UUID, FK), fiscal_year_id (BIGINT, FK fiscal_years), period_number (INT), period_name (VARCHAR 50, e.g., "January 2025"), start_date (DATE), end_date (DATE), status (VARCHAR 20: 'open', 'closed'), locked_modules (JSONB nullable - array of module names), closed_by (BIGINT nullable, FK users), closed_at (TIMESTAMP nullable), timestamps; UNIQUE constraint (fiscal_year_id, period_number); indexes: tenant_id, fiscal_year_id, status; CHECK constraint (end_date > start_date) | | |
| TASK-2.2 | Create enum `PeriodStatus` with values: OPEN, CLOSED | | |
| TASK-2.3 | Create enum `LockableModule` with values: GENERAL_LEDGER, ACCOUNTS_PAYABLE, ACCOUNTS_RECEIVABLE, INVENTORY, SALES, PURCHASING (modules that can be locked independently) | | |
| TASK-2.4 | Create model `packages/backoffice/src/Models/AccountingPeriod.php` with traits: BelongsToTenant, HasActivityLogging; fillable: period_number, period_name, start_date, end_date, status, locked_modules, closed_by, closed_at; casts: status → PeriodStatus enum, start_date → date, end_date → date, locked_modules → array, closed_at → datetime; relationships: fiscalYear (belongsTo), closedByUser (belongsTo User), lockHistory (hasMany PeriodLockHistory); scopes: open(), closed(), forFiscalYear(int $fiscalYearId), current(), lockedForModule(string $module); methods: isLockedForModule(string $module): bool, lockModule(string $module): void, unlockModule(string $module): void | | |
| TASK-2.5 | Create migration `2025_01_01_000006_create_period_lock_history_table.php` with columns: id (BIGSERIAL), tenant_id (UUID, FK), accounting_period_id (BIGINT, FK accounting_periods), action (VARCHAR 20: 'locked', 'unlocked'), module_name (VARCHAR 50), locked_by (BIGINT, FK users), locked_at (TIMESTAMP), reason (TEXT nullable), timestamps; indexes: tenant_id, accounting_period_id, module_name | | |
| TASK-2.6 | Create model `PeriodLockHistory.php` with traits: BelongsToTenant; fillable: action, module_name, locked_by, locked_at, reason; casts: locked_at → datetime; relationships: accountingPeriod (belongsTo), lockedByUser (belongsTo User) | | |
| TASK-2.7 | Create factory `AccountingPeriodFactory.php` with faker data: period_number = 1-12, period_name = faker->monthName + ' ' + year, start_date/end_date = month boundaries, status = OPEN, locked_modules = []; states: closed(User $user), withLockedModules(array $modules) | | |
| TASK-2.8 | Create contract `AccountingPeriodRepositoryContract.php` with methods: findById(int $id): ?AccountingPeriod, create(array $data): AccountingPeriod, update(AccountingPeriod $period, array $data): AccountingPeriod, getByFiscalYear(int $fiscalYearId): Collection, getCurrentPeriod(int $companyId): ?AccountingPeriod, getPeriodByDate(int $companyId, Carbon $date): ?AccountingPeriod, getOpenPeriods(int $fiscalYearId): Collection, isModuleLocked(int $periodId, string $module): bool, paginate(int $perPage, array $filters): LengthAwarePaginator | | |
| TASK-2.9 | Implement repository `AccountingPeriodRepository.php` implementing contract; implement getPeriodByDate() with WHERE clause: `start_date <= $date AND end_date >= $date`; implement isModuleLocked() by checking locked_modules JSONB column contains module name | | |
| TASK-2.10 | Create action `LockPeriodAction.php` using AsAction; inject AccountingPeriodRepositoryContract, ActivityLoggerContract; handle(AccountingPeriod $period, string $module, ?string $reason = null): AccountingPeriod; validation: check user hasRole('admin'); verify module in LockableModule enum; add module to locked_modules array; create PeriodLockHistory record with action='locked'; invalidate Redis cache for period; log activity "Period locked for {$module}"; dispatch PeriodLockedEvent; return period | | |
| TASK-2.11 | Create action `UnlockPeriodAction.php` using AsAction; similar to LockPeriodAction but removes module from locked_modules; create PeriodLockHistory with action='unlocked'; dispatch PeriodUnlockedEvent | | |
| TASK-2.12 | Create action `ClosePeriodAction.php` using AsAction; inject AccountingPeriodRepositoryContract, ActivityLoggerContract; handle(AccountingPeriod $period): AccountingPeriod; validation: check user hasRole('admin'); update status = CLOSED, closed_by = auth()->id(), closed_at = now(); invalidate Redis cache; log activity "Period closed"; dispatch PeriodClosedEvent; return period | | |
| TASK-2.13 | Create action `ReopenPeriodAction.php` using AsAction; handle(AccountingPeriod $period): AccountingPeriod; validation: check user hasRole('admin'); verify fiscal year is open; update status = OPEN, closed_by = null, closed_at = null, locked_modules = []; invalidate Redis cache; log activity "Period reopened"; dispatch PeriodReopenedEvent; return period | | |
| TASK-2.14 | Create event `PeriodLockedEvent` with properties: AccountingPeriod $period, string $module, User $lockedBy, ?string $reason | | |
| TASK-2.15 | Create event `PeriodUnlockedEvent` with properties: AccountingPeriod $period, string $module, User $unlockedBy | | |
| TASK-2.16 | Create event `PeriodClosedEvent` with properties: AccountingPeriod $period, User $closedBy | | |
| TASK-2.17 | Create event `PeriodReopenedEvent` with properties: AccountingPeriod $period, User $reopenedBy | | |
| TASK-2.18 | Create policy `AccountingPeriodPolicy.php` with methods: viewAny, view, lock, unlock, close, reopen; all require admin role + appropriate permissions | | |
| TASK-2.19 | Create API controller `AccountingPeriodController.php` with routes: index (GET /backoffice/periods), show (GET /backoffice/periods/{id}), lock (POST /backoffice/periods/{id}/lock), unlock (POST /backoffice/periods/{id}/unlock), close (POST /backoffice/periods/{id}/close), reopen (POST /backoffice/periods/{id}/reopen); inject AccountingPeriodRepositoryContract | | |
| TASK-2.20 | Create form request `LockPeriodRequest.php` with validation: module (required, in:LockableModule values), reason (nullable, string, max:500) | | |
| TASK-2.21 | Create API resource `AccountingPeriodResource.php` with fields: id, period_number, period_name, start_date, end_date, status, locked_modules, fiscal_year (nested), closed_by (user resource), closed_at, timestamps | | |
| TASK-2.22 | Create API resource `PeriodLockHistoryResource.php` with fields: id, action, module_name, locked_by (user resource), locked_at, reason | | |
| TASK-2.23 | Write unit test `AccountingPeriodTest.php`: test period factory, test relationships, test isLockedForModule() method, test lockModule() adds to array, test unlockModule() removes from array, test scope lockedForModule() | | |
| TASK-2.24 | Write unit test `LockPeriodActionTest.php`: mock repository; test locks period for module; test creates lock history record; test dispatches PeriodLockedEvent; test throws AuthorizationException for non-admin | | |
| TASK-2.25 | Write feature test `AccountingPeriodManagementTest.php`: test lock/unlock period via API; test close/reopen period; test lock history is created; test authorization checks; test cannot lock invalid module | | |

**Test Coverage:** 25 tests (10 unit, 15 feature)

---

### GOAL-003: Period Validation Service with Redis Caching

**Objective:** Implement high-performance period validation service with Redis caching to meet < 10ms validation requirement, supporting all transactional modules.

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| BR-BO-002 | Closed accounting periods cannot accept new transactions | Business Rule |
| IR-BO-001 | Integrate with all transactional modules for period validation | Integration |
| PR-BO-002 | Period validation check must complete in < 10ms | Performance |
| ARCH-BO-002 | Cache current period status in Redis for fast validation | Architecture |

**Tasks:**

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-3.1 | Create service `PeriodValidationService.php` with methods: validateTransaction(int $companyId, Carbon $transactionDate, string $module): bool - check if period is open and not locked for module; getPeriodStatus(int $companyId, Carbon $date): array - return period status info; canPostToDate(int $companyId, Carbon $date, string $module): bool - comprehensive validation; invalidateCache(int $companyId): void - clear Redis cache; getCacheKey(int $companyId, string $date, string $module): string - generate cache key | | |
| TASK-3.2 | Implement validateTransaction() logic: generate cache key = "period:validation:{companyId}:{date}:{module}"; check Redis cache first (return cached result if exists); if cache miss: query AccountingPeriod by date and company; check period status is OPEN; check module not in locked_modules; cache result in Redis with 900 second TTL; return boolean result | | |
| TASK-3.3 | Implement getPeriodStatus() with caching: cache key = "period:status:{companyId}:{date}"; return array with keys: period_id, period_name, status, locked_modules, fiscal_year_status; cache with 900 second TTL | | |
| TASK-3.4 | Implement invalidateCache(): delete all keys matching pattern "period:validation:{companyId}:*" and "period:status:{companyId}:*" from Redis; use Redis SCAN command to find matching keys; delete in batch | | |
| TASK-3.5 | Create listener `InvalidatePeriodCacheListener.php` listening to: PeriodLockedEvent, PeriodUnlockedEvent, PeriodClosedEvent, PeriodReopenedEvent, FiscalYearClosedEvent, FiscalYearReopenedEvent; handle() method: call PeriodValidationService->invalidateCache($event->period->company_id or $event->fiscalYear->company_id) | | |
| TASK-3.6 | Create middleware `ValidateTransactionPeriodMiddleware.php` for use in transactional module routes; extract company_id and transaction_date from request; call PeriodValidationService->validateTransaction(); if validation fails, return 403 Forbidden with error "Transaction date falls in closed period" | | |
| TASK-3.7 | Create helper function `validatePeriod(int $companyId, Carbon $date, string $module): bool` as facade to PeriodValidationService->validateTransaction() for easy use in actions | | |
| TASK-3.8 | Create helper function `getPeriodStatus(int $companyId, Carbon $date): array` as facade to PeriodValidationService->getPeriodStatus() | | |
| TASK-3.9 | Update config `packages/backoffice/config/backoffice.php` add section: 'period_validation' => ['cache_ttl' => 900, 'enable_cache' => true, 'require_open_period' => true] | | |
| TASK-3.10 | Write unit test `PeriodValidationServiceTest.php`: mock AccountingPeriodRepository and Redis; test validateTransaction() returns true for open period; test returns false for closed period; test returns false for locked module; test caches result; test uses cached result on second call; test invalidateCache() clears all keys | | |
| TASK-3.11 | Write integration test `PeriodValidationIntegrationTest.php`: seed fiscal year and periods; test validateTransaction() with real database and Redis; test cache is created; test cache is invalidated when period locked; test validation respects module locking | | |
| TASK-3.12 | Write performance test `PeriodValidationPerformanceTest.php`: seed 100 companies with fiscal years and periods; measure validateTransaction() execution time for 1000 calls; assert average < 10ms (PR-BO-002); assert cache hit rate > 95% after warm-up | | |

**Test Coverage:** 12 tests (6 unit, 3 integration, 3 performance)

---

### GOAL-004: Transactional Module Integration

**Objective:** Implement event listeners and integration contracts for all transactional modules to enforce period validation before transaction posting.

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| IR-BO-001 | Integrate with all transactional modules for period validation | Integration |
| BR-BO-002 | Closed accounting periods cannot accept new transactions | Business Rule |

**Tasks:**

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-4.1 | Create listener `ValidateGLPeriodBeforePostingListener.php` listening to GLTransactionCreatedEvent (SUB08); handle() method: extract transaction date and company_id; call PeriodValidationService->validateTransaction() with module='GENERAL_LEDGER'; if validation fails, throw ValidationException "Cannot post to closed period"; log validation attempt | | |
| TASK-4.2 | Create listener `ValidateAPPeriodBeforePostingListener.php` listening to APInvoiceCreatedEvent (SUB11); validate period for module='ACCOUNTS_PAYABLE'; throw ValidationException if period closed | | |
| TASK-4.3 | Create listener `ValidateARPeriodBeforePostingListener.php` listening to ARInvoiceCreatedEvent (SUB12); validate period for module='ACCOUNTS_RECEIVABLE' | | |
| TASK-4.4 | Create listener `ValidateInventoryPeriodListener.php` listening to StockMovementCreatedEvent (SUB14); validate period for module='INVENTORY' | | |
| TASK-4.5 | Create listener `ValidateSalesPeriodListener.php` listening to SalesOrderCreatedEvent (SUB17); validate period for module='SALES' | | |
| TASK-4.6 | Create listener `ValidatePurchasingPeriodListener.php` listening to PurchaseOrderCreatedEvent (SUB16); validate period for module='PURCHASING' | | |
| TASK-4.7 | Create contract `PeriodValidationContract.php` with methods: validateTransaction(int $companyId, Carbon $date, string $module): bool; getPeriodStatus(int $companyId, Carbon $date): array; canPostToDate(int $companyId, Carbon $date, string $module): bool | | |
| TASK-4.8 | Update PeriodValidationService to implement PeriodValidationContract | | |
| TASK-4.9 | Register PeriodValidationContract binding in BackofficeServiceProvider: bind to PeriodValidationService | | |
| TASK-4.10 | Create API endpoint `GET /api/v1/backoffice/periods/validate` with query params: company_id, transaction_date, module; return validation result with period details; useful for frontend pre-validation | | |
| TASK-4.11 | Create API endpoint `GET /api/v1/backoffice/periods/current` with query param: company_id; return current open period details | | |
| TASK-4.12 | Write integration test `TransactionalModuleIntegrationTest.php`: mock transactional events (GLTransactionCreatedEvent, etc.); test listeners validate periods correctly; test ValidationException thrown for closed periods; test validation respects module-specific locking | | |

**Test Coverage:** 12 tests (12 integration)

---

### GOAL-005: Testing, Documentation & Deployment

**Objective:** Establish comprehensive test coverage for fiscal year and period management, complete API documentation, and deployment readiness.

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| PR-BO-002 | Period validation < 10ms | Performance |
| SR-BO-001 | Role-based access to administrative functions | Security |
| SR-BO-002 | Log all administrative actions | Security |

**Tasks:**

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-5.1 | Write comprehensive unit tests for FiscalYear model: test factory states, test relationships, test scopes, test business logic methods | | |
| TASK-5.2 | Write comprehensive unit tests for AccountingPeriod model: test lockModule/unlockModule methods, test isLockedForModule(), test scope queries | | |
| TASK-5.3 | Write unit tests for all actions: test CreateFiscalYearAction validates overlaps, test CloseFiscalYearAction checks all periods closed, test LockPeriodAction creates history record, test all actions dispatch events | | |
| TASK-5.4 | Write feature tests for complete fiscal year workflow via API: create fiscal year, create periods, lock periods, close periods, close fiscal year, reopen fiscal year; test authorization at each step | | |
| TASK-5.5 | Write feature tests for period management workflow: test lock/unlock individual modules, test close/reopen periods, test lock history is recorded, test cannot post to locked period | | |
| TASK-5.6 | Write integration tests for cross-module period validation: test GL posting validation, test AP/AR validation, test inventory validation; verify ValidationException thrown for closed periods | | |
| TASK-5.7 | Write performance test for Redis caching: measure cache hit rate after warm-up, verify < 10ms validation time with cache, verify cache invalidation works correctly | | |
| TASK-5.8 | Set up Pest configuration for backoffice fiscal/period tests; configure database seeding, Redis mock, authentication helpers | | |
| TASK-5.9 | Achieve minimum 80% code coverage for fiscal year and period modules; run `./vendor/bin/pest --coverage` for backoffice package; add tests for uncovered lines | | |
| TASK-5.10 | Create fiscal year management guide in docs/guides/fiscal-year-management.md: document year-end closing process, best practices for period locking, troubleshooting common issues | | |
| TASK-5.11 | Create period validation guide in docs/guides/period-validation.md: document how transactional modules integrate, explain caching mechanism, provide examples of period validation usage | | |
| TASK-5.12 | Update API documentation in docs/api/backoffice-api.md: document fiscal year endpoints, period endpoints, validation endpoints; include OpenAPI spec; provide request/response examples | | |
| TASK-5.13 | Create database seeder `FiscalYearSeeder.php`: create sample fiscal years for current and previous year; create 12 monthly periods for each year; seed for development and testing | | |
| TASK-5.14 | Update main README.md with fiscal year and period management overview; document integration requirements for new modules | | |
| TASK-5.15 | Create migration rollback tests: test all migrations can be rolled back cleanly; verify foreign key constraints don't prevent rollback | | |
| TASK-5.16 | Validate all acceptance criteria from PRD: fiscal year creation/closing/reopening functional, periods can be locked per module, period validation integrated with transactional modules, performance targets met | | |
| TASK-5.17 | Conduct code review: verify PSR-12 compliance, verify strict types, verify PHPDoc completeness, verify repository pattern usage, verify no direct Model access in services | | |
| TASK-5.18 | Deploy to staging environment; run full test suite; perform end-to-end testing: create fiscal year, create periods, lock periods, attempt posting to closed period (should fail), close fiscal year | | |

**Test Coverage:** 18 tests (8 unit, 6 feature, 3 integration, 1 performance)

---

## 3. Alternatives

- **ALT-001**: Use filesystem-based caching instead of Redis for period validation - Rejected: Redis provides better performance and distributed caching support; filesystem cache may not meet < 10ms requirement
- **ALT-002**: Store period lock status in separate table instead of JSONB locked_modules column - Rejected: JSONB provides simpler schema and efficient querying with PostgreSQL GIN indexes
- **ALT-003**: Allow fiscal year overlap for different entities - Rejected: Business requirement BR-BO-003 explicitly prohibits overlaps; overlaps would complicate period validation logic
- **ALT-004**: Auto-close periods at month-end via scheduled task - Rejected: Period closing requires administrator approval and audit; should not be automated without explicit action

---

## 4. Dependencies

### Module Dependencies

- **DEP-001**: SUB01 (Multi-Tenancy) - Tenant model, tenant_id foreign keys, tenant scope validation
- **DEP-002**: SUB02 (Authentication & Authorization) - User model, role-based access control, admin role requirement
- **DEP-003**: SUB03 (Audit Logging) - ActivityLoggerContract for all administrative actions
- **DEP-004**: SUB15-PLAN01 (Organizational Foundation) - Company model, company_id foreign key
- **DEP-005**: SUB08 (General Ledger) - GLTransactionCreatedEvent for period validation (optional integration)
- **DEP-006**: SUB11 (Accounts Payable) - APInvoiceCreatedEvent for period validation (optional integration)
- **DEP-007**: SUB12 (Accounts Receivable) - ARInvoiceCreatedEvent for period validation (optional integration)

### Package Dependencies

- **DEP-008**: PHP ^8.2 - Required for enums, readonly properties
- **DEP-009**: Laravel Framework ^12.0 - Core framework
- **DEP-010**: lorisleiva/laravel-actions ^2.0 - Action pattern
- **DEP-011**: Redis 6+ - For period validation caching
- **DEP-012**: PostgreSQL 14+ - For JSONB support (locked_modules column)

---

## 5. Files

### Models & Migrations

- **packages/backoffice/src/Models/FiscalYear.php**: Fiscal year model with lifecycle management
- **packages/backoffice/src/Models/AccountingPeriod.php**: Accounting period model with module-specific locking
- **packages/backoffice/src/Models/PeriodLockHistory.php**: Period lock audit history
- **packages/backoffice/database/migrations/2025_01_01_000002_create_fiscal_years_table.php**: Fiscal years table migration
- **packages/backoffice/database/migrations/2025_01_01_000003_create_accounting_periods_table.php**: Accounting periods table migration
- **packages/backoffice/database/migrations/2025_01_01_000006_create_period_lock_history_table.php**: Period lock history table migration

### Enums

- **packages/backoffice/src/Enums/FiscalYearStatus.php**: Fiscal year status enum (OPEN, CLOSED)
- **packages/backoffice/src/Enums/PeriodStatus.php**: Period status enum (OPEN, CLOSED)
- **packages/backoffice/src/Enums/LockableModule.php**: Modules that support period locking

### Repositories & Contracts

- **packages/backoffice/src/Contracts/FiscalYearRepositoryContract.php**: Fiscal year repository interface
- **packages/backoffice/src/Repositories/FiscalYearRepository.php**: Fiscal year repository implementation
- **packages/backoffice/src/Contracts/AccountingPeriodRepositoryContract.php**: Accounting period repository interface
- **packages/backoffice/src/Repositories/AccountingPeriodRepository.php**: Accounting period repository implementation
- **packages/backoffice/src/Contracts/PeriodValidationContract.php**: Period validation service interface

### Services

- **packages/backoffice/src/Services/PeriodValidationService.php**: High-performance period validation with Redis caching

### Actions

- **packages/backoffice/src/Actions/CreateFiscalYearAction.php**: Create fiscal year with validation
- **packages/backoffice/src/Actions/CloseFiscalYearAction.php**: Close fiscal year (all periods must be closed)
- **packages/backoffice/src/Actions/ReopenFiscalYearAction.php**: Reopen closed fiscal year
- **packages/backoffice/src/Actions/LockPeriodAction.php**: Lock accounting period for specific module
- **packages/backoffice/src/Actions/UnlockPeriodAction.php**: Unlock accounting period for specific module
- **packages/backoffice/src/Actions/ClosePeriodAction.php**: Close accounting period
- **packages/backoffice/src/Actions/ReopenPeriodAction.php**: Reopen closed accounting period

### Events & Listeners

- **packages/backoffice/src/Events/FiscalYearCreatedEvent.php**: Dispatched when fiscal year created
- **packages/backoffice/src/Events/FiscalYearClosedEvent.php**: Dispatched when fiscal year closed
- **packages/backoffice/src/Events/FiscalYearReopenedEvent.php**: Dispatched when fiscal year reopened
- **packages/backoffice/src/Events/PeriodLockedEvent.php**: Dispatched when period locked for module
- **packages/backoffice/src/Events/PeriodUnlockedEvent.php**: Dispatched when period unlocked
- **packages/backoffice/src/Events/PeriodClosedEvent.php**: Dispatched when period closed
- **packages/backoffice/src/Events/PeriodReopenedEvent.php**: Dispatched when period reopened
- **packages/backoffice/src/Listeners/InvalidatePeriodCacheListener.php**: Invalidates Redis cache when period status changes
- **packages/backoffice/src/Listeners/ValidateGLPeriodBeforePostingListener.php**: Validates period before GL posting
- **packages/backoffice/src/Listeners/ValidateAPPeriodBeforePostingListener.php**: Validates period before AP posting
- **packages/backoffice/src/Listeners/ValidateARPeriodBeforePostingListener.php**: Validates period before AR posting

### Controllers & Form Requests

- **packages/backoffice/src/Http/Controllers/FiscalYearController.php**: Fiscal year API controller
- **packages/backoffice/src/Http/Controllers/AccountingPeriodController.php**: Accounting period API controller
- **packages/backoffice/src/Http/Requests/StoreFiscalYearRequest.php**: Fiscal year creation validation
- **packages/backoffice/src/Http/Requests/UpdateFiscalYearRequest.php**: Fiscal year update validation
- **packages/backoffice/src/Http/Requests/LockPeriodRequest.php**: Period locking validation

### API Resources

- **packages/backoffice/src/Http/Resources/FiscalYearResource.php**: Fiscal year JSON transformation
- **packages/backoffice/src/Http/Resources/AccountingPeriodResource.php**: Accounting period JSON transformation
- **packages/backoffice/src/Http/Resources/PeriodLockHistoryResource.php**: Period lock history JSON transformation

### Policies

- **packages/backoffice/src/Policies/FiscalYearPolicy.php**: Fiscal year authorization policy
- **packages/backoffice/src/Policies/AccountingPeriodPolicy.php**: Accounting period authorization policy

### Middleware

- **packages/backoffice/src/Http/Middleware/ValidateTransactionPeriodMiddleware.php**: Middleware for period validation in transactional routes

### Tests

- **packages/backoffice/tests/Unit/Models/FiscalYearTest.php**: Fiscal year model unit tests
- **packages/backoffice/tests/Unit/Models/AccountingPeriodTest.php**: Accounting period model unit tests
- **packages/backoffice/tests/Unit/Actions/CreateFiscalYearActionTest.php**: Create fiscal year action tests
- **packages/backoffice/tests/Unit/Actions/CloseFiscalYearActionTest.php**: Close fiscal year action tests
- **packages/backoffice/tests/Unit/Actions/LockPeriodActionTest.php**: Lock period action tests
- **packages/backoffice/tests/Unit/Services/PeriodValidationServiceTest.php**: Period validation service tests
- **packages/backoffice/tests/Feature/FiscalYearManagementTest.php**: Fiscal year API feature tests
- **packages/backoffice/tests/Feature/AccountingPeriodManagementTest.php**: Period management API feature tests
- **packages/backoffice/tests/Integration/PeriodValidationIntegrationTest.php**: Period validation integration tests
- **packages/backoffice/tests/Integration/TransactionalModuleIntegrationTest.php**: Cross-module integration tests
- **packages/backoffice/tests/Performance/PeriodValidationPerformanceTest.php**: Performance tests for < 10ms requirement

### Documentation

- **docs/guides/fiscal-year-management.md**: Fiscal year management guide
- **docs/guides/period-validation.md**: Period validation integration guide
- **docs/api/backoffice-api.md**: Updated API documentation with fiscal/period endpoints

---

## 6. Testing

### Unit Tests (34 tests)

- **TEST-001**: FiscalYear model factory creates valid instances with correct defaults
- **TEST-002**: FiscalYear relationships (company, periods, closedByUser) load correctly
- **TEST-003**: FiscalYear scopes (open, closed, current) filter correctly
- **TEST-004**: FiscalYearRepository checkOverlap() detects overlapping date ranges
- **TEST-005**: CreateFiscalYearAction validates end_date > start_date
- **TEST-006**: CreateFiscalYearAction throws ValidationException for overlapping years
- **TEST-007**: CreateFiscalYearAction throws AuthorizationException for non-admin users
- **TEST-008**: CloseFiscalYearAction verifies all periods are closed before allowing close
- **TEST-009**: CloseFiscalYearAction sets closed_by and closed_at timestamps
- **TEST-010**: CloseFiscalYearAction dispatches FiscalYearClosedEvent
- **TEST-011**: ReopenFiscalYearAction clears closed_by and closed_at
- **TEST-012**: AccountingPeriod isLockedForModule() returns true when module in locked_modules array
- **TEST-013**: AccountingPeriod lockModule() adds module to locked_modules
- **TEST-014**: AccountingPeriod unlockModule() removes module from locked_modules
- **TEST-015**: AccountingPeriodRepository getPeriodByDate() returns period containing date
- **TEST-016**: AccountingPeriodRepository isModuleLocked() checks JSONB column correctly
- **TEST-017**: LockPeriodAction adds module to locked_modules array
- **TEST-018**: LockPeriodAction creates PeriodLockHistory record with action='locked'
- **TEST-019**: LockPeriodAction dispatches PeriodLockedEvent
- **TEST-020**: UnlockPeriodAction removes module from locked_modules
- **TEST-021**: ClosePeriodAction sets status to CLOSED
- **TEST-022**: ReopenPeriodAction verifies fiscal year is open before reopening
- **TEST-023**: PeriodValidationService validateTransaction() returns true for open period
- **TEST-024**: PeriodValidationService returns false for closed period
- **TEST-025**: PeriodValidationService returns false when module is locked
- **TEST-026**: PeriodValidationService caches result in Redis with 900s TTL
- **TEST-027**: PeriodValidationService uses cached result on subsequent calls
- **TEST-028**: PeriodValidationService invalidateCache() clears all matching keys
- **TEST-029**: PeriodValidationService getPeriodStatus() returns complete period info
- **TEST-030**: InvalidatePeriodCacheListener clears cache when PeriodLockedEvent fired
- **TEST-031**: ValidateGLPeriodBeforePostingListener throws ValidationException for closed period
- **TEST-032**: FiscalYearPolicy allows only admins to create fiscal years
- **TEST-033**: AccountingPeriodPolicy allows only admins to lock periods
- **TEST-034**: Period lock history records include user and timestamp

### Feature Tests (32 tests)

- **TEST-035**: Can create fiscal year via API with valid data
- **TEST-036**: Cannot create fiscal year with overlapping dates
- **TEST-037**: Cannot create fiscal year with end_date < start_date
- **TEST-038**: Can list fiscal years via API with pagination
- **TEST-039**: Can close fiscal year via API when all periods closed
- **TEST-040**: Cannot close fiscal year when periods still open
- **TEST-041**: Can reopen fiscal year via API
- **TEST-042**: Non-admin cannot create fiscal year (403 Forbidden)
- **TEST-043**: Non-admin cannot close fiscal year (403 Forbidden)
- **TEST-044**: Can lock accounting period for specific module via API
- **TEST-045**: Can unlock accounting period via API
- **TEST-046**: Can lock multiple modules for same period
- **TEST-047**: Period lock creates history record visible in API
- **TEST-048**: Can close accounting period via API
- **TEST-049**: Can reopen accounting period via API
- **TEST-050**: Cannot reopen period when fiscal year is closed
- **TEST-051**: Can retrieve period lock history via API
- **TEST-052**: Period validation endpoint returns correct validation status
- **TEST-053**: Current period endpoint returns open period for company
- **TEST-054**: ValidateTransactionPeriodMiddleware blocks posting to closed period
- **TEST-055**: Fiscal year resource includes periods count
- **TEST-056**: Fiscal year resource includes closed_by user details
- **TEST-057**: Accounting period resource includes locked_modules array
- **TEST-058**: Accounting period resource includes fiscal year details
- **TEST-059**: Can filter fiscal years by company via API
- **TEST-060**: Can filter periods by fiscal year via API
- **TEST-061**: Can search fiscal years by year_code
- **TEST-062**: Authorization checks prevent cross-tenant access
- **TEST-063**: Closing fiscal year invalidates Redis cache
- **TEST-064**: Locking period invalidates Redis cache
- **TEST-065**: Activity log records all fiscal year actions
- **TEST-066**: Activity log records all period locking actions

### Integration Tests (15 tests)

- **TEST-067**: Tenant creation does not auto-create fiscal year (manual setup required)
- **TEST-068**: Period validation integrates with GL transaction posting
- **TEST-069**: Period validation integrates with AP invoice posting
- **TEST-070**: Period validation integrates with AR invoice posting
- **TEST-071**: Period validation respects module-specific locking
- **TEST-072**: GL can post when period open and GL not locked
- **TEST-073**: GL cannot post when period closed
- **TEST-074**: AP can post when AP module not locked
- **TEST-075**: AR cannot post when AR module locked
- **TEST-076**: Period validation works across different fiscal years
- **TEST-077**: Cache invalidation propagates to all validation calls
- **TEST-078**: Period validation uses Redis cache after first call
- **TEST-079**: Closing fiscal year closes all open periods
- **TEST-080**: Reopening fiscal year does not auto-reopen periods
- **TEST-081**: Period lock history maintains audit trail

### Performance Tests (3 tests)

- **TEST-082**: Period validation completes in < 10ms with Redis cache (PR-BO-002)
- **TEST-083**: Cache hit rate > 95% after warm-up
- **TEST-084**: Cache invalidation completes in < 100ms for 1000 keys

**Total Test Coverage:** 84 tests (34 unit, 32 feature, 15 integration, 3 performance)

---

## 7. Risks & Assumptions

### Risks

- **RISK-001**: Redis cache failure could impact period validation performance - Mitigation: Implement fallback to database query; monitor Redis health; set up Redis cluster for high availability
- **RISK-002**: Concurrent fiscal year close operations could cause race conditions - Mitigation: Use database transactions with pessimistic locking; implement queue-based closing for large datasets
- **RISK-003**: Large number of transactional modules may slow cache invalidation - Mitigation: Use Redis pipelining for batch key deletion; implement async cache invalidation via queue
- **RISK-004**: Period overlap validation may fail with concurrent fiscal year creation - Mitigation: Use unique database constraint; implement retry logic with exponential backoff
- **RISK-005**: Module-specific locking complexity may confuse users - Mitigation: Provide clear UI/UX guidance; implement bulk locking API endpoint; create comprehensive documentation

### Assumptions

- **ASSUMPTION-001**: All transactional modules will adopt event-driven architecture for integration
- **ASSUMPTION-002**: Redis is available and properly configured in all environments
- **ASSUMPTION-003**: System administrators understand fiscal year closing procedures
- **ASSUMPTION-004**: Period validation latency < 10ms is acceptable for user experience
- **ASSUMPTION-005**: 900-second (15-minute) cache TTL balances freshness and performance
- **ASSUMPTION-006**: PostgreSQL JSONB performance is acceptable for locked_modules queries
- **ASSUMPTION-007**: Fiscal years follow standard calendar or fiscal year patterns (12 periods)
- **ASSUMPTION-008**: Period reopening is rare and requires explicit administrator action

---

## 8. KIV for Future Implementations

- **KIV-001**: Auto-generation of accounting periods when fiscal year created (create 12 monthly periods automatically)
- **KIV-002**: Fiscal year templates for different industries (e.g., retail fiscal year, academic year)
- **KIV-003**: Partial period closing (close specific modules while keeping others open)
- **KIV-004**: Scheduled period closing automation with approval workflow
- **KIV-005**: Period closing checklist and validation rules per module
- **KIV-006**: Financial calendar management (trading days, holidays, period adjustment rules)
- **KIV-007**: Multi-company consolidated fiscal year management
- **KIV-008**: Period closing notification system (email, Slack, SMS)
- **KIV-009**: Period validation bypass for emergency corrections with audit logging
- **KIV-010**: Historical period reopening with approval and time limits

---

## 9. Related PRD / Further Reading

- **Primary PRD:** [PRD01-SUB15-BACKOFFICE.md](../prd/prd-01/PRD01-SUB15-BACKOFFICE.md) - Complete Backoffice module requirements
- **Previous Plan:** [PRD01-SUB15-PLAN01-implement-organizational-foundation.md](./PRD01-SUB15-PLAN01-implement-organizational-foundation.md) - Company and organizational hierarchy
- **Next Plan:** [PRD01-SUB15-PLAN03-implement-workflows-document-numbering.md](./PRD01-SUB15-PLAN03-implement-workflows-document-numbering.md) - Approval workflows and document numbering
- **Related Modules:**
  - [PRD01-SUB08-GENERAL-LEDGER.md](../prd/prd-01/PRD01-SUB08-GENERAL-LEDGER.md) - GL integration for period validation
  - [PRD01-SUB11-ACCOUNTS-PAYABLE.md](../prd/prd-01/PRD01-SUB11-ACCOUNTS-PAYABLE.md) - AP integration
  - [PRD01-SUB12-ACCOUNTS-RECEIVABLE.md](../prd/prd-01/PRD01-SUB12-ACCOUNTS-RECEIVABLE.md) - AR integration
- **Architecture Documentation:**
  - [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md) - Project coding standards
  - [PACKAGE-DECOUPLING-STRATEGY.md](../architecture/PACKAGE-DECOUPLING-STRATEGY.md) - Package decoupling approach
- **External References:**
  - [Laravel Caching Documentation](https://laravel.com/docs/cache) - Redis caching best practices
  - [PostgreSQL JSONB Documentation](https://www.postgresql.org/docs/current/datatype-json.html) - JSONB column usage
  - [Laravel Event Documentation](https://laravel.com/docs/events) - Event-driven architecture

---

**Implementation Ready:** This plan is ready for development. All tasks are deterministic, testable, and traceable to requirements.

**Estimated Effort:** 3-4 weeks (1 developer)

**Next Steps:** Review and approve PLAN02, then proceed to create PLAN03 for Approval Workflows & Document Numbering.
