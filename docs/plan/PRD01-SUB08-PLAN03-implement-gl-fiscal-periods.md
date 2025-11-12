---
plan: Implement General Ledger Fiscal Periods and Period Closing
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, accounting, general-ledger, fiscal-periods, period-closing, trial-balance]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan completes the General Ledger system with fiscal period management, period closing workflow, and financial reporting capabilities. It implements fiscal year and period structure (configurable 12-period calendar or 13-period 4-4-5), period status management (open/closed/locked), posting validation against period status, period closing process with validation checkpoints, account balance inquiry with drill-down to transactions, and trial balance report generation meeting < 3 second performance target for 10,000 accounts. The system ensures financial integrity through controlled period closing, prevents backdated entries to closed periods, and provides comprehensive financial reporting for period-end analysis.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-GL-003**: **Period closing** with validation checks and permanent lock-down of financial data
- **FR-GL-004**: **Account balance inquiries** with drill-down capability to source transactions
- **FR-GL-006**: **Trial balance report** with comparative period support (current vs prior)

**Business Rules:**
- **BR-GL-003**: Entries can only be posted to **active (open) fiscal periods**, closed periods reject all postings

**Integration Requirements:**
- **IR-GL-002**: Integrate with **Fiscal Period Management** for period status validation during posting

**Performance Requirements:**
- **PR-GL-003**: **Trial balance generation < 3 seconds** for 10,000 accounts using aggregated balances

**Event Requirements:**
- **EV-GL-002**: `FiscalPeriodClosedEvent` - when accounting period closed and locked

**Constraints:**
- **CON-001**: Fiscal year start month configurable per tenant (not always January)
- **CON-002**: Cannot reopen a closed period without financial controller approval and audit trail
- **CON-003**: Period closing must validate all entries balanced before allowing close
- **CON-004**: Trial balance must include all accounts (even zero balance) for completeness
- **CON-005**: Period closing process must be idempotent (can retry safely)

**Guidelines:**
- **GUD-001**: Use 12-period calendar year as default, support 13-period (4-4-5) as optional
- **GUD-002**: Period numbers 1-12 (or 1-13), not month names (language-independent)
- **GUD-003**: Lock periods sequentially (cannot close period 3 if period 2 is open)
- **GUD-004**: Generate period-end checklists for accounting users
- **GUD-005**: Trial balance export to Excel/PDF for auditors

**Patterns:**
- **PAT-001**: Period status workflow (setup → open → closing → closed → locked)
- **PAT-002**: Validation-before-close checklist pattern
- **PAT-003**: Materialized view for trial balance performance

## 2. Implementation Steps

### GOAL-001: Create Fiscal Period Schema and Model

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-GL-003, BR-GL-003, IR-GL-002, CON-001 | Implement fiscal_periods table with status workflow, configurable fiscal year structure, and period validation | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `database/migrations/2025_11_12_000004_create_fiscal_periods_table.php`. Add `declare(strict_types=1);`. Use anonymous migration class. Create table with columns: `id` (bigIncrements PK), `tenant_id` (uuid NOT NULL FK → tenants ON DELETE CASCADE), `fiscal_year` (integer NOT NULL), `period` (integer NOT NULL 1-13), `period_name` (string 50 NOT NULL - e.g., "January 2025", "Period 1 FY2025"), `start_date` (date NOT NULL), `end_date` (date NOT NULL), `status` (string 20 DEFAULT 'setup' - values: setup, open, closing, closed, locked), `closed_at` (timestamp nullable), `closed_by` (unsignedBigInteger nullable FK → users), `reopened_at` (timestamp nullable), `reopened_by` (unsignedBigInteger nullable FK → users), `lock_reason` (text nullable), `metadata` (json nullable), `timestamps()` | | |
| TASK-002 | Add UNIQUE constraint: `(tenant_id, fiscal_year, period)` using `$table->unique([...], 'uq_fiscal_periods_year_period');`. Add CHECK constraint: `CHECK (period >= 1 AND period <= 13)`, `CHECK (end_date > start_date)`, `CHECK (status IN ('setup', 'open', 'closing', 'closed', 'locked'))`. Add indexes: `idx_fiscal_periods_tenant` (tenant_id), `idx_fiscal_periods_year` (fiscal_year), `idx_fiscal_periods_status` (status), `idx_fiscal_periods_dates` (start_date, end_date) | | |
| TASK-003 | Create `app/Domains/GeneralLedger/Models/FiscalPeriod.php` extending Model. Add `declare(strict_types=1);`. Use traits: `BelongsToTenant`, `HasFactory`. Define fillable: `['tenant_id', 'fiscal_year', 'period', 'period_name', 'start_date', 'end_date', 'status', 'closed_at', 'closed_by', 'reopened_at', 'reopened_by', 'lock_reason', 'metadata']`. Define casts: `['fiscal_year' => 'integer', 'period' => 'integer', 'start_date' => 'date', 'end_date' => 'date', 'closed_at' => 'datetime', 'reopened_at' => 'datetime', 'metadata' => 'array']` | | |
| TASK-004 | In FiscalPeriod model, define relationships: `closedBy(): BelongsTo` → User nullable, `reopenedBy(): BelongsTo` → User nullable, `entries(): HasMany` → GLEntry via (fiscal_year, fiscal_period) composite key. Implement helper methods: `isOpen(): bool` returning `$this->status === 'open';`, `isClosed(): bool` returning `in_array($this->status, ['closed', 'locked']);`, `isLocked(): bool` returning `$this->status === 'locked';`, `canPost(): bool` returning `$this->isOpen();` | | |
| TASK-005 | Implement scopes: `scopeOpen(Builder $query): Builder` adding `where('status', 'open')`, `scopeClosed(Builder $query): Builder` adding `whereIn('status', ['closed', 'locked'])`, `scopeForYear(Builder $query, int $year): Builder` adding `where('fiscal_year', $year)`, `scopeCurrent(Builder $query): Builder` adding `whereDate('start_date', '<=', now())->whereDate('end_date', '>=', now())` (finds current period by date) | | |
| TASK-006 | Implement `getPeriodLabelAttribute(): string` computed attribute returning formatted label: if period 1-12, map to month names using Carbon: `Carbon::create($this->fiscal_year, $this->period, 1)->format('F Y')`. If period 13, return "Period 13 FY{$this->fiscal_year}". This provides human-readable period names like "January 2025" | | |

### GOAL-002: Implement Period Initialization and Management Actions

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-GL-003, CON-001, GUD-001 | Create actions for fiscal year setup, period opening, and status transitions with configurable calendar types | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `app/Domains/GeneralLedger/Actions/InitializeFiscalYearAction.php` extending Action. Add `declare(strict_types=1);`. Use trait: `AsAction`. Implement `rules(): array` returning `['tenant_id' => ['required', 'uuid', 'exists:tenants,id'], 'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'], 'start_month' => ['required', 'integer', 'min:1', 'max:12'], 'calendar_type' => ['required', 'string', Rule::in(['12-period', '13-period'])]]` | | |
| TASK-008 | Implement `handle(string $tenantId, int $fiscalYear, int $startMonth, string $calendarType = '12-period'): Collection`. Validate fiscal year doesn't exist: `if (FiscalPeriod::where(['tenant_id' => $tenantId, 'fiscal_year' => $fiscalYear])->exists()) { throw new ValidationException("Fiscal year {$fiscalYear} already exists"); }`. Initialize periods collection: `$periods = collect();` | | |
| TASK-009 | For 12-period calendar, create 12 periods: `for ($period = 1; $period <= 12; $period++) { $periodMonth = ($startMonth + $period - 1) % 12 ?: 12; $periodYear = $fiscalYear + (($startMonth + $period - 1) > 12 ? 1 : 0); $startDate = Carbon::create($periodYear, $periodMonth, 1)->startOfMonth(); $endDate = $startDate->copy()->endOfMonth(); $fiscalPeriod = FiscalPeriod::create(['tenant_id' => $tenantId, 'fiscal_year' => $fiscalYear, 'period' => $period, 'period_name' => $startDate->format('F Y'), 'start_date' => $startDate, 'end_date' => $endDate, 'status' => 'setup']); $periods->push($fiscalPeriod); }`. Open first period: `$periods->first()->update(['status' => 'open']);` | | |
| TASK-010 | For 13-period (4-4-5 weeks) calendar, calculate periods: Implement 4-4-5 calendar logic where quarters have 3 periods of 4, 4, 5 weeks respectively. Use Carbon to calculate week boundaries. Create 13 periods with proper start/end dates. First period starts on fiscal year start date (typically first day of first quarter). Mark as implementation note: "4-4-5 calendar requires configurable quarter start dates, implement based on retail calendar standards" | | |
| TASK-011 | Create `app/Domains/GeneralLedger/Actions/OpenFiscalPeriodAction.php` extending Action. Implement `rules(): array` returning `['period_id' => ['required', 'integer', 'exists:fiscal_periods,id']]`. Implement `handle(int $periodId): FiscalPeriod`. Fetch period: `$period = FiscalPeriod::findOrFail($periodId);`. Validate status is 'setup': `if ($period->status !== 'setup') { throw new InvalidStatusException("Period must be in setup status to open"); }`. Validate previous period closed: `$previousPeriod = FiscalPeriod::where(['tenant_id' => $period->tenant_id, 'fiscal_year' => $period->fiscal_year, 'period' => $period->period - 1])->first(); if ($previousPeriod && !$previousPeriod->isClosed()) { throw new ValidationException("Previous period must be closed before opening this period"); }`. Update: `$period->update(['status' => 'open']);`. Return: `$period;` | | |

### GOAL-003: Implement Period Closing Process with Validation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-GL-003, BR-GL-003, CON-003, CON-005, EV-GL-002 | Create CloseFiscalPeriodAction with pre-close validation checklist, balance verification, and event dispatching | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-012 | Create `app/Domains/GeneralLedger/Actions/CloseFiscalPeriodAction.php` extending Action. Add `declare(strict_types=1);`. Use trait: `AsAction`. Inject: `ValidatePeriodClosingAction $validator`, `ActivityLoggerContract $activityLogger`. Implement `rules(): array` returning `['period_id' => ['required', 'integer', 'exists:fiscal_periods,id'], 'force' => ['nullable', 'boolean']]` | | |
| TASK-013 | Implement `handle(int $periodId, bool $force = false): array`. Use DB transaction. Fetch period with locking: `$period = FiscalPeriod::lockForUpdate()->findOrFail($periodId);`. Validate status: `if (!$period->isOpen()) { throw new InvalidStatusException("Only open periods can be closed"); }`. Run validation: `$validationResults = $this->validator->run($period); if ($validationResults['has_errors'] && !$force) { throw new ValidationException("Period closing validation failed", ['errors' => $validationResults['errors'], 'warnings' => $validationResults['warnings']]); }` | | |
| TASK-014 | Update period to closing status: `$period->update(['status' => 'closing']);`. Perform period-end tasks: 1) Ensure all entries posted (no drafts): `$draftCount = GLEntry::where(['fiscal_year' => $period->fiscal_year, 'fiscal_period' => $period->period, 'status' => 'draft'])->count(); if ($draftCount > 0) { throw new ValidationException("{$draftCount} draft entries exist. Post or delete before closing."); }`. 2) Verify all entries balanced: `$unbalancedEntries = GLEntry::posted()->forPeriod($period->fiscal_year, $period->period)->get()->reject(fn($e) => $e->isBalanced()); if ($unbalancedEntries->count() > 0) { throw new ValidationException("Unbalanced entries exist: " . $unbalancedEntries->pluck('entry_number')->join(', ')); }` | | |
| TASK-015 | Update aggregated balances: Trigger balance update for all accounts with transactions in this period: `$accounts = GLEntryLine::whereHas('entry', fn($q) => $q->forPeriod($period->fiscal_year, $period->period))->distinct()->pluck('account_id'); foreach ($accounts as $accountId) { UpdateAccountBalanceAction::dispatch($accountId, $period->fiscal_year, $period->period, $period->tenant_id); }`. Wait for queue completion if synchronous mode | | |
| TASK-016 | Update period to closed status: `$period->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => auth()->id()]);`. Log activity: `$this->activityLogger->log('Fiscal Period Closed', $period, auth()->user(), ['validation_results' => $validationResults]);`. Dispatch event: `event(new FiscalPeriodClosedEvent($period, $validationResults));`. Return: `['period' => $period, 'validation' => $validationResults, 'message' => "Period {$period->period_label} closed successfully"];`. Commit transaction | | |
| TASK-017 | Create `app/Domains/GeneralLedger/Actions/ValidatePeriodClosingAction.php` extending Action. Implement `handle(FiscalPeriod $period): array`. Initialize results: `$errors = []; $warnings = [];`. Validate: 1) All entries posted (no drafts). 2) All entries balanced. 3) All required accounts have balances (Assets, Liabilities, Equity, Revenue, Expense categories non-empty). 4) Trial balance balanced (total debits = total credits). 5) No pending reconciliations (if bank reconciliation module exists). 6) All recurring entries processed for period. Return: `['has_errors' => count($errors) > 0, 'has_warnings' => count($warnings) > 0, 'errors' => $errors, 'warnings' => $warnings, 'checks_passed' => count($errors) === 0];` | | |
| TASK-018 | Create `app/Domains/GeneralLedger/Events/FiscalPeriodClosedEvent.php`. Add `declare(strict_types=1);`. Implement ShouldBroadcast. Define public readonly properties: `FiscalPeriod $period`, `array $validationResults`, `string $tenantId`. Implement broadcastOn() returning private channel for tenant. Add broadcastWith() returning period summary and validation data | | |

### GOAL-004: Implement Account Balance Inquiry with Transaction Drill-Down

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-GL-004 | Create GetAccountBalanceAction with period-to-date and year-to-date calculations, transaction listing with pagination, and drill-down to source documents | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-019 | Create `app/Domains/GeneralLedger/Actions/GetAccountBalanceAction.php` extending Action. Add `declare(strict_types=1);`. Use trait: `AsAction`. Implement `rules(): array` returning `['account_id' => ['required', 'integer', 'exists:accounts,id'], 'fiscal_year' => ['required', 'integer'], 'fiscal_period' => ['nullable', 'integer', 'min:1', 'max:13'], 'include_transactions' => ['nullable', 'boolean'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]` | | |
| TASK-020 | Implement `handle(int $accountId, int $fiscalYear, ?int $fiscalPeriod = null, bool $includeTransactions = false, int $perPage = 50): array`. Get tenant: `$tenant = auth()->user()->tenant;`. Get account: `$account = Account::with('type')->findOrFail($accountId);`. If fiscal_period provided, get period-to-date balance from gl_account_balances: `$balance = GLAccountBalance::where(['tenant_id' => $tenant->id, 'account_id' => $accountId, 'fiscal_year' => $fiscalYear, 'fiscal_period' => $fiscalPeriod])->first();`. If not found, calculate: `UpdateAccountBalanceAction::run(...)` | | |
| TASK-021 | If fiscal_period is null, calculate year-to-date: Sum all period balances for the year: `$periodBalances = GLAccountBalance::where(['tenant_id' => $tenant->id, 'account_id' => $accountId, 'fiscal_year' => $fiscalYear])->get(); $ytdBalance = ['opening_balance' => $periodBalances->first()?->opening_balance ?? 0, 'debit_total' => $periodBalances->sum('debit_total'), 'credit_total' => $periodBalances->sum('credit_total'), 'closing_balance' => $periodBalances->last()?->closing_balance ?? 0, 'entry_count' => $periodBalances->sum('entry_count')];` | | |
| TASK-022 | If includeTransactions=true, fetch GL entry lines: Build query: `$query = GLEntryLine::with(['entry' => fn($q) => $q->with('source')])->where('account_id', $accountId)->whereHas('entry', fn($q) => $q->where(['tenant_id' => $tenant->id, 'fiscal_year' => $fiscalYear, 'status' => 'posted']));`. If fiscalPeriod provided: `$query->whereHas('entry', fn($q) => $q->where('fiscal_period', $fiscalPeriod));`. Order by: `$query->orderBy(GLEntry::select('posting_date')->whereColumn('gl_entries.id', 'gl_entry_lines.gl_entry_id'), 'desc')->orderBy('line_number');`. Paginate: `$transactions = $query->paginate($perPage);` | | |
| TASK-023 | Format transaction data: Transform each line: `$formattedTransactions = $transactions->map(fn($line) => ['date' => $line->entry->posting_date, 'entry_number' => $line->entry->entry_number, 'description' => $line->description ?? $line->entry->description, 'debit' => $line->debit_base_amount, 'credit' => $line->credit_base_amount, 'balance' => null, // Computed by frontend cumulatively 'source_type' => $line->entry->source_type, 'source_id' => $line->entry->source_id, 'currency' => $line->currency_code, 'foreign_amount' => $line->foreign_amount]);` | | |
| TASK-024 | Return comprehensive balance data: `return ['account' => ['id' => $account->id, 'code' => $account->code, 'name' => $account->name, 'type' => $account->account_type->value, 'normal_balance' => $account->normalBalance()], 'fiscal_year' => $fiscalYear, 'fiscal_period' => $fiscalPeriod, 'period_label' => $fiscalPeriod ? FiscalPeriod::where([...])->first()?->period_label : "Year {$fiscalYear}", 'balance' => $balance ?? $ytdBalance, 'transactions' => $includeTransactions ? $formattedTransactions : null, 'pagination' => $includeTransactions ? ['total' => $transactions->total(), 'per_page' => $transactions->perPage(), 'current_page' => $transactions->currentPage(), 'last_page' => $transactions->lastPage()] : null];` | | |

### GOAL-005: Implement Trial Balance Report Generation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-GL-006, PR-GL-003, CON-004 | Create GenerateTrialBalanceAction meeting < 3 second performance target, with comparative period support and Excel export | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-025 | Create `app/Domains/GeneralLedger/Actions/GenerateTrialBalanceAction.php` extending Action. Add `declare(strict_types=1);`. Use trait: `AsAction`. Implement `rules(): array` returning `['fiscal_year' => ['required', 'integer'], 'fiscal_period' => ['nullable', 'integer', 'min:1', 'max:13'], 'include_zero_balances' => ['nullable', 'boolean'], 'compare_with_period' => ['nullable', 'integer', 'min:1', 'max:13'], 'account_type_filter' => ['nullable', 'string', Rule::in(AccountType::values())]]` | | |
| TASK-026 | Implement `handle(int $fiscalYear, ?int $fiscalPeriod = null, bool $includeZeroBalances = true, ?int $compareWithPeriod = null, ?string $accountTypeFilter = null): array`. Start performance timer: `$startTime = microtime(true);`. Get tenant: `$tenant = auth()->user()->tenant;`. Determine period scope: If fiscalPeriod null, use last period of year: `$fiscalPeriod = FiscalPeriod::forYear($fiscalYear)->where('tenant_id', $tenant->id)->max('period');` | | |
| TASK-027 | Fetch account balances from aggregated table (FAST - PR-GL-003): `$balancesQuery = GLAccountBalance::with(['account' => fn($q) => $q->with('type')])->where(['tenant_id' => $tenant->id, 'fiscal_year' => $fiscalYear, 'fiscal_period' => $fiscalPeriod]); if ($accountTypeFilter) { $balancesQuery->whereHas('account', fn($q) => $q->where('account_type', $accountTypeFilter)); } if (!$includeZeroBalances) { $balancesQuery->where(fn($q) => $q->where('closing_balance', '!=', 0)); } $balances = $balancesQuery->get();` (using pre-aggregated data for speed) | | |
| TASK-028 | If compareWithPeriod provided, fetch comparison data: `$comparisonBalances = GLAccountBalance::where(['tenant_id' => $tenant->id, 'fiscal_year' => $fiscalYear, 'fiscal_period' => $compareWithPeriod])->get()->keyBy('account_id');`. Format accounts with comparison: `$accounts = $balances->map(function($balance) use ($comparisonBalances) { $comparison = $comparisonBalances[$balance->account_id] ?? null; $variance = $comparison ? bcsub($balance->closing_balance, $comparison->closing_balance, 4) : null; return [...account data..., 'comparison' => $comparison?->closing_balance, 'variance' => $variance, 'variance_percent' => $comparison && $comparison->closing_balance != 0 ? bcmul(bcdiv($variance, $comparison->closing_balance, 6), '100', 2) : null]; });` | | |
| TASK-029 | Group accounts by type and calculate totals: `$grouped = $accounts->groupBy('account.type'); $totals = ['total_debits' => '0', 'total_credits' => '0', 'by_type' => []]; foreach (AccountType::cases() as $type) { $typeAccounts = $grouped[$type->value] ?? collect(); $typeDebits = $typeAccounts->where('account.normal_balance', 'debit')->sum('closing_balance'); $typeCredits = $typeAccounts->where('account.normal_balance', 'credit')->sum('closing_balance'); $totals['by_type'][$type->value] = ['debit' => $typeDebits, 'credit' => $typeCredits, 'count' => $typeAccounts->count()]; if ($type->normalBalance() === 'debit') { $totals['total_debits'] = bcadd($totals['total_debits'], $typeDebits, 4); } else { $totals['total_credits'] = bcadd($totals['total_credits'], $typeCredits, 4); } }` | | |
| TASK-030 | Calculate performance and validate balance: `$endTime = microtime(true); $duration = round(($endTime - $startTime) * 1000, 2); // milliseconds $isBalanced = bccomp($totals['total_debits'], $totals['total_credits'], 4) === 0; if ($duration > 3000) { Log::warning('Trial balance generation exceeded 3s target', ['duration_ms' => $duration, 'account_count' => $accounts->count()]); }` (PR-GL-003 compliance check) | | |
| TASK-031 | Return comprehensive trial balance: `return ['period' => ['fiscal_year' => $fiscalYear, 'fiscal_period' => $fiscalPeriod, 'period_label' => FiscalPeriod::where([...])->first()?->period_label, 'comparison_period' => $compareWithPeriod, 'comparison_label' => $compareWithPeriod ? FiscalPeriod::where([...])->first()?->period_label : null], 'accounts' => $accounts, 'totals' => [...$totals, 'is_balanced' => $isBalanced, 'variance' => $isBalanced ? '0' : bcsub($totals['total_debits'], $totals['total_credits'], 4)], 'metadata' => ['generated_at' => now()->toIso8601String(), 'duration_ms' => $duration, 'account_count' => $accounts->count(), 'base_currency' => $tenant->base_currency, 'include_zero_balances' => $includeZeroBalances]];`. Add cache: Cache for 5 minutes using key: `trial_balance:{$tenant->id}:{$fiscalYear}:{$fiscalPeriod}` | | |
| TASK-032 | Create `app/Domains/GeneralLedger/Actions/ExportTrialBalanceAction.php` for Excel export. Use `Maatwebsite\Excel\Facades\Excel`. Implement `handle(array $trialBalanceData, string $format = 'xlsx'): string` returning file path. Create Excel with sheets: 1) Summary (totals by account type), 2) Detail (all accounts), 3) Comparison (if comparison period provided). Format with headers, totals, and formatting (bold headers, number formatting for amounts). Return downloadable file path | | |

## 3. Alternatives

- **ALT-001**: Allow posting to any period, validate at reporting time - **Rejected** because it violates BR-GL-003, creates data integrity issues, makes period closing meaningless, not standard accounting practice
- **ALT-002**: Automatically close periods based on date (no manual close) - **Rejected** because manual close provides control and validation opportunity, accounting staff need to review before closing, prevents accidental close
- **ALT-003**: Calculate trial balance from raw GL entries (no aggregation) - **Rejected** because it cannot meet PR-GL-003 (< 3s for 10,000 accounts), aggregated balances essential for performance, real-time calculation too slow
- **ALT-004**: Use calendar year only (no configurable fiscal year) - **Rejected** because many businesses use non-calendar fiscal years (July-June, April-March common), CON-001 requires configurability
- **ALT-005**: Allow reopening closed periods without approval - **Rejected** because it violates audit requirements, creates regulatory compliance issues, CON-002 requires controlled reopening

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `brick/math` ^0.12 (decimal arithmetic for balance calculations)
- **DEP-002**: `maatwebsite/excel` ^3.1 (Excel export for trial balance) - optional

**Internal Dependencies:**
- **DEP-003**: PRD01-SUB08-PLAN01 (GL Core) - GLEntry, GLEntryLine models
- **DEP-004**: PRD01-SUB08-PLAN02 (Multi-Currency) - GLAccountBalance model for aggregated balances
- **DEP-005**: PRD01-SUB07 (Chart of Accounts) - Account model, AccountType enum
- **DEP-006**: PRD01-SUB02 (Authentication) - User model for closed_by/reopened_by

**Configuration:**
- **DEP-007**: Tenant-level fiscal year configuration (start_month, calendar_type)

## 5. Files

**Migrations:**
- `database/migrations/2025_11_12_000004_create_fiscal_periods_table.php` - Period structure
- `database/migrations/[existing]_add_fiscal_config_to_tenants.php` - Tenant fiscal config

**Models:**
- `app/Domains/GeneralLedger/Models/FiscalPeriod.php` - Period model with status workflow
- `app/Domains/GeneralLedger/Models/GLEntry.php` - Updated with period validation
- `app/Domains/GeneralLedger/Models/GLAccountBalance.php` - Used for fast queries

**Actions:**
- `app/Domains/GeneralLedger/Actions/InitializeFiscalYearAction.php` - Fiscal year setup
- `app/Domains/GeneralLedger/Actions/OpenFiscalPeriodAction.php` - Open period
- `app/Domains/GeneralLedger/Actions/CloseFiscalPeriodAction.php` - Close period
- `app/Domains/GeneralLedger/Actions/ValidatePeriodClosingAction.php` - Pre-close validation
- `app/Domains/GeneralLedger/Actions/ReopenFiscalPeriodAction.php` - Reopen with approval
- `app/Domains/GeneralLedger/Actions/GetAccountBalanceAction.php` - Balance inquiry
- `app/Domains/GeneralLedger/Actions/GenerateTrialBalanceAction.php` - Trial balance report
- `app/Domains/GeneralLedger/Actions/ExportTrialBalanceAction.php` - Excel export

**Events:**
- `app/Domains/GeneralLedger/Events/FiscalPeriodClosedEvent.php` - Period closed
- `app/Domains/GeneralLedger/Events/FiscalPeriodReopenedEvent.php` - Period reopened

**Exceptions:**
- `app/Domains/GeneralLedger/Exceptions/InvalidStatusException.php` - Status validation errors
- `app/Domains/GeneralLedger/Exceptions/PeriodClosedException.php` - Closed period posting attempts

**Factories:**
- `database/factories/FiscalPeriodFactory.php` - Period factory with states

**Commands:**
- `app/Console/Commands/InitializeFiscalYearCommand.php` - CLI for fiscal year setup
- `app/Console/Commands/CloseFiscalPeriodCommand.php` - CLI for period closing

**Configuration:**
- `config/accounting.php` - Fiscal year defaults, calendar types

## 6. Testing

**Unit Tests (12 tests):**
- **TEST-001**: `test_fiscal_period_validates_date_range` - End date > start date
- **TEST-002**: `test_fiscal_period_validates_period_number` - 1-13 only
- **TEST-003**: `test_fiscal_period_status_helpers_work` - isOpen(), isClosed(), canPost()
- **TEST-004**: `test_fiscal_period_label_formatted_correctly` - "January 2025" format
- **TEST-005**: `test_initialize_fiscal_year_creates_12_periods` - 12-period calendar
- **TEST-006**: `test_initialize_fiscal_year_respects_start_month` - July start → July-June periods
- **TEST-007**: `test_open_period_validates_previous_closed` - Sequential opening
- **TEST-008**: `test_close_period_validates_all_posted` - No draft entries
- **TEST-009**: `test_validation_detects_unbalanced_entries` - Pre-close check
- **TEST-010**: `test_account_balance_computes_ytd_correctly` - Sum of periods
- **TEST-011**: `test_trial_balance_groups_by_account_type` - Asset, Liability, etc.
- **TEST-012**: `test_trial_balance_calculates_totals_correctly` - Debits = Credits

**Feature Tests (15 tests):**
- **TEST-013**: `test_can_initialize_fiscal_year` - Create 12 periods
- **TEST-014**: `test_first_period_opened_automatically` - Period 1 = open
- **TEST-015**: `test_can_open_next_period_after_closing_previous` - Sequential workflow
- **TEST-016**: `test_cannot_post_to_closed_period` - PeriodClosedException thrown
- **TEST-017**: `test_can_close_period_with_all_entries_posted` - Close succeeds
- **TEST-018**: `test_cannot_close_period_with_draft_entries` - Validation fails
- **TEST-019**: `test_cannot_close_period_with_unbalanced_entries` - Validation fails
- **TEST-020**: `test_period_closing_updates_aggregated_balances` - Listener fires
- **TEST-021**: `test_period_closed_event_dispatched` - FiscalPeriodClosedEvent
- **TEST-022**: `test_can_get_account_balance_with_transactions` - Drill-down works
- **TEST-023**: `test_account_balance_respects_fiscal_period` - Period filtering
- **TEST-024**: `test_can_generate_trial_balance` - All accounts listed
- **TEST-025**: `test_trial_balance_excludes_zero_balances_when_requested` - Filtering
- **TEST-026**: `test_trial_balance_includes_comparison_period` - Variance calculated
- **TEST-027**: `test_can_export_trial_balance_to_excel` - File generated

**Integration Tests (8 tests):**
- **TEST-028**: `test_posting_validates_period_status` - Integration with PostGLEntryAction
- **TEST-029**: `test_period_closing_sequential_enforcement` - Cannot skip periods
- **TEST-030**: `test_trial_balance_matches_account_balances` - Data consistency
- **TEST-031**: `test_fiscal_year_initialization_respects_tenant_config` - Tenant settings
- **TEST-032**: `test_period_reopening_requires_approval` - Authorization check
- **TEST-033**: `test_account_balance_aggregation_updated_on_posting` - Real-time update
- **TEST-034**: `test_trial_balance_cached_correctly` - Cache hit on 2nd call
- **TEST-035**: `test_13_period_calendar_creates_correct_dates` - 4-4-5 weeks

**Performance Tests (5 tests):**
- **TEST-036**: `test_trial_balance_meets_3_second_target` - PR-GL-003 compliance
- **TEST-037**: `test_trial_balance_performance_with_10000_accounts` - Target load
- **TEST-038**: `test_account_balance_query_under_100ms` - Fast retrieval
- **TEST-039**: `test_period_closing_scales_with_transaction_volume` - 100,000 entries
- **TEST-040**: `test_trial_balance_export_completes_under_10_seconds` - Excel generation

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Period closing takes too long with high transaction volume - **Mitigation**: Run balance aggregation asynchronously during month, pre-validate throughout period, implement progress indicator for long-running closes
- **RISK-002**: Users accidentally close wrong period - **Mitigation**: Require confirmation dialog, show period summary before closing, implement undo window (1 hour), add approval workflow for critical periods (year-end)
- **RISK-003**: Trial balance performance degrades with accounts > 10,000 - **Mitigation**: Implement pagination for large COAs, add account filtering by type/category, use database query optimization (proper indexes)
- **RISK-004**: Reopening periods creates audit trail gaps - **Mitigation**: Log all reopen actions with detailed reason, require financial controller approval, send alerts to auditors, maintain immutable history
- **RISK-005**: 13-period (4-4-5) calendar implementation complexity - **Mitigation**: Implement 12-period first, add 13-period as enhancement, document retail calendar standards, provide configuration wizard

**Assumptions:**
- **ASSUMPTION-001**: Most tenants use 12-period calendar (90%), 13-period is niche (retail industry)
- **ASSUMPTION-002**: Period closing happens once per month, not more frequently
- **ASSUMPTION-003**: Account balance aggregation lag < 5 minutes is acceptable (not real-time)
- **ASSUMPTION-004**: Trial balance requested < 10 times per day (not constantly)
- **ASSUMPTION-005**: Excel export for auditors sufficient, no PDF requirement initially

## 8. KIV for future implementations

- **KIV-001**: Implement 13-period (4-4-5 weeks) retail calendar with configurable quarter start dates
- **KIV-002**: Add period-end checklist templates (customizable by tenant)
- **KIV-003**: Implement automatic period closing scheduling (close Period N on Day X+5)
- **KIV-004**: Add trial balance drill-down to transaction details in UI
- **KIV-005**: Implement financial statement generation (Balance Sheet, Income Statement) from trial balance
- **KIV-006**: Add comparative analysis (3-month trend, 12-month trend, YoY comparison)
- **KIV-007**: Implement trial balance export to accounting software formats (QuickBooks, Xero)
- **KIV-008**: Add period budget vs actual variance reporting
- **KIV-009**: Implement rolling forecast based on historical period data
- **KIV-010**: Add period-end automation workflows (auto-post recurring entries, auto-run reports)

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB08-GENERAL-LEDGER.md](../prd/prd-01/PRD01-SUB08-GENERAL-LEDGER.md)
- **Related Plans:**
  - PRD01-SUB08-PLAN01 (GL Core) - Entry posting foundation
  - PRD01-SUB08-PLAN02 (Multi-Currency) - GLAccountBalance aggregation used here
  - PRD01-SUB07-PLAN01 (COA Foundation) - Account types for trial balance grouping
- **External Documentation:**
  - Fiscal Year: https://www.investopedia.com/terms/f/fiscalyear.asp
  - Period Closing Best Practices: https://www.accountingtools.com/articles/how-to-close-the-books
  - Trial Balance: https://www.investopedia.com/terms/t/trial_balance.asp
  - 4-4-5 Retail Calendar: https://en.wikipedia.org/wiki/4%E2%80%934%E2%80%935_calendar
  - Accounting Period Close Checklist: https://www.cfo.com/content/doingmore-witless/2010/08/accounting-close-checklist/
