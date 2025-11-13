# PRD01-SUB08: General Ledger System

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Mandatory Feature Modules - Finance & Accounting  
**Related Sub-PRDs:** PRD01-SUB07 (Chart of Accounts), PRD01-SUB09 (Journal Entries), PRD01-SUB10 (Banking), PRD01-SUB11 (Accounts Payable), PRD01-SUB12 (Accounts Receivable)  
**Composer Package:** `azaharizaman/erp-general-ledger`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The General Ledger (GL) System provides **core general ledger with automatic posting from submodules, multi-currency support, and balanced entry enforcement**. This is the central nervous system of the financial accounting module, aggregating all financial transactions from all submodules into a unified, auditable ledger that maintains the fundamental accounting equation: Assets = Liabilities + Equity.

### Purpose

The General Ledger System solves the critical problem of **unified financial transaction management** in an enterprise ERP. It enables:

1. **Central Repository:** Single source of truth for all financial transactions
2. **Automatic Integration:** Auto-post entries from AP, AR, Inventory, Payroll, etc.
3. **Multi-Currency:** Handle transactions in multiple currencies with exchange rates
4. **Financial Integrity:** Enforce double-entry bookkeeping (debits = credits)
5. **Real-Time Reporting:** Provide up-to-date financial position and performance data
6. **Audit Trail:** Complete history of all financial activities

### Scope

**Included in this Feature Module:**

- ✅ Double-entry bookkeeping enforcement
- ✅ Automatic posting from all submodules
- ✅ Multi-currency transaction support
- ✅ Exchange rate management
- ✅ Account balance calculations
- ✅ Fiscal period tracking
- ✅ Posting and unposting capabilities
- ✅ Entry reversal functionality
- ✅ Aggregated monthly balances for performance
- ✅ ACID compliance for data integrity

**Excluded from this Feature Module:**

- ❌ Manual journal entries (handled by SUB09)
- ❌ Financial statement generation (handled by SUB20)
- ❌ Budgeting and variance analysis (future enhancement)
- ❌ Consolidation across entities (future enhancement)

### Dependencies

**Mandatory Dependencies:**
- Laravel Framework v12.x
- PHP ≥ 8.2
- PostgreSQL with ACID support
- PRD01-SUB01 (Multi-Tenancy System)
- PRD01-SUB07 (Chart of Accounts)

**Feature Module Dependencies:**
- **Mandatory:** SUB01 (Multi-Tenancy), SUB07 (Chart of Accounts)
- **Integration:** SUB09 (Journal Entries), SUB10 (Banking), SUB11 (AP), SUB12 (AR), SUB14 (Inventory), SUB13 (HCM)

### Composer Package Information

- **Package Name:** `azaharizaman/erp-general-ledger`
- **Namespace:** `Nexus\Erp\GeneralLedger`
- **Monorepo Location:** `/packages/general-ledger/`
- **Installation:** `composer require azaharizaman/erp-general-ledger` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB08 (General Ledger). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-GL-001** | **Automatically post entries** from all submodules (AP, AR, Inventory, Payroll) to the general ledger with full audit trail | High | Planned |
| **FR-GL-002** | Support **multi-currency** transactions with automatic exchange rate conversion and revaluation | High | Planned |
| **FR-GL-003** | Implement **period closing** process with validation and lock-down to prevent backdated entries | High | Planned |
| **FR-GL-004** | Provide **account balance inquiries** at any point in time with drill-down to transaction detail | High | Planned |
| **FR-GL-005** | Support **batch journal entry posting** with validation and error reporting | Medium | Planned |
| **FR-GL-006** | Generate **trial balance report** with comparative periods and variance analysis | High | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-GL-001** | Ensure all journal entries are **balanced (debit = credit)** before posting | Planned |
| **BR-GL-002** | **Posted entries** cannot be modified; only reversed with offsetting entries | Planned |
| **BR-GL-003** | Entries can only be posted to **active fiscal periods**; closed periods reject new entries | Planned |
| **BR-GL-004** | Foreign currency transactions MUST record both **base and foreign amounts** with exchange rate | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-GL-001** | Store **aggregated monthly balances** for high-performance reporting and fast financial statement generation | Planned |
| **DR-GL-002** | GL entries MUST store: account_id, debit, credit, currency, exchange_rate, posting_date, source_type, source_id, created_by | Planned |
| **DR-GL-003** | Maintain **complete audit trail** with posted_by, posted_at, reversed_by, reversed_at timestamps | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-GL-001** | Integrate with **Chart of Accounts** for account validation during posting | Planned |
| **IR-GL-002** | Integrate with **Fiscal Period Management** (Backoffice) for period status validation | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-GL-001** | Posting **1000 journal entries** should complete in **< 1 second** using batch processing | Planned |
| **PR-GL-002** | Account balance queries MUST use pre-aggregated monthly balances for **< 100ms** response time | Planned |
| **PR-GL-003** | Trial balance generation must complete in **< 3 seconds** for 10,000 accounts | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-GL-001** | Support **10 million+ GL entries** per tenant per year with optimal indexing | Planned |

### Compliance Requirements (CR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **CR-GL-001** | Maintain **immutable audit trail** of all GL transactions for regulatory compliance | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-GL-001** | **ACID compliance non-negotiable** for financial data integrity - use database transactions with row-level locking | Planned |
| **ARCH-GL-002** | Use **PostgreSQL row-level locking** (`SELECT FOR UPDATE`) for concurrent entry posting | Planned |
| **ARCH-GL-003** | Implement **monthly balance aggregation** as materialized view or summary table | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-GL-001** | `GLEntryPostedEvent` | When journal entry is posted to GL | Planned |
| **EV-GL-002** | `FiscalPeriodClosedEvent` | When accounting period is closed | Planned |
| **EV-GL-003** | `GLEntryReversedEvent` | When entry is reversed | Planned |
| **EV-GL-004** | `BalanceRevaluatedEvent` | When foreign currency balances are revalued | Planned |

---

## Technical Specifications

### Database Schema

**GL Entries Table:**

```sql
CREATE TABLE gl_entries (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    entry_number VARCHAR(50) NOT NULL,
    posting_date DATE NOT NULL,
    fiscal_year INT NOT NULL,
    fiscal_period INT NOT NULL,
    description TEXT NULL,
    source_type VARCHAR(100) NOT NULL, -- 'JournalEntry', 'Invoice', 'Payment', 'Payroll', etc.
    source_id BIGINT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft', -- 'draft', 'posted', 'reversed'
    posted_at TIMESTAMP NULL,
    posted_by BIGINT NULL,
    reversed_at TIMESTAMP NULL,
    reversed_by BIGINT NULL,
    reversal_entry_id BIGINT NULL REFERENCES gl_entries(id),
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, entry_number),
    INDEX idx_gl_entries_tenant (tenant_id),
    INDEX idx_gl_entries_date (posting_date),
    INDEX idx_gl_entries_period (fiscal_year, fiscal_period),
    INDEX idx_gl_entries_source (source_type, source_id),
    INDEX idx_gl_entries_status (status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**GL Entry Lines Table:**

```sql
CREATE TABLE gl_entry_lines (
    id BIGSERIAL PRIMARY KEY,
    gl_entry_id BIGINT NOT NULL REFERENCES gl_entries(id) ON DELETE CASCADE,
    line_number INT NOT NULL,
    account_id BIGINT NOT NULL REFERENCES accounts(id),
    debit_amount DECIMAL(20, 4) NOT NULL DEFAULT 0,
    credit_amount DECIMAL(20, 4) NOT NULL DEFAULT 0,
    currency_code VARCHAR(3) NOT NULL DEFAULT 'USD',
    exchange_rate DECIMAL(12, 6) NOT NULL DEFAULT 1.0,
    debit_base_amount DECIMAL(20, 4) NOT NULL DEFAULT 0,
    credit_base_amount DECIMAL(20, 4) NOT NULL DEFAULT 0,
    description TEXT NULL,
    cost_center VARCHAR(50) NULL,
    project_code VARCHAR(50) NULL,
    metadata JSONB NULL,
    
    INDEX idx_gl_lines_entry (gl_entry_id),
    INDEX idx_gl_lines_account (account_id),
    INDEX idx_gl_lines_currency (currency_code)
);
```

**GL Account Balances Table (Aggregated):**

```sql
CREATE TABLE gl_account_balances (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    account_id BIGINT NOT NULL REFERENCES accounts(id),
    fiscal_year INT NOT NULL,
    fiscal_period INT NOT NULL,
    opening_balance DECIMAL(20, 4) NOT NULL DEFAULT 0,
    debit_total DECIMAL(20, 4) NOT NULL DEFAULT 0,
    credit_total DECIMAL(20, 4) NOT NULL DEFAULT 0,
    closing_balance DECIMAL(20, 4) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, account_id, fiscal_year, fiscal_period),
    INDEX idx_gl_balances_tenant (tenant_id),
    INDEX idx_gl_balances_account (account_id),
    INDEX idx_gl_balances_period (fiscal_year, fiscal_period)
);
```

**Exchange Rates Table:**

```sql
CREATE TABLE exchange_rates (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    from_currency VARCHAR(3) NOT NULL,
    to_currency VARCHAR(3) NOT NULL,
    rate DECIMAL(12, 6) NOT NULL,
    effective_date DATE NOT NULL,
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_exchange_rates_tenant (tenant_id),
    INDEX idx_exchange_rates_currency (from_currency, to_currency, effective_date)
);
```

### API Endpoints

All endpoints follow `/api/v1/gl` pattern:

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/v1/gl/entries` | List GL entries | Yes - Accounting |
| GET | `/api/v1/gl/entries/{id}` | Get entry details | Yes - Accounting |
| POST | `/api/v1/gl/entries/post` | Post draft entry to GL | Yes - Accounting Manager |
| POST | `/api/v1/gl/entries/{id}/reverse` | Reverse posted entry | Yes - Accounting Manager |
| GET | `/api/v1/gl/accounts/{id}/balance` | Get account balance | Yes - Accounting |
| GET | `/api/v1/gl/accounts/{id}/ledger` | Get account ledger (transactions) | Yes - Accounting |
| GET | `/api/v1/gl/trial-balance` | Get trial balance report | Yes - Accounting |
| POST | `/api/v1/gl/entries/batch-post` | Post multiple entries in batch | Yes - Accounting Manager |
| GET | `/api/v1/gl/exchange-rates` | List exchange rates | Yes |
| POST | `/api/v1/gl/exchange-rates` | Create/update exchange rate | Yes - Accounting Manager |
| GET | `/api/v1/gl/fiscal-periods` | List open fiscal periods | Yes |

**Query Parameters:**

- `from_date` - Start date filter
- `to_date` - End date filter
- `fiscal_year` - Filter by fiscal year
- `fiscal_period` - Filter by period
- `account_id` - Filter by account
- `source_type` - Filter by source module
- `status` - Filter by entry status

**Request/Response Examples:**

**Get GL Entry:**
```json
// GET /api/v1/gl/entries/12345

// Response 200 OK
{
    "data": {
        "id": 12345,
        "entry_number": "GL-2025-001234",
        "posting_date": "2025-11-11",
        "fiscal_year": 2025,
        "fiscal_period": 11,
        "description": "Invoice #INV-2025-001 - Customer ABC Corp",
        "source_type": "Invoice",
        "source_id": 5678,
        "status": "posted",
        "posted_at": "2025-11-11T10:00:00Z",
        "lines": [
            {
                "line_number": 1,
                "account": {
                    "code": "1120",
                    "name": "Accounts Receivable"
                },
                "debit_amount": 1150.00,
                "credit_amount": 0,
                "currency_code": "USD"
            },
            {
                "line_number": 2,
                "account": {
                    "code": "4100",
                    "name": "Sales Revenue"
                },
                "debit_amount": 0,
                "credit_amount": 1000.00,
                "currency_code": "USD"
            },
            {
                "line_number": 3,
                "account": {
                    "code": "2130",
                    "name": "Sales Tax Payable"
                },
                "debit_amount": 0,
                "credit_amount": 150.00,
                "currency_code": "USD"
            }
        ],
        "totals": {
            "debit_total": 1150.00,
            "credit_total": 1150.00,
            "is_balanced": true
        }
    }
}
```

**Post Entry:**
```json
// POST /api/v1/gl/entries/post
{
    "entry_id": 12345,
    "posting_date": "2025-11-11"
}

// Response 200 OK
{
    "data": {
        "id": 12345,
        "entry_number": "GL-2025-001234",
        "status": "posted",
        "posted_at": "2025-11-11T10:00:00Z",
        "message": "Entry posted successfully"
    }
}
```

**Get Account Balance:**
```json
// GET /api/v1/gl/accounts/123/balance?fiscal_year=2025&fiscal_period=11

// Response 200 OK
{
    "data": {
        "account": {
            "id": 123,
            "code": "1110",
            "name": "Cash"
        },
        "fiscal_year": 2025,
        "fiscal_period": 11,
        "opening_balance": 50000.00,
        "debit_total": 125000.00,
        "credit_total": 98000.00,
        "closing_balance": 77000.00
    }
}
```

**Trial Balance:**
```json
// GET /api/v1/gl/trial-balance?fiscal_year=2025&fiscal_period=11

// Response 200 OK
{
    "data": {
        "fiscal_year": 2025,
        "fiscal_period": 11,
        "accounts": [
            {
                "code": "1110",
                "name": "Cash",
                "debit_balance": 77000.00,
                "credit_balance": 0
            },
            {
                "code": "1120",
                "name": "Accounts Receivable",
                "debit_balance": 150000.00,
                "credit_balance": 0
            },
            {
                "code": "2110",
                "name": "Accounts Payable",
                "debit_balance": 0,
                "credit_balance": 85000.00
            }
        ],
        "totals": {
            "total_debits": 450000.00,
            "total_credits": 450000.00,
            "is_balanced": true
        }
    }
}
```

### Service API

**Facade Usage:**
```php
use Nexus\Erp\GeneralLedger\Facades\GL;

// Create GL entry (usually done by submodules)
$entry = GL::createEntry([
    'posting_date' => now(),
    'description' => 'Invoice #INV-001',
    'source_type' => Invoice::class,
    'source_id' => $invoice->id,
    'lines' => [
        ['account_id' => 123, 'debit' => 1000],
        ['account_id' => 456, 'credit' => 1000],
    ]
]);

// Post entry
GL::postEntry($entry);

// Reverse entry
$reversal = GL::reverseEntry($entry, reason: 'Correction needed');

// Get account balance
$balance = GL::accountBalance($account, fiscalYear: 2025, fiscalPeriod: 11);

// Validate entry is balanced
$isBalanced = GL::isBalanced($entry); // true/false

// Batch post entries
GL::batchPost($entryIds);
```

### Events

**Domain Events Emitted by this Feature Module:**

| Event Class | When Fired | Payload |
|-------------|-----------|---------|
| `GLEntryCreatedEvent` | After GL entry created | `GLEntry $entry` |
| `GLEntryPostedEvent` | After entry posted | `GLEntry $entry` |
| `GLEntryReversedEvent` | After entry reversed | `GLEntry $original, GLEntry $reversal` |
| `AccountBalanceUpdatedEvent` | After balance recalculated | `Account $account, $oldBalance, $newBalance` |

**Event Listeners:**

This module listens to events from:
- Invoice module → Auto-create AR entries
- Payment module → Auto-create AP entries
- Inventory movements → Auto-create inventory valuation entries
- Payroll → Auto-create payroll expense entries

---

## Implementation Plans

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN08-implement-general-ledger.md | FR-GL-001, FR-GL-002, BR-GL-001, DR-GL-001, PR-GL-001, ARCH-GL-001 | MILESTONE 2 | Not Started |

---

## Acceptance Criteria

- [ ] Double-entry validation enforced
- [ ] Auto-posting from submodules working
- [ ] Multi-currency support functional
- [ ] Account balances calculated correctly
- [ ] Batch posting < 1s for 1000 entries
- [ ] ACID compliance verified
- [ ] Entry reversal working
- [ ] Trial balance accurate
- [ ] Tenant isolation enforced
- [ ] 100% test coverage

---

## Testing Strategy

### Unit Tests

```php
test('validates entry is balanced', function () {
    $entry = GLEntry::factory()->create();
    $entry->lines()->createMany([
        ['account_id' => 1, 'debit_amount' => 1000, 'credit_amount' => 0],
        ['account_id' => 2, 'debit_amount' => 0, 'credit_amount' => 1000],
    ]);
    
    expect(GL::isBalanced($entry))->toBeTrue();
});

test('prevents posting unbalanced entry', function () {
    $entry = GLEntry::factory()->create();
    $entry->lines()->createMany([
        ['account_id' => 1, 'debit_amount' => 1000, 'credit_amount' => 0],
        ['account_id' => 2, 'debit_amount' => 0, 'credit_amount' => 900],
    ]);
    
    expect(fn() => GL::postEntry($entry))
        ->toThrow(UnbalancedEntryException::class);
});

test('calculates account balance correctly', function () {
    $account = Account::factory()->create();
    
    GLEntryLine::factory()->create([
        'account_id' => $account->id,
        'debit_amount' => 5000
    ]);
    
    GLEntryLine::factory()->create([
        'account_id' => $account->id,
        'credit_amount' => 2000
    ]);
    
    $balance = GL::accountBalance($account);
    expect($balance)->toBe(3000.00);
});
```

---

## Dependencies

### Feature Module Dependencies

- **Mandatory:** SUB01 (Multi-Tenancy), SUB07 (Chart of Accounts)
- **Integration:** SUB09, SUB10, SUB11, SUB12, SUB13, SUB14

### External Package Dependencies

None - uses Laravel primitives

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Batch Post Performance | < 1s for 1000 entries |
| Balance Query Performance | < 100ms |
| Data Integrity | 100% (zero unbalanced entries) |

---

## Monorepo Integration

- Development: `/packages/general-ledger/`
- Published as: `azaharizaman/erp-general-ledger`

---

## References

- Master PRD: [../PRD01-MVP.md](../PRD01-MVP.md)
- Chart of Accounts: PRD01-SUB07-CHART-OF-ACCOUNTS.md

---

**Document Status:** Draft - Pending Review  
**Last Updated:** November 11, 2025
