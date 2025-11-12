---
plan: Implement Journal Entries Core System with Multi-line Entries and Templates
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, accounting, journal-entries, finance, core-infrastructure, business-logic]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan establishes the core Journal Entries system with manual multi-line journal entry creation, balance validation, journal templates, and recurring journal schedules. Journal entries are critical for recording manual accounting adjustments, accruals, reclassifications, and other entries that don't originate from transactional submodules. This plan delivers the foundation for manual journal entry management before approval workflows and GL integration in PLAN02.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-JE-001**: Support **manual journal entry creation** with multi-line debit/credit entries and attachments
- **FR-JE-002**: Support **recurring journals, reversing entries, and templates** for common accounting patterns
- **FR-JE-004**: Provide **journal entry inquiry** with search, filter, and drill-down capabilities

**Business Rules:**
- **BR-JE-002**: Journal entries MUST be **balanced (debit = credit)** before approval
- **BR-JE-004**: Recurring journals **auto-generate on schedule** but require approval before posting

**Data Requirements:**
- **DR-JE-001**: Store journal entry metadata: entry_number, entry_date, description, status, created_by, approved_by
- **DR-JE-002**: Store journal entry lines: account_id, debit_amount, credit_amount, description, reference

**Integration Requirements:**
- **IR-JE-002**: Integrate with **Chart of Accounts** for account validation during entry

**Performance Requirements:**
- **PR-JE-001**: Approval and posting workflow must complete within **2 seconds** per entry
- **PR-JE-002**: Support **batch approval** of 100+ journal entries in under 5 seconds

**Security Requirements:**
- **SR-JE-001**: Enforce **role-based access** for journal entry creation, approval, and posting

**Architecture Requirements:**
- **ARCH-JE-001**: Use **database transactions** to ensure atomicity when posting multiple lines to GL

**Events:**
- **EV-JE-001**: Dispatch `JournalEntryCreatedEvent` when new manual journal entry is created

**Constraints:**
- **CON-001**: Journal entries must be balanced (total debits = total credits) before submission for approval
- **CON-002**: Entry numbers must follow serial numbering pattern: JE-{YYYY}-{NNNNNN}
- **CON-003**: Cannot edit journal entry after approval (status = 'approved' or 'posted')
- **CON-004**: Recurring journals auto-generate on schedule date but remain in draft status
- **CON-005**: Reversing entries automatically create offsetting entry in next period
- **CON-006**: Templates store only account mappings and descriptions, not actual amounts

**Guidelines:**
- **GUD-001**: Use PSR-12 coding standards, strict types, repository pattern, and Laravel Actions
- **GUD-002**: Log all journal entry operations using Spatie Activity Log
- **GUD-003**: Use serial numbering service for entry_number generation
- **GUD-004**: Validate account exists and is active before allowing in journal lines
- **GUD-005**: Store amounts as DECIMAL(20,4) for precision

**Patterns:**
- **PAT-001**: Repository pattern with JournalEntryRepositoryContract
- **PAT-002**: Laravel Actions for CreateJournalEntryAction, UpdateJournalEntryAction
- **PAT-003**: Status enum pattern for entry lifecycle (draft → pending_approval → approved → posted)
- **PAT-004**: Template pattern for reusable journal entry structures

## 2. Implementation Steps

### GOAL-001: Create Database Schema for Journal Entries

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-JE-001, DR-JE-001, DR-JE-002, BR-JE-002 | Implement journal_entries, journal_entry_lines, journal_templates, and recurring_journal_schedules tables with proper constraints and indexes | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `database/migrations/YYYY_MM_DD_HHMMSS_create_journal_entries_table.php` with namespace. Add `declare(strict_types=1);`. Use anonymous migration class format: `return new class extends Migration` | | |
| TASK-002 | In up() method, create `journal_entries` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `tenant_id` (UUID NOT NULL), `entry_number` (VARCHAR 50 NOT NULL - from serial numbering service), `entry_date` (DATE NOT NULL - accounting date), `description` (TEXT NOT NULL), `status` (VARCHAR 20 NOT NULL DEFAULT 'draft' - draft/pending_approval/approved/posted/rejected), `is_recurring` (BOOLEAN DEFAULT FALSE), `is_reversing` (BOOLEAN DEFAULT FALSE - if true, auto-create reversal next period), `reverse_date` (DATE NULL - when to create reversal), `template_id` (BIGINT NULL - if created from template), `created_by` (BIGINT NOT NULL - user who created), `approved_by` (BIGINT NULL), `approved_at` (TIMESTAMP NULL), `posted_to_gl_at` (TIMESTAMP NULL), `gl_entry_id` (BIGINT NULL - reference to GL entry after posting), timestamps, soft deletes | | |
| TASK-003 | Add unique constraint: `UNIQUE KEY uk_journal_entries_tenant_number (tenant_id, entry_number)` to enforce unique entry numbers per tenant | | |
| TASK-004 | Create indexes: `INDEX idx_je_tenant (tenant_id)` for tenant filtering, `INDEX idx_je_date (entry_date)` for date queries, `INDEX idx_je_status (status)` for status filtering, `INDEX idx_je_created_by (created_by)` for user filtering | | |
| TASK-005 | Add foreign key constraints: `FOREIGN KEY fk_je_tenant (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE`, `FOREIGN KEY fk_je_created_by (created_by) REFERENCES users(id)`, `FOREIGN KEY fk_je_approved_by (approved_by) REFERENCES users(id)`, `FOREIGN KEY fk_je_template (template_id) REFERENCES journal_templates(id) ON DELETE SET NULL` | | |
| TASK-006 | Create `journal_entry_lines` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `journal_entry_id` (BIGINT NOT NULL - parent entry), `line_number` (INT NOT NULL - display order), `account_id` (BIGINT NOT NULL - COA account), `description` (TEXT NULL - line-specific notes), `debit_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0), `credit_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0), `cost_center` (VARCHAR 50 NULL - optional dimension), `project_code` (VARCHAR 50 NULL - optional dimension), timestamps | | |
| TASK-007 | Create indexes on lines table: `INDEX idx_je_lines_entry (journal_entry_id)` for parent lookup, `INDEX idx_je_lines_account (account_id)` for account filtering. Add foreign keys: `FOREIGN KEY fk_je_lines_entry (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE`, `FOREIGN KEY fk_je_lines_account (account_id) REFERENCES accounts(id)` | | |
| TASK-008 | Create `journal_templates` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `tenant_id` (UUID NOT NULL), `name` (VARCHAR 255 NOT NULL - template display name), `description` (TEXT NULL), `is_active` (BOOLEAN DEFAULT TRUE), `created_by` (BIGINT NOT NULL), timestamps. Add index: `INDEX idx_jt_tenant (tenant_id)`, foreign keys for tenant and created_by | | |
| TASK-009 | Create `journal_template_lines` table (structure similar to entry lines): `id`, `template_id`, `line_number`, `account_id`, `description`, `debit_amount` (can be 0 as placeholder), `credit_amount` (can be 0 as placeholder), `cost_center`, `project_code`. Templates store account structure, users fill amounts when creating from template | | |
| TASK-010 | Create `recurring_journal_schedules` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `tenant_id` (UUID NOT NULL), `journal_template_id` (BIGINT NOT NULL - which template to generate), `name` (VARCHAR 255 NOT NULL), `frequency` (VARCHAR 20 NOT NULL - monthly/quarterly/annually), `start_date` (DATE NOT NULL), `end_date` (DATE NULL - if NULL, runs indefinitely), `next_generation_date` (DATE NOT NULL - when to auto-generate next), `is_active` (BOOLEAN DEFAULT TRUE), timestamps. Add indexes for tenant, next_generation_date (used by scheduler) | | |
| TASK-011 | In down() method, drop all tables in reverse order: `Schema::dropIfExists('recurring_journal_schedules')`, then templates, then lines, then entries | | |

### GOAL-002: Create Journal Entry Models with Status Enum

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-JE-001, BR-JE-002, CON-003 | Implement JournalEntry Eloquent model with relationships, computed balance, and status lifecycle management | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-012 | Create `app/Domains/Accounting/Enums/JournalEntryStatus.php` with namespace. Add `declare(strict_types=1);`. Define as `enum JournalEntryStatus: string` with cases: `DRAFT = 'draft'`, `PENDING_APPROVAL = 'pending_approval'`, `APPROVED = 'approved'`, `POSTED = 'posted'`, `REJECTED = 'rejected'`. Implement `label(): string` for display, `canEdit(): bool` (only DRAFT and REJECTED), `canApprove(): bool` (only PENDING_APPROVAL), `canPost(): bool` (only APPROVED) | | |
| TASK-013 | Create `app/Domains/Accounting/Models/JournalEntry.php` with namespace. Add `declare(strict_types=1);`. Import traits: `use BelongsToTenant, HasFactory, SoftDeletes, LogsActivity;` | | |
| TASK-014 | Define $fillable array: `['tenant_id', 'entry_number', 'entry_date', 'description', 'status', 'is_recurring', 'is_reversing', 'reverse_date', 'template_id', 'created_by', 'approved_by', 'approved_at', 'posted_to_gl_at', 'gl_entry_id']`. Exclude sensitive timestamps from mass assignment | | |
| TASK-015 | Define $casts array: `['entry_date' => 'date', 'status' => JournalEntryStatus::class, 'is_recurring' => 'boolean', 'is_reversing' => 'boolean', 'reverse_date' => 'date', 'approved_at' => 'datetime', 'posted_to_gl_at' => 'datetime', 'deleted_at' => 'datetime']`. Enum casting for type-safe status access | | |
| TASK-016 | Implement `getActivitylogOptions(): LogOptions` for audit trail: `return LogOptions::defaults()->logOnly(['entry_number', 'entry_date', 'description', 'status', 'approved_by', 'posted_to_gl_at'])->logOnlyDirty()->dontSubmitEmptyLogs();` | | |
| TASK-017 | Add relationships: `lines()` hasMany JournalEntryLine with order by line_number, `template()` belongsTo JournalTemplate with withDefault(), `creator()` belongsTo User (created_by), `approver()` belongsTo User (approved_by) with withDefault(), `glEntry()` belongsTo GLEntry (for future integration) | | |
| TASK-018 | Add custom scopes: `scopeStatus(Builder $query, JournalEntryStatus $status): Builder` returning `$query->where('status', $status)`, `scopePendingApproval(Builder $query): Builder`, `scopeDraft(Builder $query): Builder`, `scopeByDateRange(Builder $query, Carbon $from, Carbon $to): Builder` | | |
| TASK-019 | Implement `getTotalDebitsAttribute(): float` computed attribute: `return $this->lines->sum('debit_amount')`. Similarly for `getTotalCreditsAttribute()` | | |
| TASK-020 | Implement `isBalanced(): bool` method: `return bccomp((string)$this->total_debits, (string)$this->total_credits, 4) === 0`. Use bcmath for precision, compare to 4 decimal places | | |
| TASK-021 | Implement `canEdit(): bool` method: `return $this->status->canEdit() && $this->posted_to_gl_at === null`. Cannot edit if posted to GL | | |
| TASK-022 | Override `delete()` method to prevent deletion of posted entries: `if ($this->status === JournalEntryStatus::POSTED) { throw new CannotDeletePostedException('Cannot delete posted journal entry'); }` | | |

### GOAL-003: Create Journal Entry Lines and Template Models

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-JE-002, FR-JE-002 | Implement JournalEntryLine model for multi-line entries and JournalTemplate models for reusable patterns | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-023 | Create `app/Domains/Accounting/Models/JournalEntryLine.php` with namespace. Add `declare(strict_types=1);`. Import traits: `use HasFactory;` (no tenant trait needed, inherits from parent) | | |
| TASK-024 | Define $fillable: `['journal_entry_id', 'line_number', 'account_id', 'description', 'debit_amount', 'credit_amount', 'cost_center', 'project_code']`. Define $casts: `['debit_amount' => 'decimal:4', 'credit_amount' => 'decimal:4', 'line_number' => 'integer']` | | |
| TASK-025 | Add relationships: `journalEntry()` belongsTo JournalEntry, `account()` belongsTo Account with eager loading for common queries. Use `->with('account')` in common scopes | | |
| TASK-026 | Implement `getAmountAttribute(): float` computed attribute: `return $this->debit_amount > 0 ? $this->debit_amount : $this->credit_amount`. Convenience for display | | |
| TASK-027 | Implement `isDebit(): bool` and `isCredit(): bool` helpers: `return $this->debit_amount > 0` and `return $this->credit_amount > 0` | | |
| TASK-028 | Add scope `scopeForAccount(Builder $query, int $accountId): Builder` returning `$query->where('account_id', $accountId)` for account ledger queries | | |
| TASK-029 | Create `app/Domains/Accounting/Models/JournalTemplate.php` with namespace. Add `declare(strict_types=1);`. Use `BelongsToTenant, HasFactory, LogsActivity` traits | | |
| TASK-030 | Define $fillable: `['tenant_id', 'name', 'description', 'is_active', 'created_by']`. Define $casts: `['is_active' => 'boolean']` | | |
| TASK-031 | Add relationships: `lines()` hasMany JournalTemplateLine ordered by line_number, `creator()` belongsTo User, `recurringSchedules()` hasMany RecurringJournalSchedule | | |
| TASK-032 | Implement `scopeActive(Builder $query): Builder` returning `$query->where('is_active', true)` for active templates only | | |

### GOAL-004: Implement Repository Pattern for Journal Entries

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PAT-001, PR-JE-001 | Create repository contract and implementation for efficient journal entry queries with eager loading to avoid N+1 | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-033 | Create `app/Domains/Accounting/Contracts/JournalEntryRepositoryContract.php` with namespace. Add `declare(strict_types=1);`. Define interface methods: `findById(int $id): ?JournalEntry`, `findByEntryNumber(string $entryNumber, ?string $tenantId = null): ?JournalEntry`, `paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator`, `getByDateRange(Carbon $from, Carbon $to, ?string $tenantId = null): Collection`, `getByStatus(JournalEntryStatus $status, ?string $tenantId = null): Collection`, `getPendingApproval(?string $tenantId = null): Collection`, `create(array $data): JournalEntry`, `update(JournalEntry $entry, array $data): JournalEntry`, `delete(JournalEntry $entry): bool`, `createLines(JournalEntry $entry, array $linesData): Collection` | | |
| TASK-034 | Create `app/Domains/Accounting/Repositories/DatabaseJournalEntryRepository.php` implementing JournalEntryRepositoryContract. Add `declare(strict_types=1);`. Constructor has no dependencies (uses JournalEntry model directly) | | |
| TASK-035 | Implement `findById()` with eager loading: `return JournalEntry::with(['lines.account', 'creator', 'approver', 'template'])->find($id);`. Prevent N+1 queries when displaying entry details | | |
| TASK-036 | Implement `findByEntryNumber()`: `return JournalEntry::with(['lines.account'])->where('entry_number', $entryNumber)->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q->where('tenant_id', tenant_id()))->first();` | | |
| TASK-037 | Implement `paginate()` with filters: Support filters array with keys: `status` (JournalEntryStatus), `from_date` (Carbon), `to_date` (Carbon), `created_by` (int), `search` (string for description). Build query dynamically: `$query = JournalEntry::with(['lines', 'creator'])->when($filters['status'] ?? null, fn($q, $status) => $q->status($status))->when($filters['from_date'] ?? null, fn($q, $date) => $q->where('entry_date', '>=', $date))->when($filters['to_date'] ?? null, fn($q, $date) => $q->where('entry_date', '<=', $date))->when($filters['search'] ?? null, fn($q, $search) => $q->where('description', 'like', "%{$search}%"))->orderBy('entry_date', 'desc')->orderBy('entry_number', 'desc'); return $query->paginate($perPage);` | | |
| TASK-038 | Implement `getByDateRange()`: `return JournalEntry::with(['lines.account'])->whereBetween('entry_date', [$from, $to])->where('tenant_id', $tenantId ?? tenant_id())->orderBy('entry_date')->get();` | | |
| TASK-039 | Implement `getPendingApproval()`: `return JournalEntry::with(['lines.account', 'creator'])->pendingApproval()->where('tenant_id', $tenantId ?? tenant_id())->orderBy('entry_date')->get();`. Used for approval queue display | | |
| TASK-040 | Implement `createLines()`: `DB::transaction(function() use ($entry, $linesData) { $entry->lines()->delete(); // Clear existing, foreach($linesData as $index => $lineData) { $lineData['line_number'] = $index + 1; $entry->lines()->create($lineData); }, return $entry->lines()->with('account')->get(); });`. Wrapped in transaction for atomicity | | |
| TASK-041 | Bind contract to implementation in `app/Providers/AppServiceProvider.php` register() method: `$this->app->bind(JournalEntryRepositoryContract::class, DatabaseJournalEntryRepository::class);` | | |

### GOAL-005: Create Journal Entry Actions with Validation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-JE-001, BR-JE-002, GUD-003, EV-JE-001 | Implement Laravel Actions for creating and updating journal entries with balance validation and serial numbering | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-042 | Create `app/Domains/Accounting/Actions/CreateJournalEntryAction.php` with namespace. Add `declare(strict_types=1);`. Import `use Lorisleiva\Actions\Concerns\AsAction;` | | |
| TASK-043 | Define constructor with dependencies: `public function __construct(private readonly JournalEntryRepositoryContract $repository, private readonly SerialNumberServiceContract $serialNumber, private readonly ActivityLoggerContract $activityLogger) {}`. Use dependency injection for services | | |
| TASK-044 | Implement `handle(array $data): JournalEntry` method. Step 1: Validate required fields exist: `Validator::make($data, ['entry_date' => ['required', 'date'], 'description' => ['required', 'string', 'max:1000'], 'lines' => ['required', 'array', 'min:2'], 'lines.*.account_id' => ['required', 'exists:accounts,id'], 'lines.*.debit_amount' => ['required', 'numeric', 'min:0'], 'lines.*.credit_amount' => ['required', 'numeric', 'min:0']])->validate();` | | |
| TASK-045 | Step 2: Validate business rules: Check each line has either debit OR credit (not both): `foreach($data['lines'] as $line) { if (($line['debit_amount'] > 0 && $line['credit_amount'] > 0) || ($line['debit_amount'] == 0 && $line['credit_amount'] == 0)) { throw ValidationException::withMessages(['lines' => 'Each line must have debit OR credit, not both or neither']); } }` | | |
| TASK-046 | Step 3: Calculate totals and validate balance: `$totalDebits = collect($data['lines'])->sum('debit_amount'); $totalCredits = collect($data['lines'])->sum('credit_amount'); if (bccomp((string)$totalDebits, (string)$totalCredits, 4) !== 0) { throw new UnbalancedEntryException("Entry not balanced: Debits={$totalDebits}, Credits={$totalCredits}"); }` | | |
| TASK-047 | Step 4: Generate entry number: `$data['entry_number'] = $this->serialNumber->generate('journal-entry', tenant_id());`. Serial number format: JE-2025-000001 | | |
| TASK-048 | Step 5: Set defaults: `$data['status'] = JournalEntryStatus::DRAFT; $data['created_by'] = auth()->id(); $data['tenant_id'] = tenant_id();`. Default to draft status | | |
| TASK-049 | Step 6: Create entry in transaction: `DB::transaction(function() use ($data) { $lines = $data['lines']; unset($data['lines']); $entry = $this->repository->create($data); $this->repository->createLines($entry, $lines); $this->activityLogger->log('Journal entry created', $entry, auth()->user()); event(new JournalEntryCreatedEvent($entry)); return $entry->fresh(['lines.account']); });` | | |
| TASK-050 | Create `app/Domains/Accounting/Actions/UpdateJournalEntryAction.php` with namespace. Add `declare(strict_types=1);`. Use AsAction trait. Constructor same as CreateJournalEntryAction | | |
| TASK-051 | Implement `handle(JournalEntry $entry, array $data): JournalEntry` method. Step 1: Validate entry can be edited: `if (!$entry->canEdit()) { throw new CannotEditJournalEntryException('Entry cannot be edited in current status'); }` | | |
| TASK-052 | Step 2: Validate input (same rules as create). Step 3: Validate balance if lines updated. Step 4: Update in transaction: `DB::transaction(function() use ($entry, $data) { if (isset($data['lines'])) { $lines = $data['lines']; unset($data['lines']); $this->repository->createLines($entry, $lines); } $this->repository->update($entry, $data); $this->activityLogger->log('Journal entry updated', $entry, auth()->user()); return $entry->fresh(['lines.account']); });` | | |

## 3. Alternatives

- **ALT-001**: Use single journal_entries table without separate lines table - **Rejected** because cannot support multi-line entries efficiently, JSON columns don't support relational integrity
- **ALT-002**: Store amounts as integers (cents) instead of DECIMAL - **Rejected** because financial systems require decimal precision for percentages, exchange rates, and complex calculations
- **ALT-003**: Allow unbalanced entries in draft status - **Rejected** because unbalanced entries should never exist, even in draft (could be saved incomplete but must validate on any status change)
- **ALT-004**: Generate entry numbers on approval instead of creation - **Rejected** because users need stable identifiers for referencing drafts, serial numbers should be assigned immediately
- **ALT-005**: Store templates as JSON instead of related tables - **Rejected** because structured tables allow easier querying, validation, and modification

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `laravel/framework` ^12.0
- **DEP-002**: `spatie/laravel-activitylog` ^4.0 (audit logging)
- **DEP-003**: `lorisleiva/laravel-actions` ^2.0 (action pattern)
- **DEP-004**: `brick/math` ^0.12 (for precise decimal calculations)

**Internal Dependencies:**
- **DEP-005**: PRD01-SUB01 (Multi-Tenancy System) - MUST be implemented first
- **DEP-006**: PRD01-SUB03 (Audit Logging System)
- **DEP-007**: PRD01-SUB04 (Serial Numbering System) - MUST be implemented for entry numbers
- **DEP-008**: PRD01-SUB07 (Chart of Accounts) - MUST exist for account validation

**Infrastructure:**
- **DEP-009**: PostgreSQL 14+ OR MySQL 8.0+ with ACID support
- **DEP-010**: Laravel Scheduler (for recurring journal generation)

## 5. Files

**Migrations:**
- `database/migrations/YYYY_MM_DD_HHMMSS_create_journal_entries_table.php` - Main entries table
- `database/migrations/YYYY_MM_DD_HHMMSS_create_journal_entry_lines_table.php` - Entry lines
- `database/migrations/YYYY_MM_DD_HHMMSS_create_journal_templates_table.php` - Templates
- `database/migrations/YYYY_MM_DD_HHMMSS_create_recurring_journal_schedules_table.php` - Recurring schedules

**Models:**
- `app/Domains/Accounting/Models/JournalEntry.php` - Main entry model
- `app/Domains/Accounting/Models/JournalEntryLine.php` - Entry lines
- `app/Domains/Accounting/Models/JournalTemplate.php` - Templates
- `app/Domains/Accounting/Models/JournalTemplateLine.php` - Template lines
- `app/Domains/Accounting/Models/RecurringJournalSchedule.php` - Recurring schedules

**Enums:**
- `app/Domains/Accounting/Enums/JournalEntryStatus.php` - Entry status lifecycle
- `app/Domains/Accounting/Enums/RecurrenceFrequency.php` - Monthly/Quarterly/Annually

**Contracts:**
- `app/Domains/Accounting/Contracts/JournalEntryRepositoryContract.php` - Repository interface
- `app/Domains/Accounting/Contracts/JournalTemplateRepositoryContract.php` - Template repository

**Repositories:**
- `app/Domains/Accounting/Repositories/DatabaseJournalEntryRepository.php` - Entry repository
- `app/Domains/Accounting/Repositories/DatabaseJournalTemplateRepository.php` - Template repository

**Actions:**
- `app/Domains/Accounting/Actions/CreateJournalEntryAction.php` - Create entry
- `app/Domains/Accounting/Actions/UpdateJournalEntryAction.php` - Update entry
- `app/Domains/Accounting/Actions/CreateJournalTemplateAction.php` - Create template
- `app/Domains/Accounting/Actions/CreateFromTemplateAction.php` - Generate entry from template
- `app/Domains/Accounting/Actions/GenerateRecurringJournalsAction.php` - Auto-generate from schedules

**Events:**
- `app/Domains/Accounting/Events/JournalEntryCreatedEvent.php` - Entry created
- `app/Domains/Accounting/Events/JournalEntryUpdatedEvent.php` - Entry updated

**Exceptions:**
- `app/Domains/Accounting/Exceptions/UnbalancedEntryException.php` - Debits ≠ Credits
- `app/Domains/Accounting/Exceptions/CannotEditJournalEntryException.php` - Status prevents edit
- `app/Domains/Accounting/Exceptions/CannotDeletePostedException.php` - Cannot delete posted

**Service Provider (updated):**
- `app/Providers/AppServiceProvider.php` - Repository bindings

## 6. Testing

**Unit Tests (15 tests):**
- **TEST-001**: `test_journal_entry_status_enum_has_all_cases` - Verify 5 status cases
- **TEST-002**: `test_status_can_edit_only_for_draft_rejected` - Test canEdit() logic
- **TEST-003**: `test_journal_entry_model_has_all_fillable_fields` - Verify $fillable
- **TEST-004**: `test_journal_entry_is_balanced_returns_true_when_balanced` - Test isBalanced()
- **TEST-005**: `test_journal_entry_is_balanced_returns_false_when_unbalanced` - Test precision
- **TEST-006**: `test_journal_entry_total_debits_computed_correctly` - Test getTotalDebitsAttribute()
- **TEST-007**: `test_journal_entry_can_edit_false_when_posted` - Test canEdit() with posted
- **TEST-008**: `test_journal_entry_line_is_debit_returns_true` - Test isDebit() helper
- **TEST-009**: `test_journal_entry_line_amount_returns_correct_value` - Test getAmountAttribute()
- **TEST-010**: `test_repository_finds_entry_by_number` - Test findByEntryNumber()
- **TEST-011**: `test_repository_filters_by_status` - Test getByStatus()
- **TEST-012**: `test_repository_filters_by_date_range` - Test getByDateRange()
- **TEST-013**: `test_journal_entry_factory_generates_valid_data` - Test factory defaults
- **TEST-014**: `test_journal_template_scope_active_works` - Test scopeActive()
- **TEST-015**: `test_recurring_schedule_next_date_calculated` - Test schedule logic

**Feature Tests (12 tests):**
- **TEST-016**: `test_create_journal_entry_action_generates_entry_number` - Test serial numbering
- **TEST-017**: `test_create_journal_entry_action_validates_balance` - Test balance validation
- **TEST-018**: `test_create_journal_entry_action_creates_lines` - Test line creation
- **TEST-019**: `test_create_journal_entry_action_throws_on_unbalanced` - Test UnbalancedEntryException
- **TEST-020**: `test_create_journal_entry_action_dispatches_event` - Test JournalEntryCreatedEvent
- **TEST-021**: `test_update_journal_entry_action_prevents_edit_when_posted` - Test canEdit()
- **TEST-022**: `test_update_journal_entry_action_updates_lines` - Test line update in transaction
- **TEST-023**: `test_cannot_delete_posted_journal_entry` - Test delete() override
- **TEST-024**: `test_journal_entry_audit_log_records_creation` - Test LogsActivity
- **TEST-025**: `test_unique_constraint_enforced_per_tenant` - Test entry_number uniqueness
- **TEST-026**: `test_soft_delete_preserves_entry_number` - Test soft deletes
- **TEST-027**: `test_tenant_scoping_isolates_entries` - Test BelongsToTenant trait

**Integration Tests (8 tests):**
- **TEST-028**: `test_entry_creation_with_lines_atomic` - Test DB transaction rollback
- **TEST-029**: `test_balance_validation_uses_bcmath_precision` - Test 4 decimal precision
- **TEST-030**: `test_eager_loading_prevents_n_plus_one` - Test query count
- **TEST-031**: `test_activity_log_records_all_changes` - Test Spatie Activity Log
- **TEST-032**: `test_repository_binding_resolves_correctly` - Test service container
- **TEST-033**: `test_create_from_template_copies_structure` - Test CreateFromTemplateAction
- **TEST-034**: `test_recurring_journal_generation_works` - Test GenerateRecurringJournalsAction
- **TEST-035**: `test_status_transitions_enforce_workflow` - Test status enum constraints

**Performance Tests (2 tests):**
- **TEST-036**: `test_paginate_with_filters_completes_under_100ms` - Test query performance
- **TEST-037**: `test_create_entry_with_100_lines_under_2s` - Test PR-JE-001

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Floating point precision issues with amounts - **Mitigation**: Use DECIMAL(20,4) in database, bcmath for calculations, never use float type
- **RISK-002**: Race condition when generating serial numbers - **Mitigation**: Use database-level unique constraint, handle duplicate key exceptions gracefully
- **RISK-003**: Large journal entries (100+ lines) slow to save - **Mitigation**: Use batch insert, optimize N+1 queries with eager loading
- **RISK-004**: Recurring journal generation fails silently - **Mitigation**: Implement proper error logging, send alerts for failed generations
- **RISK-005**: Balance validation false negatives due to rounding - **Mitigation**: Use bccomp() with 4 decimal precision, document rounding rules

**Assumptions:**
- **ASSUMPTION-001**: Most journal entries have 2-10 lines (simple debits/credits), rarely exceed 50 lines
- **ASSUMPTION-002**: Users understand double-entry bookkeeping concepts (debit/credit, balanced entries)
- **ASSUMPTION-003**: Entry numbers are unique within tenant but can duplicate across tenants
- **ASSUMPTION-004**: Draft entries can remain unbalanced for work-in-progress, but must balance before approval
- **ASSUMPTION-005**: Templates store structure only, not actual transaction amounts (amounts = 0 or placeholder)

## 8. KIV for future implementations

- **KIV-001**: Implement journal entry attachments (scan receipts, supporting documents)
- **KIV-002**: Add bulk import from CSV/Excel with validation
- **KIV-003**: Implement journal entry copy functionality (duplicate with new date)
- **KIV-004**: Add email notifications for approval requests
- **KIV-005**: Implement journal entry search with full-text search on descriptions
- **KIV-006**: Add support for foreign currency journal entries (multi-currency)
- **KIV-007**: Implement journal entry history/audit trail UI
- **KIV-008**: Add batch delete for draft entries (with confirmation)

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB09-JOURNAL-ENTRIES.md](../prd/prd-01/PRD01-SUB09-JOURNAL-ENTRIES.md)
- **Related Sub-PRDs:**
  - PRD01-SUB01 (Multi-Tenancy) - Tenant scoping
  - PRD01-SUB04 (Serial Numbering) - Entry number generation
  - PRD01-SUB07 (Chart of Accounts) - Account validation
  - PRD01-SUB08 (General Ledger) - GL posting (PLAN02)
- **Related Plans:**
  - PRD01-SUB09-PLAN02 (Approval Workflow and GL Integration)
- **External Documentation:**
  - Double-Entry Bookkeeping: https://en.wikipedia.org/wiki/Double-entry_bookkeeping
  - Laravel Actions: https://laravelactions.com/
  - Decimal Precision in PHP: https://www.php.net/manual/en/ref.bc.php
