---
plan: Implement General Ledger Core Posting and Double-Entry Enforcement
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, accounting, general-ledger, double-entry, acid-compliance, core]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan delivers the foundational General Ledger system with double-entry bookkeeping enforcement, ACID-compliant posting operations, and automated integration from source modules. It implements the core GL entry structure with header (`gl_entries`) and line items (`gl_entry_lines`), PostgreSQL-based transaction management with row-level locking (SELECT FOR UPDATE), entry status workflow (draft → posted → reversed), and batch posting capabilities targeting 1000 entries per second. The system ensures Assets = Liabilities + Equity through mandatory debit-credit balance validation and provides immutable audit trails for regulatory compliance.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-GL-001**: **Auto-post entries** from all submodules (Invoice, Payment, Inventory, Payroll) with complete audit trail
- **FR-GL-005**: **Batch journal entry posting** with validation and rollback on any failure

**Business Rules:**
- **BR-GL-001**: All entries MUST be **balanced** (total debits = total credits) before posting
- **BR-GL-002**: **Posted entries are immutable**, only reversible with offsetting entries (audit compliance)
- **BR-GL-003**: Entries can only be posted to **active fiscal periods** (closed periods reject entries)

**Data Requirements:**
- **DR-GL-002**: GL entries store: account_id, debit, credit, currency, exchange_rate, posting_date, source_type, source_id, created_by
- **DR-GL-003**: Complete **audit trail** with posted_by, posted_at, reversed_by, reversed_at timestamps

**Architecture Requirements:**
- **ARCH-GL-001**: **ACID compliance non-negotiable** (database transactions + row-level locking)
- **ARCH-GL-002**: Use PostgreSQL **SELECT FOR UPDATE** row-level locking for concurrent posting

**Performance Requirements:**
- **PR-GL-001**: **1000 journal entries posted in < 1 second** (batch processing with chunking)

**Event Requirements:**
- **EV-GL-001**: `GLEntryPostedEvent` - when journal entry posted to GL
- **EV-GL-003**: `GLEntryReversedEvent` - when entry reversed

**Constraints:**
- **CON-001**: All GL entries must have at least 2 lines (minimum one debit, one credit)
- **CON-002**: Total debits MUST exactly equal total credits (to 4 decimal places)
- **CON-003**: Cannot delete posted entries, only reverse
- **CON-004**: Entry numbers must be sequential per tenant per fiscal year
- **CON-005**: All database operations must use transactions with proper isolation level

**Guidelines:**
- **GUD-001**: Use Laravel Actions for all GL operations
- **GUD-002**: Wrap all posting operations in database transactions
- **GUD-003**: Use Spatie Activitylog for comprehensive audit trail
- **GUD-004**: Implement repository pattern for data access
- **GUD-005**: Use brick/math for precise decimal arithmetic

**Patterns:**
- **PAT-001**: Header-line table structure (gl_entries + gl_entry_lines)
- **PAT-002**: Status workflow (draft → posted → reversed)
- **PAT-003**: Batch processing with chunking for performance

## 2. Implementation Steps

### GOAL-001: Create GL Database Schema with ACID Compliance

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-GL-002, DR-GL-003, ARCH-GL-001, CON-001 | Implement two-table GL structure (header + lines) with foreign keys, indexes, and constraints for ACID compliance and performance | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `database/migrations/2025_11_12_000001_create_gl_entries_table.php`. Add `declare(strict_types=1);`. Use anonymous migration class: `return new class extends Migration { ... };` | | |
| TASK-002 | In up() method, create gl_entries table with columns: `id` (bigIncrements PK), `tenant_id` (uuid NOT NULL FK → tenants ON DELETE CASCADE), `entry_number` (string 50 NOT NULL), `posting_date` (date NOT NULL), `fiscal_year` (integer NOT NULL), `fiscal_period` (integer NOT NULL 1-12), `description` (text nullable), `source_type` (string 100 NOT NULL - polymorphic), `source_id` (unsignedBigInteger NOT NULL - polymorphic), `status` (string 20 DEFAULT 'draft' - values: draft, posted, reversed), `posted_at` (timestamp nullable), `posted_by` (unsignedBigInteger nullable FK → users), `reversed_at` (timestamp nullable), `reversed_by` (unsignedBigInteger nullable FK → users), `reversal_entry_id` (unsignedBigInteger nullable FK → gl_entries self-reference), `timestamps()`, `softDeletes()` | | |
| TASK-003 | Add constraints to gl_entries: `UNIQUE (tenant_id, entry_number)` using `$table->unique(['tenant_id', 'entry_number'], 'uq_gl_entries_tenant_number');`. Add indexes: `idx_gl_entries_tenant` (tenant_id), `idx_gl_entries_date` (posting_date), `idx_gl_entries_period` (fiscal_year, fiscal_period), `idx_gl_entries_source` (source_type, source_id), `idx_gl_entries_status` (status). Foreign keys already defined inline | | |
| TASK-004 | In up() method, create gl_entry_lines table with columns: `id` (bigIncrements PK), `gl_entry_id` (unsignedBigInteger NOT NULL FK → gl_entries ON DELETE CASCADE), `line_number` (integer NOT NULL), `account_id` (unsignedBigInteger NOT NULL FK → accounts), `debit_amount` (decimal 20,4 DEFAULT 0), `credit_amount` (decimal 20,4 DEFAULT 0), `description` (text nullable), `cost_center` (string 50 nullable), `project_code` (string 50 nullable), `metadata` (json nullable for extensibility), `timestamps()` | | |
| TASK-005 | Add constraints to gl_entry_lines: `UNIQUE (gl_entry_id, line_number)` using `$table->unique(['gl_entry_id', 'line_number'], 'uq_gl_lines_entry_number');`. Add CHECK constraint (PostgreSQL): `ALTER TABLE gl_entry_lines ADD CONSTRAINT chk_gl_lines_amount CHECK ((debit_amount > 0 AND credit_amount = 0) OR (credit_amount > 0 AND debit_amount = 0) OR (debit_amount = 0 AND credit_amount = 0));` (prevent both debit and credit on same line). Add indexes: `idx_gl_lines_entry` (gl_entry_id), `idx_gl_lines_account` (account_id) | | |
| TASK-006 | Implement down() method: `Schema::dropIfExists('gl_entry_lines'); Schema::dropIfExists('gl_entries');`. Drop lines table first due to FK constraint | | |

### GOAL-002: Create GL Entry Models with Validation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-GL-002, DR-GL-003, BR-GL-001, CON-002 | Implement Eloquent models for GL entries and lines with relationships, validation, and double-entry balance checking | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `app/Domains/GeneralLedger/Models/GLEntry.php` extending Model. Add `declare(strict_types=1);`. Use traits: `BelongsToTenant`, `SoftDeletes`, `HasFactory`. Define table: `protected $table = 'gl_entries';` | | |
| TASK-008 | Define fillable fields: `protected $fillable = ['tenant_id', 'entry_number', 'posting_date', 'fiscal_year', 'fiscal_period', 'description', 'source_type', 'source_id', 'status'];`. Define casts: `protected $casts = ['posting_date' => 'date', 'posted_at' => 'datetime', 'reversed_at' => 'datetime', 'fiscal_year' => 'integer', 'fiscal_period' => 'integer', 'metadata' => 'array'];` | | |
| TASK-009 | Define relationships: `lines(): HasMany` → GLEntryLine, `source(): MorphTo` (polymorphic to Invoice, Payment, etc.), `postedBy(): BelongsTo` → User nullable, `reversedBy(): BelongsTo` → User nullable, `reversalEntry(): BelongsTo` → self-referencing GLEntry nullable (the reversal entry if this was reversed), `originalEntry(): HasOne` → self-referencing GLEntry nullable (the original entry if this is a reversal) | | |
| TASK-010 | Implement `isBalanced(): bool` method. Calculate total debits: `$totalDebits = $this->lines->sum('debit_amount');`. Calculate total credits: `$totalCredits = $this->lines->sum('credit_amount');`. Return: `bccomp((string) $totalDebits, (string) $totalCredits, 4) === 0;` (using bcmath for 4 decimal precision). This enforces BR-GL-001 | | |
| TASK-011 | Implement `getTotalDebitsAttribute(): string` computed attribute returning `$this->lines->sum('debit_amount');`. Implement `getTotalCreditsAttribute(): string` returning `$this->lines->sum('credit_amount');`. Implement `isPosted(): bool` returning `$this->status === 'posted';`. Implement `isDraft(): bool` returning `$this->status === 'draft';`. Implement `isReversed(): bool` returning `$this->status === 'reversed';` | | |
| TASK-012 | Implement scopes: `scopePosted(Builder $query): Builder` adding `where('status', 'posted')`, `scopeDraft(Builder $query): Builder` adding `where('status', 'draft')`, `scopeForPeriod(Builder $query, int $year, int $period): Builder` adding `where(['fiscal_year' => $year, 'fiscal_period' => $period])`, `scopeForAccount(Builder $query, int $accountId): Builder` with `whereHas('lines', fn($q) => $q->where('account_id', $accountId))` | | |
| TASK-013 | Create `app/Domains/GeneralLedger/Models/GLEntryLine.php` extending Model. Add `declare(strict_types=1);`. Use trait: `HasFactory`. Define table: `protected $table = 'gl_entry_lines';`. Define fillable: `['gl_entry_id', 'line_number', 'account_id', 'debit_amount', 'credit_amount', 'description', 'cost_center', 'project_code', 'metadata']`. Define casts: `['debit_amount' => 'decimal:4', 'credit_amount' => 'decimal:4', 'line_number' => 'integer', 'metadata' => 'array']` | | |
| TASK-014 | In GLEntryLine, define relationships: `entry(): BelongsTo` → GLEntry, `account(): BelongsTo` → Account. Implement `getAmountAttribute(): string` returning `$this->debit_amount > 0 ? $this->debit_amount : $this->credit_amount;` (convenience accessor). Implement `getTypeAttribute(): string` returning `$this->debit_amount > 0 ? 'debit' : 'credit';` | | |

### GOAL-003: Implement Core Posting Action with ACID Transactions

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-GL-001, BR-GL-001, BR-GL-002, ARCH-GL-001, ARCH-GL-002, EV-GL-001 | Create PostGLEntryAction with double-entry validation, SELECT FOR UPDATE locking, transaction management, and event dispatching | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-015 | Create `app/Domains/GeneralLedger/Actions/PostGLEntryAction.php` extending Action. Add `declare(strict_types=1);`. Use trait: `AsAction`. Inject dependencies via constructor: `GLEntryRepositoryContract $repository`, `ActivityLoggerContract $activityLogger` | | |
| TASK-016 | Implement `rules(): array` returning `['entry_id' => ['required', 'integer', 'exists:gl_entries,id'], 'posting_date' => ['nullable', 'date']]`. Implement `handle(int $entryId, ?Carbon $postingDate = null): GLEntry`. Start with: `DB::beginTransaction(); try { ... DB::commit(); } catch (\Throwable $e) { DB::rollBack(); throw $e; }` for ACID compliance (ARCH-GL-001) | | |
| TASK-017 | In handle(), fetch entry with row-level locking: `$entry = GLEntry::lockForUpdate()->with('lines.account')->findOrFail($entryId);`. This implements ARCH-GL-002 (SELECT FOR UPDATE). Validate entry is in draft status: `if ($entry->status !== 'draft') { throw new InvalidStatusException("Entry {$entry->entry_number} is already {$entry->status}"); }` | | |
| TASK-018 | Validate entry has minimum 2 lines: `if ($entry->lines->count() < 2) { throw new ValidationException("Entry must have at least 2 lines"); }`. Validate entry is balanced: `if (! $entry->isBalanced()) { throw new UnbalancedEntryException("Entry total debits ({$entry->total_debits}) do not match total credits ({$entry->total_credits})"); }`. This enforces BR-GL-001 and CON-001, CON-002 | | |
| TASK-019 | Validate fiscal period is open: `$period = FiscalPeriod::where(['tenant_id' => $entry->tenant_id, 'fiscal_year' => $entry->fiscal_year, 'period' => $entry->fiscal_period])->firstOrFail(); if ($period->status !== 'open') { throw new PeriodClosedException("Fiscal period {$entry->fiscal_year}-{$entry->fiscal_period} is closed"); }`. This enforces BR-GL-003 | | |
| TASK-020 | Update entry to posted status: `$entry->update(['status' => 'posted', 'posted_at' => $postingDate ?? now(), 'posted_by' => auth()->id()]);`. Refresh: `$entry->refresh();`. Log activity: `$this->activityLogger->log('GL Entry Posted', $entry, auth()->user());`. Dispatch event: `event(new GLEntryPostedEvent($entry));`. Return: `$entry;`. Close try block, commit transaction | | |
| TASK-021 | Create exception classes in `app/Domains/GeneralLedger/Exceptions/`: `InvalidStatusException extends \RuntimeException`, `UnbalancedEntryException extends \RuntimeException`, `PeriodClosedException extends \RuntimeException`, `ValidationException extends \RuntimeException`. All should accept message and previous exception in constructor | | |

### GOAL-004: Implement Entry Reversal Action with Offsetting Entries

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-GL-002, EV-GL-003 | Create ReverseGLEntryAction that generates offsetting entry, maintains immutability, and links original and reversal entries | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-022 | Create `app/Domains/GeneralLedger/Actions/ReverseGLEntryAction.php` extending Action. Add `declare(strict_types=1);`. Use trait: `AsAction`. Inject: `GLEntryRepositoryContract $repository`, `ActivityLoggerContract $activityLogger`. Implement `rules(): array` returning `['entry_id' => ['required', 'integer', 'exists:gl_entries,id'], 'reason' => ['required', 'string', 'max:1000'], 'reversal_date' => ['nullable', 'date']]` | | |
| TASK-023 | Implement `handle(int $entryId, string $reason, ?Carbon $reversalDate = null): GLEntry`. Use DB transaction. Fetch original entry with locking: `$original = GLEntry::lockForUpdate()->with('lines.account')->findOrFail($entryId);`. Validate entry is posted: `if ($original->status !== 'posted') { throw new InvalidStatusException("Only posted entries can be reversed"); }`. Validate not already reversed: `if ($original->status === 'reversed') { throw new InvalidStatusException("Entry already reversed"); }` | | |
| TASK-024 | Create reversal entry: `$reversalEntry = GLEntry::create(['tenant_id' => $original->tenant_id, 'entry_number' => $this->repository->generateEntryNumber($original->tenant_id, $reversalDate?->year ?? now()->year), 'posting_date' => $reversalDate ?? now(), 'fiscal_year' => $reversalDate?->year ?? now()->year, 'fiscal_period' => $reversalDate?->month ?? now()->month, 'description' => "REVERSAL: {$original->description} - Reason: {$reason}", 'source_type' => $original->source_type, 'source_id' => $original->source_id, 'status' => 'posted', 'posted_at' => now(), 'posted_by' => auth()->id()]);` | | |
| TASK-025 | Copy lines with reversed amounts: `foreach ($original->lines as $line) { GLEntryLine::create(['gl_entry_id' => $reversalEntry->id, 'line_number' => $line->line_number, 'account_id' => $line->account_id, 'debit_amount' => $line->credit_amount, 'credit_amount' => $line->debit_amount, 'description' => "REVERSAL: {$line->description}", 'cost_center' => $line->cost_center, 'project_code' => $line->project_code]); }` (swap debits and credits to reverse) | | |
| TASK-026 | Update original entry to reversed status: `$original->update(['status' => 'reversed', 'reversed_at' => now(), 'reversed_by' => auth()->id(), 'reversal_entry_id' => $reversalEntry->id]);`. Log activity: `$this->activityLogger->log('GL Entry Reversed', $original, auth()->user(), ['reason' => $reason, 'reversal_entry_id' => $reversalEntry->id]);`. Dispatch event: `event(new GLEntryReversedEvent($original, $reversalEntry, $reason));`. Return: `$reversalEntry;`. Commit transaction | | |

### GOAL-005: Implement Batch Posting with Performance Optimization

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-GL-005, PR-GL-001 | Create BatchPostGLEntriesAction with chunking, parallel processing, and performance monitoring to achieve 1000 entries/second | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-027 | Create `app/Domains/GeneralLedger/Actions/BatchPostGLEntriesAction.php` extending Action. Add `declare(strict_types=1);`. Use trait: `AsAction`. Inject: `PostGLEntryAction $postAction`, `GLEntryRepositoryContract $repository`. Implement `rules(): array` returning `['entry_ids' => ['required', 'array', 'min:1', 'max:1000'], 'entry_ids.*' => ['required', 'integer', 'exists:gl_entries,id'], 'posting_date' => ['nullable', 'date']]` (limit to 1000 for safety) | | |
| TASK-028 | Implement `handle(array $entryIds, ?Carbon $postingDate = null): array`. Initialize result tracking: `$results = ['success' => [], 'failed' => [], 'total' => count($entryIds), 'duration_ms' => 0]; $startTime = microtime(true);`. Validate entries exist and are drafts: `$entries = GLEntry::whereIn('id', $entryIds)->where('status', 'draft')->get(); if ($entries->count() !== count($entryIds)) { throw new ValidationException("Some entries not found or not in draft status"); }` | | |
| TASK-029 | Process entries in chunks of 100 for optimal performance: `$chunks = collect($entryIds)->chunk(100); foreach ($chunks as $chunk) { DB::beginTransaction(); try { foreach ($chunk as $entryId) { $entry = $this->postAction->run($entryId, $postingDate); $results['success'][] = $entryId; } DB::commit(); } catch (\Throwable $e) { DB::rollBack(); foreach ($chunk as $entryId) { $results['failed'][] = ['entry_id' => $entryId, 'error' => $e->getMessage()]; } } }` (chunk-level transactions for better performance) | | |
| TASK-030 | Calculate performance metrics: `$endTime = microtime(true); $results['duration_ms'] = round(($endTime - $startTime) * 1000, 2); $results['entries_per_second'] = $results['total'] > 0 ? round($results['total'] / ($endTime - $startTime), 2) : 0;`. Log performance warning if < 1000/s: `if ($results['entries_per_second'] < 1000 && $results['total'] >= 100) { Log::warning('Batch posting performance below target', $results); }`. Return: `$results;` | | |
| TASK-031 | Create `app/Domains/GeneralLedger/Contracts/GLEntryRepositoryContract.php` interface. Add `declare(strict_types=1);`. Define methods: `findById(int $id): ?GLEntry;`, `create(array $data): GLEntry;`, `update(GLEntry $entry, array $data): GLEntry;`, `generateEntryNumber(string $tenantId, int $fiscalYear): string;` (generates sequential GL-YYYY-NNNNNN), `getByPeriod(string $tenantId, int $year, int $period): Collection;`, `getByAccount(int $accountId, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection;` | | |
| TASK-032 | Create `app/Domains/GeneralLedger/Repositories/DatabaseGLEntryRepository.php` implementing GLEntryRepositoryContract. Implement all interface methods. In generateEntryNumber(): `$lastEntry = GLEntry::where(['tenant_id' => $tenantId, 'fiscal_year' => $fiscalYear])->orderBy('entry_number', 'desc')->first(); $lastNumber = $lastEntry ? (int) substr($lastEntry->entry_number, -6) : 0; $nextNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT); return "GL-{$fiscalYear}-{$nextNumber}";`. Use DB transaction and locking for sequence generation | | |

## 3. Alternatives

- **ALT-001**: Use NoSQL (MongoDB) for GL entries - **Rejected** because ACID compliance is non-negotiable (ARCH-GL-001), relational integrity critical for financial data, PostgreSQL provides superior transaction guarantees
- **ALT-002**: Store only net amount (debit - credit) per line - **Rejected** because it violates double-entry accounting principles, makes audit trail unclear, loses granularity needed for reporting
- **ALT-003**: Allow entry modification after posting - **Rejected** because it violates immutability requirement (BR-GL-002), breaks audit compliance, creates regulatory issues. Reversal pattern is industry standard
- **ALT-004**: Synchronous posting without batching - **Rejected** because it cannot meet performance requirement (PR-GL-001: 1000 entries/second), batching with chunking essential for high throughput
- **ALT-005**: Use UUID for entry_number - **Rejected** because sequential numbers are required for audit (CON-004), easier troubleshooting, standard accounting practice

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `brick/math` ^0.12 (precise decimal arithmetic for balance validation)
- **DEP-002**: `lorisleiva/laravel-actions` ^2.0 (Action pattern)
- **DEP-003**: `spatie/laravel-activitylog` ^4.0 (audit logging)

**Internal Dependencies:**
- **DEP-004**: PRD01-SUB01 (Multi-Tenancy) - tenant_id FK, BelongsToTenant trait
- **DEP-005**: PRD01-SUB07 (Chart of Accounts) - accounts table FK, AccountType enum for validation
- **DEP-006**: PRD01-SUB09 (Fiscal Period Management) - fiscal_periods table for period status validation (will be created in PLAN03)
- **DEP-007**: PRD01-SUB02 (Authentication) - users table FK for posted_by/reversed_by

**Database:**
- **DEP-008**: PostgreSQL 14+ (ACID compliance, SELECT FOR UPDATE, CHECK constraints)
- **DEP-009**: Database transaction isolation level: READ COMMITTED (default is safe)

## 5. Files

**Migrations:**
- `database/migrations/2025_11_12_000001_create_gl_entries_table.php` - GL entries and lines tables

**Models:**
- `app/Domains/GeneralLedger/Models/GLEntry.php` - Entry header model with validation
- `app/Domains/GeneralLedger/Models/GLEntryLine.php` - Entry line model

**Actions:**
- `app/Domains/GeneralLedger/Actions/PostGLEntryAction.php` - Single entry posting
- `app/Domains/GeneralLedger/Actions/ReverseGLEntryAction.php` - Entry reversal
- `app/Domains/GeneralLedger/Actions/BatchPostGLEntriesAction.php` - Bulk posting

**Contracts:**
- `app/Domains/GeneralLedger/Contracts/GLEntryRepositoryContract.php` - Repository interface

**Repositories:**
- `app/Domains/GeneralLedger/Repositories/DatabaseGLEntryRepository.php` - Repository implementation

**Exceptions:**
- `app/Domains/GeneralLedger/Exceptions/InvalidStatusException.php` - Status validation errors
- `app/Domains/GeneralLedger/Exceptions/UnbalancedEntryException.php` - Balance validation errors
- `app/Domains/GeneralLedger/Exceptions/PeriodClosedException.php` - Period status errors
- `app/Domains/GeneralLedger/Exceptions/ValidationException.php` - General validation errors

**Events:**
- `app/Domains/GeneralLedger/Events/GLEntryPostedEvent.php` - Entry posted
- `app/Domains/GeneralLedger/Events/GLEntryReversedEvent.php` - Entry reversed

**Factories:**
- `database/factories/GLEntryFactory.php` - Entry factory
- `database/factories/GLEntryLineFactory.php` - Line factory

**Service Provider:**
- `app/Providers/GeneralLedgerServiceProvider.php` - Register repository binding

## 6. Testing

**Unit Tests (15 tests):**
- **TEST-001**: `test_gl_entry_validates_balanced_correctly` - isBalanced() returns true for balanced entry
- **TEST-002**: `test_gl_entry_detects_unbalanced_entry` - isBalanced() returns false when debits ≠ credits
- **TEST-003**: `test_gl_entry_computes_totals_correctly` - total_debits and total_credits attributes
- **TEST-004**: `test_gl_entry_line_determines_type_correctly` - getType() returns debit or credit
- **TEST-005**: `test_gl_entry_scopes_filter_correctly` - posted(), draft(), forPeriod() scopes
- **TEST-006**: `test_repository_generates_sequential_entry_numbers` - GL-2025-000001, 000002, etc.
- **TEST-007**: `test_post_action_validates_minimum_lines` - Throws exception for < 2 lines
- **TEST-008**: `test_post_action_validates_balanced_entry` - Throws UnbalancedEntryException
- **TEST-009**: `test_post_action_validates_draft_status` - Throws exception for non-draft
- **TEST-010**: `test_reverse_action_validates_posted_status` - Only posted entries can be reversed
- **TEST-011**: `test_reverse_action_swaps_debits_credits` - Reversal has opposite amounts
- **TEST-012**: `test_reverse_action_links_entries` - reversal_entry_id and status updated
- **TEST-013**: `test_batch_post_processes_chunks_correctly` - Chunks of 100
- **TEST-014**: `test_batch_post_calculates_performance_metrics` - entries_per_second calculated
- **TEST-015**: `test_exception_classes_work_correctly` - All custom exceptions throw properly

**Feature Tests (18 tests):**
- **TEST-016**: `test_can_create_gl_entry_with_lines` - Create entry + 3 lines
- **TEST-017**: `test_can_post_balanced_entry` - Draft → Posted transition
- **TEST-018**: `test_cannot_post_unbalanced_entry` - Returns 422 or throws exception
- **TEST-019**: `test_cannot_post_entry_twice` - Second post attempt fails
- **TEST-020**: `test_posting_updates_timestamps_and_user` - posted_at and posted_by set
- **TEST-021**: `test_can_reverse_posted_entry` - Creates reversal entry
- **TEST-022**: `test_reversal_has_opposite_amounts` - Debit ↔ Credit swap verified
- **TEST-023**: `test_cannot_reverse_draft_entry` - Only posted entries reversible
- **TEST-024**: `test_cannot_reverse_already_reversed_entry` - Prevents double reversal
- **TEST-025**: `test_reversal_links_to_original` - reversal_entry_id populated
- **TEST-026**: `test_batch_post_succeeds_for_valid_entries` - All 100 entries posted
- **TEST-027**: `test_batch_post_rolls_back_chunk_on_error` - Single error rolls back chunk
- **TEST-028**: `test_batch_post_continues_after_chunk_failure` - Other chunks still process
- **TEST-029**: `test_batch_post_returns_detailed_results` - success/failed arrays populated
- **TEST-030**: `test_entry_number_generation_sequential` - GL-2025-000001, 000002, 000003
- **TEST-031**: `test_entry_relationships_load_correctly` - lines, source, postedBy eager load
- **TEST-032**: `test_activity_logging_records_all_changes` - Post and reversal logged
- **TEST-033**: `test_events_dispatched_correctly` - GLEntryPostedEvent and GLEntryReversedEvent fired

**Integration Tests (10 tests):**
- **TEST-034**: `test_posting_respects_tenant_isolation` - Cannot post other tenant's entry
- **TEST-035**: `test_posting_validates_account_exists` - FK constraint enforced
- **TEST-036**: `test_posting_validates_fiscal_period_exists` - Period must exist (integration with SUB09)
- **TEST-037**: `test_posting_rejects_closed_period` - Period status checked
- **TEST-038**: `test_concurrent_posting_handles_locking` - SELECT FOR UPDATE prevents conflicts
- **TEST-039**: `test_transaction_rollback_on_error` - No partial commits
- **TEST-040**: `test_entry_number_sequence_no_gaps_under_load` - Concurrent entry creation
- **TEST-041**: `test_polymorphic_source_relationship_works` - Invoice, Payment sources
- **TEST-042**: `test_soft_delete_preserves_entry` - Deleted entries still in DB
- **TEST-043**: `test_repository_contract_implementation_complete` - All methods implemented

**Performance Tests (5 tests):**
- **TEST-044**: `test_batch_post_meets_1000_per_second_target` - PR-GL-001 compliance
- **TEST-045**: `test_single_post_completes_under_100ms` - Individual entry posting
- **TEST-046**: `test_balance_validation_efficient` - isBalanced() < 10ms for 100 lines
- **TEST-047**: `test_entry_number_generation_no_contention` - 100 concurrent generations
- **TEST-048**: `test_select_for_update_prevents_deadlocks` - Concurrent posting stress test

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Database deadlocks under high concurrent posting load - **Mitigation**: Use SELECT FOR UPDATE consistently, implement retry logic with exponential backoff, monitor deadlock frequency, adjust transaction isolation if needed
- **RISK-002**: Entry number sequence gaps under high contention - **Mitigation**: Use advisory locks for sequence generation, implement gap detection and reconciliation, accept small gaps for performance (common in accounting systems)
- **RISK-003**: Batch posting performance degrades with large entries (100+ lines) - **Mitigation**: Adjust chunk size dynamically based on line count, implement adaptive batching, profile and optimize line insertion queries
- **RISK-004**: Fiscal period validation dependency not yet implemented - **Mitigation**: Mock FiscalPeriod model for now, implement basic validation, complete in PLAN03, add integration tests after PLAN03
- **RISK-005**: Transaction log growth from heavy posting activity - **Mitigation**: Configure PostgreSQL WAL archiving, implement log rotation, monitor disk space, set up automated cleanup

**Assumptions:**
- **ASSUMPTION-001**: Average GL entry has 2-5 lines (simple transactions), < 10% have > 10 lines
- **ASSUMPTION-002**: Posting frequency is bursty (month-end processing), not constant 24/7 load
- **ASSUMPTION-003**: Reversal is rare operation (< 1% of posted entries need reversal)
- **ASSUMPTION-004**: PostgreSQL default isolation level (READ COMMITTED) is sufficient for our use case
- **ASSUMPTION-005**: Entry number format GL-YYYY-NNNNNN is acceptable (max 999,999 entries per year per tenant)

## 8. KIV for future implementations

- **KIV-001**: Implement entry templates for common transaction types (recurring entries)
- **KIV-002**: Add entry copying functionality (duplicate entry with modifications)
- **KIV-003**: Implement entry approval workflow (draft → pending approval → posted)
- **KIV-004**: Add entry attachments support (invoices, receipts as files)
- **KIV-005**: Implement entry tags for categorization and filtering
- **KIV-006**: Add entry notes/comments feature for collaboration
- **KIV-007**: Implement provisional posting (post with "pending" flag, finalize later)
- **KIV-008**: Add entry scheduling (post automatically at future date)
- **KIV-009**: Implement change tracking for draft entries (version history before posting)
- **KIV-010**: Add entry validation rules engine (configurable validation beyond balance check)

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB08-GENERAL-LEDGER.md](../prd/prd-01/PRD01-SUB08-GENERAL-LEDGER.md)
- **Related Plans:**
  - PRD01-SUB07-PLAN01 (COA Foundation) - Account model and validation
  - PRD01-SUB08-PLAN02 (Multi-Currency) - Exchange rates and foreign currency (next)
  - PRD01-SUB08-PLAN03 (Fiscal Periods) - Period management and closing (next)
- **External Documentation:**
  - Double-Entry Bookkeeping: https://en.wikipedia.org/wiki/Double-entry_bookkeeping
  - PostgreSQL Transactions: https://www.postgresql.org/docs/current/tutorial-transactions.html
  - PostgreSQL Locking: https://www.postgresql.org/docs/current/explicit-locking.html
  - ACID Compliance: https://en.wikipedia.org/wiki/ACID
  - Laravel Database Transactions: https://laravel.com/docs/11.x/database#database-transactions
  - brick/math Documentation: https://github.com/brick/math
