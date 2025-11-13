---
plan: Implement Serial Numbering System
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, infrastructure, serial-numbering, document-management, sequences, core-infrastructure]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan covers the comprehensive Serial Numbering System for the Laravel ERP, providing automated document numbering with configurable patterns, tenant-specific sequences, and collision-free ID generation. This system ensures unique, sequential, human-readable identifiers for all business documents (invoices, purchase orders, receipts, etc.) with support for complex formatting rules, reset periods, and high-concurrency environments.

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-SN-001**: Support Configurable Serial Number Patterns with variables: {YEAR}, {MONTH}, {DAY}, {COUNTER}, {PREFIX}, {TENANT}, {DEPARTMENT}
- **REQ-FR-SN-002**: Provide centralized sequence management API for all modules requiring auto-numbering
- **REQ-FR-SN-003**: Provide Reset Periods (daily, monthly, yearly, never) for counter sequences
- **REQ-FR-SN-004**: Support manual number override for exceptional cases with audit logging
- **REQ-FR-SN-005**: Provide sequence preview to show next generated number without consuming it
- **REQ-BR-SN-001**: Serial numbers MUST be unique within their sequence and tenant scope
- **REQ-BR-SN-002**: Counter MUST be zero-padded to configured width (e.g., 001, 0001)
- **REQ-BR-SN-003**: Number generation MUST be atomic and transaction-safe
- **REQ-BR-SN-004**: Failed transactions MUST NOT consume sequence numbers
- **REQ-BR-SN-005**: Pattern variables MUST be evaluated at generation time, not configuration time
- **REQ-DR-SN-001**: Sequence configuration MUST store: tenant_id, sequence_name, pattern, reset_period, padding, current_value
- **REQ-DR-SN-002**: Generated numbers MUST be logged with: timestamp, tenant_id, sequence_name, generated_number, causer_id
- **REQ-DR-SN-003**: Sequence state MUST support optimistic locking for concurrent updates
- **REQ-IR-SN-001**: Provide sequence generation API for all modules (invoices, POs, items, etc.)
- **REQ-IR-SN-002**: Integrate with SUB03 (Audit Logging) for sequence generation tracking
- **REQ-PR-SN-001**: Number generation MUST complete in < 50ms for 95th percentile
- **REQ-PR-SN-002**: System MUST support 100 concurrent number generations without collisions
- **REQ-SR-SN-001**: Enforce Race Condition Prevention using database-level atomic locking (SELECT FOR UPDATE)
- **REQ-SR-SN-002**: Tenant isolation MUST prevent cross-tenant sequence access
- **REQ-SR-SN-003**: Manual overrides MUST require admin permission and be fully audited
- **REQ-SCR-SN-001**: Support 1000+ sequences per tenant with sub-10ms lookup time
- **REQ-ARCH-SN-001**: Use database row-level locking (SELECT FOR UPDATE) for atomic counter increment
- **REQ-ARCH-SN-002**: Implement transaction-safe number generation with automatic rollback on failure
- **REQ-EV-SN-001**: Dispatch SequenceGeneratedEvent when new serial number is generated
- **REQ-EV-SN-002**: Dispatch SequenceResetEvent when sequence counter is reset
- **REQ-EV-SN-003**: Dispatch SequenceOverriddenEvent when manual override is used

### Security Constraints

- **SEC-001**: Sequence generation must use SELECT FOR UPDATE to prevent race conditions
- **SEC-002**: Manual override must verify 'override-sequence-numbers' permission
- **SEC-003**: Sequence configuration must be tenant-scoped - no cross-tenant access
- **SEC-004**: Generated numbers must be logged to audit trail with causer information

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: All number generation must occur within database transactions
- **GUD-003**: Use Laravel 12+ conventions (anonymous migrations, modern factory syntax)
- **GUD-004**: Follow PSR-12 coding standards, enforced by Laravel Pint
- **GUD-005**: Pattern variables must be case-insensitive for consistency

### Patterns to Follow

- **PAT-001**: Use Action pattern for sequence operations (GenerateSerialNumberAction, ResetSequenceAction)
- **PAT-002**: Use Repository pattern for sequence storage with row-level locking
- **PAT-003**: Use Service pattern for pattern parsing and variable evaluation
- **PAT-004**: Use Events for sequence lifecycle notifications (generated, reset, overridden)
- **PAT-005**: Use Policy pattern for authorization (can generate, can override, can reset)

### Constraints

- **CON-001**: Must support PostgreSQL 14+ and MySQL 8.0+ with row-level locking
- **CON-002**: Pattern variables must be evaluated in single query to avoid multiple DB hits
- **CON-003**: Package must be installable independently via Composer
- **CON-004**: Counter padding must support 1-10 digits (e.g., {COUNTER:5} = 00001)
- **CON-005**: Sequence names must be unique per tenant (composite unique constraint)

## 2. Implementation Steps

### GOAL-001: Package Setup and Database Schema

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-SN-001, DR-SN-002, ARCH-SN-001 | Set up serial-numbering package structure with Composer, create database schema for sequence configurations and generation logs with support for row-level locking and optimistic locking. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create package directory structure: `packages/serial-numbering/` with subdirectories `src/`, `database/migrations/`, `database/seeders/`, `config/`, `tests/`. Initialize `composer.json` with package name `azaharizaman/erp-serial-numbering`, namespace `Nexus\Erp\SerialNumbering`, require Laravel 12+, PHP 8.2+. | | |
| TASK-002 | Create migration `database/migrations/create_serial_number_sequences_table.php` (anonymous class): Define `serial_number_sequences` table with columns: id (BIGSERIAL), tenant_id (UUID/BIGINT, indexed, NOT NULL), sequence_name (VARCHAR(255), NOT NULL), pattern (VARCHAR(500), NOT NULL), reset_period (ENUM: 'never', 'daily', 'monthly', 'yearly'), padding (TINYINT, default 5), current_value (BIGINT, default 0), last_reset_at (TIMESTAMP, nullable), created_at, updated_at, version (INT, default 0 for optimistic locking). Add unique constraint on (tenant_id, sequence_name). Add index on (tenant_id, sequence_name). | | |
| TASK-003 | Create migration `database/migrations/create_serial_number_logs_table.php` (anonymous class): Define `serial_number_logs` table with columns: id (BIGSERIAL), tenant_id (UUID/BIGINT, indexed, NOT NULL), sequence_name (VARCHAR(255), indexed), generated_number (VARCHAR(255), indexed), causer_type (VARCHAR(255), nullable), causer_id (BIGINT, nullable), metadata (JSONB, nullable for additional context), created_at (TIMESTAMP, indexed). This table is append-only for audit trail. | | |
| TASK-004 | Create `config/serial-numbering.php` configuration file with settings: enabled (bool, default true), default_padding (int, default 5), default_reset_period (string, default 'yearly'), enable_override (bool, default false), log_generations (bool, default true), cache_sequences (bool, default true, cache sequence configs in Redis), cache_ttl (int, default 3600 seconds). | | |
| TASK-005 | Create `src/SerialNumberingServiceProvider.php`: Register config, migrations, service bindings. Bind `SequenceRepositoryContract` to `DatabaseSequenceRepository`. Bind `PatternParserContract` to `PatternParserService`. Register routes for API endpoints. Publish config and migrations. | | |

### GOAL-002: Pattern Engine and Variable Evaluation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SN-001, BR-SN-002, BR-SN-005 | Implement pattern parsing engine to evaluate variables ({YEAR}, {MONTH}, {COUNTER}, etc.) at generation time with zero-padding, tenant/department context, and validation. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-006 | Create `src/Contracts/PatternParserContract.php` interface: Define methods: `parse(string $pattern, array $context): string` (evaluates pattern with context variables), `validate(string $pattern): bool` (validates pattern syntax), `getVariables(string $pattern): array` (extracts variables from pattern), `preview(string $pattern, array $context): string` (shows sample output without consuming counter). All methods must have PHPDoc with parameter types and return types. | | |
| TASK-007 | Create `src/Services/PatternParserService.php` implementing `PatternParserContract`: Implement `parse()` to find all pattern variables (regex: `/{([A-Z]+)(:(\d+))?}/i`), evaluate each: {YEAR} => date('Y'), {YEAR:2} => date('y'), {MONTH} => date('m'), {DAY} => date('d'), {COUNTER} => $context['counter'] padded, {COUNTER:8} => $context['counter'] padded to 8 digits, {PREFIX} => $context['prefix'], {TENANT} => $context['tenant_code'], {DEPARTMENT} => $context['department_code']. Return evaluated string. | | |
| TASK-008 | Implement `validate()` in `PatternParserService`: Check pattern syntax using regex, ensure all variables are recognized (whitelist: YEAR, MONTH, DAY, COUNTER, PREFIX, TENANT, DEPARTMENT), verify COUNTER has valid padding (1-10), return true if valid, false otherwise. Throw `InvalidPatternException` with descriptive message if invalid. | | |
| TASK-009 | Implement `getVariables()` in `PatternParserService`: Use regex to extract all variables from pattern, return array of variable names (e.g., ['YEAR', 'COUNTER:5', 'PREFIX']). This helps UI show required context and preview. | | |
| TASK-010 | Implement `preview()` in `PatternParserService`: Similar to `parse()` but uses sample counter value (e.g., 1) instead of consuming actual sequence counter. Show example: Pattern "INV-{YEAR}-{COUNTER:5}" => Preview "INV-2025-00001". This allows users to test patterns before saving. | | |

### GOAL-003: Atomic Sequence Generation with Row-Level Locking

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SN-002, BR-SN-001, BR-SN-003, BR-SN-004, PR-SN-001, PR-SN-002, SR-SN-001, ARCH-SN-001, ARCH-SN-002 | Implement transaction-safe sequence generation with database row-level locking (SELECT FOR UPDATE) to prevent race conditions and ensure unique number generation under high concurrency. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-011 | Create `src/Contracts/SequenceRepositoryContract.php` interface: Define methods: `find(string $tenantId, string $sequenceName): ?Sequence` (retrieves sequence config), `lockAndIncrement(string $tenantId, string $sequenceName): int` (locks row, increments counter, returns new value atomically), `create(array $data): Sequence` (creates new sequence config), `update(Sequence $sequence, array $data): bool` (updates sequence config), `reset(string $tenantId, string $sequenceName): void` (resets counter to 0). | | |
| TASK-012 | Create `src/Repositories/DatabaseSequenceRepository.php` implementing `SequenceRepositoryContract`: Use Eloquent model `Sequence`. Implement `lockAndIncrement()` within database transaction: `DB::transaction(function() { $sequence = Sequence::where('tenant_id', $tenantId)->where('sequence_name', $sequenceName)->lockForUpdate()->firstOrFail(); $sequence->current_value++; $sequence->save(); return $sequence->current_value; })`. This uses SELECT FOR UPDATE to lock the row, preventing concurrent increments. | | |
| TASK-013 | Create `src/Actions/GenerateSerialNumberAction.php` using AsAction trait: In `handle(string $tenantId, string $sequenceName, array $context = []): string`, check if sequence needs reset based on reset_period (call `shouldReset()` helper), if yes, call `SequenceRepositoryContract::reset()`. Then call `lockAndIncrement()` to get next counter atomically. Build context array with counter, tenant_code, prefix, etc. Call `PatternParserContract::parse($pattern, $context)` to generate final number. Log to `serial_number_logs` table. Dispatch `SequenceGeneratedEvent`. Return generated number. Wrap entire logic in DB transaction for rollback safety. | | |
| TASK-014 | Implement `shouldReset()` helper in `GenerateSerialNumberAction`: Check `$sequence->reset_period` and `$sequence->last_reset_at`. If reset_period is 'daily' and last_reset_at is not today, return true. If 'monthly' and not this month, return true. If 'yearly' and not this year, return true. If 'never', return false. This ensures counter resets at correct intervals. | | |
| TASK-015 | Create `src/Actions/PreviewSerialNumberAction.php` using AsAction trait: In `handle(string $tenantId, string $sequenceName): string`, retrieve sequence config, get current_value + 1 (don't increment), build context, call `PatternParserContract::preview()`. Return preview string WITHOUT consuming counter. This allows UI to show "Next number will be: INV-2025-00123". | | |

### GOAL-004: Sequence Reset Logic and Manual Override

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SN-003, FR-SN-004, BR-SN-004, SR-SN-003, EV-SN-002, EV-SN-003 | Implement automated sequence reset based on configured periods (daily, monthly, yearly) and manual override capability with admin authorization and audit logging. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-016 | Create `src/Actions/ResetSequenceAction.php` using AsAction trait: In `handle(string $tenantId, string $sequenceName, string $reason): void`, check authorization (Gate::authorize('reset-sequence')), retrieve sequence, set current_value to 0, set last_reset_at to now(), save. Log reset action to audit log via SUB03 integration. Dispatch `SequenceResetEvent` with tenant_id, sequence_name, reason, reset_at. | | |
| TASK-017 | Implement automated reset check in `GenerateSerialNumberAction`: Before incrementing counter, check if reset is needed using `shouldReset()`. If true, call `SequenceRepositoryContract::reset()`, set last_reset_at, then proceed with generation. This ensures counter resets automatically on first generation after period boundary (e.g., first invoice of new year resets yearly counter). | | |
| TASK-018 | Create `src/Actions/OverrideSerialNumberAction.php` using AsAction trait: In `handle(string $tenantId, string $sequenceName, string $overrideNumber): void`, check authorization (Gate::authorize('override-sequence-number')), validate override number format matches sequence pattern (use `PatternParserContract::validate()`), check uniqueness (query serial_number_logs to ensure override number not already used), log to serial_number_logs with metadata indicating manual override. Dispatch `SequenceOverriddenEvent`. This allows admins to set specific number for exceptional cases (e.g., starting from specific number, filling gap). | | |
| TASK-019 | Create Policy `src/Policies/SequencePolicy.php`: Implement methods: `generate(User $user, Sequence $sequence): bool` checks user has permission to generate numbers (default: true for authenticated users in same tenant), `reset(User $user, Sequence $sequence): bool` checks 'reset-sequence' permission (admin only), `override(User $user, Sequence $sequence): bool` checks 'override-sequence-number' permission (super-admin only). Register policy in SerialNumberingServiceProvider. | | |
| TASK-020 | Create `src/Events/SequenceResetEvent.php`: Event class with properties: tenant_id, sequence_name, reason (string, e.g., "yearly reset", "manual admin reset"), reset_at (Carbon), previous_value (int). Dispatched when sequence counter is reset. Allows other modules to react (e.g., notify admins, update analytics). | | |

### GOAL-005: API Endpoints and Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SN-002, FR-SN-005, IR-SN-001, IR-SN-002, EV-SN-001 | Build RESTful API endpoints for sequence management (CRUD, generate, preview, reset, override) and integrate with audit logging system and multi-tenancy. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-021 | Create `src/Http/Controllers/SequenceController.php`: Implement methods: `index(Request $request): JsonResponse` (list all sequences for current tenant), `store(CreateSequenceRequest $request): JsonResponse` (create new sequence config), `show(string $sequenceName): JsonResponse` (get sequence details), `update(UpdateSequenceRequest $request, string $sequenceName): JsonResponse` (update sequence config), `destroy(string $sequenceName): JsonResponse` (delete sequence - soft delete only if no generated numbers). Apply `auth:sanctum` and `tenant` middleware. | | |
| TASK-022 | Implement `generate()` endpoint in SequenceController: `generate(Request $request, string $sequenceName): JsonResponse` handles POST /api/v1/sequences/{sequenceName}/generate. Validate input (context variables if needed), call `GenerateSerialNumberAction::run()`, return JSON with generated number and metadata. This is the primary API for modules to generate numbers. | | |
| TASK-023 | Implement `preview()` endpoint in SequenceController: `preview(string $sequenceName): JsonResponse` handles GET /api/v1/sequences/{sequenceName}/preview. Call `PreviewSerialNumberAction::run()`, return JSON with preview number. This allows UI to show "Next number will be: X" without consuming counter. | | |
| TASK-024 | Implement `reset()` endpoint in SequenceController: `reset(Request $request, string $sequenceName): JsonResponse` handles POST /api/v1/sequences/{sequenceName}/reset. Require reason in request body. Check authorization (Gate::authorize('reset-sequence')). Call `ResetSequenceAction::run()`, return success response. | | |
| TASK-025 | Implement `override()` endpoint in SequenceController: `override(Request $request, string $sequenceName): JsonResponse` handles POST /api/v1/sequences/{sequenceName}/override. Require override_number in request body. Check authorization (Gate::authorize('override-sequence-number')). Call `OverrideSerialNumberAction::run()`, return success response. | | |
| TASK-026 | Create Form Requests: `CreateSequenceRequest.php` (validation: sequence_name required|unique per tenant, pattern required|valid syntax, reset_period in enum, padding 1-10), `UpdateSequenceRequest.php` (same validation, all fields optional). Authorization: check user belongs to tenant, has 'manage-sequences' permission. | | |
| TASK-027 | Create API Resource `src/Http/Resources/SequenceResource.php`: Transform Sequence model to JSON:API format with fields: id, tenant_id (only if super-admin), sequence_name, pattern, reset_period, padding, current_value, last_reset_at, preview (computed using PreviewSerialNumberAction), created_at, updated_at. | | |
| TASK-028 | Integrate with SUB03 Audit Logging: In `GenerateSerialNumberAction`, after successful generation, log activity via `ActivityLoggerContract::log('Serial number generated', $sequence, auth()->user(), ['generated_number' => $number])`. In `OverrideSerialNumberAction`, log override with full context. In `ResetSequenceAction`, log reset with reason. | | |
| TASK-029 | Integrate with SUB01 Multi-Tenancy: Create middleware `src/Http/Middleware/InjectTenantToSequences.php` to automatically inject tenant_id from current tenant context. Register middleware in routes. Ensure all sequence operations are tenant-scoped automatically. | | |
| TASK-030 | Add API routes in SerialNumberingServiceProvider: `Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/v1/sequences')->group(function() { Route::get('/', [SequenceController::class, 'index']); Route::post('/', [SequenceController::class, 'store'])->middleware('can:manage-sequences'); Route::get('/{sequence}', [SequenceController::class, 'show']); Route::patch('/{sequence}', [SequenceController::class, 'update'])->middleware('can:manage-sequences'); Route::delete('/{sequence}', [SequenceController::class, 'destroy'])->middleware('can:manage-sequences'); Route::post('/{sequence}/generate', [SequenceController::class, 'generate']); Route::get('/{sequence}/preview', [SequenceController::class, 'preview']); Route::post('/{sequence}/reset', [SequenceController::class, 'reset'])->middleware('can:reset-sequence'); Route::post('/{sequence}/override', [SequenceController::class, 'override'])->middleware('can:override-sequence-number'); })`. | | |

## 3. Alternatives

- **ALT-001**: Use UUID instead of sequential numbers for document IDs
  - *Pros*: No collision risk, truly unique globally, no locking needed
  - *Cons*: Not human-readable, no business meaning, doesn't meet regulatory requirements for sequential invoicing
  - *Decision*: Not chosen - sequential numbers required for compliance and usability

- **ALT-002**: Implement Redis-based atomic counter instead of database row locking
  - *Pros*: Faster performance, simpler implementation, built-in atomic INCR
  - *Cons*: Redis failure loses counter state, not persistent, harder to audit, adds infrastructure dependency
  - *Decision*: Not chosen for MVP - database locking more reliable and auditable; Redis can be added as alternative driver later

- **ALT-003**: Use stored procedures for atomic generation
  - *Pros*: Guaranteed atomicity at database level, excellent performance
  - *Cons*: Database-specific logic, harder to test, less portable across PostgreSQL/MySQL
  - *Decision*: Not chosen - SELECT FOR UPDATE achieves same atomicity with better portability

- **ALT-004**: Pre-allocate number ranges to reduce lock contention
  - *Pros*: Higher throughput under extreme concurrency, less database load
  - *Cons*: Gaps in sequence if range not fully used, more complex logic, harder to guarantee strict sequentiality
  - *Decision*: Not chosen for MVP - strict sequential numbering prioritized; range allocation can be future optimization if needed

## 4. Dependencies

**Package Dependencies:**
- None (pure Laravel implementation using Eloquent and database transactions)

**Internal Dependencies:**
- **azaharizaman/erp-multitenancy** (PRD01-SUB01) - Required for tenant context resolution
- **azaharizaman/erp-audit-logging** (PRD01-SUB03) - Optional for generation audit trail
- **azaharizaman/erp-settings-management** (PRD01-SUB05) - Optional for per-tenant configuration

**Infrastructure Dependencies:**
- PostgreSQL 14+ OR MySQL 8.0+ with row-level locking support
- Redis 6+ for optional sequence config caching (not for counter storage)

## 5. Files

**Configuration:**
- `packages/serial-numbering/config/serial-numbering.php` - Package configuration

**Database:**
- `packages/serial-numbering/database/migrations/create_serial_number_sequences_table.php` - Sequence configurations
- `packages/serial-numbering/database/migrations/create_serial_number_logs_table.php` - Generation audit log
- `packages/serial-numbering/database/seeders/DefaultSequenceSeeder.php` - Default sequences (Invoice, PO, etc.)

**Models:**
- `packages/serial-numbering/src/Models/Sequence.php` - Sequence configuration Eloquent model
- `packages/serial-numbering/src/Models/SerialNumberLog.php` - Generation log Eloquent model

**Contracts:**
- `packages/serial-numbering/src/Contracts/SequenceRepositoryContract.php` - Repository interface
- `packages/serial-numbering/src/Contracts/PatternParserContract.php` - Pattern parsing interface

**Repositories:**
- `packages/serial-numbering/src/Repositories/DatabaseSequenceRepository.php` - PostgreSQL/MySQL implementation

**Services:**
- `packages/serial-numbering/src/Services/PatternParserService.php` - Pattern evaluation engine

**Actions:**
- `packages/serial-numbering/src/Actions/GenerateSerialNumberAction.php` - Primary generation logic
- `packages/serial-numbering/src/Actions/PreviewSerialNumberAction.php` - Preview without consumption
- `packages/serial-numbering/src/Actions/ResetSequenceAction.php` - Manual/automated reset
- `packages/serial-numbering/src/Actions/OverrideSerialNumberAction.php` - Manual override

**Controllers:**
- `packages/serial-numbering/src/Http/Controllers/SequenceController.php` - API endpoints

**Requests:**
- `packages/serial-numbering/src/Http/Requests/CreateSequenceRequest.php` - Validation for creation
- `packages/serial-numbering/src/Http/Requests/UpdateSequenceRequest.php` - Validation for updates

**Resources:**
- `packages/serial-numbering/src/Http/Resources/SequenceResource.php` - JSON:API transformation
- `packages/serial-numbering/src/Http/Resources/SerialNumberLogResource.php` - Log transformation

**Middleware:**
- `packages/serial-numbering/src/Http/Middleware/InjectTenantToSequences.php` - Tenant context injection

**Policies:**
- `packages/serial-numbering/src/Policies/SequencePolicy.php` - Authorization policies

**Events:**
- `packages/serial-numbering/src/Events/SequenceGeneratedEvent.php` - Number generated notification
- `packages/serial-numbering/src/Events/SequenceResetEvent.php` - Counter reset notification
- `packages/serial-numbering/src/Events/SequenceOverriddenEvent.php` - Manual override notification

**Exceptions:**
- `packages/serial-numbering/src/Exceptions/InvalidPatternException.php` - Pattern validation errors
- `packages/serial-numbering/src/Exceptions/SequenceNotFoundException.php` - Sequence not found
- `packages/serial-numbering/src/Exceptions/DuplicateNumberException.php` - Number already exists

**Service Provider:**
- `packages/serial-numbering/src/SerialNumberingServiceProvider.php` - Package registration

## 6. Testing

**Unit Tests (15):**
- **TEST-001**: PatternParserService::parse() evaluates {YEAR} correctly (current year)
- **TEST-002**: PatternParserService::parse() evaluates {COUNTER:5} with zero-padding (00001)
- **TEST-003**: PatternParserService::parse() handles pattern "INV-{YEAR}-{MONTH}-{COUNTER:4}" correctly
- **TEST-004**: PatternParserService::validate() rejects invalid pattern with unknown variable
- **TEST-005**: PatternParserService::validate() rejects pattern with invalid padding (>10)
- **TEST-006**: PatternParserService::getVariables() extracts all variables from pattern
- **TEST-007**: PatternParserService::preview() shows sample output without consuming counter
- **TEST-008**: DatabaseSequenceRepository::lockAndIncrement() increments counter atomically
- **TEST-009**: GenerateSerialNumberAction checks reset_period and resets if needed (yearly boundary)
- **TEST-010**: GenerateSerialNumberAction generates unique number and logs to serial_number_logs
- **TEST-011**: ResetSequenceAction sets current_value to 0 and updates last_reset_at
- **TEST-012**: OverrideSerialNumberAction validates override number format matches pattern
- **TEST-013**: SequencePolicy::reset() requires 'reset-sequence' permission
- **TEST-014**: SequencePolicy::override() requires 'override-sequence-number' permission (super-admin)
- **TEST-015**: Sequence model enforces unique constraint on (tenant_id, sequence_name)

**Feature Tests (12):**
- **TEST-016**: POST /api/v1/sequences creates new sequence config with valid pattern
- **TEST-017**: POST /api/v1/sequences validates pattern syntax and rejects invalid patterns
- **TEST-018**: GET /api/v1/sequences lists all sequences for current tenant only
- **TEST-019**: POST /api/v1/sequences/{sequence}/generate returns unique sequential number
- **TEST-020**: POST /api/v1/sequences/{sequence}/generate increments counter atomically
- **TEST-021**: GET /api/v1/sequences/{sequence}/preview returns next number without consuming
- **TEST-022**: POST /api/v1/sequences/{sequence}/reset resets counter to 0 (admin only, 403 without permission)
- **TEST-023**: POST /api/v1/sequences/{sequence}/override sets specific number (super-admin only)
- **TEST-024**: Sequence generation respects yearly reset period (resets on Jan 1)
- **TEST-025**: Sequence generation respects monthly reset period (resets on 1st of month)
- **TEST-026**: Tenant A cannot generate numbers from Tenant B's sequences (tenant isolation)
- **TEST-027**: Generated numbers are logged to serial_number_logs with causer information

**Integration Tests (8):**
- **TEST-028**: 100 concurrent number generations produce 100 unique numbers with no collisions
- **TEST-029**: Failed transaction rollback does not consume sequence counter
- **TEST-030**: SequenceGeneratedEvent is dispatched after successful generation
- **TEST-031**: SequenceResetEvent is dispatched when counter is reset
- **TEST-032**: SequenceOverriddenEvent is dispatched when manual override is used
- **TEST-033**: Audit log receives generation activity via SUB03 integration
- **TEST-034**: Multi-tenancy middleware injects tenant_id automatically into sequence operations
- **TEST-035**: Sequence reset occurs automatically on first generation after period boundary

**Performance Tests (5):**
- **TEST-036**: Number generation completes in < 50ms for 95th percentile (including lock and log)
- **TEST-037**: 100 concurrent generations complete without deadlock or timeout
- **TEST-038**: Sequence lookup uses index on (tenant_id, sequence_name) - verify with EXPLAIN
- **TEST-039**: Pattern parsing completes in < 5ms (no complex regex)
- **TEST-040**: 1000 sequences per tenant with sub-10ms lookup time (cached in Redis)

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: High concurrency could cause lock contention and slow down generation
  - *Mitigation*: Use optimistic locking with version field, implement retry logic, monitor lock wait times
  
- **RISK-002**: Reset logic might fire incorrectly if system clock changes (timezone issues)
  - *Mitigation*: Store reset timestamps in UTC, use Carbon for timezone-aware comparisons, add tests for DST transitions
  
- **RISK-003**: Manual override could introduce gaps or duplicates if not validated properly
  - *Mitigation*: Strict validation against pattern, check uniqueness in logs table, require super-admin permission
  
- **RISK-004**: Database row locking could deadlock under extreme concurrent load
  - *Mitigation*: Set reasonable lock timeout (5 seconds), implement retry logic, monitor for deadlocks, alert on failures
  
- **RISK-005**: Pattern changes after numbers generated could cause format inconsistency
  - *Mitigation*: Warn users when modifying patterns on sequences with generated numbers, recommend creating new sequence instead

**Assumptions:**
- **ASSUMPTION-001**: Most tenants will have < 100 active sequences at any time
- **ASSUMPTION-002**: Concurrent generation load will be < 100 requests/second per sequence
- **ASSUMPTION-003**: Reset periods are sufficient for most use cases (no need for custom reset logic)
- **ASSUMPTION-004**: PostgreSQL/MySQL row-level locking is reliable and performant enough (no need for Redis)
- **ASSUMPTION-005**: Manual override will be rare (< 1% of generations) and only for exceptional cases
- **ASSUMPTION-006**: Counter can safely use BIGINT (supports 9,223,372,036,854,775,807 numbers per sequence)
- **ASSUMPTION-007**: Pattern evaluation performance is not critical (< 5ms acceptable)

## 8. Related PRD / Further Reading

**Primary PRD:**
- [PRD01-SUB04: Serial Numbering System](../prd/prd-01/PRD01-SUB04-SERIAL-NUMBERING.md) - Complete feature requirements

**Related Sub-PRDs:**
- [PRD01-SUB01: Multi-Tenancy System](../prd/prd-01/PRD01-SUB01-MULTITENANCY.md) - Tenant context resolution
- [PRD01-SUB03: Audit Logging System](../prd/prd-01/PRD01-SUB03-AUDIT-LOGGING.md) - Generation audit trail
- [PRD01-SUB05: Settings Management](../prd/prd-01/PRD01-SUB05-SETTINGS-MANAGEMENT.md) - Per-tenant configuration

**Master PRD:**
- [PRD01-MVP: Laravel ERP MVP](../prd/PRD01-MVP.md) - Overall system architecture

**External Documentation:**
- [PostgreSQL Row Locking](https://www.postgresql.org/docs/current/explicit-locking.html#LOCKING-ROWS) - SELECT FOR UPDATE documentation
- [MySQL Row Locking](https://dev.mysql.com/doc/refman/8.0/en/innodb-locking-reads.html) - InnoDB locking reads
- [Laravel Database Transactions](https://laravel.com/docs/12.x/database#database-transactions) - Transaction safety
- [Optimistic Locking Pattern](https://en.wikipedia.org/wiki/Optimistic_concurrency_control) - Version-based concurrency
