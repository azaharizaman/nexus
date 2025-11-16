

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
