---
plan: Implement Chart of Accounts Foundation with Nested Set Model
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, infrastructure, accounting, chart-of-accounts, hierarchical-data, core-infrastructure]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan establishes the Chart of Accounts (COA) foundation using the nested set model for efficient hierarchical queries. It implements the core database schema with `lft` and `rgt` columns using `kalnoy/nestedset` package, creates Account models with type inheritance, defines account type and category enums, and establishes the repository pattern. The COA is the backbone of the accounting system, defining the structure for all financial transactions and reporting. This plan delivers unlimited-depth hierarchy support, efficient tree operations, and tenant-scoped account management.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-COA-001**: Manage **hierarchical account structure** with parent-child relationships
- **FR-COA-002**: Support **5 account types**: Asset, Liability, Equity, Revenue, Expense
- **FR-COA-003**: Define **account categories** within each type (e.g., Current Assets, Fixed Assets)
- **FR-COA-004**: Enforce **header accounts** cannot have direct transactions (BR-COA-003)

**Business Rules:**
- **BR-COA-001**: Each account MUST have an **account type** determining normal balance (debit/credit)
- **BR-COA-002**: Account codes MUST be **unique within tenant**
- **BR-COA-003**: Only **leaf accounts** (non-header) can have transactions posted

**Data Requirements:**
- **DR-COA-001**: Store accounts in **nested set model** (lft, rgt columns) for efficient hierarchy queries
- **DR-COA-002**: Support **unlimited depth** hierarchies (level column tracks depth)

**Integration Requirements:**
- **IR-COA-001**: Integrate with **General Ledger** for transaction posting

**Performance Requirements:**
- **PR-COA-001**: Account tree retrieval MUST complete in **< 100ms** for 10,000 accounts
- **PR-COA-002**: Support **10,000+ accounts** per tenant without performance degradation

**Security Requirements:**
- **SR-COA-001**: Prevent **accidental account deletion** if transactions exist

**Scalability Requirements:**
- **SCR-COA-001**: Scale to **10,000+ accounts per tenant**

**Architecture Requirements:**
- **ARCH-COA-001**: Use **kalnoy/nestedset** package for nested set model implementation

**Events:**
- **EV-COA-001**: Dispatch `AccountCreated` event when new account is added
- **EV-COA-002**: Dispatch `AccountUpdated` event when account is modified
- **EV-COA-003**: Dispatch `AccountDeleted` event when account is removed

**Constraints:**
- **CON-001**: All hierarchy operations must maintain nested set integrity (lft < rgt, no gaps)
- **CON-002**: Account code format follows pattern: {type_prefix}{sequence} (e.g., 1000-1999 for Assets)
- **CON-003**: Account type cannot be changed after creation (immutable)
- **CON-004**: Parent account type must match child account type (Asset parent → Asset children)

**Guidelines:**
- **GUD-001**: Use PSR-12 coding standards, strict types, and repository pattern
- **GUD-002**: Log all account structure changes using Spatie Activity Log
- **GUD-003**: Use Laravel Actions for account operations (CreateAccountAction, UpdateAccountAction)

**Patterns:**
- **PAT-001**: Repository pattern with AccountRepositoryContract
- **PAT-002**: Nested Set pattern using kalnoy/nestedset
- **PAT-003**: Enum pattern for AccountType and AccountCategory

## 2. Implementation Steps

### GOAL-001: Create Database Schema with Nested Set Columns

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-COA-001, DR-COA-001, DR-COA-002, BR-COA-002, ARCH-COA-001 | Implement accounts table with nested set model columns (lft, rgt, level) for efficient hierarchical queries and unlimited depth support | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `database/migrations/YYYY_MM_DD_HHMMSS_create_accounts_table.php` with namespace. Add `declare(strict_types=1);`. Use anonymous migration class format: `return new class extends Migration` | | |
| TASK-002 | In up() method, create `accounts` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `tenant_id` (UUID NULL for system accounts, indexes on this), `code` (VARCHAR 50 UNIQUE per tenant), `name` (VARCHAR 255), `description` (TEXT NULL), `account_type` (VARCHAR 50 - asset/liability/equity/revenue/expense), `account_category` (VARCHAR 100 NULL), `parent_id` (BIGINT NULL FOREIGN KEY → accounts(id) ON DELETE RESTRICT), `level` (INT NOT NULL DEFAULT 0 - tracks depth), `lft` (INT NOT NULL - nested set left value), `rgt` (INT NOT NULL - nested set right value), `is_active` (BOOLEAN DEFAULT TRUE), `is_header` (BOOLEAN DEFAULT FALSE - true means cannot have transactions), `reporting_group` (VARCHAR 100 NULL - balance_sheet/income_statement/cash_flow), `tax_category` (VARCHAR 50 NULL), `metadata` (JSON NULL), timestamps, soft deletes | | |
| TASK-003 | Add unique constraint: `UNIQUE KEY uk_accounts_tenant_code (tenant_id, code)` to enforce code uniqueness within tenant. System accounts (tenant_id=NULL) have globally unique codes | | |
| TASK-004 | Create composite indexes: `INDEX idx_accounts_tenant (tenant_id)` for tenant filtering, `INDEX idx_accounts_parent (parent_id)` for parent lookups, `INDEX idx_accounts_type (account_type)` for type filtering, `INDEX idx_accounts_nested_set (lft, rgt)` for tree queries (CRITICAL for performance), `INDEX idx_accounts_active (is_active)` for active account queries | | |
| TASK-005 | Add foreign key constraints: `FOREIGN KEY fk_accounts_tenant (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE`, `FOREIGN KEY fk_accounts_parent (parent_id) REFERENCES accounts(id) ON DELETE RESTRICT` (prevent deletion if children exist) | | |
| TASK-006 | In down() method, drop table using `Schema::dropIfExists('accounts')`. No need to manually drop foreign keys in modern Laravel | | |

### GOAL-002: Create Account Model with Nested Set Trait

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-COA-001, FR-COA-002, BR-COA-001, ARCH-COA-001 | Implement Account Eloquent model using kalnoy/nestedset trait for automatic lft/rgt management, account type casting, and relationship definitions | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `app/Domains/Accounting/Models/Account.php` with namespace. Add `declare(strict_types=1);`. Import `use Kalnoy\Nestedset\NodeTrait; use Illuminate\Database\Eloquent\SoftDeletes;` | | |
| TASK-008 | Define model with traits: `use NodeTrait, SoftDeletes, BelongsToTenant, HasFactory, LogsActivity;`. NodeTrait provides: `parent()`, `children()`, `ancestors()`, `descendants()`, `siblings()`, `getLevel()`, and maintains lft/rgt automatically | | |
| TASK-009 | Define $fillable array: `['tenant_id', 'code', 'name', 'description', 'account_type', 'account_category', 'parent_id', 'is_active', 'is_header', 'reporting_group', 'tax_category', 'metadata']`. Do NOT include level, lft, rgt (managed by NodeTrait) | | |
| TASK-010 | Define $casts array: `['account_type' => AccountType::class, 'account_category' => AccountCategory::class, 'is_active' => 'boolean', 'is_header' => 'boolean', 'metadata' => 'array', 'deleted_at' => 'datetime']`. Enum casting for type-safe access | | |
| TASK-011 | Implement `getActivitylogOptions(): LogOptions` for audit trail: `return LogOptions::defaults()->logOnly(['code', 'name', 'account_type', 'parent_id', 'is_active', 'is_header'])->logOnlyDirty()->dontSubmitEmptyLogs();` | | |
| TASK-012 | Add custom scopes: `scopeActive(Builder $query): Builder` returning `$query->where('is_active', true)`, `scopeHeader(Builder $query): Builder` returning `$query->where('is_header', true)`, `scopeLeaf(Builder $query): Builder` returning `$query->where('is_header', false)`, `scopeType(Builder $query, AccountType $type): Builder` returning `$query->where('account_type', $type)` | | |
| TASK-013 | Implement `normalBalance(): string` method returning `$this->account_type->normalBalance()` (delegates to enum). This determines if account increases with debits or credits | | |
| TASK-014 | Implement `getFullPathAttribute(): string` computed attribute that returns full hierarchy path (e.g., "Assets > Current Assets > Cash"). Use `$this->ancestors->pluck('name')->push($this->name)->implode(' > ')` | | |
| TASK-015 | Add relationship: `tenant()` belongsTo with `withDefault()` for system accounts (tenant_id=NULL). System accounts are shared across all tenants (e.g., standard COA templates) | | |
| TASK-016 | Override `delete()` method to prevent deletion if account has transactions or children: check `$this->children()->exists()` and `$this->hasTransactions()` (method to be implemented in PLAN02). Throw `CannotDeleteAccountException` if conditions not met | | |

### GOAL-003: Create Account Type and Category Enums

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-COA-002, FR-COA-003, BR-COA-001 | Define PHP 8.2 backed enums for account types with normal balance logic and account categories for classification | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-017 | Create `app/Domains/Accounting/Enums/AccountType.php` with namespace. Add `declare(strict_types=1);`. Define as `enum AccountType: string` with 5 cases: `ASSET = 'asset'`, `LIABILITY = 'liability'`, `EQUITY = 'equity'`, `REVENUE = 'revenue'`, `EXPENSE = 'expense'` | | |
| TASK-018 | In AccountType enum, implement `normalBalance(): string` method using match expression: `return match($this) { self::ASSET, self::EXPENSE => 'debit', self::LIABILITY, self::EQUITY, self::REVENUE => 'credit' };`. This is fundamental accounting equation logic | | |
| TASK-019 | In AccountType enum, implement `label(): string` for display: `return match($this) { self::ASSET => 'Asset', self::LIABILITY => 'Liability', self::EQUITY => 'Equity', self::REVENUE => 'Revenue', self::EXPENSE => 'Expense' };` | | |
| TASK-020 | In AccountType enum, implement `static values(): array` returning `array_column(self::cases(), 'value')` for validation rules (e.g., `Rule::in(AccountType::values())`) | | |
| TASK-021 | In AccountType enum, implement `codePrefix(): string` method returning standard code ranges: `return match($this) { self::ASSET => '1', self::LIABILITY => '2', self::EQUITY => '3', self::REVENUE => '4', self::EXPENSE => '5' };`. Assets start with 1000, Liabilities 2000, etc. | | |
| TASK-022 | Create `app/Domains/Accounting/Enums/AccountCategory.php` as string-backed enum with categories: `CURRENT_ASSETS = 'current_assets'`, `FIXED_ASSETS = 'fixed_assets'`, `CURRENT_LIABILITIES = 'current_liabilities'`, `LONG_TERM_LIABILITIES = 'long_term_liabilities'`, `OWNERS_EQUITY = 'owners_equity'`, `OPERATING_REVENUE = 'operating_revenue'`, `NON_OPERATING_REVENUE = 'non_operating_revenue'`, `OPERATING_EXPENSE = 'operating_expense'`, `NON_OPERATING_EXPENSE = 'non_operating_expense'`, `COST_OF_GOODS_SOLD = 'cost_of_goods_sold'` | | |
| TASK-023 | In AccountCategory enum, implement `accountType(): AccountType` that maps category to type: `return match($this) { self::CURRENT_ASSETS, self::FIXED_ASSETS => AccountType::ASSET, self::CURRENT_LIABILITIES, self::LONG_TERM_LIABILITIES => AccountType::LIABILITY, self::OWNERS_EQUITY => AccountType::EQUITY, self::OPERATING_REVENUE, self::NON_OPERATING_REVENUE => AccountType::REVENUE, self::OPERATING_EXPENSE, self::NON_OPERATING_EXPENSE, self::COST_OF_GOODS_SOLD => AccountType::EXPENSE };` | | |

### GOAL-004: Implement Repository Pattern with Tree Operations

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PAT-001, PR-COA-001 | Create repository contract and implementation with efficient tree query methods, supporting 10,000+ accounts with sub-100ms response times | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-024 | Create `app/Domains/Accounting/Contracts/AccountRepositoryContract.php` with namespace. Add `declare(strict_types=1);`. Define interface methods: `findById(int $id): ?Account`, `findByCode(string $code, ?string $tenantId = null): ?Account`, `getTree(?string $tenantId = null): Collection`, `getChildren(int $parentId): Collection`, `getAncestors(int $accountId): Collection`, `findByType(AccountType $type, ?string $tenantId = null): Collection`, `create(array $data): Account`, `update(Account $account, array $data): Account`, `delete(Account $account): bool`, `isInUse(Account $account): bool` | | |
| TASK-025 | Create `app/Domains/Accounting/Repositories/DatabaseAccountRepository.php` implementing AccountRepositoryContract. Add `declare(strict_types=1);`. Constructor has no dependencies (uses Account model directly) | | |
| TASK-026 | Implement `findByCode()`: `return Account::where('code', $code)->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q->whereNull('tenant_id'))->first();`. Handles both system (NULL tenant_id) and tenant-specific accounts | | |
| TASK-027 | Implement `getTree()` using kalnoy/nestedset: `return Account::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId), fn($q) => $q->whereNull('tenant_id'))->defaultOrder()->get()->toTree();`. `toTree()` builds hierarchical structure from flat lft/rgt query (VERY efficient). Use `defaultOrder()` for lft-based ordering | | |
| TASK-028 | Implement `getChildren()`: `return Account::find($parentId)?->children ?? collect();`. NodeTrait provides optimized children relationship | | |
| TASK-029 | Implement `getAncestors()`: `return Account::find($accountId)?->ancestors ?? collect();`. Returns parent, grandparent, etc. in order | | |
| TASK-030 | Implement `isInUse()`: check if account has any transactions (to be implemented in GL integration): `return DB::table('journal_entries')->where('account_id', $account->id)->exists();` (placeholder for future GL integration) | | |
| TASK-031 | Bind contract to implementation in `app/Providers/AppServiceProvider.php` register() method: `$this->app->bind(AccountRepositoryContract::class, DatabaseAccountRepository::class);` | | |

### GOAL-005: Create Account Factory and Seed Standard COA Template

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-COA-002, SCR-COA-001 | Create factory for testing with state methods and seed a standard Chart of Accounts template that covers basic business needs | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-032 | Create `database/factories/Accounting/AccountFactory.php` with namespace. Add `declare(strict_types=1);`. Define model: `protected $model = Account::class;`. Factory should generate: `'code' => $this->faker->unique()->numerify('####'), 'name' => $this->faker->words(3, true), 'description' => $this->faker->sentence(), 'account_type' => $this->faker->randomElement(AccountType::cases()), 'is_active' => true, 'is_header' => false, 'metadata' => []`. Do NOT set lft/rgt/level (handled by NodeTrait) | | |
| TASK-033 | Add factory state methods: `system()` sets `tenant_id => null`, `forTenant(string $tenantId)` sets tenant, `header()` sets `is_header => true`, `leaf()` sets `is_header => false`, `inactive()` sets `is_active => false`, `ofType(AccountType $type)` sets account type and appropriate code prefix | | |
| TASK-034 | Create `database/seeders/StandardCoaSeeder.php` with namespace. Add `declare(strict_types=1);`. This seeder creates a basic COA structure for new tenants. Seed as system accounts (tenant_id=NULL) so they can be copied to new tenants | | |
| TASK-035 | In StandardCoaSeeder::run(), create root-level accounts (all header accounts): `1000 - Assets`, `2000 - Liabilities`, `3000 - Equity`, `4000 - Revenue`, `5000 - Expenses`. Use Account::create() with proper lft/rgt initialization by kalnoy/nestedset | | |
| TASK-036 | Seed Asset sub-accounts as children of 1000: `1100 - Current Assets (header)` with children `1110 - Cash and Cash Equivalents (leaf)`, `1120 - Accounts Receivable (leaf)`, `1130 - Inventory (leaf)`, `1140 - Prepaid Expenses (leaf)`. Then `1200 - Fixed Assets (header)` with children `1210 - Property, Plant & Equipment (leaf)`, `1220 - Accumulated Depreciation (leaf)`. Use `$parent->children()->create([...])` for nested set integrity | | |
| TASK-037 | Seed Liability sub-accounts: `2100 - Current Liabilities (header)` → `2110 - Accounts Payable`, `2120 - Accrued Expenses`, `2130 - Short-term Loans`. `2200 - Long-term Liabilities (header)` → `2210 - Long-term Debt`, `2220 - Deferred Tax Liabilities` | | |
| TASK-038 | Seed Equity sub-accounts: `3100 - Owner's Equity (header)` → `3110 - Common Stock`, `3120 - Retained Earnings`, `3130 - Current Year Earnings` | | |
| TASK-039 | Seed Revenue sub-accounts: `4100 - Operating Revenue (header)` → `4110 - Product Sales`, `4120 - Service Revenue`. `4200 - Non-Operating Revenue (header)` → `4210 - Interest Income`, `4220 - Gain on Asset Sales` | | |
| TASK-040 | Seed Expense sub-accounts: `5100 - Cost of Goods Sold (header)` → `5110 - Material Costs`, `5120 - Labor Costs`. `5200 - Operating Expenses (header)` → `5210 - Salaries and Wages`, `5220 - Rent Expense`, `5230 - Utilities`, `5240 - Office Supplies`. `5300 - Non-Operating Expenses (header)` → `5310 - Interest Expense`, `5320 - Loss on Asset Sales` | | |
| TASK-041 | Call StandardCoaSeeder from `database/seeders/DatabaseSeeder.php` in run() method. This makes standard COA available for all tenants to copy on signup | | |

## 3. Alternatives

- **ALT-001**: Use Adjacency List (parent_id only) instead of Nested Set - **Rejected** because recursive queries to build tree are slow (O(n) queries for n-level tree). Nested Set allows single query tree retrieval (O(1))
- **ALT-002**: Use Closure Table (separate ancestor-descendant mapping table) - **Rejected** because it requires additional table and more complex joins. Nested Set is simpler for read-heavy workloads (accounting queries are 95% reads)
- **ALT-003**: Use Materialized Path (store path as string like "/1/5/12") - **Rejected** because path updates on re-parenting are complex and string operations slower than integer comparisons (lft/rgt)
- **ALT-004**: Allow account type changes after creation - **Rejected** because changing Asset → Liability would break accounting equation and invalidate historical transactions
- **ALT-005**: Store account hierarchy in NoSQL (MongoDB) - **Rejected** because relational integrity is critical for accounting data, and PostgreSQL/MySQL nested set performance is sufficient

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `kalnoy/nestedset` ^6.0 (MANDATORY for nested set model)
- **DEP-002**: `laravel/framework` ^12.0
- **DEP-003**: `spatie/laravel-activitylog` ^4.0 (audit logging)

**Internal Dependencies:**
- **DEP-004**: PRD01-SUB01 (Multi-Tenancy System) - MUST be implemented first
- **DEP-005**: PRD01-SUB03 (Audit Logging System)

**Infrastructure:**
- **DEP-006**: PostgreSQL 14+ OR MySQL 8.0+ with InnoDB (nested set queries require good index support)
- **DEP-007**: Redis (optional for tree caching)

## 5. Files

**Migrations:**
- `database/migrations/YYYY_MM_DD_HHMMSS_create_accounts_table.php` - Accounts table with nested set columns

**Models:**
- `app/Domains/Accounting/Models/Account.php` - Account model with NodeTrait

**Enums:**
- `app/Domains/Accounting/Enums/AccountType.php` - 5 account types with normal balance logic
- `app/Domains/Accounting/Enums/AccountCategory.php` - Account categories for classification

**Contracts:**
- `app/Domains/Accounting/Contracts/AccountRepositoryContract.php` - Repository interface

**Repositories:**
- `app/Domains/Accounting/Repositories/DatabaseAccountRepository.php` - Repository implementation

**Factories:**
- `database/factories/Accounting/AccountFactory.php` - Test data factory

**Seeders:**
- `database/seeders/StandardCoaSeeder.php` - Standard COA template
- `database/seeders/DatabaseSeeder.php` - Updated to call StandardCoaSeeder

**Service Provider (updated):**
- `app/Providers/AppServiceProvider.php` - Repository binding

## 6. Testing

**Unit Tests (12 tests):**
- **TEST-001**: `test_account_model_has_all_fillable_fields` - Verify $fillable array
- **TEST-002**: `test_account_type_enum_has_correct_normal_balance` - Test normalBalance() method
- **TEST-003**: `test_account_type_enum_returns_correct_code_prefix` - Test codePrefix() method
- **TEST-004**: `test_account_category_maps_to_correct_type` - Test accountType() method
- **TEST-005**: `test_account_category_enum_has_all_cases` - Verify 10 categories defined
- **TEST-006**: `test_repository_finds_account_by_code` - Test findByCode() with tenant scoping
- **TEST-007**: `test_repository_returns_null_for_invalid_code` - Test not found case
- **TEST-008**: `test_repository_filters_by_type` - Test findByType() method
- **TEST-009**: `test_account_factory_generates_valid_data` - Test factory defaults
- **TEST-010**: `test_account_factory_states_work_correctly` - Test system(), header(), ofType()
- **TEST-011**: `test_account_scope_active_filters_correctly` - Test scopeActive()
- **TEST-012**: `test_account_scope_leaf_excludes_headers` - Test scopeLeaf()

**Feature Tests (10 tests):**
- **TEST-013**: `test_seeder_creates_standard_coa_structure` - Verify all 40+ accounts created
- **TEST-014**: `test_nested_set_lft_rgt_values_correct` - Verify lft < rgt for all accounts
- **TEST-015**: `test_account_full_path_computed_correctly` - Test getFullPathAttribute()
- **TEST-016**: `test_account_normal_balance_returns_correct_value` - Test normalBalance() on model
- **TEST-017**: `test_unique_constraint_enforced_per_tenant` - Test code uniqueness
- **TEST-018**: `test_cannot_delete_account_with_children` - Test delete() override
- **TEST-019**: `test_soft_delete_preserves_nested_set_integrity` - Verify lft/rgt after soft delete
- **TEST-020**: `test_parent_account_type_must_match_children` - Test CON-004
- **TEST-021**: `test_header_account_flag_set_correctly` - Verify is_header logic
- **TEST-022**: `test_tenant_scoping_isolates_accounts` - Test BelongsToTenant trait

**Integration Tests (6 tests):**
- **TEST-023**: `test_kalnoy_nestedset_trait_works_correctly` - Test NodeTrait methods
- **TEST-024**: `test_get_tree_returns_hierarchical_structure` - Test getTree() performance
- **TEST-025**: `test_get_ancestors_returns_parent_chain` - Test getAncestors()
- **TEST-026**: `test_get_children_returns_direct_descendants` - Test getChildren()
- **TEST-027**: `test_activity_log_records_account_changes` - Test LogsActivity trait
- **TEST-028**: `test_repository_binding_resolves_correctly` - Test service container

**Performance Tests (3 tests):**
- **TEST-029**: `test_tree_query_completes_under_100ms_for_10000_accounts` - Test PR-COA-001
- **TEST-030**: `test_nested_set_indexes_used_correctly` - Verify EXPLAIN shows index usage
- **TEST-031**: `test_no_n_plus_one_queries_in_tree_retrieval` - Test single query for full tree

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Nested set lft/rgt corruption if direct SQL updates bypass NodeTrait - **Mitigation**: Document that all account operations must use Eloquent, add database triggers to validate lft/rgt integrity
- **RISK-002**: Re-parenting accounts (moving subtrees) is expensive operation - **Mitigation**: kalnoy/nestedset handles this efficiently, but document as potentially slow for large subtrees (>1000 accounts)
- **RISK-003**: Deleting parent account could orphan children - **Mitigation**: Use RESTRICT foreign key constraint, implement proper cascade logic in application
- **RISK-004**: Account code format varies by country/industry - **Mitigation**: Document standard format but allow customization, provide templates for different regions
- **RISK-005**: Standard COA template may not fit all businesses - **Mitigation**: Make it easy to customize, provide multiple templates (Manufacturing, Retail, Service), allow import from spreadsheet

**Assumptions:**
- **ASSUMPTION-001**: Most businesses use 100-500 accounts, maximum 10,000 (performance designed for this scale)
- **ASSUMPTION-002**: Account structure changes are infrequent (adding/moving accounts happens monthly, not daily)
- **ASSUMPTION-003**: Account tree depth rarely exceeds 5 levels (root > type > category > subcategory > account)
- **ASSUMPTION-004**: Standard 5-type COA (Asset/Liability/Equity/Revenue/Expense) covers 95% of businesses globally
- **ASSUMPTION-005**: PostgreSQL or MySQL 8.0+ with good index support is available (nested set requires efficient range queries on lft/rgt)

## 8. KIV for future implementations

- **KIV-001**: Implement multiple COA templates (Manufacturing, Retail, Non-Profit, etc.) in separate seeders
- **KIV-002**: Add COA import from Excel/CSV with validation
- **KIV-003**: Implement account code auto-generation based on parent code (1100 → 1110, 1120, 1130)
- **KIV-004**: Add account grouping for custom reporting (beyond standard reporting_group)
- **KIV-005**: Implement COA versioning to track structure changes over time (audit requirement in some industries)
- **KIV-006**: Add visual tree editor UI with drag-and-drop re-parenting
- **KIV-007**: Implement account budgeting at COA level (planned vs actual)
- **KIV-008**: Add multi-language support for account names/descriptions

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB07-CHART-OF-ACCOUNTS.md](../prd/prd-01/PRD01-SUB07-CHART-OF-ACCOUNTS.md)
- **Related Sub-PRDs:**
  - PRD01-SUB01 (Multi-Tenancy) - Tenant scoping
  - PRD01-SUB08 (General Ledger) - Transaction posting
  - PRD01-SUB09 (Journal Entries) - GL integration
- **External Documentation:**
  - kalnoy/nestedset Package: https://github.com/lazychaser/laravel-nestedset
  - Nested Set Model Explanation: https://en.wikipedia.org/wiki/Nested_set_model
  - Chart of Accounts Standards: https://en.wikipedia.org/wiki/Chart_of_accounts
  - Accounting Equation: https://en.wikipedia.org/wiki/Accounting_equation
