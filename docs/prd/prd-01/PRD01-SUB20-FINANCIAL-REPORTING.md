# PRD01-SUB20: Financial Reporting

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Optional Feature Modules - Financial Management  
**Related Sub-PRDs:** SUB08 (General Ledger), SUB11 (Accounts Payable), SUB12 (Accounts Receivable), SUB15 (Backoffice)  
**Composer Package:** `azaharizaman/erp-financial-reporting`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Financial Reporting module provides comprehensive financial statement generation, management reporting, real-time dashboards, custom report builder, and business intelligence integration for data-driven decision making.

### Purpose

This module solves the challenge of generating accurate, timely financial reports that comply with accounting standards (GAAP/IFRS) while providing drill-down capabilities and multi-dimensional analysis for management insights.

### Scope

**Included:**
- Standard financial statements (Balance Sheet, P&L, Cash Flow Statement)
- Multi-period comparative reports with variance analysis
- Drill-down capability from summary to transaction detail
- Custom report builder with drag-and-drop field selection
- Management reports (departmental P&L, cost center analysis)
- Report scheduling with email delivery and export formats
- Real-time dashboards with KPIs and trend charts
- Consolidation reporting for multi-company groups

**Excluded:**
- Transaction processing (handled by transactional modules)
- Budget planning and forecasting (future module)
- Advanced analytics and machine learning (future BI tools)

### Dependencies

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for financial data
- **SUB02 (Authentication & Authorization)** - User access control
- **SUB03 (Audit Logging)** - Track report access and generation
- **SUB08 (General Ledger)** - Primary data source for financial reports
- **SUB15 (Backoffice)** - Fiscal year and period management

**Optional Dependencies:**
- **SUB11 (Accounts Payable)** - AP aging and vendor reports
- **SUB12 (Accounts Receivable)** - AR aging and customer reports
- **SUB09 (Banking)** - Cash flow statement data
- All transactional modules - Operational reporting data

### Composer Package Information

- **Package Name:** `azaharizaman/erp-financial-reporting`
- **Namespace:** `Azaharizaman\Erp\FinancialReporting`
- **Monorepo Location:** `/packages/financial-reporting/`
- **Installation:** `composer require azaharizaman/erp-financial-reporting` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB20 (Financial Reporting). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-FR-001** | Generate **standard financial statements** (Balance Sheet, P&L, Cash Flow Statement) | High | Planned |
| **FR-FR-002** | Support **multi-period comparative reports** with variance analysis | High | Planned |
| **FR-FR-003** | Provide **drill-down capability** from summary to transaction detail | High | Planned |
| **FR-FR-004** | Support **custom report builder** with drag-and-drop field selection | Medium | Planned |
| **FR-FR-005** | Generate **management reports** (departmental P&L, cost center analysis) | High | Planned |
| **FR-FR-006** | Support **report scheduling** with email delivery and export formats (PDF, Excel, CSV) | Medium | Planned |
| **FR-FR-007** | Provide **real-time dashboards** with KPIs and trend charts | High | Planned |
| **FR-FR-008** | Support **consolidation reporting** for multi-company groups | Medium | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-FR-001** | Reports can only be generated for **closed accounting periods** or current period | Planned |
| **BR-FR-002** | **Compliance reports** (SOX, IFRS) require audit trail and version control | Planned |
| **BR-FR-003** | Financial statements must **balance** (assets = liabilities + equity) | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-FR-001** | Store **report definitions** with field mappings and formulas | Planned |
| **DR-FR-002** | Maintain **report execution history** with parameters and generated snapshots | Planned |
| **DR-FR-003** | Cache **aggregated financial data** for faster report generation | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-FR-001** | Integrate with **General Ledger** as primary data source | Planned |
| **IR-FR-002** | Integrate with **all transactional modules** for operational reports | Planned |
| **IR-FR-003** | Provide **BI tool integration** (Power BI, Tableau, Looker) via APIs | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-FR-001** | Implement **role-based access** to financial reports by sensitivity level | Planned |
| **SR-FR-002** | **Watermark** and **log access** to confidential financial reports | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-FR-001** | Dashboard queries must return in **< 3 seconds** for datasets with < 10k rows | Planned |
| **PR-FR-002** | Financial statement generation must complete in **< 5 seconds** for monthly period | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-FR-001** | Support **10+ years of historical data** for trend analysis | Planned |

### Compliance Requirements (CR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **CR-FR-001** | Comply with **GAAP/IFRS** reporting standards | Planned |
| **CR-FR-002** | Support **SOX compliance** with complete audit trails | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-FR-001** | Use **PostgreSQL Materialized Views** or **dedicated Data Warehouse** (ClickHouse) | Planned |
| **ARCH-FR-002** | Implement **OLAP cubes** for multi-dimensional analysis | Planned |
| **ARCH-FR-003** | Use **incremental aggregation** to optimize report performance | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-FR-001** | `ReportGeneratedEvent` | When report is created | Planned |
| **EV-FR-002** | `DashboardRefreshedEvent` | When real-time dashboard updates | Planned |

---

## Technical Specifications

### Database Schema

**Report Definitions Table:**

```sql
CREATE TABLE report_definitions (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    report_code VARCHAR(100) NOT NULL,
    report_name VARCHAR(255) NOT NULL,
    report_type VARCHAR(50) NOT NULL,  -- 'financial_statement', 'management', 'custom', 'dashboard'
    report_category VARCHAR(50) NULL,  -- 'balance_sheet', 'income_statement', 'cash_flow', 'custom'
    data_source VARCHAR(50) NOT NULL,  -- 'gl', 'ap', 'ar', 'inventory', 'consolidated'
    field_mappings JSONB NOT NULL,
    formulas JSONB NULL,
    filters JSONB NULL,
    sort_order JSONB NULL,
    is_standard BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE (tenant_id, report_code),
    INDEX idx_report_defs_tenant (tenant_id),
    INDEX idx_report_defs_type (report_type),
    INDEX idx_report_defs_category (report_category),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Report Execution History Table:**

```sql
CREATE TABLE report_execution_history (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    report_definition_id BIGINT NOT NULL REFERENCES report_definitions(id),
    execution_parameters JSONB NOT NULL,
    report_period_from DATE NULL,
    report_period_to DATE NULL,
    execution_status VARCHAR(20) NOT NULL DEFAULT 'pending',  -- 'pending', 'running', 'completed', 'failed'
    execution_time_ms INT NULL,
    row_count INT NULL,
    file_path TEXT NULL,
    file_format VARCHAR(20) NULL,  -- 'pdf', 'excel', 'csv', 'json'
    error_message TEXT NULL,
    executed_by BIGINT NOT NULL REFERENCES users(id),
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_report_history_tenant (tenant_id),
    INDEX idx_report_history_definition (report_definition_id),
    INDEX idx_report_history_user (executed_by),
    INDEX idx_report_history_date (report_period_from, report_period_to),
    INDEX idx_report_history_status (execution_status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Report Schedules Table:**

```sql
CREATE TABLE report_schedules (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    report_definition_id BIGINT NOT NULL REFERENCES report_definitions(id),
    schedule_name VARCHAR(255) NOT NULL,
    schedule_frequency VARCHAR(20) NOT NULL,  -- 'daily', 'weekly', 'monthly', 'quarterly', 'annual'
    schedule_time TIME NULL,
    schedule_day_of_week INT NULL,  -- 0-6 for Sunday-Saturday
    schedule_day_of_month INT NULL,  -- 1-31
    execution_parameters JSONB NULL,
    recipients JSONB NOT NULL,  -- Array of email addresses
    file_format VARCHAR(20) NOT NULL DEFAULT 'pdf',
    is_active BOOLEAN DEFAULT TRUE,
    last_execution_at TIMESTAMP NULL,
    next_execution_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_report_schedules_tenant (tenant_id),
    INDEX idx_report_schedules_definition (report_definition_id),
    INDEX idx_report_schedules_next_exec (next_execution_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Financial Aggregates Table (Materialized View):**

```sql
CREATE MATERIALIZED VIEW financial_aggregates AS
SELECT
    tenant_id,
    fiscal_year_id,
    accounting_period_id,
    gl_account_id,
    account_code,
    account_name,
    account_type,
    account_category,
    department_id,
    cost_center_id,
    SUM(debit_amount) as total_debit,
    SUM(credit_amount) as total_credit,
    SUM(debit_amount - credit_amount) as net_balance
FROM
    gl_journal_entry_lines jel
    JOIN gl_accounts a ON jel.gl_account_id = a.id
    JOIN accounting_periods p ON jel.accounting_period_id = p.id
WHERE
    jel.deleted_at IS NULL
GROUP BY
    tenant_id,
    fiscal_year_id,
    accounting_period_id,
    gl_account_id,
    account_code,
    account_name,
    account_type,
    account_category,
    department_id,
    cost_center_id;

CREATE UNIQUE INDEX idx_financial_aggregates_unique
ON financial_aggregates (tenant_id, accounting_period_id, gl_account_id, department_id, cost_center_id);

CREATE INDEX idx_financial_aggregates_tenant ON financial_aggregates (tenant_id);
CREATE INDEX idx_financial_aggregates_period ON financial_aggregates (accounting_period_id);
CREATE INDEX idx_financial_aggregates_account ON financial_aggregates (gl_account_id);
```

**Dashboard Widgets Table:**

```sql
CREATE TABLE dashboard_widgets (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    widget_code VARCHAR(100) NOT NULL,
    widget_name VARCHAR(255) NOT NULL,
    widget_type VARCHAR(50) NOT NULL,  -- 'kpi', 'chart', 'table', 'gauge'
    chart_type VARCHAR(50) NULL,  -- 'line', 'bar', 'pie', 'doughnut', 'area'
    data_source VARCHAR(50) NOT NULL,
    query_definition JSONB NOT NULL,
    display_options JSONB NULL,
    refresh_interval INT DEFAULT 300,  -- seconds
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, widget_code),
    INDEX idx_dashboard_widgets_tenant (tenant_id),
    INDEX idx_dashboard_widgets_type (widget_type),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Dashboard Layouts Table:**

```sql
CREATE TABLE dashboard_layouts (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    layout_name VARCHAR(255) NOT NULL,
    layout_type VARCHAR(50) NOT NULL,  -- 'executive', 'financial', 'operational', 'custom'
    widget_configuration JSONB NOT NULL,  -- Array of widget placements
    is_default BOOLEAN DEFAULT FALSE,
    created_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_dashboard_layouts_tenant (tenant_id),
    INDEX idx_dashboard_layouts_type (layout_type),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Report Access Log Table:**

```sql
CREATE TABLE report_access_log (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    report_definition_id BIGINT NULL REFERENCES report_definitions(id),
    report_execution_id BIGINT NULL REFERENCES report_execution_history(id),
    user_id BIGINT NOT NULL REFERENCES users(id),
    access_type VARCHAR(20) NOT NULL,  -- 'view', 'download', 'print', 'email'
    ip_address VARCHAR(50) NULL,
    user_agent TEXT NULL,
    accessed_at TIMESTAMP NOT NULL,
    
    INDEX idx_report_access_tenant (tenant_id),
    INDEX idx_report_access_user (user_id),
    INDEX idx_report_access_date (accessed_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

### API Endpoints

All endpoints follow the RESTful pattern under `/api/v1/financial-reporting/`:

**Report Definitions:**
- `GET /api/v1/financial-reporting/definitions` - List report definitions
- `POST /api/v1/financial-reporting/definitions` - Create custom report
- `GET /api/v1/financial-reporting/definitions/{id}` - Get definition details
- `PATCH /api/v1/financial-reporting/definitions/{id}` - Update definition
- `DELETE /api/v1/financial-reporting/definitions/{id}` - Delete custom report

**Standard Financial Statements:**
- `GET /api/v1/financial-reporting/balance-sheet` - Generate Balance Sheet
- `GET /api/v1/financial-reporting/income-statement` - Generate P&L
- `GET /api/v1/financial-reporting/cash-flow-statement` - Generate Cash Flow
- `GET /api/v1/financial-reporting/trial-balance` - Generate Trial Balance

**Report Execution:**
- `POST /api/v1/financial-reporting/execute` - Execute report
- `GET /api/v1/financial-reporting/executions/{id}` - Get execution details
- `GET /api/v1/financial-reporting/executions/{id}/download` - Download report
- `POST /api/v1/financial-reporting/executions/{id}/email` - Email report

**Report Scheduling:**
- `GET /api/v1/financial-reporting/schedules` - List report schedules
- `POST /api/v1/financial-reporting/schedules` - Create schedule
- `PATCH /api/v1/financial-reporting/schedules/{id}` - Update schedule
- `DELETE /api/v1/financial-reporting/schedules/{id}` - Delete schedule

**Dashboards:**
- `GET /api/v1/financial-reporting/dashboards` - List dashboard layouts
- `POST /api/v1/financial-reporting/dashboards` - Create dashboard
- `GET /api/v1/financial-reporting/dashboards/{id}` - Get dashboard layout
- `GET /api/v1/financial-reporting/dashboards/{id}/data` - Get dashboard data
- `POST /api/v1/financial-reporting/dashboards/{id}/refresh` - Refresh dashboard

**Widgets:**
- `GET /api/v1/financial-reporting/widgets` - List available widgets
- `POST /api/v1/financial-reporting/widgets` - Create custom widget
- `GET /api/v1/financial-reporting/widgets/{id}/data` - Get widget data

**BI Integration:**
- `GET /api/v1/financial-reporting/bi/datasets` - List available datasets
- `GET /api/v1/financial-reporting/bi/datasets/{id}` - Get dataset schema
- `POST /api/v1/financial-reporting/bi/query` - Execute custom BI query

### Events

**Domain Events Emitted:**

```php
namespace Azaharizaman\Erp\FinancialReporting\Events;

class ReportGeneratedEvent
{
    public function __construct(
        public readonly ReportDefinition $definition,
        public readonly ReportExecutionHistory $execution,
        public readonly string $fileFormat,
        public readonly User $generatedBy
    ) {}
}

class DashboardRefreshedEvent
{
    public function __construct(
        public readonly DashboardLayout $dashboard,
        public readonly array $widgetData,
        public readonly int $executionTimeMs
    ) {}
}
```

### Event Listeners

**Events from Other Modules:**

This module listens to:
- `AccountingPeriodClosedEvent` (SUB15) - Refresh materialized views
- `JournalEntryPostedEvent` (SUB08) - Update real-time dashboards
- `FiscalYearClosedEvent` (SUB15) - Generate annual financial statements

---

## Implementation Plans

**Note:** Implementation plans follow the naming convention `PLAN{number}-implement-{component}.md`

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN20-implement-financial-reporting.md | FR-FR-001 to FR-FR-008, BR-FR-001 to BR-FR-003 | MILESTONE 9 | Not Started |

**Implementation plan will be created separately using:** `.github/prompts/create-implementation-plan.prompt.md`

---

## Acceptance Criteria

### Functional Acceptance

- [ ] Standard financial statements generation functional (Balance Sheet, P&L, Cash Flow)
- [ ] Multi-period comparative reports with variance analysis working
- [ ] Drill-down capability from summary to transaction detail operational
- [ ] Custom report builder with drag-and-drop functional
- [ ] Management reports generation working (departmental P&L, cost center analysis)
- [ ] Report scheduling with email delivery operational
- [ ] Real-time dashboards with KPIs working
- [ ] Consolidation reporting for multi-company groups functional

### Technical Acceptance

- [ ] All API endpoints return correct responses per OpenAPI spec
- [ ] Dashboard queries return in < 3 seconds for < 10k rows (PR-FR-001)
- [ ] Financial statement generation completes in < 5 seconds for monthly period (PR-FR-002)
- [ ] System supports 10+ years of historical data (SCR-FR-001)
- [ ] Materialized views or data warehouse functional (ARCH-FR-001)
- [ ] OLAP cubes for multi-dimensional analysis operational (ARCH-FR-002)
- [ ] Incremental aggregation optimizes performance (ARCH-FR-003)

### Security Acceptance

- [ ] Role-based access to financial reports enforced (SR-FR-001)
- [ ] Confidential reports watermarked and access logged (SR-FR-002)

### Integration Acceptance

- [ ] Integration with General Ledger as primary data source functional (IR-FR-001)
- [ ] Integration with all transactional modules for operational reports working (IR-FR-002)
- [ ] BI tool integration via APIs operational (IR-FR-003)

---

## Testing Strategy

### Unit Tests

**Test Coverage Requirements:** Minimum 80% code coverage

**Key Test Areas:**
- Financial statement calculations (balance, P&L, cash flow)
- Variance analysis logic
- Report formula evaluation
- Data aggregation logic
- Balance validation (assets = liabilities + equity)

**Example Tests:**
```php
test('balance sheet must balance', function () {
    $period = AccountingPeriod::factory()->create();
    
    $balanceSheet = GenerateBalanceSheetAction::run($period);
    
    $totalAssets = $balanceSheet['assets']['total'];
    $totalLiabilities = $balanceSheet['liabilities']['total'];
    $totalEquity = $balanceSheet['equity']['total'];
    
    expect($totalAssets)->toBe($totalLiabilities + $totalEquity);
});

test('variance analysis calculates correctly', function () {
    $currentPeriod = 1000.00;
    $previousPeriod = 800.00;
    
    $variance = CalculateVarianceAction::run($currentPeriod, $previousPeriod);
    
    expect($variance['amount'])->toBe(200.00);
    expect($variance['percentage'])->toBe(25.00);
});
```

### Feature Tests

**API Integration Tests:**
- Generate standard financial statements via API
- Execute custom reports
- Schedule report delivery
- Access dashboard data

**Example Tests:**
```php
test('can generate balance sheet via API', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $period = AccountingPeriod::factory()->create(['tenant_id' => $tenant->id]);
    
    $response = $this->actingAs($user)
        ->getJson('/api/v1/financial-reporting/balance-sheet', [
            'period_id' => $period->id,
            'format' => 'json',
        ]);
    
    $response->assertOk();
    expect($response->json('data.assets'))->toHaveKey('total');
    expect($response->json('data.liabilities'))->toHaveKey('total');
    expect($response->json('data.equity'))->toHaveKey('total');
});
```

### Integration Tests

**Cross-Module Integration:**
- Financial statement data from General Ledger
- Operational report data from transactional modules
- Period closure triggering report generation

### Performance Tests

**Load Testing Scenarios:**
- Dashboard queries: < 3 seconds for < 10k rows (PR-FR-001)
- Financial statement generation: < 5 seconds for monthly period (PR-FR-002)
- 10+ years of historical data handling
- Concurrent report generation by multiple users

---

## Dependencies

### Feature Module Dependencies

**From Master PRD Section D.2.1:**

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for financial data
- **SUB02 (Authentication & Authorization)** - User access control
- **SUB03 (Audit Logging)** - Track report access and generation
- **SUB08 (General Ledger)** - Primary data source for financial reports
- **SUB15 (Backoffice)** - Fiscal year and period management

**Optional Dependencies:**
- **SUB11 (Accounts Payable)** - AP aging and vendor reports
- **SUB12 (Accounts Receivable)** - AR aging and customer reports
- **SUB09 (Banking)** - Cash flow statement data
- All transactional modules - Operational reporting data

### External Package Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "azaharizaman/erp-core": "^1.0",
    "azaharizaman/erp-general-ledger": "^1.0",
    "lorisleiva/laravel-actions": "^2.0",
    "spatie/laravel-pdf": "^1.0"
  },
  "require-dev": {
    "pestphp/pest": "^4.0"
  }
}
```

### Infrastructure Dependencies

- **Database:** PostgreSQL 14+ (for materialized views and OLAP)
- **Cache:** Redis 6+ (for dashboard data caching)
- **Queue:** Redis or database queue driver (for scheduled reports)
- **Data Warehouse:** ClickHouse (optional, for large-scale analytics)

---

## Feature Module Structure

### Directory Structure (in Monorepo)

```
packages/financial-reporting/
├── src/
│   ├── Actions/
│   │   ├── GenerateBalanceSheetAction.php
│   │   ├── GenerateIncomeStatementAction.php
│   │   ├── GenerateCashFlowAction.php
│   │   ├── ExecuteReportAction.php
│   │   └── RefreshDashboardAction.php
│   ├── Contracts/
│   │   ├── ReportGeneratorServiceContract.php
│   │   └── DashboardServiceContract.php
│   ├── Events/
│   │   ├── ReportGeneratedEvent.php
│   │   └── DashboardRefreshedEvent.php
│   ├── Listeners/
│   │   ├── RefreshMaterializedViewsListener.php
│   │   └── UpdateRealtimeDashboardListener.php
│   ├── Models/
│   │   ├── ReportDefinition.php
│   │   ├── ReportExecutionHistory.php
│   │   ├── ReportSchedule.php
│   │   ├── DashboardWidget.php
│   │   └── DashboardLayout.php
│   ├── Observers/
│   │   └── ReportDefinitionObserver.php
│   ├── Policies/
│   │   └── ReportDefinitionPolicy.php
│   ├── Repositories/
│   │   └── ReportDefinitionRepository.php
│   ├── Services/
│   │   ├── ReportGeneratorService.php
│   │   ├── DashboardService.php
│   │   ├── VarianceAnalysisService.php
│   │   └── ReportSchedulerService.php
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Resources/
│   └── FinancialReportingServiceProvider.php
├── tests/
│   ├── Feature/
│   │   ├── FinancialStatementsTest.php
│   │   ├── CustomReportsTest.php
│   │   └── DashboardTest.php
│   └── Unit/
│       ├── BalanceSheetCalculationTest.php
│       └── VarianceAnalysisTest.php
├── database/
│   ├── migrations/
│   │   ├── 2025_01_01_000001_create_report_definitions_table.php
│   │   ├── 2025_01_01_000002_create_report_execution_history_table.php
│   │   ├── 2025_01_01_000003_create_report_schedules_table.php
│   │   └── 2025_01_01_000004_create_financial_aggregates_view.php
│   └── factories/
│       └── ReportDefinitionFactory.php
├── routes/
│   └── api.php
├── config/
│   └── financial-reporting.php
├── composer.json
└── README.md
```

---

## Migration Path

This is a new module with no existing functionality to migrate from.

**Initial Setup:**
1. Install package via Composer
2. Publish migrations and run `php artisan migrate`
3. Create materialized views for aggregated data
4. Configure standard report definitions
5. Set up default dashboard layouts
6. Configure report schedules as needed

---

## Success Metrics

From Master PRD Section B.3:

**Adoption Metrics:**
- Financial statement automation > 90%
- Dashboard utilization > 75% of finance users
- Custom report creation > 50 reports per tenant

**Performance Metrics:**
- Dashboard query time < 3 seconds for < 10k rows (PR-FR-001)
- Financial statement generation < 5 seconds for monthly period (PR-FR-002)

**Accuracy Metrics:**
- 100% financial statement balancing
- < 1% variance in automated vs. manual reports

**Operational Metrics:**
- Report generation time reduction > 80%
- Financial close cycle time reduction > 50%

---

## Assumptions & Constraints

### Assumptions

1. General Ledger data is complete and accurate
2. Accounting periods closed before report generation
3. Chart of accounts properly configured with categories
4. Users trained on report builder and dashboard tools
5. Historical data migrated before trend analysis

### Constraints

1. Reports can only be generated for closed periods or current period
2. Compliance reports require audit trail and version control
3. Financial statements must balance (assets = liabilities + equity)
4. System supports 10+ years of historical data
5. GAAP/IFRS and SOX compliance required

---

## Monorepo Integration

### Development

- Lives in `/packages/financial-reporting/` during development
- Main app uses Composer path repository to require locally:
  ```json
  {
    "repositories": [
      {
        "type": "path",
        "url": "./packages/financial-reporting"
      }
    ],
    "require": {
      "azaharizaman/erp-financial-reporting": "@dev"
    }
  }
  ```
- All changes committed to monorepo

### Release (v1.0)

- Tagged with monorepo version (e.g., v1.0.0)
- Published to Packagist as `azaharizaman/erp-financial-reporting`
- Can be installed independently in external Laravel apps
- Semantic versioning: MAJOR.MINOR.PATCH

---

## References

- Master PRD: [../PRD01-MVP.md](../PRD01-MVP.md)
- Monorepo Strategy: [../PRD01-MVP.md#C.1](../PRD01-MVP.md#section-c1-core-architectural-strategy-the-monorepo)
- Feature Module Independence: [../PRD01-MVP.md#D.2.2](../PRD01-MVP.md#d22-feature-module-independence-requirements)
- Architecture Documentation: [../../architecture/](../../architecture/)
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- GitHub Copilot Instructions: [../../.github/copilot-instructions.md](../../.github/copilot-instructions.md)

---

**Next Steps:**
1. Review and approve this Sub-PRD
2. Create implementation plan: `PLAN20-implement-financial-reporting.md` in `/docs/plan/`
3. Break down into GitHub issues
4. Assign to MILESTONE 9 from Master PRD Section F.2.4
5. Set up feature module structure in `/packages/financial-reporting/`
