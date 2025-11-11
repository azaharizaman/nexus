# PRD01-SUB10: Banking & Reconciliation System

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Mandatory Feature Modules - Finance & Accounting  
**Related Sub-PRDs:** PRD01-SUB08 (General Ledger), PRD01-SUB11 (Accounts Payable), PRD01-SUB12 (Accounts Receivable)  
**Composer Package:** `azaharizaman/erp-banking`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Banking & Reconciliation System provides **bank account management with automated statement reconciliation, transaction matching, and secure credential handling**. This module ensures that recorded cash transactions match actual bank activity and maintains accurate cash balances.

### Purpose

Solves the critical problem of **cash accuracy verification** by:

1. **Bank Reconciliation:** Match bank statements with recorded transactions
2. **Automated Matching:** Intelligently match transactions by amount, date, reference
3. **Cash Management:** Track multiple bank accounts and cash positions
4. **Secure Integration:** Safely store bank credentials for automated feeds
5. **Exception Handling:** Identify and resolve unmatched items

### Scope

**Included:**
- ✅ Bank account master data management
- ✅ Manual statement upload and parsing
- ✅ Automated transaction matching
- ✅ Reconciliation workflow
- ✅ Unmatched item management
- ✅ Bank balance tracking
- ✅ Encrypted credential storage

**Excluded:**
- ❌ Direct bank API integration (future)
- ❌ Electronic payments (future)

### Dependencies

**Mandatory:**
- Laravel Framework v12.x
- PHP ≥ 8.2
- PRD01-SUB01 (Multi-Tenancy), SUB07 (COA), SUB08 (General Ledger)

### Composer Package Information

- **Package:** `azaharizaman/erp-banking`
- **Namespace:** `Azaharizaman\Erp\Banking`
- **Monorepo:** `/packages/banking/`

---

## Requirements

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| FR-BR-002 | Support **automated matching** of bank statements with AR/AP entries | High | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB10 (Banking & Reconciliation). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-BR-001** | Support **multi-bank account management** with balance tracking per currency | High | Planned |
| **FR-BR-002** | Provide **bank statement import** via CSV/Excel for automated reconciliation | High | Planned |
| **FR-BR-003** | Implement **automatic matching** of bank transactions to GL entries using rules engine | High | Planned |
| **FR-BR-004** | Support **manual reconciliation** for unmatched transactions with drill-down | High | Planned |
| **FR-BR-005** | Generate **reconciliation reports** showing matched/unmatched items | Medium | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-BR-001** | Bank accounts MUST be linked to a **valid GL cash account** | Planned |
| **BR-BR-002** | Reconciled transactions **cannot be unreconciled** without supervisor approval | Planned |
| **BR-BR-003** | Bank statement closing balance MUST match **GL balance + unreconciled items** | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-BR-001** | Store bank account metadata: account_number, bank_name, currency, gl_account_id, current_balance | Planned |
| **DR-BR-002** | Store bank transactions: transaction_date, description, debit, credit, balance, is_matched | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-BR-001** | Integrate with **General Ledger** for transaction matching and balance verification | Planned |
| **IR-BR-002** | Integrate with **Chart of Accounts** for GL account validation | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-BR-001** | **Secure bank credentials** with AES-256 encryption and access control | Planned |
| **SR-BR-002** | Enforce **role-based access** for reconciliation approvals | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-BR-001** | Reconciliation engine should handle **10k+ transactions in under 5 seconds** using batch processing | Planned |
| **PR-BR-002** | Automatic matching rules engine should process **1000 transactions in < 2 seconds** | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-BR-001** | Support **100+ bank accounts** per tenant with concurrent reconciliation | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-BR-001** | `BankStatementImportedEvent` | When bank statement CSV/Excel is imported | Planned |
| **EV-BR-002** | `TransactionMatchedEvent` | When bank transaction is matched to GL entry | Planned |
| **EV-BR-003** | `ReconciliationCompletedEvent` | When bank account reconciliation is completed | Planned |

---

## Technical Specifications

### Database Schema

```sql
CREATE TABLE bank_accounts (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    bank_name VARCHAR(255) NOT NULL,
    currency_code VARCHAR(3) NOT NULL DEFAULT 'USD',
    gl_account_id BIGINT NOT NULL REFERENCES accounts(id),
    current_balance DECIMAL(20, 4) NOT NULL DEFAULT 0,
    last_reconciled_date DATE NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, account_number),
    INDEX idx_bank_accounts_tenant (tenant_id)
);

CREATE TABLE bank_statements (
    id BIGSERIAL PRIMARY KEY,
    bank_account_id BIGINT NOT NULL REFERENCES bank_accounts(id),
    statement_date DATE NOT NULL,
    opening_balance DECIMAL(20, 4) NOT NULL,
    closing_balance DECIMAL(20, 4) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending', -- 'pending', 'reconciled'
    uploaded_by BIGINT NOT NULL,
    uploaded_at TIMESTAMP NOT NULL,
    
    INDEX idx_statements_account (bank_account_id)
);

CREATE TABLE bank_transactions (
    id BIGSERIAL PRIMARY KEY,
    bank_statement_id BIGINT NOT NULL REFERENCES bank_statements(id),
    transaction_date DATE NOT NULL,
    description TEXT NOT NULL,
    reference VARCHAR(255) NULL,
    debit_amount DECIMAL(20, 4) NOT NULL DEFAULT 0,
    credit_amount DECIMAL(20, 4) NOT NULL DEFAULT 0,
    balance DECIMAL(20, 4) NOT NULL,
    is_matched BOOLEAN NOT NULL DEFAULT FALSE,
    matched_gl_entry_id BIGINT NULL,
    
    INDEX idx_bank_trans_statement (bank_statement_id),
    INDEX idx_bank_trans_matched (is_matched)
);
```

### API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/bank-accounts` | List bank accounts |
| POST | `/api/v1/bank-accounts` | Create bank account |
| POST | `/api/v1/bank-statements/upload` | Upload statement file |
| POST | `/api/v1/bank-statements/{id}/reconcile` | Start reconciliation |
| GET | `/api/v1/bank-statements/{id}/unmatched` | Get unmatched items |

---

## Implementation Plans

| Plan File | Milestone | Status |
|-----------|-----------|--------|
| PLAN10-implement-banking.md | MILESTONE 3 | Not Started |

---

## Dependencies

- **Mandatory:** SUB01, SUB07, SUB08

---

**Document Status:** Draft  
**Last Updated:** November 11, 2025
