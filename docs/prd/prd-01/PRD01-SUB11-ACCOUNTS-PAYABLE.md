# PRD01-SUB11: Accounts Payable System

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Mandatory Feature Modules - Finance & Accounting  
**Related Sub-PRDs:** PRD01-SUB08 (General Ledger), PRD01-SUB10 (Banking), PRD01-SUB16 (Purchasing)  
**Composer Package:** `azaharizaman/erp-accounts-payable`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Accounts Payable (AP) System provides **accounts payable processing with automatic AP entry generation from purchase orders, payment processing, and vendor management**. This module manages all amounts owed to suppliers and ensures timely, accurate vendor payments.

### Purpose

Solves the critical problem of **vendor payment management** by:

1. **Automated AP Creation:** Auto-generate AP entries from approved purchase orders
2. **Payment Processing:** Schedule and execute vendor payments
3. **Vendor Management:** Track vendor balances and payment terms
4. **Cash Flow:** Optimize payment timing for cash flow management
5. **Compliance:** Maintain complete audit trail of vendor transactions

### Scope

**Included:**
- ✅ AP invoice recording and tracking
- ✅ Automatic AP entry generation from POs
- ✅ Payment scheduling and processing
- ✅ Vendor balance tracking
- ✅ Payment batch processing
- ✅ Integration with banking module
- ✅ GL integration for automatic posting

**Excluded:**
- ❌ Purchase order creation (handled by SUB16)
- ❌ Vendor onboarding workflow (future)

### Dependencies

**Mandatory:**
- Laravel Framework v12.x
- PHP ≥ 8.2
- PRD01-SUB01 (Multi-Tenancy), SUB07 (COA), SUB08 (General Ledger), SUB10 (Banking)

### Composer Package Information

- **Package:** `azaharizaman/erp-accounts-payable`
- **Namespace:** `Nexus\Erp\AccountsPayable`
- **Monorepo:** `/packages/accounts-payable/`

---

## Requirements

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| FR-AP-002 | **Auto-generate AP entries** from approved purchase orders | High | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB11 (Accounts Payable). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-AP-001** | Support **vendor invoice entry** with multi-line item details and GL distribution | High | Planned |
| **FR-AP-002** | Implement **payment processing** with batch payment capability | High | Planned |
| **FR-AP-003** | Support **payment application** to multiple invoices with partial payments | High | Planned |
| **FR-AP-004** | Provide **aging reports** (30/60/90 days) for outstanding payables | High | Planned |
| **FR-AP-005** | Support **vendor statements** reconciliation | Medium | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-AP-001** | Payment amounts must **not exceed the invoice outstanding balance** | Planned |
| **BR-AP-002** | **Posted invoices** cannot be edited; only reversed or adjusted | Planned |
| **BR-AP-003** | Payments MUST reference at least one **valid vendor invoice** | Planned |
| **BR-AP-004** | Vendor balances MUST equal sum of **unpaid invoices - unapplied payments** | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-AP-001** | Store AP invoice metadata: invoice_number, vendor_id, invoice_date, due_date, total_amount, paid_amount, status | Planned |
| **DR-AP-002** | Store AP payment metadata: payment_number, payment_date, bank_account_id, total_amount, status | Planned |
| **DR-AP-003** | Store payment applications: ap_payment_id, ap_invoice_id, amount_applied | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-AP-001** | Integrate with **Banking module** for automated disbursements and bank reconciliation | Planned |
| **IR-AP-002** | Integrate with **General Ledger** for automatic AP and expense posting | Planned |
| **IR-AP-003** | Integrate with **Vendor Master** for vendor validation and credit limits | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-AP-001** | Process **batch payments (1000 invoices)** in under 5 seconds using queue jobs | Planned |
| **PR-AP-002** | Generate **aging report** for 10,000+ invoices in under 3 seconds | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-AP-001** | Enforce **role-based access** for payment approval based on amount thresholds | Planned |
| **SR-AP-002** | Require **dual authorization** for payments exceeding configured limit | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-AP-001** | Support **100,000+ invoices** per tenant per year with optimal indexing | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-AP-001** | Use **database transactions** to ensure atomicity when applying payments to invoices | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-AP-001** | `APInvoiceCreatedEvent` | When vendor invoice is created | Planned |
| **EV-AP-002** | `APPaymentProcessedEvent` | When payment is processed | Planned |
| **EV-AP-003** | `APPaymentAppliedEvent` | When payment is applied to invoice | Planned |
| **EV-AP-004** | `APInvoiceFullyPaidEvent` | When invoice is fully paid | Planned |

---

## Technical Specifications

### Database Schema

```sql
CREATE TABLE ap_invoices (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    vendor_id BIGINT NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    total_amount DECIMAL(20, 4) NOT NULL,
    paid_amount DECIMAL(20, 4) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'open', -- 'open', 'partial', 'paid', 'void'
    gl_entry_id BIGINT NULL,
    created_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, invoice_number),
    INDEX idx_ap_tenant (tenant_id),
    INDEX idx_ap_vendor (vendor_id),
    INDEX idx_ap_status (status)
);

CREATE TABLE ap_payments (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    payment_number VARCHAR(50) NOT NULL,
    payment_date DATE NOT NULL,
    bank_account_id BIGINT NOT NULL,
    total_amount DECIMAL(20, 4) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    gl_entry_id BIGINT NULL,
    created_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, payment_number)
);

CREATE TABLE ap_payment_applications (
    id BIGSERIAL PRIMARY KEY,
    ap_payment_id BIGINT NOT NULL REFERENCES ap_payments(id),
    ap_invoice_id BIGINT NOT NULL REFERENCES ap_invoices(id),
    amount_applied DECIMAL(20, 4) NOT NULL,
    
    INDEX idx_ap_app_payment (ap_payment_id),
    INDEX idx_ap_app_invoice (ap_invoice_id)
);
```

### API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/ap/invoices` | List AP invoices |
| POST | `/api/v1/ap/invoices` | Create AP invoice |
| GET | `/api/v1/ap/invoices/{id}` | Get invoice details |
| POST | `/api/v1/ap/payments` | Create payment |
| POST | `/api/v1/ap/payments/batch` | Process batch payment |
| GET | `/api/v1/ap/aging` | Get AP aging report |

### Events

| Event Class | When Fired |
|-------------|-----------|
| `APInvoiceCreatedEvent` | After AP invoice created |
| `PaymentProcessedEvent` | After payment processed |

---

## Implementation Plans

| Plan File | Milestone | Status |
|-----------|-----------|--------|
| PLAN11-implement-accounts-payable.md | MILESTONE 3 | Not Started |

---

## Dependencies

- **Mandatory:** SUB01, SUB07, SUB08, SUB10
- **Integration:** SUB16 (Purchasing)

---

**Document Status:** Draft  
**Last Updated:** November 11, 2025
