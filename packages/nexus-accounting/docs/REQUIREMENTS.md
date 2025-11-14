# nexus-accounting Package Requirements

**Package Name:** `nexus/accounting`  
**Namespace:** `Nexus\Accounting`  
**Version:** 1.0.0  
**Status:** Design Phase  
**Created:** November 14, 2025  
**Updated:** November 15, 2025

---

## Executive Summary

Complete Financial Management atomic package implementing General Ledger, Chart of Accounts, Journal Entries, Accounts Payable, Accounts Receivable, Cash and Bank Management following the **Maximum Atomicity** architectural principles.

### Architectural Context

**Atomic Package Compliance:**
This package MUST adhere to Maximum Atomicity principles defined in the [System Architectural Document](../../../docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md):
- ✅ **Headless Design** - Contains only domain logic, no presentation layer
- ✅ **Independent Testability** - Complete test suite runnable with `composer test` 
- ✅ **Zero Cross-Package Dependencies** - Cannot directly depend on other Nexus packages
- ✅ **Contract-Based Communication** - External integration via interfaces and events
- ✅ **Laravel Actions Integration** - Business logic exposed via orchestration layer

**Orchestration Pattern:**
- Presentation layer (HTTP/CLI) handled by `Nexus\Erp` orchestration using Laravel Actions
- Package provides domain logic and contracts; orchestration provides API endpoints
- Integration testing performed at orchestration level, not within atomic package

**Consolidated From:** 6 Sub-PRDs
- PRD01-SUB07-CHART-OF-ACCOUNTS.md  
- PRD01-SUB08-GENERAL-LEDGER.md
- PRD01-SUB09-JOURNAL-ENTRIES.md
- PRD01-SUB10-BANKING.md
- PRD01-SUB11-ACCOUNTS-PAYABLE.md
- PRD01-SUB12-ACCOUNTS-RECEIVABLE.md

**Why Consolidated:**
These financial components are consolidated because they:
1. **Share tight domain coupling** - AP/AR/Bank transactions automatically post to GL
2. **Have constant data flow** - Every financial transaction requires GL posting
3. **Cannot function independently** - Chart of Accounts required by all submodules
4. **Share transaction semantics** - All use same double-entry accounting principles
5. **Violate atomicity if separated** - Would require complex cross-package choreography

**Internal Modularity Maintained:**
- Namespace separation (`Nexus\Accounting\ChartOfAccounts`, `Nexus\Accounting\GeneralLedger`)
- Bounded contexts with clear internal interfaces  
- Domain events for internal component communication
- Separate repositories and services per subdomain

---

## Architectural Compliance

### Maximum Atomicity Requirements

| Requirement | Status | Implementation Notes |
|-------------|---------|---------------------|
| **No HTTP Controllers** | ✅ Must Comply | Controllers moved to `Nexus\Erp\Actions\Accounting\*` |
| **No CLI Commands** | ✅ Must Comply | Commands converted to Actions in orchestration layer |
| **No Routes Definition** | ✅ Must Comply | Routes handled by `Nexus\Erp` service provider |
| **Independent Testability** | ✅ Must Comply | Complete test suite with Orchestra Testbench |
| **Zero Package Dependencies** | ✅ Must Comply | Communication via contracts and events only |
| **Contract-Based Integration** | ✅ Must Comply | Define interfaces for external dependencies |

### Package Dependencies

**Allowed Dependencies:**
- `laravel/framework` (framework core)
- `kalnoy/nestedset` (hierarchical account structure)
- Testing packages (`orchestra/testbench`, `pestphp/pest`)

**Forbidden Dependencies:**
- Other `nexus/*` packages (violates atomicity)
- HTTP presentation packages (`inertiajs/inertia-laravel`)
- Package-specific external services (must be abstracted)

**External Integration:**
```php
// ✅ CORRECT: Define contracts for external dependencies
interface TaxCalculatorContract {
    public function calculateTax(TaxableAmount $amount): TaxCalculation;
}

// ✅ CORRECT: Register in Nexus\Erp orchestration layer  
$this->app->bind(TaxCalculatorContract::class, SpatieVatCalculator::class);
```

---

## Functional Requirements

### 1. Chart of Accounts (COA)

**Source:** PRD01-SUB07-CHART-OF-ACCOUNTS.md

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-ACC-COA-001** | Maintain **hierarchical chart of accounts** with unlimited depth using nested set model | High |
| **FR-ACC-COA-002** | Support **5 standard account types** (Asset, Liability, Equity, Revenue, Expense) with type inheritance | High |
| **FR-ACC-COA-003** | Allow tagging accounts by **category and reporting group** for financial statement organization | High |
| **FR-ACC-COA-004** | Support **flexible account code format** (e.g., 1000-00, 1.1.1) per tenant configuration | Medium |
| **FR-ACC-COA-005** | Provide **account activation/deactivation** without deletion to preserve history | Medium |
| **FR-ACC-COA-006** | Support **account templates** for quick COA setup (manufacturing, retail, services) | Low |

### 2. General Ledger (GL)

**Source:** PRD01-SUB08-GENERAL-LEDGER.md

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-ACC-GL-001** | **Automatically post entries** from all submodules (AP, AR, Inventory, Payroll) to GL with full audit trail | High |
| **FR-ACC-GL-002** | Support **multi-currency** transactions with automatic exchange rate conversion and revaluation | High |
| **FR-ACC-GL-003** | Implement **period closing** process with validation and lock-down to prevent backdated entries | High |
| **FR-ACC-GL-004** | Provide **account balance inquiries** at any point in time with drill-down to transaction detail | High |
| **FR-ACC-GL-005** | Support **batch journal entry posting** with validation and error reporting | Medium |
| **FR-ACC-GL-006** | Generate **trial balance report** with comparative periods and variance analysis | High |

### 3. Journal Entries (JE)

**Source:** PRD01-SUB09-JOURNAL-ENTRIES.md

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-ACC-JE-001** | Support **manual journal entry creation** with multi-line debit/credit allocation | High |
| **FR-ACC-JE-002** | Enforce **balanced entry validation** (total debits = total credits) before posting | High |
| **FR-ACC-JE-003** | Provide **recurring journal entry templates** with scheduling capabilities | Medium |
| **FR-ACC-JE-004** | Support **journal entry reversal** with automatic offsetting entries | High |
| **FR-ACC-JE-005** | Enable **attachment of supporting documents** to journal entries | Medium |

### 4. Banking & Cash Management

**Source:** PRD01-SUB10-BANKING.md

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-ACC-BANK-001** | Maintain **bank account master** with account details and currency | High |
| **FR-ACC-BANK-002** | Record **bank transactions** (deposits, withdrawals, transfers) with reconciliation status | High |
| **FR-ACC-BANK-003** | Support **bank reconciliation** process matching transactions with bank statements | High |
| **FR-ACC-BANK-004** | Track **cash accounts** with petty cash management and replenishment | Medium |
| **FR-ACC-BANK-005** | Generate **cashflow statements** with operating, investing, financing activities | High |

### 5. Accounts Payable (AP)

**Source:** PRD01-SUB11-ACCOUNTS-PAYABLE.md

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-ACC-AP-001** | Record **vendor invoices** with line items, taxes, and payment terms | High |
| **FR-ACC-AP-002** | Support **three-way matching** (PO, Goods Receipt, Invoice) with variance handling | High |
| **FR-ACC-AP-003** | Process **vendor payments** with batch payment runs and check printing | High |
| **FR-ACC-AP-004** | Track **vendor aging** and generate aging reports (30, 60, 90+ days) | High |
| **FR-ACC-AP-005** | Support **vendor credit notes** and payment application | Medium |

### 6. Accounts Receivable (AR)

**Source:** PRD01-SUB12-ACCOUNTS-RECEIVABLE.md

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-ACC-AR-001** | Generate **customer invoices** from sales orders with line items and taxes | High |
| **FR-ACC-AR-002** | Record **customer payments** with payment allocation to invoices | High |
| **FR-ACC-AR-003** | Track **customer aging** and generate aging reports with collection status | High |
| **FR-ACC-AR-004** | Support **credit notes** and refund processing | Medium |
| **FR-ACC-AR-005** | Implement **payment terms** with automatic due date calculation | Medium |

---

## Business Rules

| Rule ID | Description | Scope |
|---------|-------------|-------|
| **BR-ACC-001** | All journal entries MUST be **balanced (debit = credit)** before posting | GL, JE |
| **BR-ACC-002** | **Posted entries** cannot be modified; only reversed with offsetting entries | GL, JE |
| **BR-ACC-003** | Prevent **deletion of accounts** with associated transactions or child accounts | COA |
| **BR-ACC-004** | **Account codes** MUST be unique within tenant scope | COA |
| **BR-ACC-005** | Only **leaf accounts** (no children) can have transactions posted to them | COA, GL |
| **BR-ACC-006** | Entries can only be posted to **active fiscal periods**; closed periods reject entries | GL |
| **BR-ACC-007** | Foreign currency transactions MUST record both **base and foreign amounts** with exchange rate | GL |
| **BR-ACC-008** | **Three-way matching** required for vendor invoice posting (PO, GR, Invoice) | AP |
| **BR-ACC-009** | Customer payments MUST be allocated to specific invoices for proper aging tracking | AR |

---

## Data Requirements

| Requirement ID | Description | Scope |
|----------------|-------------|-------|
| **DR-ACC-001** | Chart of accounts with nested set model: code, name, type, parent_id, lft, rgt, level, is_active | COA |
| **DR-ACC-002** | Use `kalnoy/nestedset` package for efficient hierarchical queries and operations | COA |
| **DR-ACC-003** | GL entries table: date, account_id, amount, currency, exchange_rate, description, batch_uuid | GL |
| **DR-ACC-004** | Monthly account balance aggregation table for performance optimization | GL |
| **DR-ACC-005** | Journal entry headers with line items for multi-account transactions | JE |
| **DR-ACC-006** | Bank accounts with reconciliation status tracking | BANK |
| **DR-ACC-007** | Vendor invoices with payment allocation tracking | AP |
| **DR-ACC-008** | Customer invoices with payment application history | AR |

---

## Integration Requirements

### Internal Package Communication

| Component | Integration Method | Implementation |
|-----------|-------------------|----------------|
| **Nexus\Tenancy** | Event-driven | Listen to `TenantCreated` event for COA setup |
| **Nexus\AuditLog** | Service contract | Use `ActivityLoggerContract` for change tracking |
| **External Tax Service** | Service contract | Define `TaxCalculatorContract` interface |
| **External Payment Gateway** | Service contract | Define `PaymentProcessorContract` interface |

### API Contracts Definition

```php
// Package defines contracts for external services
interface TaxCalculatorContract {
    public function calculateTax(InvoiceLineItem $item): TaxAmount;
}

interface PaymentProcessorContract {  
    public function processPayment(PaymentRequest $request): PaymentResult;
}

// Orchestration layer binds implementations
class ErpServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(TaxCalculatorContract::class, VatCalculatorService::class);
    }
}
```
---

## Orchestration Layer Integration

### Laravel Actions Implementation

The presentation layer is handled by `Nexus\Erp\Actions\Accounting\*` following the Laravel Actions pattern:

```php
// Atomic package provides business logic
namespace Nexus\Accounting\Services;
class ChartOfAccountsService {
    public function createAccount(CreateAccountData $data): Account {
        // Domain logic implementation
    }
}

// Orchestration layer exposes via Actions  
namespace Nexus\Erp\Actions\Accounting;
class CreateAccountAction {
    use AsAction;
    
    public function handle(CreateAccountRequest $request): Account {
        return app(ChartOfAccountsService::class)->createAccount(
            CreateAccountData::fromRequest($request)
        );
    }
    
    // Can be invoked as HTTP, CLI, Job, Event Listener
    public function asController(CreateAccountRequest $request) { 
        return $this->handle($request); 
    }
}
```

### Event-Driven Architecture

```php
// Package publishes domain events
namespace Nexus\Accounting\Events;
class AccountCreated {
    public function __construct(public Account $account) {}
}

// External packages subscribe via orchestration layer
class ErpEventServiceProvider extends EventServiceProvider {
    protected $listen = [
        AccountCreated::class => [
            UpdateReportingStructureListener::class,
        ],
    ];
}
```

---

## Performance Requirements

| Requirement ID | Description | Target |
|----------------|-------------|--------|
| **PR-ACC-001** | Trial balance generation for 100K transactions | < 2 seconds |
| **PR-ACC-002** | Account balance inquiry with drill-down | < 500ms |
| **PR-ACC-003** | Bank reconciliation for 10K transactions | < 5 seconds |
| **PR-ACC-004** | Aging report generation (30/60/90 days) | < 3 seconds |
| **PR-ACC-005** | Chart of accounts hierarchical query performance | < 100ms |

---

## Security Requirements

| Requirement ID | Description |
|----------------|-------------|
| **SR-ACC-001** | Implement audit logging for all GL postings using `ActivityLoggerContract` |
| **SR-ACC-002** | Enforce tenant isolation for all accounting data via tenant scoping |
| **SR-ACC-003** | Support authorization policies through contract-based permission system |
| **SR-ACC-004** | Validate business rules at domain layer (before orchestration) |
| **SR-ACC-005** | Implement immutable posting (entries cannot be modified once posted) |

---

## Dependencies

### Framework Dependencies
```json
{
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0",
        "kalnoy/nestedset": "^7.0"
    },
    "require-dev": {
        "orchestra/testbench": "^10.0",
        "pestphp/pest": "^2.0",
        "pestphp/pest-plugin-laravel": "^2.0"
    }
}
```

### Service Contracts
External dependencies MUST be abstracted behind contracts:

| Service | Contract Interface | Bound In Orchestration |
|---------|-------------------|----------------------|
| Activity Logging | `ActivityLoggerContract` | `Nexus\Erp\ErpServiceProvider` |
| Tax Calculation | `TaxCalculatorContract` | `Nexus\Erp\ErpServiceProvider` |
| Payment Processing | `PaymentProcessorContract` | `Nexus\Erp\ErpServiceProvider` |
| Inventory Posting | `InventoryGLContract` | `Nexus\Erp\ErpServiceProvider` |

---

## Implementation Notes

### Atomic Package Structure

```
packages/nexus-accounting/
├── src/
│   ├── Models/                     # Domain models
│   │   ├── Account.php
│   │   ├── GeneralLedgerEntry.php
│   │   ├── JournalEntry.php
│   │   └── ...
│   ├── Services/                   # Business logic services  
│   │   ├── ChartOfAccountsService.php
│   │   ├── GeneralLedgerService.php
│   │   └── ...
│   ├── Repositories/               # Data access layer
│   │   ├── AccountRepository.php
│   │   └── ...
│   ├── Contracts/                  # External service contracts
│   │   ├── TaxCalculatorContract.php
│   │   └── PaymentProcessorContract.php
│   ├── Events/                     # Domain events
│   │   ├── AccountCreated.php
│   │   └── TransactionPosted.php
│   ├── Data/                       # DTOs and Value Objects
│   │   ├── CreateAccountData.php
│   │   └── PostingData.php
│   └── AccountingServiceProvider.php
├── database/
│   ├── migrations/                 # Database schema
│   └── factories/                  # Test data factories
├── tests/                          # Independent test suite
│   ├── Feature/
│   ├── Unit/
│   ├── TestCase.php
│   └── bootstrap.php
├── config/
│   └── accounting.php             # Package configuration
├── composer.json                  # Package dependencies
├── phpunit.xml                   # Test configuration
└── docs/
    └── REQUIREMENTS.md (this file)
```

### Orchestration Structure

```
src/Actions/Accounting/            # In Nexus\Erp namespace
├── ChartOfAccounts/
│   ├── CreateAccountAction.php
│   ├── UpdateAccountAction.php
│   └── DeleteAccountAction.php  
├── GeneralLedger/
│   ├── PostTransactionAction.php
│   └── GenerateTrialBalanceAction.php
└── ...

routes/accounting.php              # In Nexus\Erp namespace
console/accounting.php             # Console commands via Actions
```

### Implementation Strategy

**Phase 1: Domain Foundation (Week 1)**
- Chart of Accounts models and nested set implementation
- Basic account CRUD operations with business rule validation
- Independent test suite setup with Orchestra Testbench

**Phase 2: General Ledger Core (Week 2)**
- GL entry models and posting services
- Multi-currency support and balance calculations
- Event-driven architecture for posting notifications

**Phase 3: Journal Entries (Week 3)** 
- Manual journal entry creation and validation
- Entry reversal and recurring entry functionality
- Attachment support for supporting documents

**Phase 4: Banking Integration (Week 4)**
- Bank account management and transaction recording
- Bank reconciliation algorithms and matching
- Cash flow statement generation logic

**Phase 5: AP/AR Implementation (Weeks 5-6)**
- Vendor and customer invoice management
- Payment processing and allocation algorithms
- Aging report generation and analytics

**Phase 6: Orchestration Layer (Week 7)**
- Laravel Actions implementation for all business operations
- HTTP/CLI/Job/Event integration via orchestration layer
- API documentation and external service contracts

### Testing Strategy

**Atomic Package Tests:**
```bash
cd packages/nexus-accounting
composer test                     # Run independent test suite
composer test-coverage           # Generate coverage reports  
composer test-isolated          # Test without dev dependencies
```

**Integration Tests:**
- Performed at `Nexus\Erp` orchestration layer level
- Test Actions and external service integrations
- End-to-end testing in Edward demo application

### Compliance Verification

To verify Maximum Atomicity compliance:

```bash
# 1. Test package independence
cd packages/nexus-accounting && composer test

# 2. Check for architectural violations
find src -name "*Controller*" -o -name "*Command*" | wc -l  # Should be 0
grep -r "Nexus\\\\" src/ | grep -v "Nexus\\Accounting"     # Should be empty

# 3. Verify contract dependencies only
composer show --tree | grep nexus                          # Should show none
```

---

**Document Maintenance:**
- Update after each development phase completion
- Review during architectural changes and refactoring
- Sync with [System Architectural Document](../../../docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md)

**Related Documents:**
- [System Architectural Document](../../../docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md)
- [Master PRD](../../../docs/prd/PRD01-MVP.md)
- [Package Implementation Examples](../../../packages/nexus-audit-log/TESTING.md)
