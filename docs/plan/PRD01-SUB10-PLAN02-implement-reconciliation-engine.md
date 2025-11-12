---
plan: Implement Bank Reconciliation Engine with Automated Matching and GL Integration
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, banking, reconciliation, matching-engine, gl-integration, automation]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan delivers the automated bank reconciliation engine with intelligent transaction matching, manual reconciliation workflows, and GL integration. It implements rule-based matching algorithms to automatically match bank transactions against GL entries, provides manual matching interface for exceptions, validates reconciliation balance against GL, and generates reconciliation reports. This plan completes the banking system by automating the month-end reconciliation process that typically takes hours manually.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-BR-003**: Implement **automated transaction matching** using configurable rules
- **FR-BR-004**: Provide **manual reconciliation** interface for unmatched items
- **FR-BR-005**: Generate **reconciliation reports** showing matched/unmatched items

**Business Rules:**
- **BR-BR-002**: **Reconciled transactions cannot be unreconciled** without approval
- **BR-BR-003**: **Statement balance MUST match GL balance** plus unreconciled items

**Integration Requirements:**
- **IR-BR-001**: Integrate with **General Ledger** for posting reconciled entries

**Security Requirements:**
- **SR-BR-002**: Enforce **role-based access** for reconciliation approvals

**Performance Requirements:**
- **PR-BR-001**: Reconciliation engine should handle **10k+ transactions in under 5 seconds**
- **PR-BR-002**: Match **1,000 transactions in under 2 seconds** using optimized queries

**Events:**
- **EV-BR-002**: Dispatch `BankTransactionMatchedEvent` when transaction auto-matched
- **EV-BR-003**: Dispatch `ReconciliationCompletedEvent` when statement fully reconciled

**Constraints:**
- **CON-007**: Matching rules evaluated in priority order (highest first)
- **CON-008**: Amount tolerance configurable per rule (default ±0.01)
- **CON-009**: Date range for matching configurable per rule (default ±5 days)
- **CON-010**: Cannot unmatch reconciled transaction without "unmatch-transactions" permission
- **CON-011**: Reconciliation balance tolerance ±0.02 (accounting rounding)
- **CON-012**: Only one reconciliation process per bank account at a time

**Guidelines:**
- **GUD-006**: Use query optimization (indexes, eager loading) for matching performance
- **GUD-007**: Process matching in batches of 100 transactions to prevent memory issues
- **GUD-008**: Cache matching rules per tenant (5 minute TTL)
- **GUD-009**: Use pessimistic locking during manual matching to prevent conflicts
- **GUD-010**: Generate reconciliation reports asynchronously for large datasets

**Patterns:**
- **PAT-005**: Strategy pattern for different matching strategies (exact, fuzzy, ml-based)
- **PAT-006**: Chain of Responsibility for matching rule evaluation
- **PAT-007**: Laravel Actions for MatchBankTransactionAction, ReconcileStatementAction
- **PAT-008**: Queue jobs for large-scale automated matching

## 2. Implementation Steps

### GOAL-001: Create Matching Rules Configuration System

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-BR-003, CON-007, CON-008, CON-009 | Implement configurable matching rules with priority, tolerance, and date range settings | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `database/migrations/YYYY_MM_DD_HHMMSS_create_bank_matching_rules_table.php` with namespace. Add `declare(strict_types=1);`. Use anonymous migration format | | |
| TASK-002 | In up() method, create `bank_matching_rules` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `tenant_id` (UUID NOT NULL), `name` (VARCHAR 255 NOT NULL - "Exact Amount Match", "Fuzzy Description"), `description` (TEXT NULL), `rule_type` (VARCHAR 50 NOT NULL - 'exact_amount', 'amount_date', 'description_fuzzy', 'reference_match'), `priority` (INTEGER NOT NULL DEFAULT 100 - lower = higher priority), `is_active` (BOOLEAN DEFAULT TRUE), `config` (JSON NOT NULL - rule-specific configuration), timestamps | | |
| TASK-003 | Add rule config structure example in comment: `{'amount_tolerance': 0.01, 'date_range_days': 5, 'match_type': 'debit_only', 'description_similarity': 0.8, 'reference_field': 'check_number'}`. Config varies by rule_type | | |
| TASK-004 | Add indexes: `INDEX idx_matching_rules_tenant (tenant_id)`, `INDEX idx_matching_rules_active (is_active)`, `INDEX idx_matching_rules_priority (priority)` for sorted retrieval, `INDEX idx_matching_rules_type (rule_type)` for type filtering | | |
| TASK-005 | Add foreign key: `FOREIGN KEY fk_matching_rules_tenant (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE` | | |
| TASK-006 | Create `app/Domains/Banking/Models/BankMatchingRule.php` with namespace. Add `declare(strict_types=1);`. Use `BelongsToTenant, HasFactory` traits | | |
| TASK-007 | Define $fillable: `['tenant_id', 'name', 'description', 'rule_type', 'priority', 'is_active', 'config']`. Define $casts: `['rule_type' => MatchingRuleType::class, 'priority' => 'integer', 'is_active' => 'boolean', 'config' => 'array']` | | |
| TASK-008 | Create `app/Domains/Banking/Enums/MatchingRuleType.php` as string-backed enum with cases: `EXACT_AMOUNT = 'exact_amount'` (exact amount + date range), `AMOUNT_DATE = 'amount_date'` (amount within tolerance + date match), `DESCRIPTION_FUZZY = 'description_fuzzy'` (fuzzy description similarity), `REFERENCE_MATCH = 'reference_match'` (check number, wire ref). Implement `label(): string` for display, `requiresAmountTolerance(): bool`, `requiresDateRange(): bool`, `requiresDescriptionSimilarity(): bool` | | |
| TASK-009 | Add scope in BankMatchingRule: `scopeActive(Builder $query): Builder` returning `$query->where('is_active', true)->orderBy('priority')` for rule evaluation order | | |
| TASK-010 | Implement `getAmountToleranceAttribute(): float` computed attribute: `return $this->config['amount_tolerance'] ?? 0.01;`. Similarly for `getDateRangeDaysAttribute(): int` (default 5), `getDescriptionSimilarityAttribute(): float` (default 0.8) | | |
| TASK-011 | Create seed `database/seeders/BankMatchingRulesSeeder.php` to create default rules for each tenant: Rule 1 (priority 10): Exact amount + reference match, Rule 2 (priority 20): Amount within $0.01 + date ±2 days, Rule 3 (priority 30): Fuzzy description 80% similarity + amount ±$1, Rule 4 (priority 40): Reference match only (check numbers) | | |

### GOAL-002: Implement Automated Matching Engine with Multiple Strategies

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-BR-003, PR-BR-002, PAT-005, PAT-006 | Create matching engine that evaluates rules in priority order and matches bank transactions to GL entries | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-012 | Create `app/Domains/Banking/Contracts/MatchingStrategyContract.php` interface with methods: `match(BankTransaction $bankTxn, Collection $glEntries): ?GLEntry`, `canHandle(MatchingRuleType $ruleType): bool`, `getName(): string`. This allows different matching algorithms | | |
| TASK-013 | Create `app/Domains/Banking/Services/Matching/ExactAmountStrategy.php` implementing MatchingStrategyContract. Constructor: `public function __construct(private readonly BankMatchingRule $rule) {}`. Implement `canHandle()`: `return $rule->rule_type === MatchingRuleType::EXACT_AMOUNT;` | | |
| TASK-014 | Implement `match()` in ExactAmountStrategy: `$targetAmount = $bankTxn->isDebit() ? $bankTxn->debit_amount : $bankTxn->credit_amount; $dateFrom = $bankTxn->transaction_date->subDays($this->rule->date_range_days); $dateTo = $bankTxn->transaction_date->addDays($this->rule->date_range_days); return $glEntries->first(function($entry) use ($targetAmount, $dateFrom, $dateTo) { $glAmount = $entry->isDebit() ? $entry->debit_amount : $entry->credit_amount; return bccomp((string)$glAmount, (string)$targetAmount, 4) === 0 && $entry->entry_date >= $dateFrom && $entry->entry_date <= $dateTo; });`. Use bcmath for precision | | |
| TASK-015 | Create `app/Domains/Banking/Services/Matching/AmountDateStrategy.php` with tolerance: In `match()`, use: `$tolerance = $this->rule->amount_tolerance; return $glEntries->first(function($entry) use ($targetAmount, $tolerance, $dateFrom, $dateTo) { $diff = abs($entry->amount - $targetAmount); return $diff <= $tolerance && $entry->entry_date >= $dateFrom && $entry->entry_date <= $dateTo; });` | | |
| TASK-016 | Create `app/Domains/Banking/Services/Matching/DescriptionFuzzyStrategy.php` with Levenshtein similarity: Implement `match()`: `$bankDesc = strtolower(trim($bankTxn->description)); $minSimilarity = $this->rule->description_similarity; return $glEntries->first(function($entry) use ($bankDesc, $minSimilarity, $targetAmount, $tolerance) { $glDesc = strtolower(trim($entry->description)); $similarity = $this->calculateSimilarity($bankDesc, $glDesc); return $similarity >= $minSimilarity && abs($entry->amount - $targetAmount) <= $tolerance; });`. Implement `calculateSimilarity(string $a, string $b): float` using `similar_text($a, $b, $percent); return $percent / 100;` | | |
| TASK-017 | Create `app/Domains/Banking/Services/Matching/ReferenceMatchStrategy.php`: In `match()`, use: `$bankRef = $bankTxn->reference; if (empty($bankRef)) { return null; } return $glEntries->first(function($entry) use ($bankRef, $targetAmount, $tolerance) { return $entry->reference === $bankRef && abs($entry->amount - $targetAmount) <= $tolerance; });`. Exact reference match + amount validation | | |
| TASK-018 | Create `app/Domains/Banking/Services/BankMatchingEngine.php` with constructor: `public function __construct(private readonly GLEntryRepository $glEntryRepo, private readonly BankMatchingRuleRepository $ruleRepo) {}`. This orchestrates strategy execution | | |
| TASK-019 | Implement `matchTransaction(BankTransaction $bankTxn): ?GLEntry` in BankMatchingEngine: Step 1: Get unmatched GL entries for bank account: `$glAccount = $bankTxn->statement->bankAccount->glAccount; $glEntries = $this->glEntryRepo->getUnmatchedForAccount($glAccount->id, $bankTxn->transaction_date->subDays(30), $bankTxn->transaction_date->addDays(10));`. 30 days before, 10 days after for flexibility | | |
| TASK-020 | Step 2: Get active matching rules: `$rules = $this->ruleRepo->getActive($bankTxn->statement->bankAccount->tenant_id);`. Cache these for 5 minutes: `Cache::remember("matching_rules:{$tenantId}", 300, fn() => $rules);` | | |
| TASK-021 | Step 3: Iterate rules in priority order, try each strategy: `foreach ($rules as $rule) { $strategy = $this->getStrategy($rule); if (!$strategy->canHandle($rule->rule_type)) { continue; } $match = $strategy->match($bankTxn, $glEntries); if ($match !== null) { return $match; } } return null;`. Chain of Responsibility pattern | | |
| TASK-022 | Implement `getStrategy(BankMatchingRule $rule): MatchingStrategyContract` private method: `return match($rule->rule_type) { MatchingRuleType::EXACT_AMOUNT => new ExactAmountStrategy($rule), MatchingRuleType::AMOUNT_DATE => new AmountDateStrategy($rule), MatchingRuleType::DESCRIPTION_FUZZY => new DescriptionFuzzyStrategy($rule), MatchingRuleType::REFERENCE_MATCH => new ReferenceMatchStrategy($rule), };`. Factory method for strategies | | |
| TASK-023 | Create `app/Domains/Banking/Actions/AutoMatchBankTransactionsAction.php` with AsAction trait. Constructor: `public function __construct(private readonly BankMatchingEngine $matchingEngine, private readonly ActivityLoggerContract $activityLogger) {}`. This is the entry point for automated matching | | |
| TASK-024 | Implement `handle(BankStatement $statement): array` in AutoMatchBankTransactionsAction: `$unmatchedTxns = $statement->transactions()->unmatched()->get(); $matched = []; $unmatched = []; foreach ($unmatchedTxns as $txn) { $glEntry = $this->matchingEngine->matchTransaction($txn); if ($glEntry) { $txn->update(['is_matched' => true, 'matched_gl_entry_id' => $glEntry->id, 'matched_at' => now(), 'matched_by' => auth()->id()]); event(new BankTransactionMatchedEvent($txn, $glEntry, 'auto')); $matched[] = $txn; } else { $unmatched[] = $txn; } } $this->activityLogger->log("Auto-matched {count($matched)}/{count($unmatchedTxns)} transactions", $statement); return ['matched' => $matched, 'unmatched' => $unmatched];`. Returns summary for reporting | | |
| TASK-025 | Optimize performance: Process in chunks of 100: `$unmatchedTxns->chunk(100, function($chunk) { /* matching logic */ });`. Use eager loading: `$statement->load(['transactions.statement.bankAccount.glAccount', 'transactions.glEntry']);`. Prevents N+1 queries | | |

### GOAL-003: Implement Manual Reconciliation Workflow

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-BR-004, CON-010, SR-BR-002, GUD-009 | Create manual matching interface with validation, locking, and approval workflow for unmatched transactions | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-026 | Create `app/Domains/Banking/Actions/ManualMatchTransactionAction.php` with AsAction trait. Constructor: `public function __construct(private readonly GLEntryRepository $glEntryRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-027 | Implement `handle(BankTransaction $bankTxn, int $glEntryId, ?string $notes = null): BankTransaction` method. Step 1: Authorize user: `if (!auth()->user()->can('match-bank-transactions')) { throw new UnauthorizedException('Missing permission: match-bank-transactions'); }` | | |
| TASK-028 | Step 2: Validate transaction not already matched: `if ($bankTxn->is_matched) { throw new TransactionAlreadyMatchedException('Transaction already matched. Unmatch first.'); }` | | |
| TASK-029 | Step 3: Find GL entry: `$glEntry = $this->glEntryRepo->findById($glEntryId); if (!$glEntry) { throw new GLEntryNotFoundException(); }`. Validate GL entry belongs to correct account: `if ($glEntry->account_id !== $bankTxn->statement->bankAccount->gl_account_id) { throw new InvalidGLAccountException('GL entry must belong to bank GL account'); }` | | |
| TASK-030 | Step 4: Validate amounts match within tolerance (CON-011): `$bankAmount = $bankTxn->amount; $glAmount = $glEntry->isDebit() ? -$glEntry->debit_amount : $glEntry->credit_amount; $diff = abs($bankAmount - $glAmount); $tolerance = 0.02; if ($diff > $tolerance) { throw new AmountMismatchException("Amount mismatch: bank={$bankAmount}, GL={$glAmount}, diff={$diff}"); }` | | |
| TASK-031 | Step 5: Lock transaction for update to prevent concurrent matching (GUD-009): `DB::transaction(function() use ($bankTxn, $glEntry, $notes) { $bankTxn->lockForUpdate(); $bankTxn->update(['is_matched' => true, 'matched_gl_entry_id' => $glEntry->id, 'matched_at' => now(), 'matched_by' => auth()->id()]); if ($notes) { $bankTxn->metadata = array_merge($bankTxn->metadata ?? [], ['match_notes' => $notes]); $bankTxn->save(); } event(new BankTransactionMatchedEvent($bankTxn, $glEntry, 'manual')); });` | | |
| TASK-032 | Step 6: Log activity with reason: `$this->activityLogger->log("Manually matched bank transaction to GL entry {$glEntry->id}: {$notes}", $bankTxn, auth()->user());` | | |
| TASK-033 | Return updated transaction with eager load: `return $bankTxn->fresh(['glEntry', 'matcher']);` | | |
| TASK-034 | Create `app/Domains/Banking/Actions/UnmatchTransactionAction.php` with AsAction trait. Constructor same as ManualMatchTransactionAction | | |
| TASK-035 | Implement `handle(BankTransaction $bankTxn, string $reason): BankTransaction` in UnmatchTransactionAction. Step 1: Authorize with higher permission (CON-010): `if (!auth()->user()->can('unmatch-transactions')) { throw new UnauthorizedException('Missing permission: unmatch-transactions. Requires approval.'); }` | | |
| TASK-036 | Step 2: Validate transaction is matched: `if (!$bankTxn->is_matched) { throw new TransactionNotMatchedException('Transaction is not matched'); }` | | |
| TASK-037 | Step 3: Check if statement is reconciled (BR-BR-002): `if ($bankTxn->statement->status === BankStatementStatus::RECONCILED) { throw new CannotUnmatchReconciledException('Cannot unmatch transactions from reconciled statement without approval'); }` | | |
| TASK-038 | Step 4: Unmatch in transaction: `DB::transaction(function() use ($bankTxn, $reason) { $oldGLEntry = $bankTxn->matched_gl_entry_id; $bankTxn->lockForUpdate(); $bankTxn->update(['is_matched' => false, 'matched_gl_entry_id' => null, 'matched_at' => null, 'matched_by' => null]); $bankTxn->metadata = array_merge($bankTxn->metadata ?? [], ['unmatch_reason' => $reason, 'unmatched_at' => now()->toIso8601String(), 'unmatched_by' => auth()->id()]); $bankTxn->save(); event(new BankTransactionUnmatchedEvent($bankTxn, $oldGLEntry, $reason)); });` | | |
| TASK-039 | Step 5: Log activity: `$this->activityLogger->log("Unmatched bank transaction from GL entry: {$reason}", $bankTxn, auth()->user());`. Return transaction: `return $bankTxn->fresh();` | | |
| TASK-040 | Create events: `app/Domains/Banking/Events/BankTransactionMatchedEvent.php` with properties: `public readonly BankTransaction $transaction, public readonly GLEntry $glEntry, public readonly string $matchType` (auto/manual). Similarly create `BankTransactionUnmatchedEvent.php` with properties: `public readonly BankTransaction $transaction, public readonly int $oldGLEntryId, public readonly string $reason` | | |

### GOAL-004: Implement Reconciliation Completion and Balance Validation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-BR-005, BR-BR-003, EV-BR-003, CON-011 | Create reconciliation finalization action that validates balances and marks statement as reconciled | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-041 | Create `app/Domains/Banking/Actions/CompleteReconciliationAction.php` with AsAction trait. Constructor: `public function __construct(private readonly GLEntryRepository $glEntryRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-042 | Implement `handle(BankStatement $statement): BankStatement` method. Step 1: Authorize: `if (!auth()->user()->can('complete-reconciliation')) { throw new UnauthorizedException('Missing permission: complete-reconciliation'); }` | | |
| TASK-043 | Step 2: Validate all transactions matched: `$unmatchedCount = $statement->transactions()->unmatched()->count(); if ($unmatchedCount > 0) { throw new UnmatchedTransactionsException("{$unmatchedCount} transactions remain unmatched. Match or investigate before completing."); }` | | |
| TASK-044 | Step 3: Calculate GL balance for bank account: `$glAccount = $statement->bankAccount->glAccount; $glBalance = $this->glEntryRepo->getAccountBalance($glAccount->id, $statement->statement_date);`. This is cumulative balance up to statement date | | |
| TASK-045 | Step 4: Get unreconciled GL entries (posted after last reconciliation): `$lastReconciledDate = $statement->bankAccount->last_reconciled_date; $unreconciledGLEntries = $this->glEntryRepo->getForAccountByDateRange($glAccount->id, $lastReconciledDate?->addDay() ?? Carbon::parse('1970-01-01'), $statement->statement_date)->whereNull('bank_transaction_id'); $unreconciledAmount = $unreconciledGLEntries->sum(fn($e) => $e->credit_amount - $e->debit_amount);` | | |
| TASK-046 | Step 5: Calculate expected balance (BR-BR-003): `$expectedBalance = $glBalance + $unreconciledAmount;`. Compare with statement closing balance using tolerance (CON-011): `$diff = abs($statement->closing_balance - $expectedBalance); $tolerance = 0.02; if ($diff > $tolerance) { throw new ReconciliationBalanceMismatchException("Balance mismatch: Statement={$statement->closing_balance}, GL={$glBalance}, Unreconciled={$unreconciledAmount}, Expected={$expectedBalance}, Diff={$diff}"); }` | | |
| TASK-047 | Step 6: Mark statement reconciled in transaction: `DB::transaction(function() use ($statement) { $statement->update(['status' => BankStatementStatus::RECONCILED, 'reconciled_by' => auth()->id(), 'reconciled_at' => now()]); $statement->bankAccount->update(['last_reconciled_date' => $statement->statement_date, 'current_balance' => $statement->closing_balance]); });` | | |
| TASK-048 | Step 7: Dispatch event: `event(new ReconciliationCompletedEvent($statement, $unmatchedCount, $diff));`. Log activity: `$this->activityLogger->log("Reconciliation completed: {$statement->transactions->count()} matched, balance validated", $statement, auth()->user());` | | |
| TASK-049 | Return statement: `return $statement->fresh(['transactions', 'bankAccount']);` | | |
| TASK-050 | Create `app/Domains/Banking/Events/ReconciliationCompletedEvent.php` with properties: `public readonly BankStatement $statement, public readonly int $matchedCount, public readonly float $balanceDifference`. Used for notifications | | |
| TASK-051 | Create reconciliation exceptions: `app/Domains/Banking/Exceptions/UnmatchedTransactionsException.php`, `ReconciliationBalanceMismatchException.php`, `TransactionAlreadyMatchedException.php`, `TransactionNotMatchedException.php`, `CannotUnmatchReconciledException.php`, `AmountMismatchException.php`. All extend base Exception with descriptive messages | | |

### GOAL-005: Generate Reconciliation Reports and Summary

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-BR-005, GUD-010 | Create report generation for reconciliation status, matched/unmatched items, and GL comparison | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-052 | Create `app/Domains/Banking/Services/ReconciliationReportService.php` with constructor: `public function __construct(private readonly BankStatementRepository $statementRepo, private readonly GLEntryRepository $glEntryRepo) {}` | | |
| TASK-053 | Implement `generateStatementReport(BankStatement $statement): array` method. Return structure: `['statement' => ['id' => $statement->id, 'account_name' => $statement->bankAccount->account_name, 'statement_date' => $statement->statement_date, 'opening_balance' => $statement->opening_balance, 'closing_balance' => $statement->closing_balance, 'status' => $statement->status->value], 'transactions' => ['total' => $statement->transactions->count(), 'matched' => $statement->matched_count, 'unmatched' => $statement->unmatched_count, 'total_debits' => $statement->total_debits, 'total_credits' => $statement->total_credits], 'unmatched_items' => $statement->transactions()->unmatched()->get()->map(...), 'summary' => ['net_change' => $statement->net_change, 'is_balanced' => $statement->isBalanced(), 'reconciliation_ready' => $statement->unmatched_count === 0]]` | | |
| TASK-054 | Implement `generateAccountSummary(BankAccount $account, Carbon $from, Carbon $to): array` method: Get all statements in date range: `$statements = $this->statementRepo->getByDateRange($account->id, $from, $to);`. Calculate totals: `$totalTransactions = $statements->sum(fn($s) => $s->transactions->count()); $totalMatched = $statements->sum(fn($s) => $s->matched_count); $reconciliationRate = $totalTransactions > 0 ? ($totalMatched / $totalTransactions) * 100 : 0;` | | |
| TASK-055 | Return account summary structure: `['account' => ['id' => $account->id, 'account_name' => $account->account_name, 'currency' => $account->currency_code, 'current_balance' => $account->current_balance, 'last_reconciled' => $account->last_reconciled_date], 'period' => ['from' => $from, 'to' => $to, 'statements' => $statements->count()], 'statistics' => ['total_transactions' => $totalTransactions, 'total_matched' => $totalMatched, 'total_unmatched' => $totalTransactions - $totalMatched, 'reconciliation_rate' => round($reconciliationRate, 2), 'pending_statements' => $statements->where('status', BankStatementStatus::PENDING)->count()], 'statements' => $statements->map(...)]` | | |
| TASK-056 | Implement `generateUnmatchedItemsReport(?string $tenantId = null): array` for tenant-wide view: Get all pending statements: `$statements = $this->statementRepo->getPending($tenantId); $unmatchedTxns = BankTransaction::whereHas('statement', fn($q) => $q->pending()->whereHas('bankAccount', fn($q2) => $q2->where('tenant_id', $tenantId ?? tenant_id())))->unmatched()->with(['statement.bankAccount'])->get();`. Group by age: `$aged = ['current' => $unmatchedTxns->where('transaction_date', '>=', now()->subDays(30)), '31-60' => ..., '61-90' => ..., 'over_90' => ...];` | | |
| TASK-057 | Create `app/Domains/Banking/Actions/GenerateReconciliationReportAction.php` with AsAction trait. Implement `asJob(): bool` returning true for queue processing (GUD-010). Constructor: `public function __construct(private readonly ReconciliationReportService $reportService) {}` | | |
| TASK-058 | Implement `handle(int $statementId, string $reportType = 'statement'): array` in GenerateReconciliationReportAction: `$statement = BankStatement::findOrFail($statementId); return match($reportType) { 'statement' => $this->reportService->generateStatementReport($statement), 'account_summary' => $this->reportService->generateAccountSummary($statement->bankAccount, $statement->period_start_date, $statement->statement_date), default => throw new InvalidArgumentException("Unknown report type: {$reportType}") };`. Can be dispatched as job for large datasets: `GenerateReconciliationReportAction::dispatch($statementId, 'statement');` | | |

## 3. Alternatives

- **ALT-006**: Use machine learning for transaction matching - **Deferred** because requires training data, rule-based matching adequate for MVP
- **ALT-007**: Real-time matching as GL entries posted - **Rejected** because reconciliation is period-based (monthly), batch matching more efficient
- **ALT-008**: Allow partial reconciliation (some unmatched items) - **Rejected** because violates accounting principle, all items must be accounted for
- **ALT-009**: Automatic GL entry creation for unmatched bank transactions - **Deferred** to future enhancement, requires approval workflow
- **ALT-010**: Support multi-user concurrent reconciliation - **Rejected** because causes conflicts, one reconciler per statement (CON-012)

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `laravel/framework` ^12.0
- **DEP-002**: `spatie/laravel-activitylog` ^4.0 (audit logging)
- **DEP-003**: `lorisleiva/laravel-actions` ^2.0 (action pattern)
- **DEP-004**: `brick/math` ^0.12 (decimal precision)

**Internal Dependencies:**
- **DEP-013**: PRD01-SUB10-PLAN01 (Banking Foundation) - MUST be completed first
- **DEP-014**: PRD01-SUB08 (General Ledger System) - For GL entries and balance queries
- **DEP-015**: PRD01-SUB02 (Authentication & Authorization) - For permissions

**Infrastructure:**
- **DEP-016**: Redis or Memcached for rule caching (GUD-008)
- **DEP-017**: Queue worker for async report generation (GUD-010)
- **DEP-018**: Database query optimization (indexes from PLAN01)

## 5. Files

**Migrations:**
- `database/migrations/YYYY_MM_DD_HHMMSS_create_bank_matching_rules_table.php` - Matching rules

**Models:**
- `app/Domains/Banking/Models/BankMatchingRule.php` - Matching rule configuration

**Enums:**
- `app/Domains/Banking/Enums/MatchingRuleType.php` - Rule types

**Contracts:**
- `app/Domains/Banking/Contracts/MatchingStrategyContract.php` - Strategy interface
- `app/Domains/Banking/Contracts/BankMatchingRuleRepository.php` - Rule repository
- `app/Domains/Banking/Contracts/GLEntryRepository.php` - GL entry queries (in GL domain)

**Services:**
- `app/Domains/Banking/Services/BankMatchingEngine.php` - Matching orchestrator
- `app/Domains/Banking/Services/Matching/ExactAmountStrategy.php` - Exact match
- `app/Domains/Banking/Services/Matching/AmountDateStrategy.php` - Tolerance match
- `app/Domains/Banking/Services/Matching/DescriptionFuzzyStrategy.php` - Fuzzy match
- `app/Domains/Banking/Services/Matching/ReferenceMatchStrategy.php` - Reference match
- `app/Domains/Banking/Services/ReconciliationReportService.php` - Report generation

**Actions:**
- `app/Domains/Banking/Actions/AutoMatchBankTransactionsAction.php` - Auto match
- `app/Domains/Banking/Actions/ManualMatchTransactionAction.php` - Manual match
- `app/Domains/Banking/Actions/UnmatchTransactionAction.php` - Unmatch
- `app/Domains/Banking/Actions/CompleteReconciliationAction.php` - Finalize
- `app/Domains/Banking/Actions/GenerateReconciliationReportAction.php` - Reports

**Events:**
- `app/Domains/Banking/Events/BankTransactionMatchedEvent.php` - Transaction matched
- `app/Domains/Banking/Events/BankTransactionUnmatchedEvent.php` - Transaction unmatched
- `app/Domains/Banking/Events/ReconciliationCompletedEvent.php` - Reconciliation done

**Exceptions:**
- `app/Domains/Banking/Exceptions/UnmatchedTransactionsException.php` - Not all matched
- `app/Domains/Banking/Exceptions/ReconciliationBalanceMismatchException.php` - Balance error
- `app/Domains/Banking/Exceptions/TransactionAlreadyMatchedException.php` - Already matched
- `app/Domains/Banking/Exceptions/TransactionNotMatchedException.php` - Not matched
- `app/Domains/Banking/Exceptions/CannotUnmatchReconciledException.php` - Reconciled statement
- `app/Domains/Banking/Exceptions/AmountMismatchException.php` - Amount tolerance exceeded

**Seeders:**
- `database/seeders/BankMatchingRulesSeeder.php` - Default rules

**Repositories:**
- `app/Domains/Banking/Repositories/DatabaseBankMatchingRuleRepository.php` - Rule repo

## 6. Testing

**Unit Tests (18 tests):**
- **TEST-001**: `test_matching_rule_enum_has_all_types` - Verify 4 rule types
- **TEST-002**: `test_matching_rule_computes_tolerance_from_config` - Test getAmountToleranceAttribute()
- **TEST-003**: `test_exact_amount_strategy_matches_correctly` - Test ExactAmountStrategy
- **TEST-004**: `test_amount_date_strategy_respects_tolerance` - Test AmountDateStrategy
- **TEST-005**: `test_description_fuzzy_strategy_calculates_similarity` - Test DescriptionFuzzyStrategy
- **TEST-006**: `test_reference_match_strategy_matches_exact_reference` - Test ReferenceMatchStrategy
- **TEST-007**: `test_matching_engine_evaluates_rules_in_priority_order` - Test chain of responsibility
- **TEST-008**: `test_matching_engine_returns_first_match` - Test short-circuit
- **TEST-009**: `test_matching_engine_returns_null_if_no_match` - Test no match scenario
- **TEST-010**: `test_reconciliation_report_calculates_totals` - Test generateStatementReport()
- **TEST-011**: `test_reconciliation_report_groups_unmatched_by_age` - Test aged report
- **TEST-012**: `test_balance_validation_uses_tolerance` - Test CON-011
- **TEST-013**: `test_balance_calculation_includes_unreconciled_items` - Test BR-BR-003
- **TEST-014**: `test_cannot_complete_reconciliation_with_unmatched` - Test validation
- **TEST-015**: `test_cannot_unmatch_reconciled_statement_without_permission` - Test BR-BR-002
- **TEST-016**: `test_manual_match_validates_amount_tolerance` - Test AmountMismatchException
- **TEST-017**: `test_unmatch_stores_reason_in_metadata` - Test metadata tracking
- **TEST-018**: `test_matching_rule_factory_generates_valid_rules` - Test factory

**Feature Tests (15 tests):**
- **TEST-019**: `test_auto_match_processes_all_unmatched_transactions` - Test AutoMatchBankTransactionsAction
- **TEST-020**: `test_auto_match_dispatches_event_for_each_match` - Test BankTransactionMatchedEvent
- **TEST-021**: `test_manual_match_action_requires_permission` - Test authorization
- **TEST-022**: `test_manual_match_validates_gl_account_matches` - Test InvalidGLAccountException
- **TEST-023**: `test_manual_match_uses_pessimistic_locking` - Test GUD-009
- **TEST-024**: `test_unmatch_action_requires_higher_permission` - Test CON-010
- **TEST-025**: `test_complete_reconciliation_validates_all_matched` - Test UnmatchedTransactionsException
- **TEST-026**: `test_complete_reconciliation_validates_balance` - Test ReconciliationBalanceMismatchException
- **TEST-027**: `test_complete_reconciliation_updates_bank_account_balance` - Test current_balance update
- **TEST-028**: `test_complete_reconciliation_dispatches_event` - Test ReconciliationCompletedEvent
- **TEST-029**: `test_generate_report_action_dispatches_as_job` - Test async processing
- **TEST-030**: `test_matching_rules_seeder_creates_defaults` - Test seeder
- **TEST-031**: `test_activity_log_records_all_matching_operations` - Test audit trail
- **TEST-032**: `test_concurrent_manual_match_prevented_by_locking` - Test race condition
- **TEST-033**: `test_reconciliation_workflow_end_to_end` - Test full workflow

**Integration Tests (10 tests):**
- **TEST-034**: `test_auto_match_integrates_with_gl_entries` - Test GL integration
- **TEST-035**: `test_manual_match_creates_bidirectional_link` - Test bank_transaction_id in GL
- **TEST-036**: `test_reconciliation_balance_matches_gl_balance` - Test BR-BR-003
- **TEST-037**: `test_matching_rules_cached_correctly` - Test GUD-008
- **TEST-038**: `test_eager_loading_prevents_n_plus_one_in_matching` - Test query count
- **TEST-039**: `test_chunked_processing_handles_large_statements` - Test GUD-007
- **TEST-040**: `test_report_generation_handles_10k_transactions` - Test performance
- **TEST-041**: `test_multiple_strategies_can_coexist` - Test strategy pattern
- **TEST-042**: `test_reconciliation_respects_tenant_isolation` - Test multi-tenancy
- **TEST-043**: `test_balance_calculation_uses_bcmath_precision` - Test decimal precision

**Performance Tests (4 tests):**
- **TEST-044**: `test_auto_match_1000_transactions_under_2_seconds` - Test PR-BR-002
- **TEST-045**: `test_reconciliation_engine_handles_10k_transactions_under_5s` - Test PR-BR-001
- **TEST-046**: `test_report_generation_for_large_dataset_completes` - Test async processing
- **TEST-047**: `test_concurrent_100_accounts_reconciliation` - Test SCR-BR-001

## 7. Risks & Assumptions

**Risks:**
- **RISK-006**: Fuzzy matching has false positives (matches wrong transactions) - **Mitigation**: Require amount validation on all fuzzy matches, provide review interface
- **RISK-007**: GL balance calculation slow for large transaction history - **Mitigation**: Use indexed queries, cache GL balances per period
- **RISK-008**: Concurrent matching causes deadlocks - **Mitigation**: Use pessimistic locking (lockForUpdate), process one statement at a time per account
- **RISK-009**: Balance mismatch due to timing differences (cut-off dates) - **Mitigation**: Allow configurable tolerance (CON-011), provide detailed variance report
- **RISK-010**: Users bypass reconciliation and manually adjust GL - **Mitigation**: Enforce permissions, audit all GL modifications

**Assumptions:**
- **ASSUMPTION-006**: Most transactions match using simple rules (80%+ auto-match rate)
- **ASSUMPTION-007**: GL entries have reliable descriptions for fuzzy matching
- **ASSUMPTION-008**: Bank transactions post to GL within 10 days (date range)
- **ASSUMPTION-009**: Unmatched items are exceptions requiring investigation, not bulk matches
- **ASSUMPTION-010**: Reconciliation performed monthly, not daily

## 8. KIV for future implementations

- **KIV-009**: Implement machine learning-based matching (pattern recognition)
- **KIV-010**: Add automatic GL entry creation for unmatched bank transactions (with approval)
- **KIV-011**: Implement real-time matching as GL entries posted (optional mode)
- **KIV-012**: Add multi-user review workflow (assign unmatched items to team members)
- **KIV-013**: Implement bank feed integration (direct API, no file upload)
- **KIV-014**: Add predictive matching suggestions based on historical patterns
- **KIV-015**: Implement variance analysis reports (trends, anomalies)
- **KIV-016**: Add mobile app for on-the-go reconciliation approval

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB10-BANKING.md](../prd/prd-01/PRD01-SUB10-BANKING.md)
- **Related Sub-PRDs:**
  - PRD01-SUB08 (General Ledger) - GL integration
  - PRD01-SUB07 (Chart of Accounts) - Account structure
  - PRD01-SUB02 (Authentication & Authorization) - Permissions
- **Related Plans:**
  - PRD01-SUB10-PLAN01 (Banking Foundation) - Prerequisites
- **External Documentation:**
  - Bank Reconciliation Best Practices: https://www.investopedia.com/terms/b/bankreconciliation.asp
  - Levenshtein Distance Algorithm: https://en.wikipedia.org/wiki/Levenshtein_distance
  - Chain of Responsibility Pattern: https://refactoring.guru/design-patterns/chain-of-responsibility
  - Strategy Pattern: https://refactoring.guru/design-patterns/strategy
