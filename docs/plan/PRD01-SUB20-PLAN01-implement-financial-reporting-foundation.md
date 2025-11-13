---
plan: Implement Financial Reporting Foundation
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, financial-reporting, reporting, accounting, balance-sheet, income-statement, foundation]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan establishes the foundation for the Financial Reporting module, including package structure, database schema for report definitions and execution history, core report generation engine, and integration with the General Ledger module. This foundation enables generation of standard financial statements (Balance Sheet, Income Statement, Cash Flow Statement) with drill-down capabilities and multi-period comparative analysis.

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-FR-001**: Generate standard financial statements (Balance Sheet, P&L, Cash Flow Statement)
- **REQ-FR-FR-002**: Support multi-period comparative reports with variance analysis
- **REQ-FR-FR-003**: Provide drill-down capability from summary to transaction detail
- **REQ-BR-FR-001**: Reports can only be generated for closed accounting periods or current period
- **REQ-BR-FR-003**: Financial statements must balance (assets = liabilities + equity)
- **REQ-DR-FR-001**: Store report definitions with field mappings and formulas
- **REQ-DR-FR-002**: Maintain report execution history with parameters and generated snapshots
- **REQ-DR-FR-003**: Cache aggregated financial data for faster report generation
- **REQ-IR-FR-001**: Integrate with General Ledger as primary data source
- **REQ-SR-FR-001**: Implement role-based access to financial reports by sensitivity level
- **REQ-PR-FR-002**: Financial statement generation must complete in < 5 seconds for monthly period
- **REQ-ARCH-FR-001**: Use SQL for report data retrieval with optimized queries
- **REQ-ARCH-FR-002**: Implement materialized views for pre-aggregated financial data
- **REQ-ARCH-FR-003**: Use repository pattern for report definition and execution management

### Security Constraints

- **SEC-001**: Financial reports must enforce tenant isolation at query level
- **SEC-002**: Report access must verify user permissions before execution
- **SEC-003**: Sensitive financial data must be logged for audit compliance
- **SEC-004**: Report parameters must be validated to prevent SQL injection

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: Use Laravel 12+ conventions (anonymous migrations, modern factory syntax)
- **GUD-003**: Follow PSR-12 coding standards, enforced by Laravel Pint
- **GUD-004**: Use type hints for all method parameters and return types
- **GUD-005**: All public methods must have PHPDoc blocks

### Patterns to Follow

- **PAT-001**: Use Repository pattern for report definition and execution data access
- **PAT-002**: Use Service pattern for report generation logic and calculations
- **PAT-003**: Use Action pattern for report generation operations
- **PAT-004**: Use Event pattern for report execution tracking and caching
- **PAT-005**: Use Strategy pattern for different report types (Balance Sheet, P&L, Cash Flow)

### Constraints

- **CON-001**: Must support PostgreSQL 14+ and MySQL 8.0+ for report storage
- **CON-002**: Package must be installable independently via Composer
- **CON-003**: Report generation must complete within 5 seconds for monthly period
- **CON-004**: Must integrate with existing General Ledger (SUB08) module
- **CON-005**: Financial statements must always balance (assets = liabilities + equity)

## 2. Implementation Steps

### GOAL-001: Package Setup and Database Schema

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-FR-001, DR-FR-002, DR-FR-003, ARCH-FR-001 | Set up financial-reporting package structure with Composer, create database schema for report definitions, execution history, and aggregated data caching. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create package directory structure: `packages/financial-reporting/` with subdirectories `src/`, `database/migrations/`, `database/factories/`, `database/seeders/`, `config/`, `tests/Feature/`, `tests/Unit/`, `routes/`. Initialize `composer.json` with package name `azaharizaman/erp-financial-reporting`, namespace `Nexus\Erp\FinancialReporting`, require Laravel 12+, PHP 8.2+, `azaharizaman/erp-general-ledger`, `azaharizaman/erp-multitenancy`. | | |
| TASK-002 | Create migration `database/migrations/create_report_definitions_table.php` (anonymous class): Define `report_definitions` table with columns: id (BIGSERIAL), tenant_id (UUID/BIGINT, indexed, NOT NULL), code (VARCHAR(50), unique per tenant), name (VARCHAR(255)), type (ENUM: 'balance_sheet', 'income_statement', 'cash_flow', 'custom'), category (VARCHAR(100), indexed), field_mappings (JSONB, stores GL account mappings to report lines), formulas (JSONB, stores calculation formulas for derived fields), filters (JSONB, stores default filters), sorting (JSONB, stores default sorting), is_standard (BOOLEAN, default false), is_active (BOOLEAN, default true), created_by (BIGINT), created_at, updated_at. Add unique constraint on (tenant_id, code). Add index on (tenant_id, type, is_active). | | |
| TASK-003 | Create migration `database/migrations/create_report_execution_history_table.php` (anonymous class): Define `report_execution_history` table with columns: id (BIGSERIAL), tenant_id (UUID/BIGINT, indexed, NOT NULL), report_definition_id (BIGINT, foreign key to report_definitions.id), executed_by (BIGINT, foreign key to users.id), parameters (JSONB, stores period, filters, options), result_snapshot (JSONB, stores generated report data), execution_time_ms (INTEGER), row_count (INTEGER), status (ENUM: 'completed', 'failed', 'cancelled'), error_message (TEXT, nullable), executed_at (TIMESTAMP, indexed), created_at, updated_at. Add index on (tenant_id, report_definition_id, executed_at). Add index on (tenant_id, executed_at) for history queries. | | |
| TASK-004 | Create migration `database/migrations/create_financial_aggregates_table.php` (anonymous class): Define `financial_aggregates` table for caching: id (BIGSERIAL), tenant_id (UUID/BIGINT, indexed, NOT NULL), period_id (BIGINT, foreign key to fiscal_periods.id), account_id (BIGINT, foreign key to gl_accounts.id), account_code (VARCHAR(50)), account_name (VARCHAR(255)), account_type (VARCHAR(50)), debit_total (DECIMAL(20,2)), credit_total (DECIMAL(20,2)), net_balance (DECIMAL(20,2)), currency_code (VARCHAR(3)), created_at, updated_at. Add unique constraint on (tenant_id, period_id, account_id). Add index on (tenant_id, period_id, account_type) for report queries. | | |
| TASK-005 | Create `config/financial-reporting.php` configuration file with settings: enabled (bool, default true), cache_enabled (bool, default true), cache_ttl (int, default 3600 seconds), default_currency (string, default 'USD'), execution_timeout (int, default 30 seconds), max_periods_compare (int, default 12), enable_drill_down (bool, default true), standard_reports (array of standard report codes), date_format (string, default 'Y-m-d'), number_format (array with decimals, decimal_point, thousands_separator). | | |
| TASK-006 | Create `src/FinancialReportingServiceProvider.php`: Register config, migrations, service bindings. Bind `ReportDefinitionRepositoryContract` to `ReportDefinitionRepository`. Bind `ReportExecutionRepositoryContract` to `ReportExecutionRepository`. Bind `ReportGeneratorServiceContract` to `ReportGeneratorService`. Register API routes from `routes/api.php`. Publish config and migrations. Register event listeners for report execution tracking. | | |

### GOAL-002: Report Definition Models and Repositories

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-FR-001, ARCH-FR-003 | Create Eloquent models for report definitions and execution history, implement repository pattern with contracts for data access. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `src/Models/ReportDefinition.php` Eloquent model: Include `declare(strict_types=1);`. Use traits: `BelongsToTenant`, `Searchable`, `LogsActivity`. Define fillable: tenant_id, code, name, type, category, field_mappings, formulas, filters, sorting, is_standard, is_active, created_by. Define casts: field_mappings => 'array', formulas => 'array', filters => 'array', sorting => 'array', is_standard => 'boolean', is_active => 'boolean'. Add relationships: belongsTo(Tenant), belongsTo(User, 'created_by'), hasMany(ReportExecutionHistory). Add scopes: scopeActive(), scopeByType($type), scopeStandard(). Implement searchableAs() => 'report_definitions', toSearchableArray() with tenant_id, code, name, type, category. | | |
| TASK-008 | Create `src/Models/ReportExecutionHistory.php` Eloquent model: Include `declare(strict_types=1);`. Use traits: `BelongsToTenant`. Define fillable: tenant_id, report_definition_id, executed_by, parameters, result_snapshot, execution_time_ms, row_count, status, error_message, executed_at. Define casts: parameters => 'array', result_snapshot => 'array', execution_time_ms => 'integer', row_count => 'integer', executed_at => 'datetime'. Add relationships: belongsTo(Tenant), belongsTo(ReportDefinition), belongsTo(User, 'executed_by'). Add scopes: scopeCompleted(), scopeFailed(), scopeRecent(). | | |
| TASK-009 | Create `src/Contracts/ReportDefinitionRepositoryContract.php` interface: Define methods: `find(int $id): ?ReportDefinition`, `findByCode(string $code): ?ReportDefinition`, `findByType(string $type): Collection`, `findAll(array $filters = []): Collection`, `create(array $data): ReportDefinition`, `update(int $id, array $data): ReportDefinition`, `delete(int $id): bool`, `getStandardReports(): Collection`. All methods must have PHPDoc with @param and @return types. | | |
| TASK-010 | Create `src/Repositories/ReportDefinitionRepository.php` implementing `ReportDefinitionRepositoryContract`: Use Eloquent model `ReportDefinition`. Inject `TenantManager` in constructor for automatic tenant_id filtering. Implement `find()` with tenant_id constraint. Implement `findByCode()` with tenant_id and code lookup. Implement `findByType()` filtering by type and is_active=true. Implement `findAll()` with support for filters (type, category, is_standard, is_active), sorting, and pagination. Implement `create()` with automatic tenant_id injection. Implement `update()` with tenant_id verification. Implement `delete()` with soft delete. Implement `getStandardReports()` returning is_standard=true reports. | | |
| TASK-011 | Create `src/Contracts/ReportExecutionRepositoryContract.php` interface: Define methods: `find(int $id): ?ReportExecutionHistory`, `findByReportDefinition(int $reportDefinitionId, array $filters = []): Collection`, `create(array $data): ReportExecutionHistory`, `update(int $id, array $data): ReportExecutionHistory`, `getRecentExecutions(int $limit = 10): Collection`, `getExecutionStats(int $reportDefinitionId): array`. All methods with PHPDoc. | | |
| TASK-012 | Create `src/Repositories/ReportExecutionRepository.php` implementing `ReportExecutionRepositoryContract`: Use Eloquent model `ReportExecutionHistory`. Implement `find()` with tenant_id constraint. Implement `findByReportDefinition()` with filters for date range, status, executed_by. Implement `create()` with automatic tenant_id and executed_at timestamp. Implement `update()` for updating status and result. Implement `getRecentExecutions()` ordering by executed_at DESC. Implement `getExecutionStats()` returning average execution time, success rate, total executions. | | |

### GOAL-003: Core Report Generation Engine

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-FR-001, BR-FR-003, PR-FR-002, ARCH-FR-001, ARCH-FR-002 | Implement core report generation service with strategy pattern for different report types, GL data aggregation, and financial statement balancing validation. | | |
| TASK-013 | Create `src/Contracts/ReportGeneratorServiceContract.php` interface: Define methods: `generate(ReportDefinition $definition, array $parameters): array` (generates report data), `validate(ReportDefinition $definition): bool` (validates report definition), `getDataSource(ReportDefinition $definition, array $parameters): Collection` (retrieves GL data), `aggregate(Collection $data, array $mappings): array` (aggregates data by report lines), `calculate(array $aggregated, array $formulas): array` (applies calculation formulas), `balance(array $report, string $type): bool` (validates financial statement balancing). All methods with full PHPDoc. | | |
| TASK-014 | Create `src/Services/ReportGeneratorService.php` implementing `ReportGeneratorServiceContract`: Inject `GeneralLedgerRepositoryContract`, `FiscalPeriodRepositoryContract`, `ReportExecutionRepositoryContract`. Implement `generate()`: 1) Validate definition, 2) Extract parameters (period_id, date_from, date_to, comparison_periods), 3) Get GL data via `getDataSource()`, 4) Aggregate by report lines via `aggregate()`, 5) Apply formulas via `calculate()`, 6) If financial statement, validate balancing via `balance()`, 7) Record execution history, 8) Return report data array. Measure execution time. Throw `ReportGenerationException` on failure. | | |
| TASK-015 | Implement `getDataSource()` in `ReportGeneratorService`: Accept report definition and parameters. Query GL posting table via `GeneralLedgerRepositoryContract::getTrialBalance($periodId)` or `getGLTransactions($dateFrom, $dateTo, $filters)`. Apply filters from parameters (account codes, cost centers, departments). If multi-period comparison requested, query each period separately. Return Collection of GL balances with account_code, account_name, account_type, debit, credit, balance, period. Use eager loading for related accounts. Cache results with period-based cache key. | | |
| TASK-016 | Implement `aggregate()` in `ReportGeneratorService`: Accept GL data Collection and field_mappings from report definition. Group GL accounts by report lines using mappings (e.g., "Current Assets" => ["1000..1999"], "Revenue" => ["4000..4999"]). Sum debits, credits, and balances for each report line. Handle account type conventions (debit-normal vs credit-normal accounts). Return associative array: ['report_line' => ['label' => 'Current Assets', 'accounts' => [...], 'debit' => 10000, 'credit' => 500, 'balance' => 9500]]. Support nested report lines (parent-child hierarchy). | | |
| TASK-017 | Implement `calculate()` in `ReportGeneratorService`: Accept aggregated data and formulas from report definition. Formulas use syntax: "Total Assets = Current Assets + Non-Current Assets". Parse each formula, extract referenced report lines, calculate value. Support basic operators: +, -, *, /, (). Evaluate formulas in dependency order (children before parents). Return updated aggregated array with calculated values. Throw `FormulaException` if formula references non-existent line or creates circular dependency. Use `symfony/expression-language` for safe formula evaluation. | | |
| TASK-018 | Implement `balance()` in `ReportGeneratorService`: Accept report data array and type. For Balance Sheet: verify "Total Assets = Total Liabilities + Total Equity" with tolerance of 0.01 due to rounding. For Income Statement: verify "Net Income = Total Revenue - Total Expenses". Return true if balanced, false otherwise. Log warning if unbalanced. Optionally throw `ReportUnbalancedException` if strict mode enabled in config. Include balancing check in report result metadata: ['is_balanced' => true, 'variance' => 0.00]. | | |

### GOAL-004: Standard Financial Statement Reports

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-FR-001, BR-FR-001 | Seed standard report definitions for Balance Sheet, Income Statement, and Cash Flow Statement with proper field mappings and formulas. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-019 | Create `database/seeders/StandardReportDefinitionsSeeder.php`: Seed 3 standard report definitions with is_standard=true, is_active=true. Set tenant_id=null for system-level definitions (copied to each tenant on creation). Define codes: 'BALANCE_SHEET', 'INCOME_STATEMENT', 'CASH_FLOW_STATEMENT'. Include field_mappings as JSON mapping GL account ranges to report lines. Include formulas for calculated totals and subtotals. Set appropriate categories. Run seeder in FinancialReportingServiceProvider::boot() after migrations. | | |
| TASK-020 | Define Balance Sheet report definition in seeder: type='balance_sheet', name='Balance Sheet', field_mappings with sections: Assets (Current Assets: 1000-1499, Non-Current Assets: 1500-1999, Total Assets = sum), Liabilities (Current Liabilities: 2000-2499, Long-Term Liabilities: 2500-2999, Total Liabilities = sum), Equity (Share Capital: 3000-3099, Retained Earnings: 3100-3199, Current Year Profit/Loss: from Income Statement, Total Equity = sum). Formulas: "Total Assets = Current Assets + Non-Current Assets", "Total Liabilities + Equity = Current Liabilities + Long-Term Liabilities + Total Equity". | | |
| TASK-021 | Define Income Statement report definition in seeder: type='income_statement', name='Income Statement (P&L)', field_mappings with sections: Revenue (Operating Revenue: 4000-4499, Other Income: 4500-4999, Total Revenue = sum), Expenses (Cost of Goods Sold: 5000-5499, Operating Expenses: 5500-5999, Other Expenses: 6000-6499, Total Expenses = sum), Net Income = Total Revenue - Total Expenses. Include Gross Profit = Revenue - COGS as intermediate calculation. Support multi-period comparison (current period vs prior period, variance %, variance amount). | | |
| TASK-022 | Define Cash Flow Statement report definition in seeder: type='cash_flow', name='Cash Flow Statement', field_mappings with sections: Operating Activities (Net Income from IS, Adjustments for non-cash items, Changes in working capital), Investing Activities (Purchase/Sale of assets), Financing Activities (Debt/Equity transactions), Net Cash Flow = sum of all sections. Note: Initial implementation may use indirect method with manual adjustments; future enhancement for full automation. Formulas: "Net Increase in Cash = Operating + Investing + Financing", "Ending Cash = Beginning Cash + Net Increase". | | |
| TASK-023 | Create `src/Actions/SeedStandardReportsAction.php` using Laravel Actions: Implement `handle(int $tenantId)` method that copies system-level standard reports to specific tenant. Called when new tenant is created. Clone each standard report definition, set tenant_id, keep is_standard=true. This allows tenants to customize standard reports without affecting other tenants. Use Action pattern for reusability: invokable as job, command, or direct call. | | |

### GOAL-005: Integration with General Ledger and Testing

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-FR-001, BR-FR-001, PR-FR-002 | Integrate with General Ledger module for data retrieval, implement fiscal period validation, and create comprehensive tests. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-024 | Create `src/Contracts/GeneralLedgerIntegrationContract.php` interface: Define methods: `getTrialBalance(int $periodId, ?array $filters = null): Collection` (returns account balances for period), `getGLTransactions(string $dateFrom, string $dateTo, ?array $filters = null): Collection` (returns detailed transactions), `getPeriodStatus(int $periodId): string` (returns 'open' or 'closed'), `getAccountDetails(string $accountCode): ?array` (returns account type, name, balance). Interface for decoupling from GL module. | | |
| TASK-025 | Create `src/Services/GeneralLedgerIntegration.php` implementing `GeneralLedgerIntegrationContract`: Inject `GeneralLedgerRepositoryContract` from GL package. Implement `getTrialBalance()` by calling GL repository's trial balance method, apply tenant_id filter, apply additional filters (account codes, cost centers), return Collection of account balances. Implement `getPeriodStatus()` by querying fiscal_periods table for period status. Throw `PeriodNotClosedException` if report requires closed period but period is open. | | |
| TASK-026 | Implement period validation in `ReportGeneratorService::generate()`: Before generating report, check if BR-FR-001 applies (report requires closed period). Call `GeneralLedgerIntegrationContract::getPeriodStatus($periodId)`. If period is 'open' and report requires closed period (configurable per report definition), throw `PeriodNotClosedException`. Allow current period reports only if explicitly enabled in parameters. Log period status in execution history. | | |
| TASK-027 | Create Feature test `tests/Feature/ReportGenerationTest.php`: Use Pest syntax. Test scenarios: 1) Generate Balance Sheet for closed period (expect success, balanced statement), 2) Generate Income Statement for date range (expect success, correct revenue/expense totals), 3) Attempt report for open period requiring closed (expect exception), 4) Generate report with comparison periods (expect multiple period data), 5) Verify execution history recorded, 6) Verify execution time < 5 seconds for monthly period (PR-FR-002). Use factories for test data: tenants, GL accounts, GL postings, fiscal periods. Assert report structure, balancing, and performance. | | |
| TASK-028 | Create Unit test `tests/Unit/ReportGeneratorServiceTest.php`: Test `aggregate()` method with sample GL data and mappings (expect correct grouping and summation), Test `calculate()` method with formulas (expect correct calculated values, handle missing references), Test `balance()` method for Balance Sheet (expect true for balanced, false for unbalanced), Test formula parsing and dependency resolution, Test error handling (invalid formulas, circular dependencies). Mock dependencies (repositories, GL integration). | | |
| TASK-029 | Create Factory `database/factories/ReportDefinitionFactory.php`: Define factory for ReportDefinition model with random data: code (unique), name, type (random from enum), field_mappings (sample JSON structure), formulas (sample formulas), is_standard (false by default), is_active (true). State methods: standard() sets is_standard=true, balanceSheet() sets type and appropriate mappings, incomeStatement() sets type and mappings, cashFlow() sets type and mappings. Used in tests for creating test report definitions. | | |
| TASK-030 | Create Factory `database/factories/ReportExecutionHistoryFactory.php`: Define factory for ReportExecutionHistory model: report_definition_id (from ReportDefinition factory), executed_by (from User factory), parameters (sample period_id), result_snapshot (sample report data), execution_time_ms (random 1000-5000), row_count (random 10-100), status ('completed'), executed_at (now()). State methods: failed() sets status='failed' with error_message, withSnapshot() sets custom result_snapshot. | | |

## 3. Alternatives

- **ALT-001**: Use external BI tool (Power BI, Tableau) instead of custom reporting
  - *Pros*: Rich visualization, mature platform, less development effort
  - *Cons*: Licensing costs, less customization, requires data export, not API-first
  - *Decision*: Not chosen - Headless ERP requires API-based reporting for AI agents

- **ALT-002**: Generate reports on-demand without caching or aggregation tables
  - *Pros*: Simpler architecture, always fresh data, no cache invalidation complexity
  - *Cons*: Slow performance for large datasets, cannot meet 5-second requirement for monthly reports
  - *Decision*: Not chosen - Performance requirements mandate caching and pre-aggregation

- **ALT-003**: Store report definitions as code (PHP classes) instead of database
  - *Pros*: Version controlled, type-safe, easier testing
  - *Cons*: Not runtime-configurable, no multi-tenancy support, requires deployment for changes
  - *Decision*: Not chosen - ERP requires runtime configuration by tenants

- **ALT-004**: Use OLAP cube for financial reporting
  - *Pros*: Fast multi-dimensional analysis, optimized for aggregation
  - *Cons*: Additional infrastructure (e.g., Mondrian, SQL Server Analysis Services), overkill for MVP
  - *Decision*: Deferred to future - Materialized views sufficient for MVP, can migrate later

## 4. Dependencies

**Package Dependencies:**
- `azaharizaman/erp-multitenancy` (PRD01-SUB01) - Required for tenant context
- `azaharizaman/erp-authentication` (PRD01-SUB02) - Required for user permissions
- `azaharizaman/erp-general-ledger` (PRD01-SUB08) - Required for GL data source
- `azaharizaman/erp-backoffice` (PRD01-SUB15) - Required for fiscal period management
- `symfony/expression-language` - For safe formula evaluation

**Internal Dependencies:**
- General Ledger repository for trial balance and transaction data
- Fiscal Period repository for period status and date ranges
- Tenant Manager for tenant context resolution

**Infrastructure Dependencies:**
- PostgreSQL 14+ OR MySQL 8.0+ for report storage
- Redis for caching aggregated data (optional but recommended)

## 5. Files

**Configuration:**
- `packages/financial-reporting/config/financial-reporting.php` - Package configuration

**Migrations:**
- `packages/financial-reporting/database/migrations/create_report_definitions_table.php` - Report definitions schema
- `packages/financial-reporting/database/migrations/create_report_execution_history_table.php` - Execution history schema
- `packages/financial-reporting/database/migrations/create_financial_aggregates_table.php` - Aggregated data cache schema

**Models:**
- `packages/financial-reporting/src/Models/ReportDefinition.php` - Report definition model
- `packages/financial-reporting/src/Models/ReportExecutionHistory.php` - Execution history model
- `packages/financial-reporting/src/Models/FinancialAggregate.php` - Aggregated data model (added in future task)

**Contracts:**
- `packages/financial-reporting/src/Contracts/ReportDefinitionRepositoryContract.php` - Report definition repository interface
- `packages/financial-reporting/src/Contracts/ReportExecutionRepositoryContract.php` - Execution history repository interface
- `packages/financial-reporting/src/Contracts/ReportGeneratorServiceContract.php` - Report generator service interface
- `packages/financial-reporting/src/Contracts/GeneralLedgerIntegrationContract.php` - GL integration interface

**Repositories:**
- `packages/financial-reporting/src/Repositories/ReportDefinitionRepository.php` - Report definition repository
- `packages/financial-reporting/src/Repositories/ReportExecutionRepository.php` - Execution history repository

**Services:**
- `packages/financial-reporting/src/Services/ReportGeneratorService.php` - Core report generation service
- `packages/financial-reporting/src/Services/GeneralLedgerIntegration.php` - GL integration service

**Actions:**
- `packages/financial-reporting/src/Actions/SeedStandardReportsAction.php` - Seed standard reports for tenant

**Seeders:**
- `packages/financial-reporting/database/seeders/StandardReportDefinitionsSeeder.php` - Standard report definitions

**Factories:**
- `packages/financial-reporting/database/factories/ReportDefinitionFactory.php` - Report definition factory
- `packages/financial-reporting/database/factories/ReportExecutionHistoryFactory.php` - Execution history factory

**Tests:**
- `packages/financial-reporting/tests/Feature/ReportGenerationTest.php` - Report generation feature tests
- `packages/financial-reporting/tests/Unit/ReportGeneratorServiceTest.php` - Report generator unit tests

**Service Provider:**
- `packages/financial-reporting/src/FinancialReportingServiceProvider.php` - Package service provider

## 6. Testing

- **TEST-001**: Generate Balance Sheet for closed fiscal period, verify all sections present, verify assets = liabilities + equity with tolerance 0.01
- **TEST-002**: Generate Income Statement for date range, verify revenue and expense totals, verify net income calculation
- **TEST-003**: Attempt to generate report for open period requiring closed period, expect PeriodNotClosedException
- **TEST-004**: Generate report with 3 comparison periods, verify each period data returned, verify variance calculations
- **TEST-005**: Verify report execution history recorded with parameters, result snapshot, execution time, row count
- **TEST-006**: Load test: Generate monthly report with 10,000 GL transactions, verify completion < 5 seconds (PR-FR-002)
- **TEST-007**: Unit test aggregate() method with various GL account mappings, verify correct grouping and summation
- **TEST-008**: Unit test calculate() method with formulas containing +, -, *, /, (), verify correct evaluation
- **TEST-009**: Unit test balance() method for unbalanced Balance Sheet, verify returns false and logs warning
- **TEST-010**: Integration test with real GL module: seed GL accounts and postings, generate Balance Sheet, verify data accuracy

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Performance degradation with large GL datasets (100K+ transactions per period)
  - *Mitigation*: Implement financial_aggregates table for pre-aggregation, use database indexing, implement query optimization
- **RISK-002**: Formula complexity causing security vulnerabilities (code injection)
  - *Mitigation*: Use symfony/expression-language for safe evaluation, validate formulas before saving, restrict formula syntax
- **RISK-003**: Report definition changes breaking historical execution snapshots
  - *Mitigation*: Store complete report definition in execution history, version report definitions
- **RISK-004**: GL data inconsistency causing unbalanced financial statements
  - *Mitigation*: Implement strict balancing validation, log warnings, provide drill-down to identify issues

**Assumptions:**
- **ASSUMPTION-001**: General Ledger data is complete and accurate (no missing postings or account misclassifications)
- **ASSUMPTION-002**: Fiscal periods are properly configured and closed before report generation
- **ASSUMPTION-003**: Chart of Accounts follows standard numbering conventions (1000s=Assets, 2000s=Liabilities, etc.)
- **ASSUMPTION-004**: Users understand financial accounting concepts (Balance Sheet, Income Statement, debits/credits)
- **ASSUMPTION-005**: Report performance requirements (< 5 seconds) based on single-tenant workload with monthly period

## 8. KIV for future implementations

- **KIV-001**: Implement materialized views for real-time aggregation refresh triggered by GL posting events
- **KIV-002**: Add support for consolidated reporting across multiple legal entities/companies within tenant
- **KIV-003**: Implement Cash Flow Statement automation with direct method (instead of indirect method)
- **KIV-004**: Add support for XBRL export for regulatory filing compliance
- **KIV-005**: Implement report scheduling with automatic generation and email delivery (addressed in PLAN02)
- **KIV-006**: Add support for custom formulas with advanced functions (IF, SUM, AVG, etc.)
- **KIV-007**: Implement report versioning with change tracking and rollback capability
- **KIV-008**: Add support for graphical visualization (charts, graphs) in addition to tabular reports

## 9. Related PRD / Further Reading

- Master PRD: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- Sub-PRD: [../prd/prd-01/PRD01-SUB20-FINANCIAL-REPORTING.md](../prd/prd-01/PRD01-SUB20-FINANCIAL-REPORTING.md)
- Related Sub-PRD: [../prd/prd-01/PRD01-SUB08-GENERAL-LEDGER.md](../prd/prd-01/PRD01-SUB08-GENERAL-LEDGER.md) - General Ledger integration
- Related Sub-PRD: [../prd/prd-01/PRD01-SUB15-BACKOFFICE.md](../prd/prd-01/PRD01-SUB15-BACKOFFICE.md) - Fiscal period management
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- GitHub Copilot Instructions: [../../.github/copilot-instructions.md](../../.github/copilot-instructions.md)
