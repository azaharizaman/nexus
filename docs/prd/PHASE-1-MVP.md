# Phase 1: MVP (Foundation) - Requirements Document

**Version:** 1.0.0  
**Date:** November 8, 2025  
**Timeline:** Months 1-3  
**Status:** Draft

---

## Phase 1 Overview

Establish foundational ERP backend with core business functionality. Focus on essential modules enabling basic business operations: organization management, inventory tracking, sales order processing, and purchase order management.

### Phase 1 Objectives

1. Implement modular architecture with module activation system
2. Establish multi-tenancy and security foundation
3. Deploy core business domains with RESTful API
4. Enable CLI-based administration
5. Integrate required packages (UOM, Inventory, Backoffice, Serial Numbering)
6. Implement audit logging and event-driven architecture

### Success Metrics

- ✅ All core modules operational with API coverage
- ✅ Module enable/disable without system failure
- ✅ Complete audit trail for all operations
- ✅ CLI commands for all CRUD operations
- ✅ 80% test coverage minimum
- ✅ API response time < 200ms (p95)
- ✅ Multi-tenant data isolation verified

---

## Module Requirements: Core Domain

### Core.001: Multi-Tenancy System

**Priority:** P0 (Critical)  
**Complexity:** High

#### Requirements

- **Tenant Model**
  - Unique tenant identifier (UUID)
  - Company name, domain, status
  - Configuration storage (JSON)
  - Subscription/plan information
  - Contact information
  
- **Tenant Isolation**
  - Global scope for tenant filtering on all models
  - Database-level tenant_id on all tables
  - Tenant context management via middleware
  - Prevent cross-tenant data access
  
- **Tenant Management**
  - Create/update/archive tenant
  - Tenant migration utilities
  - Tenant-specific configuration
  - Tenant impersonation for support

#### Implementation Notes

```php
// Trait for tenant-aware models
trait BelongsToTenant {
    protected static function bootBelongsToTenant() {
        static::addGlobalScope(new TenantScope);
        static::creating(function ($model) {
            $model->tenant_id = tenant()->id;
        });
    }
}

// Service for tenant management
interface TenantManagerContract {
    public function create(array $data): Tenant;
    public function setActive(Tenant $tenant): void;
    public function current(): ?Tenant;
}
```

#### API Endpoints

- `POST /api/v1/tenants` - Create tenant (admin only)
- `GET /api/v1/tenants` - List tenants (admin only)
- `GET /api/v1/tenants/{id}` - Get tenant details
- `PATCH /api/v1/tenants/{id}` - Update tenant
- `DELETE /api/v1/tenants/{id}` - Archive tenant

#### CLI Commands

```bash
php artisan erp:tenant:create --name="Acme Corp" --domain="acme"
php artisan erp:tenant:list
php artisan erp:tenant:migrate {tenant-id}
```

---

### Core.002: Authentication & Authorization

**Priority:** P0 (Critical)  
**Complexity:** High

#### Requirements

- **User Management**
  - User model with UUID primary key
  - Email/password authentication
  - Multi-factor authentication support
  - Password reset functionality
  - User status management (active/inactive/locked)
  
- **Role-Based Access Control**
  - Roles: Super Admin, Tenant Admin, Manager, User, API Client
  - Permissions per resource/action
  - Role hierarchy support
  - Permission checking via gates/policies
  
- **API Authentication**
  - Token-based (Laravel Sanctum)
  - Token scoping per tenant
  - Token expiration/refresh
  - API rate limiting per user/tenant

#### Implementation Notes

```php
// Using Spatie Laravel Permission
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable {
    use HasRoles, BelongsToTenant;
    
    // User belongs to tenant
    public function tenant(): BelongsTo {
        return $this->belongsTo(Tenant::class);
    }
}

// Policy example
class ItemPolicy {
    public function view(User $user, Item $item): bool {
        return $user->hasPermissionTo('view items') 
            && $user->tenant_id === $item->tenant_id;
    }
}
```

#### API Endpoints

- `POST /api/v1/auth/register` - Register user
- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/logout` - Logout
- `POST /api/v1/auth/refresh` - Refresh token
- `POST /api/v1/auth/forgot-password` - Request password reset
- `POST /api/v1/auth/reset-password` - Reset password

#### CLI Commands

```bash
php artisan erp:user:create --email="admin@example.com" --role="admin"
php artisan erp:user:assign-role {user-id} {role}
php artisan erp:role:create {name}
php artisan erp:permission:create {name}
```

---

### Core.003: Audit Logging System

**Priority:** P0 (Critical)  
**Complexity:** Medium

#### Requirements

- **Activity Logging**
  - Log all model changes (create/update/delete)
  - Capture user, timestamp, IP address
  - Store old/new values
  - Support for custom activities
  
- **Audit Trail**
  - Immutable audit records
  - Tamper-evident storage
  - Query by user, model, date range
  - Export audit logs
  
  - Hash critical transactions
  - Verification endpoint

#### Implementation Notes

```php
// Using Spatie Laravel Activitylog
use Spatie\Activitylog\Traits\LogsActivity;

class Item extends Model {
    use LogsActivity;
    
    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
}

```

#### API Endpoints

- `GET /api/v1/audit/activities` - List activities
- `GET /api/v1/audit/activities/{id}` - Get activity detail

#### CLI Commands

```bash
php artisan erp:audit:export --from="2025-01-01" --to="2025-12-31"
php artisan erp:audit:verify {model} {id}
```

---

### Core.004: Serial Numbering System

**Priority:** P0 (Critical)  
**Complexity:** Low (Package Integration)

#### Requirements

- **Integration:** `azaharizaman/laravel-serial-numbering`
- **Serial Patterns**
  - Sales orders: `SO-{year}{month}-{number:5}`
  - Purchase orders: `PO-{year}{month}-{number:5}`
  - Invoices: `INV-{year}{month}-{number:5}`
  - Stock movements: `SM-{year}{month}{day}-{number:6}`
  - Items: `ITEM-{year}-{number:6}`
  - Customers: `CUST-{number:6}`
  - Vendors: `VEND-{number:6}`

#### Implementation Notes

```php
// Configuration
'patterns' => [
    'sales_order' => [
        'pattern' => 'SO-{year}{month}-{number}',
        'start' => 10000,
        'digits' => 5,
        'reset' => 'monthly',
    ],
],

// Usage in model
class SalesOrder extends Model {
    use HasSerialNumbering;
    
    protected $serialPattern = 'sales_order';
    protected $serialColumn = 'order_number';
}
```

---

### Core.005: Settings Management

**Priority:** P1 (High)  
**Complexity:** Low

#### Requirements

- **System Settings**
  - Global settings (system-wide)
  - Tenant settings (tenant-specific)
  - User preferences
  - Module configuration
  
- **Setting Types**
  - String, integer, boolean, JSON
  - Encrypted settings for sensitive data
  - Validation rules per setting
  - Default values

#### Implementation Notes

```php
interface SettingsRepositoryContract {
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function has(string $key): bool;
    public function forget(string $key): void;
    public function tenant(): self; // Scope to tenant
}
```

#### API Endpoints

- `GET /api/v1/settings` - List tenant settings
- `GET /api/v1/settings/{key}` - Get setting value
- `PUT /api/v1/settings/{key}` - Update setting
- `DELETE /api/v1/settings/{key}` - Delete setting

---

## Module Requirements: Backoffice Domain

### Backoffice.001: Company Management

**Priority:** P0 (Critical)  
**Complexity:** Low (Package Integration)

#### Requirements

- **Integration:** `azaharizaman/laravel-backoffice`
- **Company Hierarchy**
  - Parent-child company relationships
  - Company details (name, registration, tax info)
  - Company status management
  - Multi-level hierarchy support

#### Implementation Notes

```php
// Using package models
use AzahariZaman\BackOffice\Models\Company;

// Extend if needed
class ErpCompany extends Company {
    // Additional ERP-specific methods
}
```

#### API Endpoints

- `POST /api/v1/backoffice/companies` - Create company
- `GET /api/v1/backoffice/companies` - List companies
- `GET /api/v1/backoffice/companies/{id}` - Get company
- `PATCH /api/v1/backoffice/companies/{id}` - Update company
- `DELETE /api/v1/backoffice/companies/{id}` - Delete company
- `GET /api/v1/backoffice/companies/{id}/children` - Get child companies

---

### Backoffice.002: Office Management

**Priority:** P0 (Critical)  
**Complexity:** Low (Package Integration)

#### Requirements

- **Office Hierarchy**
  - Physical location management
  - Office types (HQ, Branch, Warehouse, etc.)
  - Parent-child office relationships
  - Office contact information

#### API Endpoints

- `POST /api/v1/backoffice/offices` - Create office
- `GET /api/v1/backoffice/offices` - List offices
- `GET /api/v1/backoffice/offices/{id}` - Get office
- `PATCH /api/v1/backoffice/offices/{id}` - Update office
- `DELETE /api/v1/backoffice/offices/{id}` - Delete office

---

### Backoffice.003: Department Management

**Priority:** P1 (High)  
**Complexity:** Low (Package Integration)

#### Requirements

- **Department Hierarchy**
  - Logical organizational structure
  - Department types
  - Parent-child department relationships
  - Department head assignment

#### API Endpoints

- `POST /api/v1/backoffice/departments` - Create department
- `GET /api/v1/backoffice/departments` - List departments
- `GET /api/v1/backoffice/departments/{id}` - Get department
- `PATCH /api/v1/backoffice/departments/{id}` - Update department
- `DELETE /api/v1/backoffice/departments/{id}` - Delete department

---

### Backoffice.004: Staff Management

**Priority:** P0 (Critical)  
**Complexity:** Low (Package Integration)

#### Requirements

- **Staff Records**
  - Employee information
  - Office/department assignment
  - Reporting line management
  - Staff status (active/inactive/terminated)

#### API Endpoints

- `POST /api/v1/backoffice/staff` - Create staff
- `GET /api/v1/backoffice/staff` - List staff
- `GET /api/v1/backoffice/staff/{id}` - Get staff
- `PATCH /api/v1/backoffice/staff/{id}` - Update staff
- `DELETE /api/v1/backoffice/staff/{id}` - Delete staff
- `GET /api/v1/backoffice/staff/{id}/org-chart` - Get organizational chart

---

## Module Requirements: Inventory Domain

### Inventory.001: Item Master

**Priority:** P0 (Critical)  
**Complexity:** Medium

#### Requirements

- **Item Attributes**
  - SKU (auto-generated via serial numbering)
  - Name, description
  - Item type (Stockable, Service, Non-stockable)
  - Category/subcategory
  - UOM (base unit + conversions)
  - Status (Active, Inactive, Discontinued)
  
- **Item Variants**
  - Support for product variants
  - Variant attributes (Size, Color, etc.)
  - Variant-specific pricing/costs
  
- **Item Costs**
  - Standard cost
  - Average cost
  - Last purchase cost
  - Cost method (FIFO, LIFO, Average, Standard)

#### Implementation Notes

```php
class Item extends Model {
    use BelongsToTenant, LogsActivity, HasSerialNumbering;
    
    protected $serialPattern = 'item';
    protected $serialColumn = 'sku';
    
    // Relationships
    public function baseUom(): BelongsTo {
        return $this->belongsTo(Uom::class, 'base_uom_id');
    }
    
    public function category(): BelongsTo {
        return $this->belongsTo(ItemCategory::class);
    }
    
    public function variants(): HasMany {
        return $this->hasMany(ItemVariant::class);
    }
}
```

#### API Endpoints

- `POST /api/v1/inventory/items` - Create item
- `GET /api/v1/inventory/items` - List items
- `GET /api/v1/inventory/items/{id}` - Get item
- `PATCH /api/v1/inventory/items/{id}` - Update item
- `DELETE /api/v1/inventory/items/{id}` - Delete item
- `GET /api/v1/inventory/items/{id}/stock-levels` - Get stock levels
- `GET /api/v1/inventory/items/{id}/movements` - Get movements history

---

### Inventory.002: Warehouse Management

**Priority:** P0 (Critical)  
**Complexity:** Medium

#### Requirements

- **Warehouse Structure**
  - Warehouse details (name, code, address)
  - Warehouse type (Main, Transit, Consignment)
  - Link to office (from Backoffice module)
  - Warehouse status
  
- **Location Hierarchy**
  - Zone → Aisle → Rack → Shelf → Bin
  - Location code generation
  - Location capacity tracking
  - Location type (Receiving, Storage, Picking, Shipping)

#### Implementation Notes

```php
class Warehouse extends Model {
    use BelongsToTenant, LogsActivity;
    
    public function office(): BelongsTo {
        return $this->belongsTo(Office::class);
    }
    
    public function locations(): HasMany {
        return $this->hasMany(WarehouseLocation::class);
    }
}

class WarehouseLocation extends Model {
    use BelongsToTenant;
    
    // Self-referencing for hierarchy
    public function parent(): BelongsTo {
        return $this->belongsTo(WarehouseLocation::class, 'parent_id');
    }
    
    public function children(): HasMany {
        return $this->hasMany(WarehouseLocation::class, 'parent_id');
    }
}
```

#### API Endpoints

- `POST /api/v1/inventory/warehouses` - Create warehouse
- `GET /api/v1/inventory/warehouses` - List warehouses
- `GET /api/v1/inventory/warehouses/{id}` - Get warehouse
- `PATCH /api/v1/inventory/warehouses/{id}` - Update warehouse
- `DELETE /api/v1/inventory/warehouses/{id}` - Delete warehouse
- `POST /api/v1/inventory/warehouses/{id}/locations` - Create location
- `GET /api/v1/inventory/warehouses/{id}/locations` - List locations

---

### Inventory.003: Stock Management

**Priority:** P0 (Critical)  
**Complexity:** High

#### Requirements

- **Integration:** `azaharizaman/laravel-inventory-management`
- **Stock Tracking**
  - Real-time stock levels per item/warehouse/location
  - Available, reserved, on-order quantities
  - Stock valuation (by cost method)
  - Minimum/maximum stock levels
  - Reorder points
  
- **Stock Movements**
  - Movement types: Receipt, Issue, Transfer, Adjustment
  - Immutable movement records
  - Serial number tracking per movement
  - Lot/batch tracking
  - Movement status workflow
  - Approval requirements for adjustments

#### Implementation Notes

```php
// Using package
use Azaharizaman\LaravelInventoryManagement\Models\StockMovement;

class ErpStockMovement extends StockMovement {
    use HasSerialNumbering;
    
    protected $serialPattern = 'stock_movement';
    protected $serialColumn = 'movement_number';
}

// Stock level calculation
interface StockCalculatorContract {
    public function getAvailable(Item $item, Warehouse $warehouse): string;
    public function getReserved(Item $item, Warehouse $warehouse): string;
    public function getOnOrder(Item $item, Warehouse $warehouse): string;
}
```

#### API Endpoints

- `GET /api/v1/inventory/stock-levels` - Query stock levels
- `GET /api/v1/inventory/stock-levels/item/{id}` - Item stock across warehouses
- `POST /api/v1/inventory/stock-movements` - Create stock movement
- `GET /api/v1/inventory/stock-movements` - List movements
- `GET /api/v1/inventory/stock-movements/{id}` - Get movement
- `PATCH /api/v1/inventory/stock-movements/{id}/approve` - Approve movement
- `POST /api/v1/inventory/stock-transfers` - Create stock transfer

---

### Inventory.004: Unit of Measure (UOM)

**Priority:** P0 (Critical)  
**Complexity:** Low (Package Integration)

#### Requirements

- **Integration:** `azaharizaman/laravel-uom-management`
- **UOM Types**
  - Length, Weight, Volume, Area, Quantity, Time
  - Custom UOM types per tenant
  
- **UOM Conversions**
  - Base unit per type
  - Conversion factors
  - Compound unit support (e.g., kg/m³)
  
- **Item UOM Management**
  - Base UOM per item
  - Alternate UOMs with conversions
  - Purchase/sales UOM defaults

#### Implementation Notes

```php
// Using package
use Azaharizaman\LaravelUomManagement\Services\DefaultUnitConverter;

class ItemUomService {
    public function __construct(
        private DefaultUnitConverter $converter
    ) {}
    
    public function convertQuantity(
        string $quantity,
        string $fromUom,
        string $toUom
    ): string {
        return $this->converter->convert($quantity, $fromUom, $toUom);
    }
}
```

---

## Module Requirements: Sales Domain

### Sales.001: Customer Management

**Priority:** P0 (Critical)  
**Complexity:** Medium

#### Requirements

- **Customer Master**
  - Customer code (auto-generated)
  - Company name/individual name
  - Contact information (multiple contacts)
  - Billing/shipping addresses (multiple)
  - Payment terms
  - Credit limit
  - Tax information
  - Customer status (Active, Inactive, Blocked)
  
- **Customer Classification**
  - Customer groups
  - Price groups
  - Industry/sector
  - Customer rating

#### Implementation Notes

```php
class Customer extends Model {
    use BelongsToTenant, LogsActivity, HasSerialNumbering;
    
    protected $serialPattern = 'customer';
    protected $serialColumn = 'customer_code';
    
    public function contacts(): HasMany {
        return $this->hasMany(CustomerContact::class);
    }
    
    public function addresses(): HasMany {
        return $this->hasMany(CustomerAddress::class);
    }
    
    public function priceGroup(): BelongsTo {
        return $this->belongsTo(PriceGroup::class);
    }
}
```

#### API Endpoints

- `POST /api/v1/sales/customers` - Create customer
- `GET /api/v1/sales/customers` - List customers
- `GET /api/v1/sales/customers/{id}` - Get customer
- `PATCH /api/v1/sales/customers/{id}` - Update customer
- `DELETE /api/v1/sales/customers/{id}` - Delete customer
- `POST /api/v1/sales/customers/{id}/contacts` - Add contact
- `POST /api/v1/sales/customers/{id}/addresses` - Add address

---

### Sales.002: Sales Quotation

**Priority:** P1 (High)  
**Complexity:** Medium

#### Requirements

- **Quotation Header**
  - Quote number (auto-generated)
  - Customer reference
  - Quote date, valid until date
  - Salesperson
  - Quote status (Draft, Sent, Accepted, Rejected, Expired)
  - Terms and conditions
  
- **Quotation Lines**
  - Item/description
  - Quantity, UOM
  - Unit price, discount
  - Tax
  - Line total
  
- **Quotation Actions**
  - Convert to sales order
  - Revise quotation
  - Send to customer (log activity)

#### Implementation Notes

```php
class SalesQuotation extends Model {
    use BelongsToTenant, LogsActivity, HasSerialNumbering, HasStatus;
    
    protected $serialPattern = 'sales_quotation';
    protected $serialColumn = 'quote_number';
    
    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }
    
    public function lines(): HasMany {
        return $this->hasMany(SalesQuotationLine::class);
    }
    
    public function convertToOrder(): SalesOrder {
        // Logic to convert to order
    }
}
```

#### API Endpoints

- `POST /api/v1/sales/quotations` - Create quotation
- `GET /api/v1/sales/quotations` - List quotations
- `GET /api/v1/sales/quotations/{id}` - Get quotation
- `PATCH /api/v1/sales/quotations/{id}` - Update quotation
- `DELETE /api/v1/sales/quotations/{id}` - Delete quotation
- `POST /api/v1/sales/quotations/{id}/convert` - Convert to order

---

### Sales.003: Sales Order

**Priority:** P0 (Critical)  
**Complexity:** High

#### Requirements

- **Order Header**
  - Order number (auto-generated)
  - Customer reference
  - Order date, requested delivery date
  - Salesperson
  - Payment terms
  - Shipping method
  - Order status (Draft, Confirmed, In Progress, Completed, Cancelled)
  - Total amount, tax, discount
  
- **Order Lines**
  - Item reference
  - Quantity ordered/shipped/invoiced
  - UOM
  - Unit price, discount, tax
  - Line status
  - Delivery date per line
  
- **Order Fulfillment**
  - Stock reservation on confirmation
  - Partial shipment support
  - Backorder handling
  - Shipment creation
  
- **Order Workflow**
  - Draft → Confirmed → In Progress → Completed
  - Approval workflow (if required)
  - Cancel/hold order

#### Implementation Notes

```php
class SalesOrder extends Model {
    use BelongsToTenant, LogsActivity, HasSerialNumbering, HasStatus;
    
    protected $serialPattern = 'sales_order';
    protected $serialColumn = 'order_number';
    
    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }
    
    public function lines(): HasMany {
        return $this->hasMany(SalesOrderLine::class);
    }
    
    public function shipments(): HasMany {
        return $this->hasMany(Shipment::class);
    }
    
    // State transitions
    public function confirm(): void {
        $this->status()->transitionTo('confirmed');
        event(new SalesOrderConfirmedEvent($this));
    }
    
    public function reserveStock(): void {
        // Reserve stock for order lines
    }
}
```

#### API Endpoints

- `POST /api/v1/sales/orders` - Create order
- `GET /api/v1/sales/orders` - List orders
- `GET /api/v1/sales/orders/{id}` - Get order
- `PATCH /api/v1/sales/orders/{id}` - Update order
- `DELETE /api/v1/sales/orders/{id}` - Cancel order
- `POST /api/v1/sales/orders/{id}/confirm` - Confirm order
- `POST /api/v1/sales/orders/{id}/ship` - Create shipment
- `GET /api/v1/sales/orders/{id}/shipments` - List shipments

---

### Sales.004: Pricing Management

**Priority:** P1 (High)  
**Complexity:** Medium

#### Requirements

- **Price Lists**
  - Multiple price lists per tenant
  - Currency per price list
  - Valid from/to dates
  - Priority/precedence
  
- **Item Pricing**
  - Base price per item
  - Price per UOM
  - Quantity breaks
  - Customer-specific pricing
  - Price group pricing
  
- **Discount Management**
  - Discount types (Percentage, Fixed amount)
  - Line discount
  - Order discount
  - Promotional discounts

#### Implementation Notes

```php
class PriceList extends Model {
    use BelongsToTenant;
    
    public function items(): HasMany {
        return $this->hasMany(PriceListItem::class);
    }
}

interface PricingEngineContract {
    public function calculatePrice(
        Item $item,
        Customer $customer,
        string $quantity,
        string $uom,
        ?Carbon $date = null
    ): Money;
}
```

#### API Endpoints

- `POST /api/v1/sales/price-lists` - Create price list
- `GET /api/v1/sales/price-lists` - List price lists
- `POST /api/v1/sales/price-lists/{id}/items` - Add item pricing
- `GET /api/v1/sales/prices/calculate` - Calculate price (query)

---

## Module Requirements: Purchasing Domain

### Purchasing.001: Vendor Management

**Priority:** P0 (Critical)  
**Complexity:** Medium

#### Requirements

- **Vendor Master**
  - Vendor code (auto-generated)
  - Company name
  - Contact information (multiple contacts)
  - Payment terms
  - Currency
  - Tax information
  - Bank details
  - Vendor status (Active, Inactive, Blocked)
  
- **Vendor Classification**
  - Vendor groups
  - Vendor rating
  - Lead time

#### Implementation Notes

```php
class Vendor extends Model {
    use BelongsToTenant, LogsActivity, HasSerialNumbering;
    
    protected $serialPattern = 'vendor';
    protected $serialColumn = 'vendor_code';
    
    public function contacts(): HasMany {
        return $this->hasMany(VendorContact::class);
    }
    
    public function addresses(): HasMany {
        return $this->hasMany(VendorAddress::class);
    }
}
```

#### API Endpoints

- `POST /api/v1/purchasing/vendors` - Create vendor
- `GET /api/v1/purchasing/vendors` - List vendors
- `GET /api/v1/purchasing/vendors/{id}` - Get vendor
- `PATCH /api/v1/purchasing/vendors/{id}` - Update vendor
- `DELETE /api/v1/purchasing/vendors/{id}` - Delete vendor

---

### Purchasing.002: Purchase Requisition

**Priority:** P1 (High)  
**Complexity:** Medium

#### Requirements

- **Requisition Header**
  - Requisition number (auto-generated)
  - Requester (staff)
  - Department
  - Request date, required date
  - Status (Draft, Submitted, Approved, Rejected, Converted)
  
- **Requisition Lines**
  - Item/description
  - Quantity, UOM
  - Estimated cost
  - Purpose/justification
  
- **Approval Workflow**
  - Multi-level approval
  - Approval rules based on amount
  - Convert to purchase order

#### Implementation Notes

```php
class PurchaseRequisition extends Model {
    use BelongsToTenant, LogsActivity, HasSerialNumbering, HasStatus;
    
    protected $serialPattern = 'purchase_requisition';
    protected $serialColumn = 'requisition_number';
    
    public function requester(): BelongsTo {
        return $this->belongsTo(Staff::class, 'requester_id');
    }
    
    public function lines(): HasMany {
        return $this->hasMany(PurchaseRequisitionLine::class);
    }
    
    public function approve(): void {
        $this->status()->transitionTo('approved');
        event(new PurchaseRequisitionApprovedEvent($this));
    }
}
```

#### API Endpoints

- `POST /api/v1/purchasing/requisitions` - Create requisition
- `GET /api/v1/purchasing/requisitions` - List requisitions
- `GET /api/v1/purchasing/requisitions/{id}` - Get requisition
- `PATCH /api/v1/purchasing/requisitions/{id}` - Update requisition
- `POST /api/v1/purchasing/requisitions/{id}/submit` - Submit for approval
- `POST /api/v1/purchasing/requisitions/{id}/approve` - Approve
- `POST /api/v1/purchasing/requisitions/{id}/reject` - Reject

---

### Purchasing.003: Purchase Order

**Priority:** P0 (Critical)  
**Complexity:** High

#### Requirements

- **Order Header**
  - PO number (auto-generated)
  - Vendor reference
  - Order date, delivery date
  - Buyer (staff)
  - Payment terms
  - Shipping terms (Incoterms)
  - Order status (Draft, Sent, Acknowledged, Receiving, Completed, Cancelled)
  - Total amount, tax
  
- **Order Lines**
  - Item reference
  - Quantity ordered/received/invoiced
  - UOM
  - Unit price, discount, tax
  - Line status
  - Delivery schedule
  
- **Receiving Process**
  - Goods receipt note (GRN)
  - Partial receipt support
  - Quality inspection trigger
  - Automatic stock movement creation
  
- **Order Workflow**
  - Draft → Sent → Acknowledged → Receiving → Completed
  - Approval workflow (based on amount)

#### Implementation Notes

```php
class PurchaseOrder extends Model {
    use BelongsToTenant, LogsActivity, HasSerialNumbering, HasStatus;
    
    protected $serialPattern = 'purchase_order';
    protected $serialColumn = 'po_number';
    
    public function vendor(): BelongsTo {
        return $this->belongsTo(Vendor::class);
    }
    
    public function lines(): HasMany {
        return $this->hasMany(PurchaseOrderLine::class);
    }
    
    public function receipts(): HasMany {
        return $this->hasMany(GoodsReceiptNote::class);
    }
    
    public function receive(array $lines): GoodsReceiptNote {
        // Create GRN and stock movements
    }
}
```

#### API Endpoints

- `POST /api/v1/purchasing/orders` - Create PO
- `GET /api/v1/purchasing/orders` - List POs
- `GET /api/v1/purchasing/orders/{id}` - Get PO
- `PATCH /api/v1/purchasing/orders/{id}` - Update PO
- `DELETE /api/v1/purchasing/orders/{id}` - Cancel PO
- `POST /api/v1/purchasing/orders/{id}/send` - Send to vendor
- `POST /api/v1/purchasing/orders/{id}/receive` - Create GRN
- `GET /api/v1/purchasing/orders/{id}/receipts` - List GRNs

---

### Purchasing.004: Goods Receipt

**Priority:** P0 (Critical)  
**Complexity:** Medium

#### Requirements

- **GRN Header**
  - GRN number (auto-generated)
  - PO reference
  - Vendor
  - Receipt date
  - Received by (staff)
  - Warehouse/location
  - GRN status (Draft, Posted, Cancelled)
  
- **GRN Lines**
  - PO line reference
  - Item
  - Quantity received, UOM
  - Quantity accepted/rejected
  - Lot/serial numbers
  
- **Stock Integration**
  - Automatic stock movement creation (Receipt type)
  - Update stock levels
  - Update PO line received quantity

#### Implementation Notes

```php
class GoodsReceiptNote extends Model {
    use BelongsToTenant, LogsActivity, HasSerialNumbering;
    
    protected $serialPattern = 'goods_receipt';
    protected $serialColumn = 'grn_number';
    
    public function purchaseOrder(): BelongsTo {
        return $this->belongsTo(PurchaseOrder::class);
    }
    
    public function lines(): HasMany {
        return $this->hasMany(GoodsReceiptLine::class);
    }
    
    public function post(): void {
        DB::transaction(function () {
            // Create stock movements
            // Update PO lines
            // Update stock levels
            $this->status = 'posted';
            $this->save();
        });
    }
}
```

#### API Endpoints

- `POST /api/v1/purchasing/receipts` - Create GRN
- `GET /api/v1/purchasing/receipts` - List GRNs
- `GET /api/v1/purchasing/receipts/{id}` - Get GRN
- `POST /api/v1/purchasing/receipts/{id}/post` - Post GRN

---

## Cross-Cutting Requirements

### API Specifications

**API Standards:**
- REST architectural style
- JSON request/response format
- HTTP status codes (200, 201, 204, 400, 401, 403, 404, 422, 500)
- Pagination: `?page=1&per_page=50`
- Filtering: `?filter[status]=active&filter[name]=John`
- Sorting: `?sort=-created_at,name`
- Field selection: `?fields=id,name,email`
- Include relations: `?include=customer,lines`

**Response Format:**
```json
{
  "data": {},
  "meta": {
    "current_page": 1,
    "total": 100
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

**Error Response:**
```json
{
  "error": {
    "message": "Validation failed",
    "code": "VALIDATION_ERROR",
    "details": {
      "email": ["The email field is required."]
    }
  }
}
```

---

### CLI Commands Structure

```bash
# Module management
php artisan erp:module:list
php artisan erp:module:enable {module}
php artisan erp:module:disable {module}

# Core commands
php artisan erp:tenant:create
php artisan erp:user:create
php artisan erp:audit:export

# Backoffice commands
php artisan erp:backoffice:company:create
php artisan erp:backoffice:staff:list

# Inventory commands
php artisan erp:inventory:item:create
php artisan erp:inventory:stock:adjust
php artisan erp:inventory:transfer

# Sales commands
php artisan erp:sales:customer:create
php artisan erp:sales:order:list

# Purchasing commands
php artisan erp:purchasing:vendor:create
php artisan erp:purchasing:order:create
```

---

### Event Architecture

**Event Naming:** `Domain\[Module]\Events\[Entity][Action]Event`

**Core Events:**
- `Core\Events\TenantCreatedEvent`
- `Core\Events\UserLoggedInEvent`

**Backoffice Events:**
- `Backoffice\Events\CompanyCreatedEvent`
- `Backoffice\Events\StaffAssignedEvent`

**Inventory Events:**
- `Inventory\Events\ItemCreatedEvent`
- `Inventory\Events\StockMovementPostedEvent`
- `Inventory\Events\StockLevelBelowMinimumEvent`

**Sales Events:**
- `Sales\Events\SalesOrderConfirmedEvent`
- `Sales\Events\SalesOrderShippedEvent`

**Purchasing Events:**
- `Purchasing\Events\PurchaseOrderSentEvent`
- `Purchasing\Events\GoodsReceivedEvent`

---

### Database Schema Highlights

**Table Naming:**
- `tenants`
- `users`, `roles`, `permissions`
- `companies`, `offices`, `departments`, `staff`
- `inventory_items`, `inventory_warehouses`, `inventory_stock_movements`
- `sales_customers`, `sales_orders`, `sales_order_lines`
- `purchasing_vendors`, `purchasing_orders`, `purchasing_order_lines`

**Standard Columns (All Tables):**
```sql
id                 UUID PRIMARY KEY
tenant_id          UUID REFERENCES tenants(id)
created_at         TIMESTAMP
updated_at         TIMESTAMP
deleted_at         TIMESTAMP (soft delete)
created_by_id      UUID REFERENCES users(id)
updated_by_id      UUID REFERENCES users(id)
```

---

## Testing Requirements

### Unit Tests

- All Action classes must have unit tests
- All Service methods must have unit tests
- All Helper functions must have unit tests
- Value objects validation tests
- Repository tests with mocked models

### Integration Tests

- API endpoint tests (happy path + error cases)
- Database transaction tests
- Event dispatching tests
- Multi-module interaction tests

### Feature Tests

- Complete order-to-cash workflow
- Complete procure-to-pay workflow
- Stock movement workflows
- Multi-tenant isolation tests

### Test Data

- Factories for all models
- Seeders for baseline data
- Test database per developer
- SQLite for CI pipeline

---

## Documentation Deliverables

### Code Documentation

- PHPDoc for all public methods
- Interface usage examples
- README per module

### API Documentation

- OpenAPI 3.0 specification
- Postman collection
- Authentication guide
- Rate limiting guide

### User Documentation

- Installation guide
- Module activation guide
- CLI command reference
- Workflow guides (Order processing, receiving, etc.)

---

## Deployment Checklist

### Phase 1 Deployment Prerequisites

- [ ] Laravel 12+ installed with PHP 8.2+
- [ ] Database configured (MySQL/PostgreSQL)
- [ ] Redis installed for caching/queues
- [ ] Composer dependencies installed
- [ ] Environment variables configured
- [ ] Database migrations executed
- [ ] Baseline data seeded
- [ ] Queue workers configured
- [ ] API documentation published
- [ ] Monitoring/logging configured

### Phase 1 Success Criteria

- [ ] All modules installed and activated
- [ ] Multi-tenant isolation verified
- [ ] API endpoints responding < 200ms
- [ ] All CLI commands functional
- [ ] Test coverage ≥ 80%
- [ ] Audit logging operational
- [ ] Stock movements creating correctly
- [ ] Sales order workflow complete
- [ ] Purchase order workflow complete
- [ ] Module enable/disable working

---

**Document Status:** Draft  
**Next Review:** Week 4 of Phase 1  
**Approval Required:** Project Sponsor, Technical Lead
