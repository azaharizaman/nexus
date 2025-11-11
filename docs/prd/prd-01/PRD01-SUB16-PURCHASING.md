# PRD01-SUB16: Purchasing

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Optional Feature Modules - Supply Chain  
**Related Sub-PRDs:** SUB14 (Inventory Management), SUB11 (Accounts Payable), SUB15 (Backoffice)  
**Composer Package:** `azaharizaman/erp-purchasing`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Purchasing module provides comprehensive procurement management including purchase requisitions, purchase orders, vendor management, multi-level approvals, goods receipt processing, and vendor performance tracking.

### Purpose

This module solves the challenge of managing the complete procure-to-pay cycle from requisition through receipt, integrating with inventory management and accounts payable for seamless operations. It ensures proper authorization controls and maintains complete procurement audit trails.

### Scope

**Included:**
- Purchase requisition workflow with approvals
- Purchase order creation and management
- Vendor master data management
- Multi-level purchase approval based on thresholds
- Goods receipt processing with quality verification
- Partial deliveries and back order tracking
- Three-way matching (PO, goods receipt, vendor invoice)
- Vendor performance metrics and ratings

**Excluded:**
- Accounts payable invoice processing (handled by SUB11 Accounts Payable)
- Inventory valuation (handled by SUB14 Inventory Management)
- Payment processing (handled by SUB09 Banking & Cash Management)
- Strategic sourcing and RFQ management (future module)

### Dependencies

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for purchasing data
- **SUB02 (Authentication & Authorization)** - User access control and approval authorization
- **SUB03 (Audit Logging)** - Track all purchase document changes
- **SUB14 (Inventory Management)** - Goods receipt posting
- **SUB15 (Backoffice)** - Approval workflow enforcement

**Optional Dependencies:**
- **SUB11 (Accounts Payable)** - Three-way invoice matching
- **SUB06 (UOM)** - Unit of measure conversions

### Composer Package Information

- **Package Name:** `azaharizaman/erp-purchasing`
- **Namespace:** `Azaharizaman\Erp\Purchasing`
- **Monorepo Location:** `/packages/purchasing/`
- **Installation:** `composer require azaharizaman/erp-purchasing` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB16 (Purchasing). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-PO-001** | Support **purchase requisition** workflow with approvals before PO creation | High | Planned |
| **FR-PO-002** | Create **purchase orders** with line items, pricing, delivery dates, and terms | High | Planned |
| **FR-PO-003** | Manage **vendor master data** including contact info, payment terms, and ratings | High | Planned |
| **FR-PO-004** | Support **multi-level purchase approval** based on amount thresholds and authority matrix | High | Planned |
| **FR-PO-005** | Process **goods receipt** against PO with quantity and quality verification | High | Planned |
| **FR-PO-006** | Support **partial deliveries** and **back orders** with fulfillment tracking | Medium | Planned |
| **FR-PO-007** | Implement **three-way matching** (PO, goods receipt, vendor invoice) for AP processing | Medium | Planned |
| **FR-PO-008** | Track **vendor performance metrics** (on-time delivery, quality, pricing) | Low | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-PO-001** | POs above **approval threshold** require multi-level authorization | Planned |
| **BR-PO-002** | **Closed POs** cannot be modified; require change order or cancellation | Planned |
| **BR-PO-003** | Goods receipt quantity **cannot exceed PO quantity** without override | Planned |
| **BR-PO-004** | Vendors with **active POs** cannot be deleted | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-PO-001** | Store **complete PO history** including revisions and approvals | Planned |
| **DR-PO-002** | Maintain **goods receipt records** with quality inspection results | Planned |
| **DR-PO-003** | Record **vendor evaluation data** for performance analysis | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-PO-001** | Integrate with **Accounts Payable** for 3-way invoice matching | Planned |
| **IR-PO-002** | Integrate with **Inventory** for automatic stock updates on goods receipt | Planned |
| **IR-PO-003** | Integrate with **Backoffice** for approval workflow enforcement | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-PO-001** | Implement **authorization matrix** for purchase approval limits | Planned |
| **SR-PO-002** | Log all **PO modifications** with user and timestamp | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-PO-001** | PO creation and approval must complete in **< 1 second** | Planned |
| **PR-PO-002** | Vendor search must return results in **< 100ms** | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-PO-001** | Support **100,000+ POs** per tenant per year | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-PO-001** | Use **workflow engine** for flexible approval routing | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-PO-001** | `PurchaseOrderCreatedEvent` | When PO is created | Planned |
| **EV-PO-002** | `PurchaseOrderApprovedEvent` | When PO receives final approval | Planned |
| **EV-PO-003** | `GoodsReceivedEvent` | When goods are received against PO | Planned |
| **EV-PO-004** | `VendorRatingUpdatedEvent` | When vendor performance is evaluated | Planned |

---

## Technical Specifications

### Database Schema

**Vendors Table:**

```sql
CREATE TABLE vendors (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    vendor_code VARCHAR(50) NOT NULL,
    vendor_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(100) NULL,
    tax_id VARCHAR(100) NULL,
    payment_terms VARCHAR(100) NULL,  -- 'NET30', 'NET60', 'COD'
    credit_limit DECIMAL(15, 2) NULL,
    rating VARCHAR(20) NULL,  -- 'excellent', 'good', 'average', 'poor'
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE (tenant_id, vendor_code),
    INDEX idx_vendors_tenant (tenant_id),
    INDEX idx_vendors_active (is_active),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Purchase Requisitions Table:**

```sql
CREATE TABLE purchase_requisitions (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    requisition_number VARCHAR(100) NOT NULL,
    requisition_date DATE NOT NULL,
    requested_by BIGINT NOT NULL REFERENCES users(id),
    department_id BIGINT NULL REFERENCES organizations(id),
    required_date DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',  -- 'draft', 'pending', 'approved', 'rejected', 'converted'
    approved_by BIGINT NULL REFERENCES users(id),
    approved_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, requisition_number),
    INDEX idx_pr_tenant (tenant_id),
    INDEX idx_pr_status (status),
    INDEX idx_pr_requested_by (requested_by),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE purchase_requisition_lines (
    id BIGSERIAL PRIMARY KEY,
    requisition_id BIGINT NOT NULL REFERENCES purchase_requisitions(id) ON DELETE CASCADE,
    line_number INT NOT NULL,
    item_id BIGINT NULL REFERENCES inventory_items(id),
    item_description TEXT NOT NULL,
    quantity DECIMAL(15, 4) NOT NULL,
    uom_id BIGINT NOT NULL REFERENCES uoms(id),
    estimated_price DECIMAL(15, 2) NULL,
    required_date DATE NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_pr_lines_requisition (requisition_id),
    INDEX idx_pr_lines_item (item_id)
);
```

**Purchase Orders Table:**

```sql
CREATE TABLE purchase_orders (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    po_number VARCHAR(100) NOT NULL,
    po_date DATE NOT NULL,
    vendor_id BIGINT NOT NULL REFERENCES vendors(id),
    requisition_id BIGINT NULL REFERENCES purchase_requisitions(id),
    delivery_address TEXT NULL,
    delivery_date DATE NULL,
    payment_terms VARCHAR(100) NULL,
    currency_code VARCHAR(10) NOT NULL,
    subtotal DECIMAL(15, 2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(15, 2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(15, 2) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',  -- 'draft', 'pending', 'approved', 'partial', 'fulfilled', 'closed'
    approved_by BIGINT NULL REFERENCES users(id),
    approved_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE (tenant_id, po_number),
    INDEX idx_po_tenant (tenant_id),
    INDEX idx_po_vendor (vendor_id),
    INDEX idx_po_status (status),
    INDEX idx_po_date (po_date),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE purchase_order_lines (
    id BIGSERIAL PRIMARY KEY,
    po_id BIGINT NOT NULL REFERENCES purchase_orders(id) ON DELETE CASCADE,
    line_number INT NOT NULL,
    item_id BIGINT NULL REFERENCES inventory_items(id),
    item_description TEXT NOT NULL,
    quantity DECIMAL(15, 4) NOT NULL,
    received_quantity DECIMAL(15, 4) NOT NULL DEFAULT 0,
    uom_id BIGINT NOT NULL REFERENCES uoms(id),
    unit_price DECIMAL(15, 2) NOT NULL,
    line_total DECIMAL(15, 2) NOT NULL,
    delivery_date DATE NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_po_lines_po (po_id),
    INDEX idx_po_lines_item (item_id)
);
```

**Goods Receipt Notes (GRN) Table:**

```sql
CREATE TABLE goods_receipt_notes (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    grn_number VARCHAR(100) NOT NULL,
    grn_date DATE NOT NULL,
    po_id BIGINT NOT NULL REFERENCES purchase_orders(id),
    warehouse_id BIGINT NOT NULL REFERENCES warehouses(id),
    received_by BIGINT NOT NULL REFERENCES users(id),
    delivery_note_number VARCHAR(100) NULL,
    inspection_status VARCHAR(20) NULL,  -- 'passed', 'failed', 'pending'
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, grn_number),
    INDEX idx_grn_tenant (tenant_id),
    INDEX idx_grn_po (po_id),
    INDEX idx_grn_date (grn_date),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE goods_receipt_note_lines (
    id BIGSERIAL PRIMARY KEY,
    grn_id BIGINT NOT NULL REFERENCES goods_receipt_notes(id) ON DELETE CASCADE,
    po_line_id BIGINT NOT NULL REFERENCES purchase_order_lines(id),
    item_id BIGINT NOT NULL REFERENCES inventory_items(id),
    quantity_ordered DECIMAL(15, 4) NOT NULL,
    quantity_received DECIMAL(15, 4) NOT NULL,
    quantity_accepted DECIMAL(15, 4) NOT NULL,
    quantity_rejected DECIMAL(15, 4) NULL,
    batch_number VARCHAR(100) NULL,
    serial_number VARCHAR(100) NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_grn_lines_grn (grn_id),
    INDEX idx_grn_lines_po_line (po_line_id),
    INDEX idx_grn_lines_item (item_id)
);
```

**Vendor Performance Table:**

```sql
CREATE TABLE vendor_performance (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    vendor_id BIGINT NOT NULL REFERENCES vendors(id),
    evaluation_date DATE NOT NULL,
    on_time_delivery_score INT NULL,  -- 1-5
    quality_score INT NULL,            -- 1-5
    price_competitiveness_score INT NULL,  -- 1-5
    overall_rating VARCHAR(20) NULL,   -- 'excellent', 'good', 'average', 'poor'
    comments TEXT NULL,
    evaluated_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_vendor_perf_vendor (vendor_id),
    INDEX idx_vendor_perf_date (evaluation_date),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

### API Endpoints

All endpoints follow the RESTful pattern under `/api/v1/purchasing/`:

**Vendor Management:**
- `GET /api/v1/purchasing/vendors` - List vendors with filtering
- `POST /api/v1/purchasing/vendors` - Create new vendor
- `GET /api/v1/purchasing/vendors/{id}` - Get vendor details
- `PATCH /api/v1/purchasing/vendors/{id}` - Update vendor
- `DELETE /api/v1/purchasing/vendors/{id}` - Soft delete vendor
- `GET /api/v1/purchasing/vendors/{id}/performance` - Get vendor performance history

**Purchase Requisitions:**
- `GET /api/v1/purchasing/requisitions` - List requisitions
- `POST /api/v1/purchasing/requisitions` - Create requisition
- `GET /api/v1/purchasing/requisitions/{id}` - Get requisition details
- `PATCH /api/v1/purchasing/requisitions/{id}` - Update requisition
- `POST /api/v1/purchasing/requisitions/{id}/submit` - Submit for approval
- `POST /api/v1/purchasing/requisitions/{id}/approve` - Approve requisition

**Purchase Orders:**
- `GET /api/v1/purchasing/purchase-orders` - List POs
- `POST /api/v1/purchasing/purchase-orders` - Create PO
- `GET /api/v1/purchasing/purchase-orders/{id}` - Get PO details
- `PATCH /api/v1/purchasing/purchase-orders/{id}` - Update PO
- `POST /api/v1/purchasing/purchase-orders/{id}/submit` - Submit for approval
- `POST /api/v1/purchasing/purchase-orders/{id}/approve` - Approve PO
- `POST /api/v1/purchasing/purchase-orders/{id}/close` - Close PO

**Goods Receipt:**
- `GET /api/v1/purchasing/goods-receipts` - List GRNs
- `POST /api/v1/purchasing/goods-receipts` - Create GRN
- `GET /api/v1/purchasing/goods-receipts/{id}` - Get GRN details
- `POST /api/v1/purchasing/goods-receipts/{id}/post` - Post to inventory

**Reports:**
- `GET /api/v1/purchasing/reports/open-pos` - Open purchase orders
- `GET /api/v1/purchasing/reports/pending-receipts` - POs pending receipt
- `GET /api/v1/purchasing/reports/vendor-analysis` - Vendor performance analysis

### Events

**Domain Events Emitted:**

```php
namespace Azaharizaman\Erp\Purchasing\Events;

class PurchaseOrderCreatedEvent
{
    public function __construct(
        public readonly PurchaseOrder $purchaseOrder,
        public readonly User $createdBy
    ) {}
}

class PurchaseOrderApprovedEvent
{
    public function __construct(
        public readonly PurchaseOrder $purchaseOrder,
        public readonly User $approvedBy,
        public readonly int $approvalLevel
    ) {}
}

class GoodsReceivedEvent
{
    public function __construct(
        public readonly GoodsReceiptNote $grn,
        public readonly PurchaseOrder $purchaseOrder,
        public readonly array $receivedItems
    ) {}
}

class VendorRatingUpdatedEvent
{
    public function __construct(
        public readonly Vendor $vendor,
        public readonly string $newRating,
        public readonly VendorPerformance $evaluation
    ) {}
}
```

### Event Listeners

**Events from Other Modules:**

This module listens to:
- `InvoiceReceivedEvent` (SUB11) - Three-way matching with PO and GRN
- `TenantCreatedEvent` (SUB01) - Initialize default approval workflows

---

## Implementation Plans

**Note:** Implementation plans follow the naming convention `PLAN{number}-implement-{component}.md`

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN16-implement-purchasing.md | FR-PO-001 to FR-PO-008, BR-PO-001 to BR-PO-004 | MILESTONE 5 | Not Started |

**Implementation plan will be created separately using:** `.github/prompts/create-implementation-plan.prompt.md`

---

## Acceptance Criteria

### Functional Acceptance

- [ ] Can create and manage vendor master data
- [ ] Purchase requisition workflow operational with approvals
- [ ] Purchase orders created with complete line items and pricing
- [ ] Multi-level approval based on amount thresholds functional
- [ ] Goods receipt processing against POs working
- [ ] Partial deliveries and back order tracking operational
- [ ] Three-way matching (PO, GRN, invoice) functional
- [ ] Vendor performance tracking and rating system working

### Technical Acceptance

- [ ] All API endpoints return correct responses per OpenAPI spec
- [ ] PO creation completes in < 1 second (PR-PO-001)
- [ ] Vendor search completes in < 100ms (PR-PO-002)
- [ ] Workflow engine routes approvals correctly (ARCH-PO-001)
- [ ] System supports 100,000+ POs per year (SCR-PO-001)

### Security Acceptance

- [ ] Authorization matrix enforced for approval limits (SR-PO-001)
- [ ] All PO modifications logged (SR-PO-002)
- [ ] Closed POs cannot be modified (BR-PO-002)
- [ ] Audit trail complete for all purchasing activities

### Integration Acceptance

- [ ] Integration with Inventory for goods receipt functional (IR-PO-002)
- [ ] Integration with Accounts Payable for invoice matching working (IR-PO-001)
- [ ] Integration with Backoffice for approval workflows operational (IR-PO-003)

---

## Testing Strategy

### Unit Tests

**Test Coverage Requirements:** Minimum 80% code coverage

**Key Test Areas:**
- Purchase order amount calculations
- Approval threshold validation
- Goods receipt quantity validation
- Three-way matching logic
- Vendor performance score calculations

**Example Tests:**
```php
test('PO above threshold requires approval', function () {
    $po = PurchaseOrder::factory()->create([
        'total_amount' => 10000,
    ]);
    
    $requiresApproval = RequiresApprovalAction::run($po);
    
    expect($requiresApproval)->toBeTrue();
});

test('cannot receive more than PO quantity without override', function () {
    $poLine = PurchaseOrderLine::factory()->create([
        'quantity' => 100,
    ]);
    
    expect(fn () => CreateGoodsReceiptAction::run($poLine, 150))
        ->toThrow(QuantityExceededException::class);
});
```

### Feature Tests

**API Integration Tests:**
- Complete CRUD operations for vendors via API
- Purchase requisition workflow (create, submit, approve, convert to PO)
- Purchase order lifecycle (create, approve, receive, close)
- Goods receipt posting and inventory update

**Example Tests:**
```php
test('can create and approve purchase order via API', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $vendor = Vendor::factory()->create(['tenant_id' => $tenant->id]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/purchasing/purchase-orders', [
            'vendor_id' => $vendor->id,
            'po_date' => now()->format('Y-m-d'),
            'lines' => [
                [
                    'item_description' => 'Test Item',
                    'quantity' => 10,
                    'unit_price' => 100,
                ],
            ],
        ]);
    
    $response->assertCreated();
    
    $poId = $response->json('data.id');
    
    $approveResponse = $this->actingAs($user)
        ->postJson("/api/v1/purchasing/purchase-orders/{$poId}/approve");
    
    $approveResponse->assertOk();
});
```

### Integration Tests

**Cross-Module Integration:**
- Goods receipt creates stock movement in Inventory (SUB14)
- Invoice matching with PO and GRN in Accounts Payable (SUB11)
- Approval workflow enforcement from Backoffice (SUB15)

### Performance Tests

**Load Testing Scenarios:**
- PO creation: < 1 second (PR-PO-001)
- Vendor search: < 100ms (PR-PO-002)
- 100,000+ POs per year handling

---

## Dependencies

### Feature Module Dependencies

**From Master PRD Section D.2.1:**

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for all purchasing data
- **SUB02 (Authentication & Authorization)** - User access control
- **SUB03 (Audit Logging)** - Track all purchasing activities
- **SUB14 (Inventory Management)** - Goods receipt posting
- **SUB15 (Backoffice)** - Approval workflow enforcement

**Optional Dependencies:**
- **SUB11 (Accounts Payable)** - Three-way invoice matching
- **SUB06 (UOM)** - Unit of measure conversions
- **SUB22 (Notifications)** - Approval request notifications

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

- **Database:** PostgreSQL 14+ (for advanced indexing)
- **Cache:** Redis 6+ (for vendor search caching)
- **Queue:** Redis or database queue driver (for approval notifications)

---

## Feature Module Structure

### Directory Structure (in Monorepo)

```
packages/purchasing/
├── src/
│   ├── Actions/
│   │   ├── CreatePurchaseOrderAction.php
│   │   ├── ApprovePurchaseOrderAction.php
│   │   ├── CreateGoodsReceiptAction.php
│   │   └── EvaluateVendorAction.php
│   ├── Contracts/
│   │   ├── VendorRepositoryContract.php
│   │   ├── PurchaseOrderRepositoryContract.php
│   │   └── GoodsReceiptRepositoryContract.php
│   ├── Events/
│   │   ├── PurchaseOrderCreatedEvent.php
│   │   ├── PurchaseOrderApprovedEvent.php
│   │   ├── GoodsReceivedEvent.php
│   │   └── VendorRatingUpdatedEvent.php
│   ├── Listeners/
│   │   ├── PostGoodsReceiptToInventoryListener.php
│   │   └── UpdateVendorPerformanceListener.php
│   ├── Models/
│   │   ├── Vendor.php
│   │   ├── PurchaseRequisition.php
│   │   ├── PurchaseOrder.php
│   │   └── GoodsReceiptNote.php
│   ├── Observers/
│   │   └── PurchaseOrderObserver.php
│   ├── Policies/
│   │   ├── VendorPolicy.php
│   │   └── PurchaseOrderPolicy.php
│   ├── Repositories/
│   │   ├── VendorRepository.php
│   │   └── PurchaseOrderRepository.php
│   ├── Services/
│   │   ├── PurchaseApprovalService.php
│   │   ├── ThreeWayMatchingService.php
│   │   └── VendorPerformanceService.php
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Resources/
│   └── PurchasingServiceProvider.php
├── tests/
│   ├── Feature/
│   │   ├── VendorManagementTest.php
│   │   ├── PurchaseOrderTest.php
│   │   └── GoodsReceiptTest.php
│   └── Unit/
│       ├── PurchaseOrderTest.php
│       └── ThreeWayMatchingTest.php
├── database/
│   ├── migrations/
│   │   ├── 2025_01_01_000001_create_vendors_table.php
│   │   ├── 2025_01_01_000002_create_purchase_requisitions_table.php
│   │   ├── 2025_01_01_000003_create_purchase_orders_table.php
│   │   └── 2025_01_01_000004_create_goods_receipt_notes_table.php
│   └── factories/
│       ├── VendorFactory.php
│       └── PurchaseOrderFactory.php
├── routes/
│   └── api.php
├── config/
│   └── purchasing.php
├── composer.json
└── README.md
```

---

## Migration Path

This is a new module with no existing functionality to migrate from.

**Initial Setup:**
1. Install package via Composer
2. Publish migrations and run `php artisan migrate`
3. Configure approval thresholds in Settings (SUB05)
4. Import initial vendor master data
5. Set up approval workflows in Backoffice (SUB15)

---

## Success Metrics

From Master PRD Section B.3:

**Adoption Metrics:**
- Purchase order automation > 80% (vs. manual processes)
- Three-way matching usage > 90% of all vendor invoices

**Performance Metrics:**
- PO creation time < 1 second (PR-PO-001)
- Vendor search time < 100ms (PR-PO-002)

**Accuracy Metrics:**
- 99% PO accuracy rate (no amendments required)
- < 5% goods receipt discrepancies

**Operational Metrics:**
- Average PO approval time < 24 hours
- Vendor performance evaluation completion > 70%

---

## Assumptions & Constraints

### Assumptions

1. Approval workflows configured in Backoffice (SUB15) before use
2. Vendor master data imported or manually entered before PO creation
3. Warehouses configured in Inventory (SUB14) for goods receipt
4. UOM conversions handled by UOM module (SUB06) if needed
5. Three-way matching requires Accounts Payable module (SUB11)

### Constraints

1. POs above threshold require multi-level approval
2. Closed POs cannot be modified (require change order)
3. Goods receipt quantity cannot exceed PO quantity without override
4. Vendors with active POs cannot be deleted
5. System supports 100,000+ POs per tenant per year

---

## Monorepo Integration

### Development

- Lives in `/packages/purchasing/` during development
- Main app uses Composer path repository to require locally:
  ```json
  {
    "repositories": [
      {
        "type": "path",
        "url": "./packages/purchasing"
      }
    ],
    "require": {
      "azaharizaman/erp-purchasing": "@dev"
    }
  }
  ```
- All changes committed to monorepo

### Release (v1.0)

- Tagged with monorepo version (e.g., v1.0.0)
- Published to Packagist as `azaharizaman/erp-purchasing`
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
2. Create implementation plan: `PLAN16-implement-purchasing.md` in `/docs/plan/`
3. Break down into GitHub issues
4. Assign to MILESTONE 5 from Master PRD Section F.2.4
5. Set up feature module structure in `/packages/purchasing/`
