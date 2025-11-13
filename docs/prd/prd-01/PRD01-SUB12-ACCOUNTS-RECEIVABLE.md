# PRD01-SUB12: Accounts Receivable System

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Mandatory Feature Modules - Finance & Accounting  
**Related Sub-PRDs:** PRD01-SUB08 (General Ledger), PRD01-SUB10 (Banking), PRD01-SUB17 (Sales)  
**Composer Package:** `azaharizaman/erp-accounts-receivable`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Accounts Receivable (AR) System provides **accounts receivable management with automatic AR entry generation from sales orders, receipt processing, and customer credit control**. This module manages all amounts owed by customers and ensures efficient collection of outstanding invoices.

### Purpose

Solves the critical problem of **customer payment management** by:

1. **Automated AR Creation:** Auto-generate AR entries from sales orders
2. **Receipt Processing:** Record and apply customer payments
3. **Credit Management:** Monitor customer credit limits and payment terms
4. **Collections:** Track overdue invoices and aging
5. **Cash Application:** Automatically match payments to invoices

### Scope

**Included:**
- ✅ AR invoice recording and tracking
- ✅ Automatic AR entry generation from sales orders
- ✅ Customer payment recording
- ✅ Payment application to invoices
- ✅ Credit limit monitoring
- ✅ AR aging reports
- ✅ Integration with banking module
- ✅ GL integration for automatic posting

**Excluded:**
- ❌ Sales order creation (handled by SUB17)
- ❌ Customer onboarding (future)
- ❌ Collections workflow automation (future)

### Dependencies

**Mandatory:**
- Laravel Framework v12.x
- PHP ≥ 8.2
- PRD01-SUB01 (Multi-Tenancy), SUB07 (COA), SUB08 (General Ledger), SUB10 (Banking)

### Composer Package Information

- **Package:** `azaharizaman/erp-accounts-receivable`
- **Namespace:** `Nexus\Erp\AccountsReceivable`
- **Monorepo:** `/packages/accounts-receivable/`

---

## Requirements

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| FR-AR-002 | **Auto-generate AR entries** from sales orders or delivery notes | High | Planned |

### Integration Requirements (IR)

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB12 (Accounts Receivable). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-AR-001** | Support **customer invoice creation** with multi-line items and GL distribution | High | Planned |
| **FR-AR-002** | Implement **receipt processing** with payment method tracking | High | Planned |
| **FR-AR-003** | Support **receipt application** to multiple invoices with partial payments | High | Planned |
| **FR-AR-004** | Provide **aging reports** (30/60/90 days) for outstanding receivables | High | Planned |
| **FR-AR-005** | Support **credit memo** processing for customer refunds | Medium | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-AR-001** | Receipt amounts MUST **not exceed the invoice outstanding balance** | Planned |
| **BR-AR-002** | **Posted invoices** cannot be edited; only reversed or adjusted via credit memo | Planned |
| **BR-AR-003** | Receipts MUST reference at least one **valid customer invoice** | Planned |
| **BR-AR-004** | Customer balances MUST equal sum of **unpaid invoices - unapplied receipts** | Planned |
| **BR-AR-005** | Overdue invoices MUST be flagged automatically based on **due_date vs current_date** | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-AR-001** | Store AR invoice metadata: invoice_number, customer_id, invoice_date, due_date, total_amount, paid_amount, status | Planned |
| **DR-AR-002** | Store AR receipt metadata: receipt_number, receipt_date, customer_id, bank_account_id, payment_method, total_amount | Planned |
| **DR-AR-003** | Store receipt applications: ar_receipt_id, ar_invoice_id, amount_applied | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-AR-001** | Integrate with **Banking module** for payment reconciliation and deposit tracking | Planned |
| **IR-AR-002** | Integrate with **General Ledger** for automatic AR and revenue posting | Planned |
| **IR-AR-003** | Integrate with **Customer Master** for customer validation and credit limits | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-AR-001** | Generate and post receipts under **2 seconds** per transaction | Planned |
| **PR-AR-002** | Generate **aging report** for 10,000+ invoices in under 3 seconds | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-AR-001** | Enforce **role-based access** for credit memo approval based on amount thresholds | Planned |
| **SR-AR-002** | Require **dual authorization** for credit memos exceeding configured limit | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-AR-001** | Support **100,000+ invoices** per tenant per year with optimal indexing | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-AR-001** | Use **database transactions** to ensure atomicity when applying receipts to invoices | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-AR-001** | `ARInvoiceCreatedEvent` | When customer invoice is created | Planned |
| **EV-AR-002** | `ARReceiptProcessedEvent` | When payment receipt is processed | Planned |
| **EV-AR-003** | `ARReceiptAppliedEvent` | When receipt is applied to invoice | Planned |
| **EV-AR-004** | `ARInvoiceFullyPaidEvent` | When invoice is fully paid | Planned |

---

## Technical Specifications

### Database Schema

```sql
CREATE TABLE ar_invoices (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    customer_id BIGINT NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    total_amount DECIMAL(20, 4) NOT NULL,
    paid_amount DECIMAL(20, 4) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'open', -- 'open', 'partial', 'paid', 'void', 'overdue'
    gl_entry_id BIGINT NULL,
    sales_order_id BIGINT NULL,
    created_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, invoice_number),
    INDEX idx_ar_tenant (tenant_id),
    INDEX idx_ar_customer (customer_id),
    INDEX idx_ar_status (status),
    INDEX idx_ar_due_date (due_date)
);

CREATE TABLE ar_receipts (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    receipt_number VARCHAR(50) NOT NULL,
    receipt_date DATE NOT NULL,
    customer_id BIGINT NOT NULL,
    bank_account_id BIGINT NOT NULL,
    total_amount DECIMAL(20, 4) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(50) NOT NULL, -- 'cash', 'check', 'wire', 'card'
    reference VARCHAR(255) NULL,
    gl_entry_id BIGINT NULL,
    created_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, receipt_number),
    INDEX idx_ar_receipts_tenant (tenant_id),
    INDEX idx_ar_receipts_customer (customer_id)
);

CREATE TABLE ar_receipt_applications (
    id BIGSERIAL PRIMARY KEY,
    ar_receipt_id BIGINT NOT NULL REFERENCES ar_receipts(id),
    ar_invoice_id BIGINT NOT NULL REFERENCES ar_invoices(id),
    amount_applied DECIMAL(20, 4) NOT NULL,
    
    INDEX idx_ar_app_receipt (ar_receipt_id),
    INDEX idx_ar_app_invoice (ar_invoice_id)
);

CREATE TABLE customer_credit_limits (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    customer_id BIGINT NOT NULL,
    credit_limit DECIMAL(20, 4) NOT NULL,
    current_balance DECIMAL(20, 4) NOT NULL DEFAULT 0,
    payment_terms_days INT NOT NULL DEFAULT 30,
    is_on_hold BOOLEAN NOT NULL DEFAULT FALSE,
    
    UNIQUE (tenant_id, customer_id)
);
```

### API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/ar/invoices` | List AR invoices |
| POST | `/api/v1/ar/invoices` | Create AR invoice |
| GET | `/api/v1/ar/invoices/{id}` | Get invoice details |
| POST | `/api/v1/ar/receipts` | Record customer payment |
| POST | `/api/v1/ar/receipts/{id}/apply` | Apply receipt to invoices |
| GET | `/api/v1/ar/aging` | Get AR aging report |
| GET | `/api/v1/ar/customers/{id}/balance` | Get customer balance |
| POST | `/api/v1/ar/customers/{id}/credit-hold` | Place customer on credit hold |

**Request/Response Examples:**

**Create AR Receipt:**
```json
// POST /api/v1/ar/receipts
{
    "receipt_date": "2025-11-11",
    "customer_id": 123,
    "bank_account_id": 5,
    "total_amount": 5000.00,
    "payment_method": "wire",
    "reference": "TRF-20251111-001",
    "applications": [
        {"invoice_id": 456, "amount": 3000.00},
        {"invoice_id": 457, "amount": 2000.00}
    ]
}

// Response 201 Created
{
    "data": {
        "id": 789,
        "receipt_number": "RCP-2025-001",
        "total_amount": 5000.00,
        "applied_amount": 5000.00,
        "status": "applied",
        "gl_entry_id": 9876
    }
}
```

**Get AR Aging:**
```json
// GET /api/v1/ar/aging

// Response 200 OK
{
    "data": {
        "current": 25000.00,
        "days_1_30": 15000.00,
        "days_31_60": 8000.00,
        "days_61_90": 3000.00,
        "over_90": 2000.00,
        "total": 53000.00
    }
}
```

### Service API

```php
use Nexus\Erp\AccountsReceivable\Facades\AR;

// Create AR invoice from sales order
$invoice = AR::createInvoiceFromSalesOrder($salesOrder);

// Record customer payment
$receipt = AR::recordPayment([
    'customer_id' => $customer->id,
    'amount' => 5000,
    'payment_method' => 'wire'
]);

// Apply receipt to invoices
AR::applyPayment($receipt, [
    ['invoice_id' => 123, 'amount' => 3000],
    ['invoice_id' => 124, 'amount' => 2000]
]);

// Check credit limit
$canSell = AR::checkCreditLimit($customer, $orderAmount); // true/false

// Get customer balance
$balance = AR::customerBalance($customer);
```

### Events

**Domain Events Emitted:**

| Event Class | When Fired |
|-------------|-----------|
| `ARInvoiceCreatedEvent` | After AR invoice created |
| `PaymentReceivedEvent` | After customer payment recorded |
| `PaymentAppliedEvent` | After payment applied to invoice |
| `CreditLimitExceededEvent` | When customer exceeds credit limit |
| `InvoiceOverdueEvent` | When invoice becomes overdue |

**Event Usage:**
```php
// Listen to invoice overdue events
class SendOverdueReminderListener
{
    public function handle(InvoiceOverdueEvent $event): void
    {
        Notification::send(
            $event->invoice->customer,
            new InvoiceOverdueNotification($event->invoice)
        );
    }
}
```

---

## Implementation Plans

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN12-implement-accounts-receivable.md | FR-AR-002, IR-AR-001, PR-AR-001 | MILESTONE 3 | Not Started |

---

## Acceptance Criteria

- [ ] AR invoices auto-generated from sales orders
- [ ] Customer payments recorded correctly
- [ ] Payment application working
- [ ] Credit limit enforcement functional
- [ ] AR aging report accurate
- [ ] Banking integration working
- [ ] GL posting automatic
- [ ] Receipt generation < 2s
- [ ] Tenant isolation enforced
- [ ] 100% test coverage

---

## Testing Strategy

### Unit Tests

```php
test('creates AR invoice from sales order', function () {
    $salesOrder = SalesOrder::factory()->create(['total' => 5000]);
    
    $invoice = AR::createInvoiceFromSalesOrder($salesOrder);
    
    expect($invoice->total_amount)->toBe(5000.00);
    expect($invoice->sales_order_id)->toBe($salesOrder->id);
});

test('applies payment to multiple invoices', function () {
    $invoice1 = ARInvoice::factory()->create(['total_amount' => 3000]);
    $invoice2 = ARInvoice::factory()->create(['total_amount' => 2000]);
    
    $receipt = ARReceipt::factory()->create(['total_amount' => 5000]);
    
    AR::applyPayment($receipt, [
        ['invoice_id' => $invoice1->id, 'amount' => 3000],
        ['invoice_id' => $invoice2->id, 'amount' => 2000]
    ]);
    
    expect($invoice1->fresh()->paid_amount)->toBe(3000.00);
    expect($invoice1->fresh()->status)->toBe('paid');
    expect($invoice2->fresh()->paid_amount)->toBe(2000.00);
});

test('prevents exceeding credit limit', function () {
    $customer = Customer::factory()->create();
    CustomerCreditLimit::factory()->create([
        'customer_id' => $customer->id,
        'credit_limit' => 10000,
        'current_balance' => 8000
    ]);
    
    $canSell = AR::checkCreditLimit($customer, orderAmount: 3000);
    
    expect($canSell)->toBeFalse();
});
```

### Feature Tests

```php
test('records customer payment via API', function () {
    $customer = Customer::factory()->create();
    $invoice = ARInvoice::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 5000
    ]);
    
    $response = $this->actingAs($accountant)
        ->postJson('/api/v1/ar/receipts', [
            'customer_id' => $customer->id,
            'total_amount' => 5000,
            'payment_method' => 'wire',
            'applications' => [
                ['invoice_id' => $invoice->id, 'amount' => 5000]
            ]
        ]);
    
    $response->assertCreated();
    expect($invoice->fresh()->status)->toBe('paid');
});
```

---

## Dependencies

### Feature Module Dependencies

- **Mandatory:** SUB01 (Multi-Tenancy), SUB07 (Chart of Accounts), SUB08 (General Ledger), SUB10 (Banking)
- **Integration:** SUB17 (Sales)

### External Package Dependencies

None - uses Laravel primitives

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Receipt Processing | < 2s per transaction |
| Payment Application | < 500ms for 10 invoices |
| AR Aging Report | < 1s for 10k invoices |

---

## Monorepo Integration

- Development: `/packages/accounts-receivable/`
- Published as: `azaharizaman/erp-accounts-receivable`

---

## References

- Master PRD: [../PRD01-MVP.md](../PRD01-MVP.md)
- General Ledger: PRD01-SUB08-GENERAL-LEDGER.md
- Banking: PRD01-SUB10-BANKING.md
- Sales: PRD01-SUB17-SALES.md

---

**Document Status:** Draft - Pending Review  
**Last Updated:** November 11, 2025
