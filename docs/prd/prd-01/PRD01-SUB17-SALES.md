# PRD01-SUB17: Sales

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Optional Feature Modules - Revenue Management  
**Related Sub-PRDs:** SUB14 (Inventory Management), SUB12 (Accounts Receivable), SUB15 (Backoffice)  
**Composer Package:** `azaharizaman/erp-sales`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Sales module provides comprehensive order-to-cash management including sales quotations, sales orders, customer management, pricing management, order fulfillment, and delivery documentation.

### Purpose

This module solves the challenge of managing the complete sales cycle from quotation through delivery, integrating with inventory management and accounts receivable for seamless revenue recognition. It ensures proper credit control and maintains complete sales audit trails.

### Scope

**Included:**
- Sales quotation creation with pricing and validity periods
- Sales order management with approval workflow
- Customer master data management
- Order fulfillment with picking, packing, and shipping
- Pricing management (customer-specific pricing, volume discounts, promotions)
- Order status tracking (draft, confirmed, partial, fulfilled, invoiced, closed)
- Back order management for out-of-stock items
- Delivery notes and packing list generation

**Excluded:**
- Accounts receivable invoice processing (handled by SUB12 Accounts Receivable)
- Inventory valuation (handled by SUB14 Inventory Management)
- Payment collection (handled by SUB09 Banking & Cash Management)
- Marketing and CRM features (future module)

### Dependencies

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for sales data
- **SUB02 (Authentication & Authorization)** - User access control
- **SUB03 (Audit Logging)** - Track all sales document changes
- **SUB14 (Inventory Management)** - Stock reservation and fulfillment
- **SUB15 (Backoffice)** - Credit limit checking

**Optional Dependencies:**
- **SUB12 (Accounts Receivable)** - Automatic invoice generation
- **SUB06 (UOM)** - Unit of measure conversions

### Composer Package Information

- **Package Name:** `azaharizaman/erp-sales`
- **Namespace:** `Azaharizaman\Erp\Sales`
- **Monorepo Location:** `/packages/sales/`
- **Installation:** `composer require azaharizaman/erp-sales` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB17 (Sales). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-SD-001** | Support **sales quotation** creation with pricing, discounts, and validity periods | High | Planned |
| **FR-SD-002** | Convert quotations to **sales orders** with approval workflow | High | Planned |
| **FR-SD-003** | Manage **customer master data** including billing/shipping addresses and credit terms | High | Planned |
| **FR-SD-004** | Support **order fulfillment** with picking, packing, and shipping documentation | High | Planned |
| **FR-SD-005** | Implement **pricing management** with customer-specific pricing, volume discounts, and promotions | High | Planned |
| **FR-SD-006** | Track **sales order status** (draft, confirmed, partial, fulfilled, invoiced, closed) | Medium | Planned |
| **FR-SD-007** | Support **back orders** for out-of-stock items with automatic fulfillment on restock | Medium | Planned |
| **FR-SD-008** | Generate **delivery notes** and **packing lists** for shipments | Medium | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-SD-001** | Orders **cannot exceed customer credit limit** without management override | Planned |
| **BR-SD-002** | **Confirmed orders** cannot be modified; require change order or cancellation | Planned |
| **BR-SD-003** | Fulfilled quantity **cannot exceed ordered quantity** without authorization | Planned |
| **BR-SD-004** | Customers with **active orders** cannot be deleted | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-SD-001** | Store **complete order history** including revisions and approvals | Planned |
| **DR-SD-002** | Maintain **pricing history** for audit and analysis | Planned |
| **DR-SD-003** | Record **fulfillment details** with serial/lot numbers for traceability | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-SD-001** | Integrate with **Inventory** for stock reservation and fulfillment | Planned |
| **IR-SD-002** | Integrate with **Accounts Receivable** for automatic invoice generation | Planned |
| **IR-SD-003** | Integrate with **Backoffice** for customer credit limit checking | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-SD-001** | Implement **customer-specific pricing** access controls | Planned |
| **SR-SD-002** | Log all **order modifications** with user and timestamp | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-SD-001** | Order processing (including stock allocation) must complete in **< 2 seconds** for orders with < 100 items | Planned |
| **PR-SD-002** | Customer search must return results in **< 100ms** | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-SD-001** | Support **500,000+ sales orders** per tenant per year | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-SD-001** | Use **optimistic locking** for concurrent order modifications | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-SD-001** | `SalesOrderCreatedEvent` | When order is created | Planned |
| **EV-SD-002** | `SalesOrderConfirmedEvent` | When order receives approval | Planned |
| **EV-SD-003** | `OrderFulfilledEvent` | When shipment is completed | Planned |
| **EV-SD-004** | `CustomerCreditLimitExceededEvent` | When order exceeds credit limit | Planned |

---

## Technical Specifications

### Database Schema

**Customers Table:**

```sql
CREATE TABLE customers (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    customer_code VARCHAR(50) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    billing_address TEXT NULL,
    shipping_address TEXT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(100) NULL,
    tax_id VARCHAR(100) NULL,
    payment_terms VARCHAR(100) NULL,  -- 'NET30', 'NET60', 'COD'
    credit_limit DECIMAL(15, 2) NULL,
    current_balance DECIMAL(15, 2) DEFAULT 0,
    price_list_id BIGINT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE (tenant_id, customer_code),
    INDEX idx_customers_tenant (tenant_id),
    INDEX idx_customers_active (is_active),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Sales Quotations Table:**

```sql
CREATE TABLE sales_quotations (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    quotation_number VARCHAR(100) NOT NULL,
    quotation_date DATE NOT NULL,
    customer_id BIGINT NOT NULL REFERENCES customers(id),
    valid_until DATE NULL,
    delivery_address TEXT NULL,
    currency_code VARCHAR(10) NOT NULL,
    subtotal DECIMAL(15, 2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(15, 2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(15, 2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(15, 2) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',  -- 'draft', 'sent', 'accepted', 'rejected', 'expired', 'converted'
    notes TEXT NULL,
    created_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE (tenant_id, quotation_number),
    INDEX idx_quotes_tenant (tenant_id),
    INDEX idx_quotes_customer (customer_id),
    INDEX idx_quotes_status (status),
    INDEX idx_quotes_date (quotation_date),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE sales_quotation_lines (
    id BIGSERIAL PRIMARY KEY,
    quotation_id BIGINT NOT NULL REFERENCES sales_quotations(id) ON DELETE CASCADE,
    line_number INT NOT NULL,
    item_id BIGINT NULL REFERENCES inventory_items(id),
    item_description TEXT NOT NULL,
    quantity DECIMAL(15, 4) NOT NULL,
    uom_id BIGINT NOT NULL REFERENCES uoms(id),
    unit_price DECIMAL(15, 2) NOT NULL,
    discount_percent DECIMAL(5, 2) DEFAULT 0,
    discount_amount DECIMAL(15, 2) DEFAULT 0,
    line_total DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_quote_lines_quotation (quotation_id),
    INDEX idx_quote_lines_item (item_id)
);
```

**Sales Orders Table:**

```sql
CREATE TABLE sales_orders (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    order_number VARCHAR(100) NOT NULL,
    order_date DATE NOT NULL,
    customer_id BIGINT NOT NULL REFERENCES customers(id),
    quotation_id BIGINT NULL REFERENCES sales_quotations(id),
    delivery_address TEXT NULL,
    requested_delivery_date DATE NULL,
    payment_terms VARCHAR(100) NULL,
    currency_code VARCHAR(10) NOT NULL,
    subtotal DECIMAL(15, 2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(15, 2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(15, 2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(15, 2) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',  -- 'draft', 'confirmed', 'partial', 'fulfilled', 'invoiced', 'closed', 'cancelled'
    approved_by BIGINT NULL REFERENCES users(id),
    approved_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    version INT NOT NULL DEFAULT 1,  -- For optimistic locking
    
    UNIQUE (tenant_id, order_number),
    INDEX idx_orders_tenant (tenant_id),
    INDEX idx_orders_customer (customer_id),
    INDEX idx_orders_status (status),
    INDEX idx_orders_date (order_date),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE sales_order_lines (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES sales_orders(id) ON DELETE CASCADE,
    line_number INT NOT NULL,
    item_id BIGINT NULL REFERENCES inventory_items(id),
    item_description TEXT NOT NULL,
    quantity DECIMAL(15, 4) NOT NULL,
    fulfilled_quantity DECIMAL(15, 4) NOT NULL DEFAULT 0,
    reserved_quantity DECIMAL(15, 4) NOT NULL DEFAULT 0,
    uom_id BIGINT NOT NULL REFERENCES uoms(id),
    unit_price DECIMAL(15, 2) NOT NULL,
    discount_percent DECIMAL(5, 2) DEFAULT 0,
    discount_amount DECIMAL(15, 2) DEFAULT 0,
    line_total DECIMAL(15, 2) NOT NULL,
    requested_delivery_date DATE NULL,
    warehouse_id BIGINT NULL REFERENCES warehouses(id),
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_order_lines_order (order_id),
    INDEX idx_order_lines_item (item_id)
);
```

**Delivery Notes Table:**

```sql
CREATE TABLE delivery_notes (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    delivery_note_number VARCHAR(100) NOT NULL,
    delivery_date DATE NOT NULL,
    order_id BIGINT NOT NULL REFERENCES sales_orders(id),
    customer_id BIGINT NOT NULL REFERENCES customers(id),
    warehouse_id BIGINT NOT NULL REFERENCES warehouses(id),
    shipping_address TEXT NULL,
    carrier VARCHAR(255) NULL,
    tracking_number VARCHAR(100) NULL,
    shipped_by BIGINT NOT NULL REFERENCES users(id),
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, delivery_note_number),
    INDEX idx_delivery_notes_tenant (tenant_id),
    INDEX idx_delivery_notes_order (order_id),
    INDEX idx_delivery_notes_customer (customer_id),
    INDEX idx_delivery_notes_date (delivery_date),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE delivery_note_lines (
    id BIGSERIAL PRIMARY KEY,
    delivery_note_id BIGINT NOT NULL REFERENCES delivery_notes(id) ON DELETE CASCADE,
    order_line_id BIGINT NOT NULL REFERENCES sales_order_lines(id),
    item_id BIGINT NOT NULL REFERENCES inventory_items(id),
    quantity_ordered DECIMAL(15, 4) NOT NULL,
    quantity_delivered DECIMAL(15, 4) NOT NULL,
    batch_number VARCHAR(100) NULL,
    serial_number VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_delivery_lines_delivery_note (delivery_note_id),
    INDEX idx_delivery_lines_order_line (order_line_id),
    INDEX idx_delivery_lines_item (item_id)
);
```

**Price Lists Table:**

```sql
CREATE TABLE price_lists (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    price_list_code VARCHAR(50) NOT NULL,
    price_list_name VARCHAR(255) NOT NULL,
    currency_code VARCHAR(10) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    valid_from DATE NULL,
    valid_to DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, price_list_code),
    INDEX idx_price_lists_tenant (tenant_id),
    INDEX idx_price_lists_active (is_active),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE price_list_items (
    id BIGSERIAL PRIMARY KEY,
    price_list_id BIGINT NOT NULL REFERENCES price_lists(id) ON DELETE CASCADE,
    item_id BIGINT NOT NULL REFERENCES inventory_items(id),
    unit_price DECIMAL(15, 2) NOT NULL,
    discount_percent DECIMAL(5, 2) DEFAULT 0,
    min_quantity DECIMAL(15, 4) DEFAULT 0,
    valid_from DATE NULL,
    valid_to DATE NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (price_list_id, item_id, min_quantity),
    INDEX idx_price_list_items_list (price_list_id),
    INDEX idx_price_list_items_item (item_id)
);
```

### API Endpoints

All endpoints follow the RESTful pattern under `/api/v1/sales/`:

**Customer Management:**
- `GET /api/v1/sales/customers` - List customers with filtering
- `POST /api/v1/sales/customers` - Create new customer
- `GET /api/v1/sales/customers/{id}` - Get customer details
- `PATCH /api/v1/sales/customers/{id}` - Update customer
- `DELETE /api/v1/sales/customers/{id}` - Soft delete customer
- `GET /api/v1/sales/customers/{id}/credit-status` - Check credit limit status

**Sales Quotations:**
- `GET /api/v1/sales/quotations` - List quotations
- `POST /api/v1/sales/quotations` - Create quotation
- `GET /api/v1/sales/quotations/{id}` - Get quotation details
- `PATCH /api/v1/sales/quotations/{id}` - Update quotation
- `POST /api/v1/sales/quotations/{id}/send` - Send to customer
- `POST /api/v1/sales/quotations/{id}/convert` - Convert to sales order

**Sales Orders:**
- `GET /api/v1/sales/orders` - List sales orders
- `POST /api/v1/sales/orders` - Create sales order
- `GET /api/v1/sales/orders/{id}` - Get order details
- `PATCH /api/v1/sales/orders/{id}` - Update order
- `POST /api/v1/sales/orders/{id}/confirm` - Confirm order
- `POST /api/v1/sales/orders/{id}/reserve-stock` - Reserve inventory
- `POST /api/v1/sales/orders/{id}/cancel` - Cancel order

**Order Fulfillment:**
- `GET /api/v1/sales/delivery-notes` - List delivery notes
- `POST /api/v1/sales/delivery-notes` - Create delivery note
- `GET /api/v1/sales/delivery-notes/{id}` - Get delivery note details
- `POST /api/v1/sales/delivery-notes/{id}/ship` - Mark as shipped

**Pricing Management:**
- `GET /api/v1/sales/price-lists` - List price lists
- `POST /api/v1/sales/price-lists` - Create price list
- `PATCH /api/v1/sales/price-lists/{id}` - Update price list
- `GET /api/v1/sales/pricing/{customerId}/{itemId}` - Get effective price

**Reports:**
- `GET /api/v1/sales/reports/open-orders` - Open sales orders
- `GET /api/v1/sales/reports/pending-shipments` - Orders pending delivery
- `GET /api/v1/sales/reports/back-orders` - Back-ordered items

### Events

**Domain Events Emitted:**

```php
namespace Azaharizaman\Erp\Sales\Events;

class SalesOrderCreatedEvent
{
    public function __construct(
        public readonly SalesOrder $order,
        public readonly Customer $customer,
        public readonly User $createdBy
    ) {}
}

class SalesOrderConfirmedEvent
{
    public function __construct(
        public readonly SalesOrder $order,
        public readonly User $confirmedBy
    ) {}
}

class OrderFulfilledEvent
{
    public function __construct(
        public readonly SalesOrder $order,
        public readonly DeliveryNote $deliveryNote,
        public readonly array $fulfilledItems
    ) {}
}

class CustomerCreditLimitExceededEvent
{
    public function __construct(
        public readonly Customer $customer,
        public readonly SalesOrder $order,
        public readonly float $creditLimit,
        public readonly float $proposedAmount
    ) {}
}
```

### Event Listeners

**Events from Other Modules:**

This module listens to:
- `StockReplenishedEvent` (SUB14) - Fulfill back orders automatically
- `InvoiceCreatedEvent` (SUB12) - Update order status to invoiced
- `TenantCreatedEvent` (SUB01) - Initialize default price lists

---

## Implementation Plans

**Note:** Implementation plans follow the naming convention `PLAN{number}-implement-{component}.md`

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN17-implement-sales.md | FR-SD-001 to FR-SD-008, BR-SD-001 to BR-SD-004 | MILESTONE 6 | Not Started |

**Implementation plan will be created separately using:** `.github/prompts/create-implementation-plan.prompt.md`

---

## Acceptance Criteria

### Functional Acceptance

- [ ] Can create and manage customer master data
- [ ] Sales quotation creation and conversion to orders functional
- [ ] Sales order management with approval workflow operational
- [ ] Order fulfillment with picking, packing, shipping working
- [ ] Pricing management (customer-specific, volume discounts) functional
- [ ] Order status tracking accurate throughout lifecycle
- [ ] Back order management and auto-fulfillment working
- [ ] Delivery notes and packing lists generated correctly

### Technical Acceptance

- [ ] All API endpoints return correct responses per OpenAPI spec
- [ ] Order processing completes in < 2 seconds for < 100 items (PR-SD-001)
- [ ] Customer search completes in < 100ms (PR-SD-002)
- [ ] Optimistic locking prevents concurrent modifications (ARCH-SD-001)
- [ ] System supports 500,000+ orders per year (SCR-SD-001)

### Security Acceptance

- [ ] Customer-specific pricing access controls enforced (SR-SD-001)
- [ ] All order modifications logged (SR-SD-002)
- [ ] Credit limit checks enforced (BR-SD-001)
- [ ] Confirmed orders cannot be modified (BR-SD-002)

### Integration Acceptance

- [ ] Integration with Inventory for stock reservation functional (IR-SD-001)
- [ ] Integration with Accounts Receivable for invoice generation working (IR-SD-002)
- [ ] Integration with Backoffice for credit limit checking operational (IR-SD-003)

---

## Testing Strategy

### Unit Tests

**Test Coverage Requirements:** Minimum 80% code coverage

**Key Test Areas:**
- Sales order amount calculations
- Credit limit validation
- Stock reservation logic
- Pricing calculation (discounts, volume pricing)
- Order fulfillment quantity tracking

**Example Tests:**
```php
test('order cannot exceed customer credit limit without override', function () {
    $customer = Customer::factory()->create([
        'credit_limit' => 5000,
        'current_balance' => 3000,
    ]);
    
    expect(fn () => CreateSalesOrderAction::run([
        'customer_id' => $customer->id,
        'total_amount' => 3000,  // Would exceed limit
    ]))->toThrow(CreditLimitExceededException::class);
});

test('confirmed order cannot be modified', function () {
    $order = SalesOrder::factory()->create([
        'status' => 'confirmed',
    ]);
    
    expect(fn () => UpdateSalesOrderAction::run($order, ['notes' => 'Changed']))
        ->toThrow(OrderLockedException::class);
});
```

### Feature Tests

**API Integration Tests:**
- Complete CRUD operations for customers via API
- Sales quotation lifecycle (create, send, convert to order)
- Sales order lifecycle (create, confirm, fulfill, invoice)
- Delivery note creation and shipment

**Example Tests:**
```php
test('can create and confirm sales order via API', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $item = InventoryItem::factory()->create(['tenant_id' => $tenant->id]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/sales/orders', [
            'customer_id' => $customer->id,
            'order_date' => now()->format('Y-m-d'),
            'lines' => [
                [
                    'item_id' => $item->id,
                    'quantity' => 10,
                    'unit_price' => 100,
                ],
            ],
        ]);
    
    $response->assertCreated();
    
    $orderId = $response->json('data.id');
    
    $confirmResponse = $this->actingAs($user)
        ->postJson("/api/v1/sales/orders/{$orderId}/confirm");
    
    $confirmResponse->assertOk();
});
```

### Integration Tests

**Cross-Module Integration:**
- Stock reservation in Inventory (SUB14)
- Invoice generation in Accounts Receivable (SUB12)
- Credit limit checking in Backoffice (SUB15)

### Performance Tests

**Load Testing Scenarios:**
- Order processing: < 2 seconds for < 100 items (PR-SD-001)
- Customer search: < 100ms (PR-SD-002)
- 500,000+ orders per year handling

---

## Dependencies

### Feature Module Dependencies

**From Master PRD Section D.2.1:**

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for all sales data
- **SUB02 (Authentication & Authorization)** - User access control
- **SUB03 (Audit Logging)** - Track all sales activities
- **SUB14 (Inventory Management)** - Stock reservation and fulfillment
- **SUB15 (Backoffice)** - Credit limit checking

**Optional Dependencies:**
- **SUB12 (Accounts Receivable)** - Automatic invoice generation
- **SUB06 (UOM)** - Unit of measure conversions
- **SUB22 (Notifications)** - Order confirmation notifications

### External Package Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "azaharizaman/erp-core": "^1.0",
    "azaharizaman/erp-inventory-management": "^1.0",
    "azaharizaman/laravel-backoffice": "^1.0",
    "lorisleiva/laravel-actions": "^2.0"
  },
  "require-dev": {
    "pestphp/pest": "^4.0"
  }
}
```

### Infrastructure Dependencies

- **Database:** PostgreSQL 14+ (for advanced indexing and optimistic locking)
- **Cache:** Redis 6+ (for customer and pricing cache)
- **Queue:** Redis or database queue driver (for order confirmations)

---

## Feature Module Structure

### Directory Structure (in Monorepo)

```
packages/sales/
├── src/
│   ├── Actions/
│   │   ├── CreateSalesOrderAction.php
│   │   ├── ConfirmSalesOrderAction.php
│   │   ├── FulfillOrderAction.php
│   │   └── CalculatePriceAction.php
│   ├── Contracts/
│   │   ├── CustomerRepositoryContract.php
│   │   ├── SalesOrderRepositoryContract.php
│   │   └── PricingServiceContract.php
│   ├── Events/
│   │   ├── SalesOrderCreatedEvent.php
│   │   ├── SalesOrderConfirmedEvent.php
│   │   ├── OrderFulfilledEvent.php
│   │   └── CustomerCreditLimitExceededEvent.php
│   ├── Listeners/
│   │   ├── ReserveStockListener.php
│   │   ├── GenerateInvoiceListener.php
│   │   └── FulfillBackOrdersListener.php
│   ├── Models/
│   │   ├── Customer.php
│   │   ├── SalesQuotation.php
│   │   ├── SalesOrder.php
│   │   └── DeliveryNote.php
│   ├── Observers/
│   │   └── SalesOrderObserver.php
│   ├── Policies/
│   │   ├── CustomerPolicy.php
│   │   └── SalesOrderPolicy.php
│   ├── Repositories/
│   │   ├── CustomerRepository.php
│   │   └── SalesOrderRepository.php
│   ├── Services/
│   │   ├── PricingService.php
│   │   ├── CreditCheckService.php
│   │   └── OrderFulfillmentService.php
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Resources/
│   └── SalesServiceProvider.php
├── tests/
│   ├── Feature/
│   │   ├── CustomerManagementTest.php
│   │   ├── SalesOrderTest.php
│   │   └── OrderFulfillmentTest.php
│   └── Unit/
│       ├── SalesOrderTest.php
│       └── PricingServiceTest.php
├── database/
│   ├── migrations/
│   │   ├── 2025_01_01_000001_create_customers_table.php
│   │   ├── 2025_01_01_000002_create_sales_quotations_table.php
│   │   ├── 2025_01_01_000003_create_sales_orders_table.php
│   │   └── 2025_01_01_000004_create_delivery_notes_table.php
│   └── factories/
│       ├── CustomerFactory.php
│       └── SalesOrderFactory.php
├── routes/
│   └── api.php
├── config/
│   └── sales.php
├── composer.json
└── README.md
```

---

## Migration Path

This is a new module with no existing functionality to migrate from.

**Initial Setup:**
1. Install package via Composer
2. Publish migrations and run `php artisan migrate`
3. Import initial customer master data
4. Configure price lists and pricing rules
5. Set up credit limits and payment terms

---

## Success Metrics

From Master PRD Section B.3:

**Adoption Metrics:**
- Sales order automation > 85% (vs. manual processes)
- Customer self-service quotation acceptance > 60%

**Performance Metrics:**
- Order processing time < 2 seconds for < 100 items (PR-SD-001)
- Customer search time < 100ms (PR-SD-002)

**Accuracy Metrics:**
- 99% order accuracy rate (no amendments required)
- < 3% order fulfillment discrepancies

**Operational Metrics:**
- Average order confirmation time < 4 hours
- On-time delivery rate > 95%

---

## Assumptions & Constraints

### Assumptions

1. Customer credit limits configured before order processing
2. Price lists maintained regularly for accurate pricing
3. Inventory stock available for order fulfillment
4. Warehouses configured in Inventory (SUB14) for shipping
5. UOM conversions handled by UOM module (SUB06) if needed

### Constraints

1. Orders cannot exceed customer credit limit without override
2. Confirmed orders cannot be modified (require change order)
3. Fulfilled quantity cannot exceed ordered quantity without authorization
4. Customers with active orders cannot be deleted
5. System supports 500,000+ orders per tenant per year

---

## Monorepo Integration

### Development

- Lives in `/packages/sales/` during development
- Main app uses Composer path repository to require locally:
  ```json
  {
    "repositories": [
      {
        "type": "path",
        "url": "./packages/sales"
      }
    ],
    "require": {
      "azaharizaman/erp-sales": "@dev"
    }
  }
  ```
- All changes committed to monorepo

### Release (v1.0)

- Tagged with monorepo version (e.g., v1.0.0)
- Published to Packagist as `azaharizaman/erp-sales`
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
2. Create implementation plan: `PLAN17-implement-sales.md` in `/docs/plan/`
3. Break down into GitHub issues
4. Assign to MILESTONE 6 from Master PRD Section F.2.4
5. Set up feature module structure in `/packages/sales/`
