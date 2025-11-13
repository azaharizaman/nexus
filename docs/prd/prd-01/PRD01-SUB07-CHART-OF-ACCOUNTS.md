# PRD01-SUB07: Chart of Accounts System

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Mandatory Feature Modules - Finance & Accounting  
**Related Sub-PRDs:** PRD01-SUB08 (General Ledger), PRD01-SUB09 (Journal Entries), PRD01-SUB20 (Financial Reporting)  
**Composer Package:** `azaharizaman/erp-chart-of-accounts`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Chart of Accounts (COA) System provides **hierarchical chart of accounts structure with account types, categories, and reporting groups for financial classification**. This mandatory feature module is the foundation of the entire financial accounting system, defining how all financial transactions are classified, organized, and reported.

### Purpose

The Chart of Accounts System solves the critical problem of **financial classification and organization** in an enterprise ERP. It enables:

1. **Financial Structure:** Define the complete hierarchy of accounts for balance sheet and income statement
2. **Standardization:** Use industry-standard account codes and structures (e.g., GAAP, IFRS)
3. **Flexibility:** Support custom account hierarchies tailored to business needs
4. **Reporting:** Group accounts for financial statements and management reports
5. **Multi-Entity:** Support tenant-specific COA while allowing standardized templates

### Scope

**Included in this Feature Module:**

- ✅ Hierarchical account structure (parent-child relationships)
- ✅ Account types (Asset, Liability, Equity, Revenue, Expense)
- ✅ Account categories and subcategories
- ✅ Reporting groups for financial statements
- ✅ Account status management (Active, Inactive, Closed)
- ✅ Account tagging and custom attributes
- ✅ COA templates for different industries
- ✅ Account search and filtering
- ✅ Protection against deletion of accounts with transactions
- ✅ Tenant-scoped COA isolation

**Excluded from this Feature Module:**

- ❌ Actual transaction posting (handled by SUB08 - General Ledger)
- ❌ Journal entries (handled by SUB09)
- ❌ Financial reports generation (handled by SUB20)
- ❌ Budgeting and forecasting (future enhancement)

### Dependencies

**Mandatory Dependencies:**
- Laravel Framework v12.x
- PHP ≥ 8.2
- PostgreSQL or MySQL with nested set support
- PRD01-SUB01 (Multi-Tenancy System)

**Feature Module Dependencies:**
- **Mandatory:** SUB01 (Multi-Tenancy)

### Composer Package Information

- **Package Name:** `azaharizaman/erp-chart-of-accounts`
- **Namespace:** `Nexus\Erp\ChartOfAccounts`
- **Monorepo Location:** `/packages/chart-of-accounts/`
- **Installation:** `composer require azaharizaman/erp-chart-of-accounts` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB07 (Chart of Accounts). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-COA-001** | Maintain a **hierarchical chart of accounts** with unlimited depth supporting parent-child relationships using nested set model | High | Planned |
| **FR-COA-002** | Support **5 standard account types** (Asset, Liability, Equity, Revenue, Expense) with type inheritance | High | Planned |
| **FR-COA-003** | Allow tagging accounts by **category and reporting group** for financial statement organization | High | Planned |
| **FR-COA-004** | Support **flexible account code format** (e.g., 1000-00, 1.1.1) per tenant configuration | Medium | Planned |
| **FR-COA-005** | Provide **account activation/deactivation** without deletion to preserve history | Medium | Planned |
| **FR-COA-006** | Support **account templates** for quick COA setup (manufacturing, retail, services) | Low | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-COA-001** | Prevent **deletion of accounts** that have associated transactions or child accounts | Planned |
| **BR-COA-002** | **Account codes** MUST be unique within tenant scope | Planned |
| **BR-COA-003** | Only **leaf accounts** (accounts without children) can have transactions posted to them | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-COA-001** | Accounts table MUST store: code, name, type, category, parent_id, lft, rgt, level, is_active, reporting_group | Planned |
| **DR-COA-002** | Use **nested set model** (lft, rgt columns) for efficient hierarchical queries with `kalnoy/nestedset` package | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-COA-001** | Integrate with **General Ledger** for account validation during journal posting | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-COA-001** | Chart of Accounts loading and filtering must complete within **200ms** for up to 10,000 accounts | Planned |
| **PR-COA-002** | Subtree queries using **nested set** must execute in < 50ms for efficient reporting | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-COA-001** | Support **10,000+ accounts** per tenant with optimal tree structure | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-COA-001** | Use **`kalnoy/nestedset`** Laravel package for nested set implementation | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-COA-001** | `AccountCreatedEvent` | When new account is added to chart | Planned |
| **EV-COA-002** | `AccountDeactivatedEvent` | When account is deactivated | Planned |
| **EV-COA-003** | `COARestructuredEvent` | When account hierarchy is reorganized | Planned |

---

## Technical Specifications

### Database Schema

**Accounts Table:**

```sql
CREATE TABLE accounts (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    account_type VARCHAR(50) NOT NULL, -- 'asset', 'liability', 'equity', 'revenue', 'expense'
    account_category VARCHAR(100) NULL, -- 'current_assets', 'fixed_assets', 'operating_expenses', etc.
    parent_id BIGINT NULL REFERENCES accounts(id),
    level INT NOT NULL DEFAULT 0,
    lft INT NOT NULL,
    rgt INT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    is_header BOOLEAN NOT NULL DEFAULT FALSE, -- Header accounts cannot have transactions
    reporting_group VARCHAR(100) NULL, -- 'balance_sheet', 'income_statement', 'cash_flow'
    tax_category VARCHAR(50) NULL,
    metadata JSONB NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE (tenant_id, code),
    INDEX idx_accounts_tenant (tenant_id),
    INDEX idx_accounts_parent (parent_id),
    INDEX idx_accounts_type (account_type),
    INDEX idx_accounts_nested_set (lft, rgt),
    INDEX idx_accounts_active (is_active),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Account Types Enum:**
```php
enum AccountType: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case REVENUE = 'revenue';
    case EXPENSE = 'expense';
    
    public function normalBalance(): string
    {
        return match($this) {
            self::ASSET, self::EXPENSE => 'debit',
            self::LIABILITY, self::EQUITY, self::REVENUE => 'credit',
        };
    }
}
```

**Example COA Hierarchy:**
```
1000 - Assets (Header)
├── 1100 - Current Assets (Header)
│   ├── 1110 - Cash and Cash Equivalents
│   ├── 1120 - Accounts Receivable
│   └── 1130 - Inventory
├── 1200 - Fixed Assets (Header)
│   ├── 1210 - Property, Plant & Equipment
│   └── 1220 - Accumulated Depreciation
2000 - Liabilities (Header)
├── 2100 - Current Liabilities (Header)
│   ├── 2110 - Accounts Payable
│   └── 2120 - Accrued Expenses
3000 - Equity (Header)
4000 - Revenue (Header)
5000 - Expenses (Header)
```

### API Endpoints

All endpoints follow `/api/v1/accounts` pattern:

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/v1/accounts` | List all accounts (flat or tree) | Yes |
| GET | `/api/v1/accounts/tree` | Get hierarchical account tree | Yes |
| GET | `/api/v1/accounts/{id}` | Get specific account details | Yes |
| POST | `/api/v1/accounts` | Create new account | Yes - Accounting Manager |
| PATCH | `/api/v1/accounts/{id}` | Update account | Yes - Accounting Manager |
| DELETE | `/api/v1/accounts/{id}` | Delete account (if no transactions) | Yes - Accounting Manager |
| GET | `/api/v1/accounts/{id}/children` | Get child accounts | Yes |
| GET | `/api/v1/accounts/{id}/ancestors` | Get parent hierarchy | Yes |
| GET | `/api/v1/accounts/type/{type}` | Filter by account type | Yes |
| POST | `/api/v1/accounts/{id}/activate` | Activate account | Yes - Accounting Manager |
| POST | `/api/v1/accounts/{id}/deactivate` | Deactivate account | Yes - Accounting Manager |
| GET | `/api/v1/accounts/search` | Search accounts | Yes |
| POST | `/api/v1/accounts/import` | Import COA from template | Yes - Super Admin |
| GET | `/api/v1/accounts/templates` | List available COA templates | Yes |

**Query Parameters:**

- `type` - Filter by account type
- `category` - Filter by category
- `is_active` - Filter by status
- `is_header` - Show only header or leaf accounts
- `reporting_group` - Filter by reporting group
- `search` - Full-text search on code/name

**Request/Response Examples:**

**Get Account Tree:**
```json
// GET /api/v1/accounts/tree

// Response 200 OK
{
    "data": [
        {
            "id": 1,
            "code": "1000",
            "name": "Assets",
            "type": "asset",
            "is_header": true,
            "is_active": true,
            "level": 0,
            "children": [
                {
                    "id": 2,
                    "code": "1100",
                    "name": "Current Assets",
                    "type": "asset",
                    "is_header": true,
                    "level": 1,
                    "children": [
                        {
                            "id": 3,
                            "code": "1110",
                            "name": "Cash and Cash Equivalents",
                            "type": "asset",
                            "is_header": false,
                            "level": 2,
                            "children": []
                        }
                    ]
                }
            ]
        }
    ]
}
```

**Create Account:**
```json
// POST /api/v1/accounts
{
    "code": "1140",
    "name": "Prepaid Expenses",
    "description": "Expenses paid in advance",
    "account_type": "asset",
    "account_category": "current_assets",
    "parent_id": 2,
    "reporting_group": "balance_sheet",
    "is_header": false
}

// Response 201 Created
{
    "data": {
        "id": 15,
        "code": "1140",
        "name": "Prepaid Expenses",
        "account_type": "asset",
        "account_category": "current_assets",
        "parent_id": 2,
        "level": 2,
        "is_active": true,
        "is_header": false,
        "created_at": "2025-11-11T10:00:00Z"
    }
}
```

**Search Accounts:**
```json
// GET /api/v1/accounts/search?q=cash&type=asset

// Response 200 OK
{
    "data": [
        {
            "id": 3,
            "code": "1110",
            "name": "Cash and Cash Equivalents",
            "type": "asset",
            "full_path": "Assets > Current Assets > Cash and Cash Equivalents"
        },
        {
            "id": 8,
            "code": "1115",
            "name": "Petty Cash",
            "type": "asset",
            "full_path": "Assets > Current Assets > Petty Cash"
        }
    ]
}
```

### Service API

**Facade Usage:**
```php
use Nexus\Erp\ChartOfAccounts\Facades\COA;

// Get account tree
$tree = COA::tree();

// Find account by code
$account = COA::findByCode('1110');

// Get all child accounts
$children = COA::children($parentAccount);

// Get account path
$path = COA::path($account); // "Assets > Current Assets > Cash"

// Check if account can be deleted
$canDelete = COA::canDelete($account); // false if has transactions

// Get accounts by type
$assets = COA::byType(AccountType::ASSET);

// Search accounts
$results = COA::search('receivable');
```

### Events

**Domain Events Emitted by this Feature Module:**

| Event Class | When Fired | Payload |
|-------------|-----------|---------|
| `AccountCreatedEvent` | After account created | `Account $account` |
| `AccountUpdatedEvent` | After account updated | `Account $account, array $changes` |
| `AccountDeletedEvent` | After account deleted | `Account $account` |
| `AccountActivatedEvent` | After account activated | `Account $account` |
| `AccountDeactivatedEvent` | After account deactivated | `Account $account` |
| `COAImportedEvent` | After COA template imported | `int $accountsCreated, string $template` |

---

## Implementation Plans

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN07-implement-chart-of-accounts.md | FR-COA-001, FR-COA-002, BR-COA-001, PR-COA-001 | MILESTONE 2 | Not Started |

---

## Acceptance Criteria

- [ ] Hierarchical account structure working
- [ ] Nested set model for efficient queries
- [ ] Account types and categories functional
- [ ] Cannot delete accounts with transactions
- [ ] Tree traversal queries < 200ms
- [ ] Account search working
- [ ] Tenant isolation enforced
- [ ] COA templates available
- [ ] Import/export functionality
- [ ] 100% test coverage

---

## Testing Strategy

### Unit Tests

```php
test('creates account with parent relationship', function () {
    $parent = Account::factory()->create(['code' => '1000', 'name' => 'Assets']);
    
    $child = Account::create([
        'code' => '1100',
        'name' => 'Current Assets',
        'parent_id' => $parent->id,
        'account_type' => AccountType::ASSET
    ]);
    
    expect($child->parent_id)->toBe($parent->id);
    expect($child->level)->toBe(1);
});

test('prevents deletion of account with transactions', function () {
    $account = Account::factory()->create();
    
    // Simulate transaction
    JournalEntry::factory()->create(['account_id' => $account->id]);
    
    expect(fn() => $account->delete())
        ->toThrow(AccountHasTransactionsException::class);
});

test('nested set model calculates correctly', function () {
    $parent = Account::factory()->create(['lft' => 1, 'rgt' => 10]);
    $child1 = Account::factory()->create(['parent_id' => $parent->id, 'lft' => 2, 'rgt' => 5]);
    $child2 = Account::factory()->create(['parent_id' => $parent->id, 'lft' => 6, 'rgt' => 9]);
    
    $descendants = Account::where('lft', '>', $parent->lft)
        ->where('rgt', '<', $parent->rgt)
        ->get();
    
    expect($descendants)->toHaveCount(2);
});
```

---

## Dependencies

### Feature Module Dependencies

- **Mandatory:** SUB01 (Multi-Tenancy)

### External Package Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `kalnoy/nestedset` | ^6.0 | Nested set model for Laravel |

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Tree Load Performance | < 200ms for 10k accounts |
| Search Performance | < 100ms |
| Account Creation | < 50ms |

---

## Monorepo Integration

- Development: `/packages/chart-of-accounts/`
- Published as: `azaharizaman/erp-chart-of-accounts`

---

## References

- Master PRD: [../PRD01-MVP.md](../PRD01-MVP.md)
- General Ledger: PRD01-SUB08-GENERAL-LEDGER.md
- Nested Set Pattern: https://github.com/lazychaser/laravel-nestedset

---

**Document Status:** Draft - Pending Review  
**Last Updated:** November 11, 2025
