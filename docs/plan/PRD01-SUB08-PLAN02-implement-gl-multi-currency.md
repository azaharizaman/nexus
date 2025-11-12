---
plan: Implement General Ledger Multi-Currency and Exchange Rate Management
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, accounting, general-ledger, multi-currency, exchange-rates, forex]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan extends the General Ledger system with multi-currency transaction support, exchange rate management, and foreign currency revaluation. It implements currency-specific entry lines storing both base currency (tenant's functional currency) and foreign currency amounts with exchange rates, automatic conversion using configurable exchange rate providers, unrealized gain/loss calculation for foreign currency balances, and aggregated monthly account balances for high-performance reporting. The system ensures all GL operations support multiple currencies while maintaining double-entry balance in base currency and providing accurate financial reporting across currencies.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-GL-002**: **Multi-currency transactions** with exchange rate conversion at transaction time
- **FR-GL-004**: **Account balance inquiries** with drill-down to source transactions

**Business Rules:**
- **BR-GL-004**: Foreign currency transactions MUST record **base amount + foreign amount + exchange rate** for audit trail

**Data Requirements:**
- **DR-GL-001**: Store **aggregated monthly account balances** for high-performance reporting queries
- **DR-GL-002**: GL entry lines store: debit/credit in base currency AND foreign currency with exchange rate

**Performance Requirements:**
- **PR-GL-002**: **Balance queries < 100ms** using pre-aggregated monthly balances (not real-time calculation)

**Event Requirements:**
- **EV-GL-004**: `BalanceRevaluatedEvent` - when foreign currency balances revalued at period end

**Constraints:**
- **CON-001**: Base currency (functional currency) is set at tenant level, cannot change after transactions exist
- **CON-002**: Exchange rates effective for a specific date, no backdating after entries posted
- **CON-003**: Monthly balance aggregation must be updated incrementally (not full recalculation)
- **CON-004**: Foreign currency balances revalued only at period-end, not continuously
- **CON-005**: All GL entries must balance in BASE currency (foreign amounts informational only)

**Guidelines:**
- **GUD-001**: Use brick/math for all currency arithmetic
- **GUD-002**: Store exchange rates with 6 decimal precision (e.g., 1.234567)
- **GUD-003**: Use ISO 4217 currency codes (USD, EUR, JPY, etc.)
- **GUD-004**: Revaluation creates GL entries like any other transaction
- **GUD-005**: Aggregate balances updated via event listeners

**Patterns:**
- **PAT-001**: Dual-currency storage (base + foreign on each line)
- **PAT-002**: Exchange rate lookup with effective date
- **PAT-003**: Incremental balance aggregation (not batch recalculation)

## 2. Implementation Steps

### GOAL-001: Extend GL Schema for Multi-Currency Support

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-GL-004, DR-GL-002 | Add currency fields to GL entry lines, create exchange_rates table, add base_currency to tenants table | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `database/migrations/2025_11_12_000002_add_multi_currency_to_gl_entries.php`. Add `declare(strict_types=1);`. Use anonymous migration class | | |
| TASK-002 | In up() method, add columns to gl_entry_lines table: `currency_code` (string 3 DEFAULT 'USD' NOT NULL - ISO 4217), `exchange_rate` (decimal 12,6 DEFAULT 1.0 NOT NULL), `debit_foreign_amount` (decimal 20,4 DEFAULT 0), `credit_foreign_amount` (decimal 20,4 DEFAULT 0), `debit_base_amount` (decimal 20,4 DEFAULT 0 - computed: debit_foreign * exchange_rate), `credit_base_amount` (decimal 20,4 DEFAULT 0 - computed: credit_foreign * exchange_rate). Rename existing debit_amount/credit_amount to these new base fields using ALTER TABLE | | |
| TASK-003 | Add index to gl_entry_lines: `idx_gl_lines_currency` (currency_code) for filtering and reporting. Update existing CHECK constraint to use base amounts: `ALTER TABLE gl_entry_lines DROP CONSTRAINT chk_gl_lines_amount; ALTER TABLE gl_entry_lines ADD CONSTRAINT chk_gl_lines_amount CHECK ((debit_base_amount > 0 AND credit_base_amount = 0) OR (credit_base_amount > 0 AND debit_base_amount = 0) OR (debit_base_amount = 0 AND credit_base_amount = 0));` | | |
| TASK-004 | Create exchange_rates table with columns: `id` (bigIncrements PK), `tenant_id` (uuid NOT NULL FK → tenants), `from_currency` (string 3 NOT NULL), `to_currency` (string 3 NOT NULL), `rate` (decimal 12,6 NOT NULL), `effective_date` (date NOT NULL), `source` (string 50 nullable - 'manual', 'api', 'provider'), `metadata` (json nullable), `created_at` (timestamp), `updated_at` (timestamp). Add UNIQUE constraint: `(tenant_id, from_currency, to_currency, effective_date)` | | |
| TASK-005 | Add indexes to exchange_rates: `idx_exchange_rates_tenant` (tenant_id), `idx_exchange_rates_lookup` (from_currency, to_currency, effective_date DESC) for fast rate lookup. Add FK: `tenant_id` → tenants ON DELETE CASCADE | | |
| TASK-006 | In tenants table migration `database/migrations/[existing]_add_base_currency_to_tenants.php`, add column: `base_currency` (string 3 DEFAULT 'USD' NOT NULL) using ALTER TABLE. Add index: `idx_tenants_currency` (base_currency). This is tenant's functional currency for financial reporting | | |

### GOAL-002: Create Exchange Rate Model and Rate Lookup Service

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-GL-002, BR-GL-004 | Implement ExchangeRate model, rate lookup service with caching, and automatic rate conversion logic | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `app/Domains/GeneralLedger/Models/ExchangeRate.php` extending Model. Add `declare(strict_types=1);`. Use traits: `BelongsToTenant`, `HasFactory`. Define fillable: `['tenant_id', 'from_currency', 'to_currency', 'rate', 'effective_date', 'source', 'metadata']`. Define casts: `['rate' => 'decimal:6', 'effective_date' => 'date', 'metadata' => 'array']` | | |
| TASK-008 | In ExchangeRate model, implement validation: `protected static function booted(): void { static::creating(function ($rate) { if ($rate->from_currency === $rate->to_currency) { throw new ValidationException('From and to currency cannot be same'); } if ($rate->rate <= 0) { throw new ValidationException('Exchange rate must be positive'); } }); }`. Implement `getInverseRateAttribute(): string` returning `bcdiv('1', $this->rate, 6);` (reciprocal rate) | | |
| TASK-009 | Implement scopes: `scopeForCurrencyPair(Builder $query, string $from, string $to): Builder` adding `where(['from_currency' => $from, 'to_currency' => $to])`, `scopeEffectiveOn(Builder $query, Carbon $date): Builder` adding `where('effective_date', '<=', $date)->orderBy('effective_date', 'desc')`, `scopeLatest(Builder $query): Builder` adding `orderBy('effective_date', 'desc')->orderBy('created_at', 'desc')` | | |
| TASK-010 | Create `app/Domains/GeneralLedger/Services/ExchangeRateService.php` service class. Add `declare(strict_types=1);`. Inject: `ExchangeRateRepositoryContract $repository`. Implement `getRate(string $fromCurrency, string $toCurrency, Carbon $effectiveDate, string $tenantId): ?string` method. Check if same currency: return '1.000000'. Query: `$rate = ExchangeRate::where('tenant_id', $tenantId)->forCurrencyPair($from, $to)->effectiveOn($date)->first();`. If not found, try inverse: query to_currency=from & from_currency=to, return inverse_rate. Cache for 1 hour using key: `exchange_rate:{$tenantId}:{$from}:{$to}:{$date->format('Y-m-d')}` | | |
| TASK-011 | Implement `convert(string $amount, string $fromCurrency, string $toCurrency, Carbon $effectiveDate, string $tenantId): array` returning `['amount' => (string), 'rate' => (string), 'from_currency' => (string), 'to_currency' => (string)]`. Get rate using getRate(). If rate null, throw `ExchangeRateNotFoundException`. Calculate: `$convertedAmount = bcmul($amount, $rate, 4);` using brick/math. Return array with converted amount and rate used | | |
| TASK-012 | Implement `convertToBase(string $foreignAmount, string $foreignCurrency, Carbon $date, string $tenantId): array`. Get tenant's base currency: `$baseCurrency = Tenant::find($tenantId)->base_currency;`. If foreign currency = base, return foreign amount unchanged. Otherwise call convert() method. Return `['base_amount' => $amount, 'exchange_rate' => $rate]` | | |

### GOAL-003: Update GL Entry Models and Actions for Multi-Currency

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-GL-002, BR-GL-004, CON-005 | Modify GLEntryLine model, update PostGLEntryAction to handle currency conversion, ensure balance validation uses base currency | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-013 | Update `app/Domains/GeneralLedger/Models/GLEntryLine.php`. Add to fillable: `'currency_code', 'exchange_rate', 'debit_foreign_amount', 'credit_foreign_amount', 'debit_base_amount', 'credit_base_amount'`. Update casts: `['debit_foreign_amount' => 'decimal:4', 'credit_foreign_amount' => 'decimal:4', 'debit_base_amount' => 'decimal:4', 'credit_base_amount' => 'decimal:4', 'exchange_rate' => 'decimal:6']` | | |
| TASK-014 | In GLEntryLine, implement `getAmountAttribute(): string` returning `$this->debit_base_amount > 0 ? $this->debit_base_amount : $this->credit_base_amount;` (base currency amount). Implement `getForeignAmountAttribute(): string` returning `$this->debit_foreign_amount > 0 ? $this->debit_foreign_amount : $this->credit_foreign_amount;`. Implement `isMultiCurrency(): bool` returning `$this->currency_code !== $this->entry->tenant->base_currency;` | | |
| TASK-015 | Update `app/Domains/GeneralLedger/Models/GLEntry.php`. Modify `isBalanced(): bool` to use BASE amounts: `$totalDebits = $this->lines->sum('debit_base_amount'); $totalCredits = $this->lines->sum('credit_base_amount'); return bccomp((string) $totalDebits, (string) $totalCredits, 4) === 0;` (CON-005: balance in base currency only) | | |
| TASK-016 | In GLEntry model, update computed attributes: `getTotalDebitsAttribute()` returns `$this->lines->sum('debit_base_amount');`, `getTotalCreditsAttribute()` returns `$this->lines->sum('credit_base_amount');`. Add new attributes: `getHasMultiCurrencyLinesAttribute(): bool` returning `$this->lines->where('currency_code', '!=', $this->tenant->base_currency)->count() > 0;` | | |
| TASK-017 | Update `app/Domains/GeneralLedger/Actions/PostGLEntryAction.php`. In handle() before validation, auto-convert foreign currency lines to base: `foreach ($entry->lines as $line) { if ($line->currency_code !== $entry->tenant->base_currency) { $conversion = app(ExchangeRateService::class)->convertToBase($line->debit_foreign_amount > 0 ? $line->debit_foreign_amount : $line->credit_foreign_amount, $line->currency_code, $entry->posting_date, $entry->tenant_id); $line->update(['exchange_rate' => $conversion['exchange_rate'], 'debit_base_amount' => $line->debit_foreign_amount > 0 ? $conversion['base_amount'] : 0, 'credit_base_amount' => $line->credit_foreign_amount > 0 ? $conversion['base_amount'] : 0]); } }` (auto-populate base amounts) | | |
| TASK-018 | Create `app/Domains/GeneralLedger/Exceptions/ExchangeRateNotFoundException.php` extending RuntimeException. Accept from_currency, to_currency, effective_date in constructor. Format message: "Exchange rate not found for {$from} to {$to} on {$date}" | | |

### GOAL-004: Implement Monthly Balance Aggregation for Performance

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-GL-001, PR-GL-002, FR-GL-004 | Create gl_account_balances table, implement incremental aggregation, provide fast balance query service achieving < 100ms | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-019 | Create migration `database/migrations/2025_11_12_000003_create_gl_account_balances_table.php`. Add `declare(strict_types=1);`. Create table with columns: `id` (bigIncrements PK), `tenant_id` (uuid NOT NULL FK → tenants), `account_id` (unsignedBigInteger NOT NULL FK → accounts), `fiscal_year` (integer NOT NULL), `fiscal_period` (integer NOT NULL 1-12), `currency_code` (string 3 NOT NULL - ISO 4217), `opening_balance` (decimal 20,4 DEFAULT 0), `debit_total` (decimal 20,4 DEFAULT 0), `credit_total` (decimal 20,4 DEFAULT 0), `closing_balance` (decimal 20,4 DEFAULT 0), `entry_count` (integer DEFAULT 0), `updated_at` (timestamp NOT NULL) | | |
| TASK-020 | Add UNIQUE constraint: `(tenant_id, account_id, fiscal_year, fiscal_period, currency_code)` using `$table->unique([...], 'uq_gl_balances_period_currency');`. Add indexes: `idx_gl_balances_tenant` (tenant_id), `idx_gl_balances_account` (account_id), `idx_gl_balances_period` (fiscal_year, fiscal_period), `idx_gl_balances_account_period` (account_id, fiscal_year, fiscal_period) for fast queries | | |
| TASK-021 | Create `app/Domains/GeneralLedger/Models/GLAccountBalance.php` extending Model. Add `declare(strict_types=1);`. Use traits: `BelongsToTenant`, `HasFactory`. Define table: `protected $table = 'gl_account_balances';`. Define fillable: `['tenant_id', 'account_id', 'fiscal_year', 'fiscal_period', 'currency_code', 'opening_balance', 'debit_total', 'credit_total', 'closing_balance', 'entry_count']`. Define casts: `['opening_balance' => 'decimal:4', 'debit_total' => 'decimal:4', 'credit_total' => 'decimal:4', 'closing_balance' => 'decimal:4', 'fiscal_year' => 'integer', 'fiscal_period' => 'integer', 'entry_count' => 'integer']` | | |
| TASK-022 | In GLAccountBalance model, define relationships: `account(): BelongsTo` → Account, `tenant(): BelongsTo` → Tenant. Implement `calculateClosingBalance(): string` returning `bcadd(bcadd($this->opening_balance, $this->debit_total, 4), bcmul($this->credit_total, '-1', 4), 4);` (opening + debits - credits). Implement scopes: `scopeForAccount(Builder $query, int $accountId)`, `scopeForPeriod(Builder $query, int $year, int $period)`, `scopeInBaseCurrency(Builder $query, string $baseCurrency)` | | |
| TASK-023 | Create `app/Domains/GeneralLedger/Actions/UpdateAccountBalanceAction.php` extending Action. Add `declare(strict_types=1);`. Use trait: `AsAction`. Implement `handle(int $accountId, int $fiscalYear, int $fiscalPeriod, string $tenantId): GLAccountBalance`. Use UPSERT logic: `$balance = GLAccountBalance::firstOrNew(['tenant_id' => $tenantId, 'account_id' => $accountId, 'fiscal_year' => $fiscalYear, 'fiscal_period' => $fiscalPeriod, 'currency_code' => Tenant::find($tenantId)->base_currency]);` | | |
| TASK-024 | In UpdateAccountBalanceAction, calculate totals from posted GL entries: `$lines = GLEntryLine::whereHas('entry', fn($q) => $q->where(['fiscal_year' => $year, 'fiscal_period' => $period, 'status' => 'posted'])->where('tenant_id', $tenantId))->where('account_id', $accountId)->get(); $balance->debit_total = $lines->sum('debit_base_amount'); $balance->credit_total = $lines->sum('credit_base_amount'); $balance->entry_count = $lines->count();`. Get opening balance from previous period's closing balance. Calculate closing balance. Save and return | | |
| TASK-025 | Create event listener `app/Domains/GeneralLedger/Listeners/UpdateAccountBalancesListener.php`. Listen to `GLEntryPostedEvent` and `GLEntryReversedEvent`. In handle(), extract affected accounts from entry lines, dispatch `UpdateAccountBalanceAction` for each unique account in the entry's fiscal period. Use queued listener: `implements ShouldQueue` for async processing (don't slow down posting) | | |
| TASK-026 | Create `app/Domains/GeneralLedger/Services/AccountBalanceService.php`. Implement `getBalance(int $accountId, int $fiscalYear, int $fiscalPeriod, string $tenantId): array` returning `['opening_balance' => (string), 'debit_total' => (string), 'credit_total' => (string), 'closing_balance' => (string), 'currency_code' => (string), 'entry_count' => (int)]`. Query GLAccountBalance table (fast, < 100ms per PR-GL-002). If not found, trigger UpdateAccountBalanceAction and retry. Cache result for 5 minutes | | |

### GOAL-005: Implement Foreign Currency Revaluation at Period-End

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-GL-002, EV-GL-004, CON-004 | Create RevalueForeignCurrencyBalancesAction that calculates unrealized gains/losses, posts adjustment GL entries, and dispatches BalanceRevaluatedEvent | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-027 | Create `app/Domains/GeneralLedger/Actions/RevalueForeignCurrencyBalancesAction.php` extending Action. Add `declare(strict_types=1);`. Use trait: `AsAction`. Inject: `ExchangeRateService $rateService`, `GLEntryRepositoryContract $repository`. Implement `rules(): array` returning `['fiscal_year' => ['required', 'integer'], 'fiscal_period' => ['required', 'integer', 'min:1', 'max:12'], 'revaluation_date' => ['required', 'date'], 'tenant_id' => ['required', 'uuid', 'exists:tenants,id']]` | | |
| TASK-028 | Implement `handle(int $fiscalYear, int $fiscalPeriod, Carbon $revaluationDate, string $tenantId): Collection`. Get all accounts with foreign currency balances: `$foreignBalances = GLAccountBalance::where(['tenant_id' => $tenantId, 'fiscal_year' => $fiscalYear, 'fiscal_period' => $fiscalPeriod])->whereNotIn('currency_code', [Tenant::find($tenantId)->base_currency])->get();`. Initialize results: `$adjustments = collect();` | | |
| TASK-029 | For each foreign balance, calculate revaluation: `foreach ($foreignBalances as $balance) { $currentRate = $this->rateService->getRate($balance->currency_code, $tenant->base_currency, $revaluationDate, $tenantId); if (!$currentRate) { Log::warning("Exchange rate not found for revaluation", ['currency' => $balance->currency_code, 'date' => $revaluationDate]); continue; } $revaluedAmount = bcmul($balance->closing_balance, $currentRate, 4); $adjustment = bcsub($revaluedAmount, $balance->closing_balance, 4); if ($adjustment == 0) { continue; } ... }` | | |
| TASK-030 | Create GL entry for each adjustment: `$entry = GLEntry::create(['tenant_id' => $tenantId, 'entry_number' => $this->repository->generateEntryNumber($tenantId, $fiscalYear), 'posting_date' => $revaluationDate, 'fiscal_year' => $fiscalYear, 'fiscal_period' => $fiscalPeriod, 'description' => "Foreign currency revaluation - {$balance->account->name} ({$balance->currency_code})", 'source_type' => 'Revaluation', 'source_id' => $balance->id, 'status' => 'posted', 'posted_at' => now(), 'posted_by' => auth()->id()]);` | | |
| TASK-031 | Add 2 lines to revaluation entry: 1) Account being revalued (debit if gain, credit if loss): `GLEntryLine::create(['gl_entry_id' => $entry->id, 'line_number' => 1, 'account_id' => $balance->account_id, 'debit_base_amount' => $adjustment > 0 ? $adjustment : 0, 'credit_base_amount' => $adjustment < 0 ? abs($adjustment) : 0, 'currency_code' => $tenant->base_currency, 'exchange_rate' => 1.0, 'description' => 'Revaluation adjustment']);` 2) Unrealized gain/loss account (from config): `$gainLossAccountId = config('accounting.unrealized_forex_account_id'); GLEntryLine::create([... opposite of line 1 ]);`. Collect entry: `$adjustments->push($entry);` | | |
| TASK-032 | After processing all accounts, dispatch event: `event(new BalanceRevaluatedEvent($fiscalYear, $fiscalPeriod, $revaluationDate, $adjustments, $tenantId));`. Return: `$adjustments;` (Collection of GL entries created). Log summary: `Log::info('Foreign currency revaluation completed', ['fiscal_year' => $fiscalYear, 'fiscal_period' => $fiscalPeriod, 'adjustments_count' => $adjustments->count(), 'total_gain_loss' => $adjustments->sum(fn($e) => $e->total_debits)]);` | | |
| TASK-033 | Create `app/Domains/GeneralLedger/Events/BalanceRevaluatedEvent.php`. Add `declare(strict_types=1);`. Implement ShouldBroadcast. Define public readonly properties: `int $fiscalYear`, `int $fiscalPeriod`, `Carbon $revaluationDate`, `Collection $adjustments`, `string $tenantId`. Implement broadcastOn() returning private channel for tenant | | |
| TASK-034 | Add configuration in `config/accounting.php`: `'unrealized_forex_account_id' => env('ACCOUNTING_UNREALIZED_FOREX_ACCOUNT_ID', null)`, `'realized_forex_account_id' => env('ACCOUNTING_REALIZED_FOREX_ACCOUNT_ID', null)`. Document that these accounts must be created in Chart of Accounts (typically under "Other Income/Expense") | | |

## 3. Alternatives

- **ALT-001**: Store only foreign amounts, calculate base on-the-fly - **Rejected** because it violates audit requirement (BR-GL-004: must record historical exchange rate), recalculation changes historical data, performance issues for reporting
- **ALT-002**: Use third-party API for all exchange rates (no manual entry) - **Rejected** because some tenants need manual rates (special agreements, non-standard currencies), API dependency creates single point of failure, offline capability required
- **ALT-003**: Real-time balance aggregation (no pre-computed table) - **Rejected** because it cannot meet performance requirement (PR-GL-002: < 100ms), querying millions of GL lines in real-time too slow, aggregation is standard accounting practice
- **ALT-004**: Allow posting in any currency, convert at reporting time - **Rejected** because double-entry must balance at posting time (CON-005), introduces complexity in validation, breaks fundamental accounting principles
- **ALT-005**: Continuous revaluation (update balances on every rate change) - **Rejected** because it creates excessive GL entries, violates CON-004 (period-end only), not standard accounting practice (unrealized gains/losses recognized at period-end)

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `brick/math` ^0.12 (decimal arithmetic for currency calculations)

**Internal Dependencies:**
- **DEP-002**: PRD01-SUB08-PLAN01 (GL Core) - GLEntry, GLEntryLine models, PostGLEntryAction
- **DEP-003**: PRD01-SUB07 (Chart of Accounts) - Account model for balance aggregation
- **DEP-004**: PRD01-SUB01 (Multi-Tenancy) - tenant_id, base_currency at tenant level
- **DEP-005**: PRD01-SUB08-PLAN03 (Fiscal Periods) - fiscal_year, fiscal_period validation (implemented next)

**External APIs (optional):**
- **DEP-006**: Exchange rate providers (Open Exchange Rates, Fixer.io, ECB) - optional integration for automatic rate fetching

## 5. Files

**Migrations:**
- `database/migrations/2025_11_12_000002_add_multi_currency_to_gl_entries.php` - Currency fields
- `database/migrations/2025_11_12_000003_create_gl_account_balances_table.php` - Balance aggregation
- `database/migrations/[existing]_add_base_currency_to_tenants.php` - Tenant base currency

**Models:**
- `app/Domains/GeneralLedger/Models/ExchangeRate.php` - Exchange rate model
- `app/Domains/GeneralLedger/Models/GLAccountBalance.php` - Monthly balance aggregation
- `app/Domains/GeneralLedger/Models/GLEntry.php` - Updated for multi-currency
- `app/Domains/GeneralLedger/Models/GLEntryLine.php` - Updated with currency fields

**Services:**
- `app/Domains/GeneralLedger/Services/ExchangeRateService.php` - Rate lookup and conversion
- `app/Domains/GeneralLedger/Services/AccountBalanceService.php` - Fast balance queries

**Actions:**
- `app/Domains/GeneralLedger/Actions/UpdateAccountBalanceAction.php` - Aggregation update
- `app/Domains/GeneralLedger/Actions/RevalueForeignCurrencyBalancesAction.php` - Period-end revaluation
- `app/Domains/GeneralLedger/Actions/PostGLEntryAction.php` - Updated for currency conversion

**Listeners:**
- `app/Domains/GeneralLedger/Listeners/UpdateAccountBalancesListener.php` - Async balance updates

**Events:**
- `app/Domains/GeneralLedger/Events/BalanceRevaluatedEvent.php` - Revaluation completed

**Exceptions:**
- `app/Domains/GeneralLedger/Exceptions/ExchangeRateNotFoundException.php` - Rate lookup failures

**Configuration:**
- `config/accounting.php` - Unrealized/realized forex account configuration

**Contracts:**
- `app/Domains/GeneralLedger/Contracts/ExchangeRateRepositoryContract.php` - Rate repository interface

**Repositories:**
- `app/Domains/GeneralLedger/Repositories/DatabaseExchangeRateRepository.php` - Rate repository

**Factories:**
- `database/factories/ExchangeRateFactory.php` - Exchange rate factory
- `database/factories/GLAccountBalanceFactory.php` - Balance factory

## 6. Testing

**Unit Tests (12 tests):**
- **TEST-001**: `test_exchange_rate_model_validates_positive_rate` - Rate must be > 0
- **TEST-002**: `test_exchange_rate_model_prevents_same_currency` - From ≠ To
- **TEST-003**: `test_exchange_rate_computes_inverse_correctly` - 1 / rate
- **TEST-004**: `test_exchange_rate_service_finds_rate_by_date` - Effective date lookup
- **TEST-005**: `test_exchange_rate_service_tries_inverse_rate` - Automatic inverse
- **TEST-006**: `test_exchange_rate_service_converts_amounts` - bcmul precision
- **TEST-007**: `test_gl_entry_line_detects_multi_currency` - isMultiCurrency()
- **TEST-008**: `test_gl_entry_balance_uses_base_currency` - Sum base amounts only
- **TEST-009**: `test_account_balance_calculates_closing_correctly` - Opening + debits - credits
- **TEST-010**: `test_revaluation_action_calculates_adjustment` - Gain/loss computation
- **TEST-011**: `test_revaluation_creates_gl_entry` - Entry with 2 lines
- **TEST-012**: `test_account_balance_service_caches_results` - Cache hit on 2nd call

**Feature Tests (15 tests):**
- **TEST-013**: `test_can_create_exchange_rate` - Create USD/EUR rate
- **TEST-014**: `test_can_post_foreign_currency_entry` - Lines auto-convert to base
- **TEST-015**: `test_foreign_entry_stores_both_amounts` - Foreign + base amounts saved
- **TEST-016**: `test_foreign_entry_balances_in_base_currency` - Validation uses base
- **TEST-017**: `test_missing_exchange_rate_throws_exception` - ExchangeRateNotFoundException
- **TEST-018**: `test_account_balance_aggregation_created` - Posted entry triggers listener
- **TEST-019**: `test_account_balance_updated_on_reversal` - Reversal updates aggregation
- **TEST-020**: `test_balance_query_returns_correct_totals` - AccountBalanceService accuracy
- **TEST-021**: `test_revaluation_generates_adjustments` - Revalue at period-end
- **TEST-022**: `test_revaluation_creates_unrealized_gain_entry` - Gain = debit account
- **TEST-023**: `test_revaluation_creates_unrealized_loss_entry` - Loss = credit account
- **TEST-024**: `test_revaluation_skips_zero_adjustments` - No entry if no change
- **TEST-025**: `test_revaluation_dispatches_event` - BalanceRevaluatedEvent fired
- **TEST-026**: `test_multi_currency_ledger_report_accurate` - Account ledger with foreign amounts
- **TEST-027**: `test_exchange_rate_caching_works` - Cache hit improves performance

**Integration Tests (8 tests):**
- **TEST-028**: `test_tenant_base_currency_set_on_creation` - Default USD
- **TEST-029**: `test_balance_aggregation_listener_queued` - Async processing
- **TEST-030**: `test_revaluation_with_multiple_currencies` - EUR, GBP, JPY all processed
- **TEST-031**: `test_balance_query_performance_under_100ms` - PR-GL-002 compliance
- **TEST-032**: `test_foreign_currency_trial_balance_accurate` - All accounts balanced in base
- **TEST-033**: `test_exchange_rate_unique_constraint_enforced` - Duplicate prevention
- **TEST-034**: `test_account_balance_upsert_no_duplicates` - Single record per account-period-currency
- **TEST-035**: `test_revaluation_respects_fiscal_period` - Only open periods

**Performance Tests (5 tests):**
- **TEST-036**: `test_balance_query_meets_100ms_target` - PR-GL-002 verification
- **TEST-037**: `test_exchange_rate_lookup_cached_efficiently` - < 10ms with cache
- **TEST-038**: `test_balance_aggregation_incremental_fast` - Update vs full recalculation
- **TEST-039**: `test_revaluation_scales_with_accounts` - 1000 accounts < 10 seconds
- **TEST-040**: `test_multi_currency_posting_no_slowdown` - Same speed as single currency

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Exchange rate data quality issues (wrong rates, missing rates) - **Mitigation**: Implement rate validation with reasonable bounds (e.g., USD/EUR between 0.5-2.0), require manual approval for rate changes > 10%, maintain audit log of all rate changes
- **RISK-002**: Balance aggregation lag causes stale data - **Mitigation**: Use queued listener with high priority queue, implement manual balance refresh command, monitor listener queue depth
- **RISK-003**: Revaluation at month-end creates large batches of GL entries - **Mitigation**: Process revaluation in chunks, implement progress tracking, allow cancellation and restart
- **RISK-004**: Currency conversion errors accumulate due to rounding - **Mitigation**: Use 4 decimal precision consistently, document rounding policy, implement periodic reconciliation, accept small variances (< $0.01)
- **RISK-005**: Changing tenant base currency after transactions exist - **Mitigation**: Prevent base currency change if transactions exist (validation), require data migration process, document in user guide

**Assumptions:**
- **ASSUMPTION-001**: Most tenants use single currency (90%), only 10% need multi-currency
- **ASSUMPTION-002**: Exchange rates change daily at most, not intraday (no real-time forex)
- **ASSUMPTION-003**: Revaluation happens once per month, not more frequently
- **ASSUMPTION-004**: Aggregated balances acceptable (5-minute lag), no requirement for real-time balances
- **ASSUMPTION-005**: Unrealized gain/loss accounts configured by tenant admin, not created automatically

## 8. KIV for future implementations

- **KIV-001**: Integrate with external exchange rate APIs (Open Exchange Rates, Fixer.io) for automatic daily updates
- **KIV-002**: Implement realized gain/loss calculation when foreign currency transactions settled
- **KIV-003**: Add exchange rate history charts and analytics
- **KIV-004**: Implement currency hedging contract tracking (forward contracts, options)
- **KIV-005**: Add multi-currency budget vs actual reporting
- **KIV-006**: Implement translation adjustment for foreign subsidiaries (CTA - Cumulative Translation Adjustment)
- **KIV-007**: Add currency exposure reports (asset/liability position by currency)
- **KIV-008**: Implement triangulation for cross-currency rates (EUR → GBP via USD)
- **KIV-009**: Add currency gain/loss simulation for "what-if" analysis
- **KIV-010**: Implement automatic rate variance alerts (rate changed > X% since last period)

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB08-GENERAL-LEDGER.md](../prd/prd-01/PRD01-SUB08-GENERAL-LEDGER.md)
- **Related Plans:**
  - PRD01-SUB08-PLAN01 (GL Core) - Foundation for multi-currency
  - PRD01-SUB08-PLAN03 (Fiscal Periods) - Period management for revaluation (next)
  - PRD01-SUB07-PLAN01 (COA Foundation) - Account model
- **External Documentation:**
  - ISO 4217 Currency Codes: https://www.iso.org/iso-4217-currency-codes.html
  - Foreign Currency Accounting (IAS 21): https://www.ifrs.org/issued-standards/list-of-standards/ias-21-the-effects-of-changes-in-foreign-exchange-rates/
  - Unrealized Gains and Losses: https://www.investopedia.com/terms/u/unrealizedgain.asp
  - brick/math Documentation: https://github.com/brick/math
  - Multi-Currency Accounting Best Practices: https://www.accountingtools.com/articles/foreign-currency-accounting
