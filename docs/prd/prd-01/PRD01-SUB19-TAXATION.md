# PRD01-SUB19: Taxation

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Optional Feature Modules - Financial Management  
**Related Sub-PRDs:** SUB08 (General Ledger), SUB11 (Accounts Payable), SUB12 (Accounts Receivable), SUB16 (Purchasing), SUB17 (Sales)  
**Composer Package:** `azaharizaman/erp-taxation`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Taxation module provides comprehensive tax management including tax authority configuration, automatic tax calculation, multiple tax type support, tax reporting, and compliance features for various jurisdictions.

### Purpose

This module solves the challenge of managing complex tax requirements across multiple jurisdictions and tax types. It automates tax calculation, ensures compliance with local regulations, and provides complete audit trails for tax authorities.

### Scope

**Included:**
- Tax authority master data (tax offices, jurisdictions, rates)
- Multiple tax types (VAT, GST, sales tax, withholding tax, excise duty)
- Automatic tax calculation on transactions
- Tax exemptions and special tax rates
- Tax reports (VAT return, GST summary, withholding tax report)
- Reverse charge mechanism for cross-border transactions
- Tax period tracking with filing deadlines
- Tax reconciliation between GL and tax reports

**Excluded:**
- General accounting functionality (handled by SUB08 General Ledger)
- Invoice processing (handled by SUB11/SUB12 AP/AR)
- Payment processing (handled by SUB09 Banking & Cash Management)

### Dependencies

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for tax configuration
- **SUB02 (Authentication & Authorization)** - User access control
- **SUB03 (Audit Logging)** - Track all tax configuration changes
- **SUB08 (General Ledger)** - Tax posting to control accounts
- **SUB11 (Accounts Payable)** - Tax calculation on purchase invoices
- **SUB12 (Accounts Receivable)** - Tax calculation on sales invoices

**Optional Dependencies:**
- **SUB16 (Purchasing)** - Automatic tax determination on purchases
- **SUB17 (Sales)** - Automatic tax determination on sales
- **SUB15 (Backoffice)** - Fiscal period management

### Composer Package Information

- **Package Name:** `azaharizaman/erp-taxation`
- **Namespace:** `Nexus\Erp\Taxation`
- **Monorepo Location:** `/packages/taxation/`
- **Installation:** `composer require azaharizaman/erp-taxation` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB19 (Taxation). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-TAX-001** | Maintain **tax authority master data** (tax offices, jurisdictions, rates) | High | Planned |
| **FR-TAX-002** | Support **multiple tax types** (VAT, GST, sales tax, withholding tax, excise duty) | High | Planned |
| **FR-TAX-003** | Calculate **automatic tax** on transactions based on jurisdiction and item category | High | Planned |
| **FR-TAX-004** | Support **tax exemptions** and **special tax rates** for specific customers or items | High | Planned |
| **FR-TAX-005** | Generate **tax reports** (VAT return, GST summary, withholding tax report) | High | Planned |
| **FR-TAX-006** | Support **reverse charge mechanism** for cross-border transactions | Medium | Planned |
| **FR-TAX-007** | Track **tax periods** with filing deadlines and compliance status | High | Planned |
| **FR-TAX-008** | Provide **tax reconciliation** between GL and tax reports | High | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-TAX-001** | Tax rates must have **effective date ranges** and cannot overlap | Planned |
| **BR-TAX-002** | **Negative tax amounts** not allowed without explicit reversal document | Planned |
| **BR-TAX-003** | Tax configuration changes **cannot be backdated** after period closing | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-TAX-001** | Store **complete tax calculation details** for each transaction line | Planned |
| **DR-TAX-002** | Maintain **tax rate history** with effective dates for audit | Planned |
| **DR-TAX-003** | Record **tax filing submissions** with acknowledgment receipts | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-TAX-001** | Integrate with **General Ledger** for tax posting to control accounts | Planned |
| **IR-TAX-002** | Integrate with **AP/AR** for tax calculation on invoices | Planned |
| **IR-TAX-003** | Integrate with **Sales/Purchasing** for automatic tax determination | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-TAX-001** | Implement **audit trail** for all tax configuration changes | Planned |
| **SR-TAX-002** | Restrict tax rate modifications to **authorized tax administrators** | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-TAX-001** | Tax calculation must complete in **< 50ms** per transaction | Planned |
| **PR-TAX-002** | Tax report generation must complete in **< 10 seconds** for monthly period | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-TAX-001** | Support **multiple tax jurisdictions** (federal, state, county, city) | Planned |

### Compliance Requirements (CR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **CR-TAX-001** | Comply with **local tax regulations** for each supported jurisdiction | Planned |
| **CR-TAX-002** | Support **e-filing formats** for tax authorities (XML, EDI) | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-TAX-001** | Use **tax calculation engine** with rule-based configuration | Planned |
| **ARCH-TAX-002** | Cache **frequently used tax rates** in Redis for performance | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-TAX-001** | `TaxCalculatedEvent` | When tax is computed on transaction | Planned |
| **EV-TAX-002** | `TaxPeriodClosedEvent` | When tax period is finalized | Planned |
| **EV-TAX-003** | `TaxFilingSubmittedEvent` | When tax return is filed | Planned |

---

## Technical Specifications

### Database Schema

**Tax Authorities Table:**

```sql
CREATE TABLE tax_authorities (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    authority_code VARCHAR(50) NOT NULL,
    authority_name VARCHAR(255) NOT NULL,
    country_code VARCHAR(10) NOT NULL,
    jurisdiction_type VARCHAR(50) NOT NULL,  -- 'federal', 'state', 'county', 'city'
    filing_frequency VARCHAR(20) NULL,  -- 'monthly', 'quarterly', 'annual'
    contact_person VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    website VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE (tenant_id, authority_code),
    INDEX idx_tax_authorities_tenant (tenant_id),
    INDEX idx_tax_authorities_country (country_code),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Tax Types Table:**

```sql
CREATE TABLE tax_types (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    tax_type_code VARCHAR(50) NOT NULL,
    tax_type_name VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,  -- 'VAT', 'GST', 'sales_tax', 'withholding', 'excise'
    calculation_method VARCHAR(50) NOT NULL,  -- 'percentage', 'fixed_amount', 'progressive'
    is_compound BOOLEAN DEFAULT FALSE,
    is_inclusive BOOLEAN DEFAULT FALSE,
    gl_account_id BIGINT NULL REFERENCES gl_accounts(id),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE (tenant_id, tax_type_code),
    INDEX idx_tax_types_tenant (tenant_id),
    INDEX idx_tax_types_category (category),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Tax Rates Table:**

```sql
CREATE TABLE tax_rates (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    tax_type_id BIGINT NOT NULL REFERENCES tax_types(id),
    tax_authority_id BIGINT NULL REFERENCES tax_authorities(id),
    rate_name VARCHAR(255) NOT NULL,
    rate_percentage DECIMAL(5, 4) NOT NULL,  -- e.g., 20.0000 for 20%
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    applies_to VARCHAR(50) NULL,  -- 'all', 'goods', 'services', 'specific_items'
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_tax_rates_tenant (tenant_id),
    INDEX idx_tax_rates_type (tax_type_id),
    INDEX idx_tax_rates_authority (tax_authority_id),
    INDEX idx_tax_rates_dates (effective_from, effective_to),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Tax Exemptions Table:**

```sql
CREATE TABLE tax_exemptions (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    exemption_code VARCHAR(50) NOT NULL,
    exemption_name VARCHAR(255) NOT NULL,
    tax_type_id BIGINT NOT NULL REFERENCES tax_types(id),
    exemption_type VARCHAR(50) NOT NULL,  -- 'full', 'partial', 'reduced_rate'
    reduced_rate DECIMAL(5, 4) NULL,
    entity_type VARCHAR(50) NULL,  -- 'customer', 'vendor', 'item', 'category'
    entity_id VARCHAR(255) NULL,
    certificate_number VARCHAR(100) NULL,
    valid_from DATE NULL,
    valid_to DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, exemption_code),
    INDEX idx_tax_exemptions_tenant (tenant_id),
    INDEX idx_tax_exemptions_type (tax_type_id),
    INDEX idx_tax_exemptions_entity (entity_type, entity_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Tax Calculation Details Table:**

```sql
CREATE TABLE tax_calculation_details (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,  -- 'sales_invoice', 'purchase_invoice', 'sales_order', etc.
    transaction_id BIGINT NOT NULL,
    transaction_line_id BIGINT NULL,
    tax_type_id BIGINT NOT NULL REFERENCES tax_types(id),
    tax_rate_id BIGINT NOT NULL REFERENCES tax_rates(id),
    tax_base_amount DECIMAL(15, 2) NOT NULL,
    tax_rate_percentage DECIMAL(5, 4) NOT NULL,
    tax_amount DECIMAL(15, 2) NOT NULL,
    exemption_id BIGINT NULL REFERENCES tax_exemptions(id),
    reverse_charge BOOLEAN DEFAULT FALSE,
    calculation_date DATE NOT NULL,
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_tax_calc_tenant (tenant_id),
    INDEX idx_tax_calc_transaction (transaction_type, transaction_id),
    INDEX idx_tax_calc_type (tax_type_id),
    INDEX idx_tax_calc_date (calculation_date),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Tax Periods Table:**

```sql
CREATE TABLE tax_periods (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    tax_authority_id BIGINT NOT NULL REFERENCES tax_authorities(id),
    period_name VARCHAR(100) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    filing_deadline DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',  -- 'open', 'closed', 'filed', 'audited'
    total_tax_collected DECIMAL(15, 2) DEFAULT 0,
    total_tax_paid DECIMAL(15, 2) DEFAULT 0,
    net_tax_payable DECIMAL(15, 2) DEFAULT 0,
    closed_by BIGINT NULL REFERENCES users(id),
    closed_at TIMESTAMP NULL,
    filed_by BIGINT NULL REFERENCES users(id),
    filed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_tax_periods_tenant (tenant_id),
    INDEX idx_tax_periods_authority (tax_authority_id),
    INDEX idx_tax_periods_dates (period_start, period_end),
    INDEX idx_tax_periods_status (status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Tax Filings Table:**

```sql
CREATE TABLE tax_filings (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    tax_period_id BIGINT NOT NULL REFERENCES tax_periods(id),
    filing_type VARCHAR(50) NOT NULL,  -- 'VAT_return', 'GST_return', 'withholding_tax', etc.
    filing_date DATE NOT NULL,
    filing_reference VARCHAR(100) NULL,
    filing_status VARCHAR(20) NOT NULL DEFAULT 'draft',  -- 'draft', 'submitted', 'accepted', 'rejected'
    total_sales DECIMAL(15, 2) DEFAULT 0,
    total_purchases DECIMAL(15, 2) DEFAULT 0,
    tax_collected DECIMAL(15, 2) DEFAULT 0,
    tax_paid DECIMAL(15, 2) DEFAULT 0,
    net_tax DECIMAL(15, 2) DEFAULT 0,
    xml_file_path TEXT NULL,
    acknowledgment_receipt TEXT NULL,
    submitted_by BIGINT NULL REFERENCES users(id),
    submitted_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_tax_filings_tenant (tenant_id),
    INDEX idx_tax_filings_period (tax_period_id),
    INDEX idx_tax_filings_status (filing_status),
    INDEX idx_tax_filings_date (filing_date),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

### API Endpoints

All endpoints follow the RESTful pattern under `/api/v1/taxation/`:

**Tax Authority Management:**
- `GET /api/v1/taxation/authorities` - List tax authorities
- `POST /api/v1/taxation/authorities` - Create tax authority
- `GET /api/v1/taxation/authorities/{id}` - Get authority details
- `PATCH /api/v1/taxation/authorities/{id}` - Update authority
- `DELETE /api/v1/taxation/authorities/{id}` - Soft delete authority

**Tax Type and Rate Management:**
- `GET /api/v1/taxation/types` - List tax types
- `POST /api/v1/taxation/types` - Create tax type
- `GET /api/v1/taxation/rates` - List tax rates with filtering
- `POST /api/v1/taxation/rates` - Create tax rate
- `PATCH /api/v1/taxation/rates/{id}` - Update tax rate
- `GET /api/v1/taxation/rates/effective` - Get effective rate for date/jurisdiction

**Tax Exemptions:**
- `GET /api/v1/taxation/exemptions` - List exemptions
- `POST /api/v1/taxation/exemptions` - Create exemption
- `PATCH /api/v1/taxation/exemptions/{id}` - Update exemption
- `POST /api/v1/taxation/exemptions/{id}/validate` - Validate exemption certificate

**Tax Calculation:**
- `POST /api/v1/taxation/calculate` - Calculate tax for transaction
- `POST /api/v1/taxation/validate-calculation` - Validate existing calculation
- `GET /api/v1/taxation/calculations/{transactionType}/{transactionId}` - Get calculations

**Tax Period Management:**
- `GET /api/v1/taxation/periods` - List tax periods
- `POST /api/v1/taxation/periods` - Create tax period
- `POST /api/v1/taxation/periods/{id}/close` - Close tax period
- `GET /api/v1/taxation/periods/{id}/summary` - Get period summary

**Tax Filing:**
- `GET /api/v1/taxation/filings` - List tax filings
- `POST /api/v1/taxation/filings` - Create tax filing
- `POST /api/v1/taxation/filings/{id}/submit` - Submit filing
- `GET /api/v1/taxation/filings/{id}/download-xml` - Download XML file

**Tax Reports:**
- `GET /api/v1/taxation/reports/vat-return` - VAT return report
- `GET /api/v1/taxation/reports/gst-summary` - GST summary report
- `GET /api/v1/taxation/reports/withholding-tax` - Withholding tax report
- `GET /api/v1/taxation/reports/reconciliation` - Tax reconciliation

### Events

**Domain Events Emitted:**

```php
namespace Nexus\Erp\Taxation\Events;

class TaxCalculatedEvent
{
    public function __construct(
        public readonly string $transactionType,
        public readonly int $transactionId,
        public readonly array $taxDetails,
        public readonly float $totalTaxAmount
    ) {}
}

class TaxPeriodClosedEvent
{
    public function __construct(
        public readonly TaxPeriod $period,
        public readonly float $totalCollected,
        public readonly float $totalPaid,
        public readonly User $closedBy
    ) {}
}

class TaxFilingSubmittedEvent
{
    public function __construct(
        public readonly TaxFiling $filing,
        public readonly TaxPeriod $period,
        public readonly User $submittedBy
    ) {}
}
```

### Event Listeners

**Events from Other Modules:**

This module listens to:
- `InvoiceCreatedEvent` (SUB11/SUB12) - Calculate tax on invoices
- `SalesOrderConfirmedEvent` (SUB17) - Calculate tax on sales orders
- `PurchaseOrderCreatedEvent` (SUB16) - Calculate tax on purchase orders
- `FiscalYearClosedEvent` (SUB15) - Close all tax periods for year

---

## Implementation Plans

**Note:** Implementation plans follow the naming convention `PLAN{number}-implement-{component}.md`

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN19-implement-taxation.md | FR-TAX-001 to FR-TAX-008, BR-TAX-001 to BR-TAX-003 | MILESTONE 9 | Not Started |

**Implementation plan will be created separately using:** `.github/prompts/create-implementation-plan.prompt.md`

---

## Acceptance Criteria

### Functional Acceptance

- [ ] Tax authority master data management operational
- [ ] Multiple tax types supported (VAT, GST, sales tax, withholding, excise)
- [ ] Automatic tax calculation on transactions functional
- [ ] Tax exemptions and special rates working
- [ ] Tax reports generation operational (VAT return, GST summary, withholding)
- [ ] Reverse charge mechanism functional
- [ ] Tax period tracking with filing deadlines working
- [ ] Tax reconciliation between GL and tax reports accurate

### Technical Acceptance

- [ ] All API endpoints return correct responses per OpenAPI spec
- [ ] Tax calculation completes in < 50ms per transaction (PR-TAX-001)
- [ ] Tax report generation completes in < 10 seconds for monthly period (PR-TAX-002)
- [ ] Multiple jurisdictions supported (federal, state, county, city) (SCR-TAX-001)
- [ ] Tax calculation engine with rule-based configuration functional (ARCH-TAX-001)
- [ ] Redis caching for frequently used tax rates operational (ARCH-TAX-002)

### Security Acceptance

- [ ] Audit trail for all tax configuration changes implemented (SR-TAX-001)
- [ ] Tax rate modifications restricted to authorized administrators (SR-TAX-002)

### Integration Acceptance

- [ ] Integration with General Ledger for tax posting functional (IR-TAX-001)
- [ ] Integration with AP/AR for invoice tax calculation working (IR-TAX-002)
- [ ] Integration with Sales/Purchasing for automatic tax determination operational (IR-TAX-003)

---

## Testing Strategy

### Unit Tests

**Test Coverage Requirements:** Minimum 80% code coverage

**Key Test Areas:**
- Tax calculation logic (percentage, fixed amount, compound)
- Tax rate effective date range validation
- Tax exemption application
- Reverse charge mechanism
- Tax period status transitions

**Example Tests:**
```php
test('tax rates cannot have overlapping effective dates', function () {
    $taxType = TaxType::factory()->create();
    
    TaxRate::factory()->create([
        'tax_type_id' => $taxType->id,
        'effective_from' => '2025-01-01',
        'effective_to' => '2025-12-31',
        'rate_percentage' => 20.00,
    ]);
    
    expect(fn () => TaxRate::factory()->create([
        'tax_type_id' => $taxType->id,
        'effective_from' => '2025-06-01',  // Overlaps with existing
        'effective_to' => '2026-05-31',
        'rate_percentage' => 18.00,
    ]))->toThrow(TaxRateOverlapException::class);
});

test('calculates compound tax correctly', function () {
    $baseAmount = 1000.00;
    $taxRate1 = 10.00;  // 10% = 100
    $taxRate2 = 5.00;   // 5% on (1000 + 100) = 55
    
    $result = CalculateTaxAction::run($baseAmount, [$taxRate1, $taxRate2], compound: true);
    
    expect($result['total_tax'])->toBe(155.00);
});
```

### Feature Tests

**API Integration Tests:**
- Complete CRUD operations for tax authorities via API
- Tax calculation on sample transactions
- Tax period closure and filing workflow
- Tax report generation

**Example Tests:**
```php
test('can calculate tax on transaction via API', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $taxType = TaxType::factory()->create(['tenant_id' => $tenant->id]);
    $taxRate = TaxRate::factory()->create([
        'tax_type_id' => $taxType->id,
        'rate_percentage' => 20.00,
    ]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/taxation/calculate', [
            'transaction_type' => 'sales_invoice',
            'transaction_id' => 1,
            'base_amount' => 1000.00,
            'tax_type_id' => $taxType->id,
        ]);
    
    $response->assertOk();
    expect($response->json('data.tax_amount'))->toBe(200.00);
});
```

### Integration Tests

**Cross-Module Integration:**
- Tax calculation on invoices (AP/AR)
- Tax posting to General Ledger
- Automatic tax determination from Sales/Purchasing

### Performance Tests

**Load Testing Scenarios:**
- Tax calculation: < 50ms per transaction (PR-TAX-001)
- Tax report generation: < 10 seconds for monthly period (PR-TAX-002)
- Concurrent tax calculations on 1000+ transactions

---

## Dependencies

### Feature Module Dependencies

**From Master PRD Section D.2.1:**

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for tax configuration
- **SUB02 (Authentication & Authorization)** - User access control
- **SUB03 (Audit Logging)** - Track all tax configuration changes
- **SUB08 (General Ledger)** - Tax posting to control accounts
- **SUB11 (Accounts Payable)** - Tax calculation on purchase invoices
- **SUB12 (Accounts Receivable)** - Tax calculation on sales invoices

**Optional Dependencies:**
- **SUB16 (Purchasing)** - Automatic tax determination on purchases
- **SUB17 (Sales)** - Automatic tax determination on sales
- **SUB15 (Backoffice)** - Fiscal period management

### External Package Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "azaharizaman/erp-core": "^1.0",
    "azaharizaman/erp-general-ledger": "^1.0",
    "lorisleiva/laravel-actions": "^2.0",
    "brick/math": "^0.12"
  },
  "require-dev": {
    "pestphp/pest": "^4.0"
  }
}
```

### Infrastructure Dependencies

- **Database:** PostgreSQL 14+ (for date range indexing and JSONB)
- **Cache:** Redis 6+ (for tax rate caching)
- **Queue:** Redis or database queue driver (for report generation)

---

## Feature Module Structure

### Directory Structure (in Monorepo)

```
packages/taxation/
├── src/
│   ├── Actions/
│   │   ├── CalculateTaxAction.php
│   │   ├── CreateTaxPeriodAction.php
│   │   ├── CloseTaxPeriodAction.php
│   │   └── GenerateTaxFilingAction.php
│   ├── Contracts/
│   │   ├── TaxCalculationServiceContract.php
│   │   └── TaxReportingServiceContract.php
│   ├── Events/
│   │   ├── TaxCalculatedEvent.php
│   │   ├── TaxPeriodClosedEvent.php
│   │   └── TaxFilingSubmittedEvent.php
│   ├── Listeners/
│   │   ├── CalculateTaxOnInvoiceListener.php
│   │   └── PostTaxToGLListener.php
│   ├── Models/
│   │   ├── TaxAuthority.php
│   │   ├── TaxType.php
│   │   ├── TaxRate.php
│   │   ├── TaxExemption.php
│   │   └── TaxPeriod.php
│   ├── Observers/
│   │   └── TaxRateObserver.php
│   ├── Policies/
│   │   ├── TaxAuthorityPolicy.php
│   │   └── TaxPeriodPolicy.php
│   ├── Repositories/
│   │   ├── TaxAuthorityRepository.php
│   │   └── TaxRateRepository.php
│   ├── Services/
│   │   ├── TaxCalculationService.php
│   │   ├── TaxReportingService.php
│   │   └── TaxReconciliationService.php
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Resources/
│   └── TaxationServiceProvider.php
├── tests/
│   ├── Feature/
│   │   ├── TaxCalculationTest.php
│   │   ├── TaxPeriodTest.php
│   │   └── TaxReportingTest.php
│   └── Unit/
│       ├── TaxCalculationLogicTest.php
│       └── TaxRateValidationTest.php
├── database/
│   ├── migrations/
│   │   ├── 2025_01_01_000001_create_tax_authorities_table.php
│   │   ├── 2025_01_01_000002_create_tax_types_table.php
│   │   ├── 2025_01_01_000003_create_tax_rates_table.php
│   │   └── 2025_01_01_000004_create_tax_periods_table.php
│   └── factories/
│       ├── TaxAuthorityFactory.php
│       └── TaxRateFactory.php
├── routes/
│   └── api.php
├── config/
│   └── taxation.php
├── composer.json
└── README.md
```

---

## Migration Path

This is a new module with no existing functionality to migrate from.

**Initial Setup:**
1. Install package via Composer
2. Publish migrations and run `php artisan migrate`
3. Configure tax authorities and jurisdictions
4. Set up tax types (VAT, GST, sales tax, etc.)
5. Configure tax rates with effective dates
6. Create tax periods and filing schedules
7. Import tax exemption certificates

---

## Success Metrics

From Master PRD Section B.3:

**Adoption Metrics:**
- Tax automation > 95% (vs. manual calculations)
- E-filing adoption > 80%

**Performance Metrics:**
- Tax calculation time < 50ms per transaction (PR-TAX-001)
- Tax report generation < 10 seconds for monthly period (PR-TAX-002)

**Accuracy Metrics:**
- 100% tax calculation accuracy
- < 1% tax reconciliation discrepancies

**Compliance Metrics:**
- 100% on-time tax filing
- Zero tax audit penalties

---

## Assumptions & Constraints

### Assumptions

1. Tax authorities and jurisdictions configured before go-live
2. Tax rates maintained and updated regularly
3. GL accounts for tax liabilities and assets configured
4. E-filing credentials obtained from tax authorities
5. Tax exemption certificates validated before use

### Constraints

1. Tax rates must have effective date ranges and cannot overlap
2. Negative tax amounts not allowed without explicit reversal
3. Tax configuration changes cannot be backdated after period closing
4. System supports multiple jurisdictions (federal, state, county, city)
5. Compliance with local tax regulations required

---

## Monorepo Integration

### Development

- Lives in `/packages/taxation/` during development
- Main app uses Composer path repository to require locally:
  ```json
  {
    "repositories": [
      {
        "type": "path",
        "url": "./packages/taxation"
      }
    ],
    "require": {
      "azaharizaman/erp-taxation": "@dev"
    }
  }
  ```
- All changes committed to monorepo

### Release (v1.0)

- Tagged with monorepo version (e.g., v1.0.0)
- Published to Packagist as `azaharizaman/erp-taxation`
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
2. Create implementation plan: `PLAN19-implement-taxation.md` in `/docs/plan/`
3. Break down into GitHub issues
4. Assign to MILESTONE 9 from Master PRD Section F.2.4
5. Set up feature module structure in `/packages/taxation/`
