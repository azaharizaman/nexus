---
plan: Implement Banking Foundation with Bank Account Management and Statement Import
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, banking, finance, cash-management, statement-import, infrastructure]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan establishes the banking foundation with bank account management, secure credential storage, and bank statement import functionality. It implements the core database schema for bank accounts and statements, creates models with encrypted attributes, implements CSV/Excel statement parsing, and establishes the repository pattern. This plan delivers multi-currency bank account tracking, statement upload workflows, and the foundation for automated reconciliation in PLAN02.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-BR-001**: Support **multi-bank account management** with balance tracking per currency
- **FR-BR-002**: Provide **bank statement import** via CSV/Excel for automated reconciliation

**Business Rules:**
- **BR-BR-001**: Bank accounts MUST be linked to a **valid GL cash account**

**Data Requirements:**
- **DR-BR-001**: Store bank account metadata: account_number, bank_name, currency, gl_account_id, current_balance
- **DR-BR-002**: Store bank transactions: transaction_date, description, debit, credit, balance, is_matched

**Integration Requirements:**
- **IR-BR-002**: Integrate with **Chart of Accounts** for GL account validation

**Security Requirements:**
- **SR-BR-001**: **Secure bank credentials** with AES-256 encryption and access control
- **SR-BR-002**: Enforce **role-based access** for reconciliation approvals

**Performance Requirements:**
- **PR-BR-001**: Reconciliation engine should handle **10k+ transactions in under 5 seconds** using batch processing

**Scalability Requirements:**
- **SCR-BR-001**: Support **100+ bank accounts** per tenant with concurrent reconciliation

**Events:**
- **EV-BR-001**: Dispatch `BankStatementImportedEvent` when bank statement CSV/Excel is imported

**Constraints:**
- **CON-001**: Bank account numbers must be unique within tenant
- **CON-002**: Statement opening balance must match previous statement's closing balance
- **CON-003**: Bank credentials stored with Laravel's encryption (AES-256-CBC)
- **CON-004**: Only active bank accounts can receive new statements
- **CON-005**: Statement files limited to 10MB size, max 50,000 rows
- **CON-006**: Supported formats: CSV, Excel (.xlsx, .xls), OFX (future)

**Guidelines:**
- **GUD-001**: Use PSR-12 coding standards, strict types, repository pattern, and Laravel Actions
- **GUD-002**: Log all banking operations using Spatie Activity Log
- **GUD-003**: Use Laravel's encryption for sensitive data (credentials, account numbers)
- **GUD-004**: Implement chunked processing for large statement files (>1000 rows)
- **GUD-005**: Validate GL account is cash type before linking to bank account

**Patterns:**
- **PAT-001**: Repository pattern with BankAccountRepositoryContract
- **PAT-002**: Strategy pattern for different statement parsers (CSV, Excel, OFX)
- **PAT-003**: Laravel Actions for ImportBankStatementAction
- **PAT-004**: Encrypted cast for sensitive fields

## 2. Implementation Steps

### GOAL-001: Create Database Schema for Banking System

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-BR-001, DR-BR-001, DR-BR-002, BR-BR-001, SR-BR-001 | Implement bank_accounts, bank_statements, and bank_transactions tables with encrypted fields and proper constraints | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `database/migrations/YYYY_MM_DD_HHMMSS_create_bank_accounts_table.php` with namespace. Add `declare(strict_types=1);`. Use anonymous migration class format: `return new class extends Migration` | | |
| TASK-002 | In up() method, create `bank_accounts` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `tenant_id` (UUID NOT NULL), `account_number` (TEXT NOT NULL - encrypted), `account_name` (VARCHAR 255 NOT NULL), `bank_name` (VARCHAR 255 NOT NULL), `bank_code` (VARCHAR 50 NULL - SWIFT/routing), `currency_code` (VARCHAR 3 NOT NULL DEFAULT 'USD'), `gl_account_id` (BIGINT NOT NULL - must be cash account), `current_balance` (DECIMAL 20,4 NOT NULL DEFAULT 0), `last_reconciled_date` (DATE NULL), `is_active` (BOOLEAN DEFAULT TRUE), `credentials` (TEXT NULL - encrypted JSON for API access), `metadata` (JSON NULL - additional info), timestamps, soft deletes | | |
| TASK-003 | Add indexes: `INDEX idx_bank_accounts_tenant (tenant_id)` for tenant filtering, `INDEX idx_bank_accounts_active (is_active)` for active accounts, `INDEX idx_bank_accounts_gl (gl_account_id)` for GL lookups, `INDEX idx_bank_accounts_currency (currency_code)` for currency filtering | | |
| TASK-004 | Add foreign key constraints: `FOREIGN KEY fk_bank_accounts_tenant (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE`, `FOREIGN KEY fk_bank_accounts_gl (gl_account_id) REFERENCES accounts(id) ON DELETE RESTRICT` (prevent GL account deletion if bank account exists) | | |
| TASK-005 | Create `bank_statements` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `bank_account_id` (BIGINT NOT NULL), `statement_date` (DATE NOT NULL - end date of statement period), `period_start_date` (DATE NULL - start of period), `opening_balance` (DECIMAL 20,4 NOT NULL), `closing_balance` (DECIMAL 20,4 NOT NULL), `status` (VARCHAR 20 NOT NULL DEFAULT 'pending' - pending/reconciling/reconciled), `uploaded_by` (BIGINT NOT NULL - user who uploaded), `uploaded_at` (TIMESTAMP NOT NULL), `reconciled_by` (BIGINT NULL), `reconciled_at` (TIMESTAMP NULL), `file_path` (VARCHAR 500 NULL - original file location), `file_hash` (VARCHAR 64 NULL - SHA256 to prevent duplicates), timestamps | | |
| TASK-006 | Add indexes on statements: `INDEX idx_statements_account (bank_account_id)` for account filtering, `INDEX idx_statements_date (statement_date)` for date queries, `INDEX idx_statements_status (status)` for status filtering, `UNIQUE KEY uk_statements_hash (bank_account_id, file_hash)` to prevent duplicate uploads | | |
| TASK-007 | Add foreign keys on statements: `FOREIGN KEY fk_statements_account (bank_account_id) REFERENCES bank_accounts(id) ON DELETE CASCADE`, `FOREIGN KEY fk_statements_uploaded_by (uploaded_by) REFERENCES users(id)`, `FOREIGN KEY fk_statements_reconciled_by (reconciled_by) REFERENCES users(id)` | | |
| TASK-008 | Create `bank_transactions` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `bank_statement_id` (BIGINT NOT NULL), `transaction_date` (DATE NOT NULL), `value_date` (DATE NULL - when funds available), `description` (TEXT NOT NULL), `reference` (VARCHAR 255 NULL - check number, wire ref), `debit_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0), `credit_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0), `balance` (DECIMAL 20,4 NOT NULL - running balance after transaction), `is_matched` (BOOLEAN DEFAULT FALSE), `matched_gl_entry_id` (BIGINT NULL - link to GL entry), `matched_at` (TIMESTAMP NULL), `matched_by` (BIGINT NULL), `metadata` (JSON NULL - additional data from statement), timestamps | | |
| TASK-009 | Add indexes on transactions: `INDEX idx_bank_trans_statement (bank_statement_id)` for statement lookup, `INDEX idx_bank_trans_date (transaction_date)` for date filtering, `INDEX idx_bank_trans_matched (is_matched)` for reconciliation queries, `INDEX idx_bank_trans_amount (debit_amount, credit_amount)` for amount matching, `INDEX idx_bank_trans_gl (matched_gl_entry_id)` for GL entry lookup | | |
| TASK-010 | Add foreign keys on transactions: `FOREIGN KEY fk_bank_trans_statement (bank_statement_id) REFERENCES bank_statements(id) ON DELETE CASCADE`, `FOREIGN KEY fk_bank_trans_matched_by (matched_by) REFERENCES users(id)` | | |
| TASK-011 | In down() method, drop tables in reverse order: `Schema::dropIfExists('bank_transactions')`, then statements, then accounts | | |

### GOAL-002: Create Bank Account Model with Encrypted Attributes

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-BR-001, BR-BR-001, SR-BR-001, CON-003 | Implement BankAccount Eloquent model with encrypted fields, balance tracking, and GL account relationship validation | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-012 | Create `app/Domains/Banking/Models/BankAccount.php` with namespace. Add `declare(strict_types=1);`. Import traits: `use BelongsToTenant, HasFactory, SoftDeletes, LogsActivity;` | | |
| TASK-013 | Define $fillable array: `['tenant_id', 'account_number', 'account_name', 'bank_name', 'bank_code', 'currency_code', 'gl_account_id', 'current_balance', 'last_reconciled_date', 'is_active', 'credentials', 'metadata']` | | |
| TASK-014 | Define $casts array with encryption: `['account_number' => 'encrypted', 'credentials' => 'encrypted:array', 'current_balance' => 'decimal:4', 'currency_code' => 'string', 'is_active' => 'boolean', 'last_reconciled_date' => 'date', 'metadata' => 'array', 'deleted_at' => 'datetime']`. Laravel's encrypted cast uses AES-256-CBC automatically | | |
| TASK-015 | Implement `getActivitylogOptions(): LogOptions` for audit trail: `return LogOptions::defaults()->logOnly(['account_name', 'bank_name', 'currency_code', 'gl_account_id', 'is_active', 'current_balance'])->logOnlyDirty()->dontSubmitEmptyLogs();`. Do NOT log encrypted fields | | |
| TASK-016 | Add relationships: `glAccount()` belongsTo Account with withDefault(), `statements()` hasMany BankStatement with order by statement_date desc, `tenant()` belongsTo with withDefault(), `latestStatement()` hasOne BankStatement with latest() scope | | |
| TASK-017 | Add custom scopes: `scopeActive(Builder $query): Builder` returning `$query->where('is_active', true)`, `scopeCurrency(Builder $query, string $currency): Builder` returning `$query->where('currency_code', $currency)`, `scopeNeedsReconciliation(Builder $query): Builder` returning `$query->whereHas('statements', fn($q) => $q->where('status', 'pending'))` | | |
| TASK-018 | Implement `getMaskedAccountNumberAttribute(): string` computed attribute for display: `$number = $this->account_number; return substr($number, 0, 2) . str_repeat('*', strlen($number) - 6) . substr($number, -4);`. Example: "12********3456" | | |
| TASK-019 | Implement `canReceiveStatements(): bool` method: `return $this->is_active && $this->gl_account_id !== null;`. Validates account ready for statements | | |
| TASK-020 | Implement static boot method to validate GL account on create/update: `static::saving(function ($account) { if ($account->gl_account_id) { $glAccount = Account::find($account->gl_account_id); if (!$glAccount || $glAccount->account_type !== AccountType::ASSET || !str_contains(strtolower($glAccount->name), 'cash')) { throw new InvalidGLAccountException('GL account must be a cash asset account'); } } });`. Enforces BR-BR-001 | | |
| TASK-021 | Override `delete()` method to prevent deletion if has statements: `if ($this->statements()->exists()) { throw new CannotDeleteBankAccountException('Cannot delete bank account with existing statements. Archive instead.'); } return parent::delete();` | | |

### GOAL-003: Create Bank Statement and Transaction Models

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-BR-002, FR-BR-002, CON-002 | Implement BankStatement and BankTransaction models with balance validation and relationship definitions | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-022 | Create `app/Domains/Banking/Models/BankStatement.php` with namespace. Add `declare(strict_types=1);`. Use `HasFactory, LogsActivity` traits (no tenant trait, inherits from bank account) | | |
| TASK-023 | Define $fillable: `['bank_account_id', 'statement_date', 'period_start_date', 'opening_balance', 'closing_balance', 'status', 'uploaded_by', 'uploaded_at', 'reconciled_by', 'reconciled_at', 'file_path', 'file_hash']`. Define $casts: `['statement_date' => 'date', 'period_start_date' => 'date', 'opening_balance' => 'decimal:4', 'closing_balance' => 'decimal:4', 'status' => BankStatementStatus::class, 'uploaded_at' => 'datetime', 'reconciled_at' => 'datetime']` | | |
| TASK-024 | Create `app/Domains/Banking/Enums/BankStatementStatus.php` as string-backed enum with cases: `PENDING = 'pending'`, `RECONCILING = 'reconciling'`, `RECONCILED = 'reconciled'`, `REJECTED = 'rejected'`. Implement `label(): string` for display, `canReconcile(): bool` (only PENDING and RECONCILING), `isComplete(): bool` (only RECONCILED) | | |
| TASK-025 | Add relationships in BankStatement: `bankAccount()` belongsTo BankAccount, `transactions()` hasMany BankTransaction ordered by transaction_date, `uploader()` belongsTo User (uploaded_by), `reconciler()` belongsTo User (reconciled_by) with withDefault() | | |
| TASK-026 | Add scopes: `scopePending(Builder $query): Builder`, `scopeReconciled(Builder $query): Builder`, `scopeForAccount(Builder $query, int $accountId): Builder`, `scopeByDateRange(Builder $query, Carbon $from, Carbon $to): Builder` | | |
| TASK-027 | Implement `getTotalDebitsAttribute(): float` computed attribute: `return $this->transactions->sum('debit_amount')`. Similarly for `getTotalCreditsAttribute()` and `getNetChangeAttribute()` (credits - debits) | | |
| TASK-028 | Implement `isBalanced(): bool` method: `return bccomp((string)($this->opening_balance + $this->net_change), (string)$this->closing_balance, 4) === 0;`. Validates opening + net = closing | | |
| TASK-029 | Implement `getMatchedCountAttribute(): int` and `getUnmatchedCountAttribute(): int`: `return $this->transactions->where('is_matched', true)->count()` and false respectively | | |
| TASK-030 | Implement static boot to validate opening balance matches previous closing (CON-002): `static::creating(function ($statement) { $previous = BankStatement::where('bank_account_id', $statement->bank_account_id)->where('statement_date', '<', $statement->statement_date)->latest('statement_date')->first(); if ($previous && bccomp((string)$previous->closing_balance, (string)$statement->opening_balance, 4) !== 0) { throw new BalanceMismatchException("Opening balance must match previous statement closing: {$previous->closing_balance}"); } });` | | |
| TASK-031 | Create `app/Domains/Banking/Models/BankTransaction.php` with namespace. Add `declare(strict_types=1);`. Use `HasFactory` trait | | |
| TASK-032 | Define $fillable: `['bank_statement_id', 'transaction_date', 'value_date', 'description', 'reference', 'debit_amount', 'credit_amount', 'balance', 'is_matched', 'matched_gl_entry_id', 'matched_at', 'matched_by', 'metadata']`. Define $casts: `['transaction_date' => 'date', 'value_date' => 'date', 'debit_amount' => 'decimal:4', 'credit_amount' => 'decimal:4', 'balance' => 'decimal:4', 'is_matched' => 'boolean', 'matched_at' => 'datetime', 'metadata' => 'array']` | | |
| TASK-033 | Add relationships: `statement()` belongsTo BankStatement, `glEntry()` belongsTo GLEntry with withDefault(), `matcher()` belongsTo User (matched_by) with withDefault() | | |
| TASK-034 | Implement `getAmountAttribute(): float` computed attribute: `return $this->credit_amount > 0 ? $this->credit_amount : -$this->debit_amount;`. Positive = credit, negative = debit | | |
| TASK-035 | Implement `isDebit(): bool` and `isCredit(): bool` helpers: `return $this->debit_amount > 0` and `return $this->credit_amount > 0` | | |
| TASK-036 | Add scope `scopeUnmatched(Builder $query): Builder` returning `$query->where('is_matched', false)` for reconciliation queries | | |

### GOAL-004: Implement Repository Pattern for Banking Entities

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PAT-001, PR-BR-001, SCR-BR-001 | Create repository contracts and implementations for efficient banking queries with eager loading and pagination | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-037 | Create `app/Domains/Banking/Contracts/BankAccountRepositoryContract.php` with namespace. Add `declare(strict_types=1);`. Define interface methods: `findById(int $id): ?BankAccount`, `findByAccountNumber(string $accountNumber, ?string $tenantId = null): ?BankAccount`, `getActive(?string $tenantId = null): Collection`, `getByCurrency(string $currency, ?string $tenantId = null): Collection`, `getTotalBalance(string $currency, ?string $tenantId = null): float`, `paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator`, `create(array $data): BankAccount`, `update(BankAccount $account, array $data): BankAccount`, `delete(BankAccount $account): bool`, `hasStatements(BankAccount $account): bool` | | |
| TASK-038 | Create `app/Domains/Banking/Repositories/DatabaseBankAccountRepository.php` implementing BankAccountRepositoryContract. Add `declare(strict_types=1);`. Constructor has no dependencies | | |
| TASK-039 | Implement `findByAccountNumber()` with decryption consideration: Since account_number is encrypted, cannot query directly. Load all accounts for tenant and filter in PHP: `return BankAccount::where('tenant_id', $tenantId ?? tenant_id())->get()->first(fn($acc) => $acc->account_number === $accountNumber);`. Note: This is limitation of encrypted fields. Alternative: store hash of account number in separate column for searching | | |
| TASK-040 | Implement `getActive()` with eager loading: `return BankAccount::with(['glAccount', 'latestStatement'])->active()->where('tenant_id', $tenantId ?? tenant_id())->get();`. Prevents N+1 queries | | |
| TASK-041 | Implement `getTotalBalance()`: `return BankAccount::where('tenant_id', $tenantId ?? tenant_id())->where('currency_code', $currency)->where('is_active', true)->sum('current_balance');`. Returns total cash position for currency | | |
| TASK-042 | Implement `paginate()` with filters: Support filters: `currency` (string), `is_active` (bool), `search` (string for account_name/bank_name). Build query: `$query = BankAccount::with(['glAccount'])->when($filters['currency'] ?? null, fn($q, $curr) => $q->currency($curr))->when(isset($filters['is_active']), fn($q) => $q->where('is_active', $filters['is_active']))->when($filters['search'] ?? null, fn($q, $search) => $q->where(fn($q2) => $q2->where('account_name', 'like', "%{$search}%")->orWhere('bank_name', 'like', "%{$search}%")))->orderBy('bank_name')->orderBy('account_name'); return $query->paginate($perPage);` | | |
| TASK-043 | Create `app/Domains/Banking/Contracts/BankStatementRepositoryContract.php` with methods: `findById(int $id): ?BankStatement`, `getForAccount(int $accountId): Collection`, `getPending(?string $tenantId = null): Collection`, `getByDateRange(int $accountId, Carbon $from, Carbon $to): Collection`, `create(array $data): BankStatement`, `update(BankStatement $statement, array $data): BankStatement` | | |
| TASK-044 | Create `app/Domains/Banking/Repositories/DatabaseBankStatementRepository.php` implementing contract. Implement methods with proper eager loading: always load `with(['transactions', 'bankAccount'])` to prevent N+1 | | |
| TASK-045 | In BankStatementRepository, implement `getPending()`: `return BankStatement::with(['transactions', 'bankAccount'])->whereHas('bankAccount', fn($q) => $q->where('tenant_id', $tenantId ?? tenant_id()))->pending()->orderBy('statement_date')->get();`. Used for reconciliation queue | | |
| TASK-046 | Bind contracts to implementations in `app/Providers/AppServiceProvider.php` register() method: `$this->app->bind(BankAccountRepositoryContract::class, DatabaseBankAccountRepository::class); $this->app->bind(BankStatementRepositoryContract::class, DatabaseBankStatementRepository::class);` | | |

### GOAL-005: Implement Bank Statement Import Action with CSV/Excel Parsing

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-BR-002, PAT-002, EV-BR-001, CON-005, CON-006 | Create action to import bank statements from CSV/Excel files with validation, parsing, and transaction creation | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-047 | Create `app/Domains/Banking/Actions/ImportBankStatementAction.php` with namespace. Add `declare(strict_types=1);`. Use AsAction trait. Constructor: `public function __construct(private readonly BankAccountRepositoryContract $accountRepo, private readonly BankStatementRepositoryContract $statementRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-048 | Implement `handle(BankAccount $account, UploadedFile $file, array $mappingConfig): BankStatement` method. Step 1: Validate file: Check extension in ['csv', 'xlsx', 'xls'], size < 10MB (CON-005): `if (!in_array($file->extension(), ['csv', 'xlsx', 'xls'])) { throw new UnsupportedFileFormatException(); } if ($file->getSize() > 10 * 1024 * 1024) { throw new FileTooLargeException('Max 10MB'); }` | | |
| TASK-049 | Step 2: Calculate file hash to prevent duplicates: `$hash = hash_file('sha256', $file->getRealPath());`. Check if statement with same hash exists for account: `if ($this->statementRepo->existsByHash($account->id, $hash)) { throw new DuplicateStatementException('Statement already uploaded'); }` | | |
| TASK-050 | Step 3: Parse file based on extension. For CSV: `$rows = array_map('str_getcsv', file($file->getRealPath()));`. For Excel: Use `Maatwebsite\Excel\Facades\Excel::toArray($file)`. Apply $mappingConfig to map columns: `['date' => 'Transaction Date', 'description' => 'Description', 'debit' => 'Debit', 'credit' => 'Credit', 'balance' => 'Balance']`. Extract header row and data rows | | |
| TASK-051 | Step 4: Validate row count (CON-005): `if (count($rows) > 50000) { throw new TooManyRowsException('Max 50,000 rows'); }`. Validate required columns present in header | | |
| TASK-052 | Step 5: Parse transactions from rows: `$transactions = []; foreach ($rows as $index => $row) { try { $transactions[] = $this->parseTransactionRow($row, $mappingConfig); } catch (\Exception $e) { throw new RowParseException("Error at row {$index}: {$e->getMessage()}"); } }`. Collect all transactions | | |
| TASK-053 | Implement `parseTransactionRow(array $row, array $config): array` private method. Extract values using config mapping: `$date = Carbon::parse($row[$config['date_column']]); $description = trim($row[$config['description_column']]); $debit = (float)str_replace(',', '', $row[$config['debit_column']] ?? 0); $credit = (float)str_replace(',', '', $row[$config['credit_column']] ?? 0); $balance = (float)str_replace(',', '', $row[$config['balance_column']]); $reference = $row[$config['reference_column']] ?? null;`. Return array with parsed values | | |
| TASK-054 | Step 6: Calculate statement opening/closing balances: `$openingBalance = $transactions[0]['balance'] - $transactions[0]['debit_amount'] + $transactions[0]['credit_amount'];` (reverse first transaction), `$closingBalance = end($transactions)['balance'];`, `$statementDate = end($transactions)['transaction_date'];` | | |
| TASK-055 | Step 7: Store file: `$filePath = $file->store("bank-statements/{$account->tenant_id}/{$account->id}", 'local');`. Save original for audit purposes | | |
| TASK-056 | Step 8: Create statement and transactions in transaction: `DB::transaction(function() use ($account, $transactions, $openingBalance, $closingBalance, $statementDate, $filePath, $hash) { $statement = $this->statementRepo->create(['bank_account_id' => $account->id, 'statement_date' => $statementDate, 'period_start_date' => $transactions[0]['transaction_date'], 'opening_balance' => $openingBalance, 'closing_balance' => $closingBalance, 'status' => BankStatementStatus::PENDING, 'uploaded_by' => auth()->id(), 'uploaded_at' => now(), 'file_path' => $filePath, 'file_hash' => $hash]); foreach ($transactions as $index => $txn) { $statement->transactions()->create(array_merge($txn, ['line_number' => $index + 1])); } $this->activityLogger->log("Bank statement imported: {count($transactions)} transactions", $statement, auth()->user()); event(new BankStatementImportedEvent($statement)); return $statement->fresh(['transactions']); });` | | |
| TASK-057 | Create `app/Domains/Banking/Events/BankStatementImportedEvent.php` with namespace. Constructor: `public function __construct(public readonly BankStatement $statement) {}` | | |
| TASK-058 | Create exceptions: `app/Domains/Banking/Exceptions/UnsupportedFileFormatException.php`, `FileTooLargeException.php`, `DuplicateStatementException.php`, `TooManyRowsException.php`, `RowParseException.php`. All extend base Exception | | |

## 3. Alternatives

- **ALT-001**: Store unencrypted account numbers for faster searching - **Rejected** because violates security requirements (SR-BR-001), PCI compliance needs encryption
- **ALT-002**: Use NoSQL for bank transactions (high volume) - **Rejected** because relational integrity critical for financial data, joins needed for reconciliation
- **ALT-003**: Allow manual entry of bank transactions instead of file upload - **Rejected** because error-prone, time-consuming, import automation is core requirement (FR-BR-002)
- **ALT-004**: Use third-party banking API service (Plaid, Yodlee) instead of file upload - **Deferred** to future enhancement, manual upload provides universal compatibility
- **ALT-005**: Store account numbers as hash instead of encryption - **Rejected** because need to display masked numbers to users, hashing is one-way

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `laravel/framework` ^12.0
- **DEP-002**: `spatie/laravel-activitylog` ^4.0 (audit logging)
- **DEP-003**: `lorisleiva/laravel-actions` ^2.0 (action pattern)
- **DEP-004**: `maatwebsite/excel` ^3.1 (Excel parsing)
- **DEP-005**: `brick/math` ^0.12 (decimal precision)

**Internal Dependencies:**
- **DEP-006**: PRD01-SUB01 (Multi-Tenancy System) - MUST be implemented first
- **DEP-007**: PRD01-SUB03 (Audit Logging System)
- **DEP-008**: PRD01-SUB07 (Chart of Accounts) - For GL account validation
- **DEP-009**: PRD01-SUB08 (General Ledger) - For GL entry matching (PLAN02)

**Infrastructure:**
- **DEP-010**: PostgreSQL 14+ OR MySQL 8.0+ with encryption support
- **DEP-011**: Laravel Storage (local or S3) for statement files
- **DEP-012**: File upload limits configured in php.ini (upload_max_filesize, post_max_size)

## 5. Files

**Migrations:**
- `database/migrations/YYYY_MM_DD_HHMMSS_create_bank_accounts_table.php` - Bank accounts
- `database/migrations/YYYY_MM_DD_HHMMSS_create_bank_statements_table.php` - Statements
- `database/migrations/YYYY_MM_DD_HHMMSS_create_bank_transactions_table.php` - Transactions

**Models:**
- `app/Domains/Banking/Models/BankAccount.php` - Bank account with encryption
- `app/Domains/Banking/Models/BankStatement.php` - Statement header
- `app/Domains/Banking/Models/BankTransaction.php` - Individual transactions

**Enums:**
- `app/Domains/Banking/Enums/BankStatementStatus.php` - Statement lifecycle

**Contracts:**
- `app/Domains/Banking/Contracts/BankAccountRepositoryContract.php` - Account repository
- `app/Domains/Banking/Contracts/BankStatementRepositoryContract.php` - Statement repository

**Repositories:**
- `app/Domains/Banking/Repositories/DatabaseBankAccountRepository.php` - Account repo
- `app/Domains/Banking/Repositories/DatabaseBankStatementRepository.php` - Statement repo

**Actions:**
- `app/Domains/Banking/Actions/ImportBankStatementAction.php` - Import statements
- `app/Domains/Banking/Actions/CreateBankAccountAction.php` - Create account
- `app/Domains/Banking/Actions/UpdateBankAccountAction.php` - Update account

**Events:**
- `app/Domains/Banking/Events/BankStatementImportedEvent.php` - Statement imported

**Exceptions:**
- `app/Domains/Banking/Exceptions/InvalidGLAccountException.php` - Invalid GL account
- `app/Domains/Banking/Exceptions/CannotDeleteBankAccountException.php` - Has statements
- `app/Domains/Banking/Exceptions/BalanceMismatchException.php` - Opening balance error
- `app/Domains/Banking/Exceptions/UnsupportedFileFormatException.php` - File format
- `app/Domains/Banking/Exceptions/FileTooLargeException.php` - File size
- `app/Domains/Banking/Exceptions/DuplicateStatementException.php` - Duplicate upload
- `app/Domains/Banking/Exceptions/TooManyRowsException.php` - Row limit
- `app/Domains/Banking/Exceptions/RowParseException.php` - Parse error

**Service Provider (updated):**
- `app/Providers/AppServiceProvider.php` - Repository bindings

## 6. Testing

**Unit Tests (15 tests):**
- **TEST-001**: `test_bank_account_masks_account_number_correctly` - Test getMaskedAccountNumberAttribute()
- **TEST-002**: `test_bank_account_encrypts_credentials` - Verify encrypted cast works
- **TEST-003**: `test_bank_account_validates_gl_account_type` - Test boot validation
- **TEST-004**: `test_bank_statement_status_enum_has_all_cases` - Verify 4 status cases
- **TEST-005**: `test_bank_statement_calculates_totals_correctly` - Test computed attributes
- **TEST-006**: `test_bank_statement_validates_balance` - Test isBalanced()
- **TEST-007**: `test_bank_statement_validates_opening_balance` - Test CON-002
- **TEST-008**: `test_bank_transaction_amount_attribute_returns_signed_value` - Test getAmountAttribute()
- **TEST-009**: `test_repository_finds_account_by_id` - Test findById()
- **TEST-010**: `test_repository_calculates_total_balance` - Test getTotalBalance()
- **TEST-011**: `test_repository_filters_by_currency` - Test getByCurrency()
- **TEST-012**: `test_statement_repository_gets_pending` - Test getPending()
- **TEST-013**: `test_bank_account_factory_generates_valid_data` - Test factory
- **TEST-014**: `test_bank_statement_scope_pending_works` - Test scopePending()
- **TEST-015**: `test_bank_transaction_scope_unmatched_works` - Test scopeUnmatched()

**Feature Tests (12 tests):**
- **TEST-016**: `test_create_bank_account_with_encryption` - Test account creation
- **TEST-017**: `test_import_csv_statement_action_creates_statement` - Test CSV import
- **TEST-018**: `test_import_excel_statement_action_creates_statement` - Test Excel import
- **TEST-019**: `test_import_validates_file_size` - Test CON-005
- **TEST-020**: `test_import_validates_row_count` - Test 50k row limit
- **TEST-021**: `test_import_prevents_duplicate_upload` - Test file hash check
- **TEST-022**: `test_import_calculates_balances_correctly` - Test opening/closing
- **TEST-023**: `test_import_dispatches_event` - Test BankStatementImportedEvent
- **TEST-024**: `test_cannot_delete_bank_account_with_statements` - Test delete() override
- **TEST-025**: `test_activity_log_records_bank_operations` - Test LogsActivity
- **TEST-026**: `test_unique_constraint_enforced_per_tenant` - Test account_number uniqueness
- **TEST-027**: `test_tenant_scoping_isolates_accounts` - Test BelongsToTenant trait

**Integration Tests (8 tests):**
- **TEST-028**: `test_statement_creation_with_transactions_atomic` - Test DB transaction
- **TEST-029**: `test_encrypted_fields_decrypt_correctly` - Test encryption roundtrip
- **TEST-030**: `test_eager_loading_prevents_n_plus_one` - Test query count
- **TEST-031**: `test_gl_account_validation_prevents_invalid_link` - Test BR-BR-001
- **TEST-032**: `test_repository_binding_resolves_correctly` - Test service container
- **TEST-033**: `test_statement_file_stored_correctly` - Test file storage
- **TEST-034**: `test_parse_transaction_row_handles_formats` - Test parser
- **TEST-035**: `test_balance_validation_uses_bcmath_precision` - Test 4 decimal precision

**Performance Tests (3 tests):**
- **TEST-036**: `test_import_10k_transactions_completes_under_5s` - Test PR-BR-001
- **TEST-037**: `test_paginate_100_accounts_under_100ms` - Test pagination performance
- **TEST-038**: `test_no_n_plus_one_in_statement_listing` - Test query efficiency

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Encrypted account numbers prevent efficient database searching - **Mitigation**: Consider adding hashed column for search if needed, document trade-off
- **RISK-002**: Large statement files (10MB) cause memory issues - **Mitigation**: Use chunked reading (1000 rows at a time), stream parsing for Excel
- **RISK-003**: CSV format variations between banks cause parse errors - **Mitigation**: Provide configurable column mapping, support multiple date formats
- **RISK-004**: File upload failures leave orphaned database records - **Mitigation**: Wrap in DB transaction, delete file on rollback
- **RISK-005**: Balance calculations have floating point precision errors - **Mitigation**: Use DECIMAL(20,4) and bcmath for all calculations

**Assumptions:**
- **ASSUMPTION-001**: Bank statement files follow consistent format per bank (header row + data rows)
- **ASSUMPTION-002**: Most statements contain 100-5,000 transactions, rarely exceed 10,000
- **ASSUMPTION-003**: Account numbers are alphanumeric, max 50 characters
- **ASSUMPTION-004**: All amounts use 2-4 decimal places precision
- **ASSUMPTION-005**: Users can provide correct column mapping configuration

## 8. KIV for future implementations

- **KIV-001**: Implement direct bank API integration (Plaid, Yodlee, Open Banking)
- **KIV-002**: Add OFX (Open Financial Exchange) file format support
- **KIV-003**: Implement automatic column detection (ML-based mapping)
- **KIV-004**: Add bank account balance alerts (low balance, unusual activity)
- **KIV-005**: Implement multi-currency conversion for consolidated views
- **KIV-006**: Add statement preview before import (confirm mappings)
- **KIV-007**: Implement scheduled auto-import from bank APIs
- **KIV-008**: Add bank account archival (soft delete with history)

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB10-BANKING.md](../prd/prd-01/PRD01-SUB10-BANKING.md)
- **Related Sub-PRDs:**
  - PRD01-SUB01 (Multi-Tenancy) - Tenant scoping
  - PRD01-SUB07 (Chart of Accounts) - GL account validation
  - PRD01-SUB08 (General Ledger) - GL integration (PLAN02)
- **Related Plans:**
  - PRD01-SUB10-PLAN02 (Reconciliation Engine and Matching) - Next phase
- **External Documentation:**
  - Laravel Encryption: https://laravel.com/docs/encryption
  - Maatwebsite Excel: https://docs.laravel-excel.com/
  - Bank Statement Formats: https://en.wikipedia.org/wiki/Bank_statement
  - OFX Format: https://en.wikipedia.org/wiki/Open_Financial_Exchange
