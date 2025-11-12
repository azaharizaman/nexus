---
plan: Inventory Management Stock Movements - Receipt, Issue, Transfer, Adjustment
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, inventory, transactions, business-logic, actions, milestone-5]
---

# Inventory Management Stock Movements - Receipt, Issue, Transfer, Adjustment

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

## Introduction

This implementation plan builds upon the inventory foundation (PLAN01) by implementing core stock movement transactions that update inventory balances. It covers the four primary movement types: stock receipts (goods into warehouse), stock issues (goods out of warehouse), warehouse transfers (goods between warehouses), and inventory adjustments (corrections and cycle counts).

**Key Deliverables:**
- Database table for stock_movements with complete audit trail
- Laravel Actions for each movement type (Receipt, Issue, Transfer, Adjustment)
- Transaction management ensuring ACID compliance
- Optimistic locking to prevent concurrent update conflicts
- Event-driven architecture for integration with other modules
- Batch/lot and serial number tracking during movements

**Success Criteria:**
- All stock movements update balances atomically within database transactions
- Optimistic locking prevents double-posting of same document
- 1000+ concurrent movements supported without deadlocks (PR-INV-002)
- Complete audit trail captured for all movements
- Events dispatched for external module integration
- All business rules enforced (no negative stock, document validation)

---

## 1. Requirements & Constraints

### Requirements from PRD

- **FR-INV-004**: Record stock movements (receipt, issue, transfer, adjustment) with audit trail
- **FR-INV-005**: Support batch/lot tracking for items with expiry dates
- **FR-INV-006**: Track serial numbers for unique items
- **BR-INV-001**: Disallow negative stock balances for non-allowed items
- **BR-INV-002**: Require approved document (GRN, delivery note) for stock movements
- **BR-INV-004**: Warehouse transfers require matching receipt and issue documents
- **IR-INV-001**: Integrate with SUB06 (UOM) for unit conversions during movements
- **IR-INV-002**: Integrate with SUB16 (Purchasing) for goods receipt posting
- **IR-INV-003**: Integrate with SUB17 (Sales) for goods issue posting
- **SR-INV-001**: Implement optimistic locking for concurrent stock movement transactions
- **PR-INV-002**: Support 1000+ concurrent stock movements without blocking
- **ARCH-INV-001**: Use database transactions for all stock movements to ensure atomicity
- **ARCH-INV-002**: Implement event sourcing for inventory movement history
- **EV-INV-001**: Dispatch StockReceivedEvent when goods received
- **EV-INV-002**: Dispatch StockIssuedEvent when goods issued
- **EV-INV-003**: Dispatch StockAdjustedEvent when inventory adjusted
- **EV-INV-004**: Dispatch LowStockAlertEvent when balance falls below reorder point

### Constraints

- **CON-001**: All movements must update stock_balances within database transaction (rollback on error)
- **CON-002**: Movement records are immutable after creation (no updates, only reversals)
- **CON-003**: Each movement requires document_type and document_number for traceability
- **CON-004**: Warehouse transfers create two movements (issue from source, receipt to destination)
- **CON-005**: Adjustments require reason field (cannot be null/empty)
- **CON-006**: Serial numbers must be unique across all warehouses for same tenant
- **CON-007**: Batch numbers with expiry dates require expiry_date field
- **CON-008**: Movement quantities must match UOM specified (no auto-conversion in this plan)

### Guidelines

- **GUD-001**: Use Laravel Actions with AsAction trait for all movement operations
- **GUD-002**: Inject repository contracts, never access models directly
- **GUD-003**: Wrap all balance updates in DB::transaction() with rollback on exception
- **GUD-004**: Dispatch events after successful transaction commit
- **GUD-005**: Use optimistic locking (version column) on stock_balances table
- **GUD-006**: Log all movements with ActivityLogger contract (not direct Spatie)
- **GUD-007**: Validate item tracking requirements (batch/serial) before movement
- **GUD-008**: Check allow_negative_stock flag before issuing stock

### Patterns to Follow

- **PAT-001**: Action pattern: handle() method encapsulates business logic
- **PAT-002**: Repository pattern: all database access through repository contracts
- **PAT-003**: Event-driven: dispatch domain events for async integration
- **PAT-004**: Exception handling: throw domain-specific exceptions with context
- **PAT-005**: Validation: validate input, validate business rules, validate state
- **PAT-006**: Transaction management: DB::transaction with try-catch-rollback
- **PAT-007**: Audit logging: log before and after state with user context

---

## 2. Implementation Steps

### GOAL-001: Create Stock Movements Database Schema with Audit Trail

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-INV-004 | Stock movement records with complete audit trail | | |
| FR-INV-005, FR-INV-006 | Batch/lot and serial number tracking | | |
| ARCH-INV-002 | Event sourcing for movement history | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000004_create_stock_movements_table.php` with schema:<br>- `id` BIGSERIAL PRIMARY KEY<br>- `tenant_id` UUID NOT NULL FK to tenants(id) ON DELETE CASCADE<br>- `movement_type` VARCHAR(50) NOT NULL CHECK (movement_type IN ('receipt', 'issue', 'transfer', 'adjustment'))<br>- `document_type` VARCHAR(50) NOT NULL (e.g., 'grn', 'delivery_note', 'transfer_note', 'adjustment')<br>- `document_number` VARCHAR(100) NOT NULL<br>- `item_id` BIGINT NOT NULL FK to inventory_items(id) ON DELETE RESTRICT<br>- `from_warehouse_id` BIGINT NULL FK to warehouses(id) ON DELETE RESTRICT<br>- `to_warehouse_id` BIGINT NULL FK to warehouses(id) ON DELETE RESTRICT<br>- `batch_number` VARCHAR(100) NULL<br>- `serial_number` VARCHAR(100) NULL<br>- `expiry_date` DATE NULL<br>- `quantity` DECIMAL(15,4) NOT NULL CHECK (quantity > 0)<br>- `uom_id` BIGINT NOT NULL FK to uoms(id) ON DELETE RESTRICT<br>- `unit_cost` DECIMAL(15,2) NULL<br>- `total_cost` DECIMAL(15,2) NULL<br>- `movement_date` DATE NOT NULL<br>- `reference_document` VARCHAR(200) NULL<br>- `notes` TEXT NULL<br>- `created_by` BIGINT NOT NULL FK to users(id) ON DELETE RESTRICT<br>- `created_at` TIMESTAMP NOT NULL<br>- INDEX idx_movements_tenant (tenant_id)<br>- INDEX idx_movements_item (item_id)<br>- INDEX idx_movements_date (movement_date)<br>- INDEX idx_movements_document (document_type, document_number)<br>- INDEX idx_movements_from_warehouse (from_warehouse_id)<br>- INDEX idx_movements_to_warehouse (to_warehouse_id)<br>- INDEX idx_movements_batch (batch_number)<br>- INDEX idx_movements_serial (serial_number)<br>- Composite index on (tenant_id, item_id, movement_date) for history queries | | |
| TASK-002 | Add version column to stock_balances table for optimistic locking:<br>Create migration `2025_01_01_000005_add_version_to_stock_balances.php`:<br>- Add `version` INTEGER NOT NULL DEFAULT 1<br>- Update with version increment on every update | | |
| TASK-003 | Add batch expiry tracking table:<br>Create migration `2025_01_01_000006_create_batch_expiry_tracking_table.php`:<br>- `id` BIGSERIAL PRIMARY KEY<br>- `tenant_id` UUID NOT NULL<br>- `item_id` BIGINT NOT NULL FK to inventory_items(id)<br>- `batch_number` VARCHAR(100) NOT NULL<br>- `expiry_date` DATE NOT NULL<br>- `manufacture_date` DATE NULL<br>- `is_expired` BOOLEAN GENERATED ALWAYS AS (expiry_date < CURRENT_DATE) STORED<br>- `created_at`, `updated_at` TIMESTAMP<br>- UNIQUE (tenant_id, item_id, batch_number)<br>- INDEX idx_batch_expiry (expiry_date)<br>- INDEX idx_batch_item (item_id) | | |
| TASK-004 | Add serial number tracking table:<br>Create migration `2025_01_01_000007_create_serial_numbers_table.php`:<br>- `id` BIGSERIAL PRIMARY KEY<br>- `tenant_id` UUID NOT NULL<br>- `item_id` BIGINT NOT NULL FK to inventory_items(id)<br>- `serial_number` VARCHAR(100) NOT NULL<br>- `warehouse_id` BIGINT NULL FK to warehouses(id)<br>- `status` VARCHAR(20) NOT NULL DEFAULT 'in_stock' CHECK (status IN ('in_stock', 'issued', 'in_transit', 'defective'))<br>- `purchase_date` DATE NULL<br>- `warranty_expiry_date` DATE NULL<br>- `notes` TEXT NULL<br>- `created_at`, `updated_at` TIMESTAMP<br>- UNIQUE (tenant_id, serial_number) -- Global uniqueness per tenant<br>- INDEX idx_serial_item (item_id)<br>- INDEX idx_serial_warehouse (warehouse_id)<br>- INDEX idx_serial_status (status) | | |
| TASK-005 | Test migrations with rollback: `php artisan migrate && php artisan migrate:rollback` | | |
| TASK-006 | Verify CHECK constraints with invalid data:<br>- Test: INSERT with movement_type = 'invalid' (should fail)<br>- Test: INSERT with quantity = 0 or negative (should fail)<br>- Test: INSERT with status = 'invalid' (should fail)<br>Write Pest test in `tests/Feature/Database/StockMovementsMigrationTest.php` | | |

### GOAL-002: Implement Stock Movement Models and Enums

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-INV-004 | Domain models for stock movements | | |
| FR-INV-005, FR-INV-006 | Batch and serial tracking models | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create enum `src/Enums/MovementType.php`:<br>```php<br>enum MovementType: string {<br>    case RECEIPT = 'receipt';<br>    case ISSUE = 'issue';<br>    case TRANSFER = 'transfer';<br>    case ADJUSTMENT = 'adjustment';<br>    public function label(): string {<br>        return match($this) {<br>            self::RECEIPT => 'Stock Receipt',<br>            self::ISSUE => 'Stock Issue',<br>            self::TRANSFER => 'Warehouse Transfer',<br>            self::ADJUSTMENT => 'Inventory Adjustment',<br>        };<br>    }<br>}<br>```<br>Use backing strings for database storage | | |
| TASK-008 | Create enum `src/Enums/SerialStatus.php`:<br>```php<br>enum SerialStatus: string {<br>    case IN_STOCK = 'in_stock';<br>    case ISSUED = 'issued';<br>    case IN_TRANSIT = 'in_transit';<br>    case DEFECTIVE = 'defective';<br>    public function label(): string { /* implementation */ }<br>}<br>```<br>Include label() method for UI display | | |
| TASK-009 | Create model `src/Models/StockMovement.php`:<br>- Use traits: BelongsToTenant, HasActivityLogging<br>- Fillable: movement_type, document_type, document_number, item_id, from_warehouse_id, to_warehouse_id, batch_number, serial_number, expiry_date, quantity, uom_id, unit_cost, total_cost, movement_date, reference_document, notes, created_by<br>- Casts: movement_type => MovementType::class, quantity => 'decimal:4', unit_cost => 'decimal:2', total_cost => 'decimal:2', movement_date => 'date', expiry_date => 'date'<br>- Relationships: item(), fromWarehouse(), toWarehouse(), uom(), createdBy()<br>- Scopes: scopeByType(), scopeByItem(), scopeByWarehouse(), scopeByDateRange()<br>- Methods: isReceipt(), isIssue(), isTransfer(), isAdjustment()<br>- Read-only: No update() or delete() methods (immutable records)<br>- Configure activitylog: log movement_type, item_id, quantity, document_number<br>Note: Use $guarded = ['id'] instead of $fillable for flexibility | | |
| TASK-010 | Create model `src/Models/BatchExpiryTracking.php`:<br>- Use traits: BelongsToTenant<br>- Fillable: item_id, batch_number, expiry_date, manufacture_date<br>- Casts: expiry_date => 'date', manufacture_date => 'date', is_expired => 'boolean'<br>- Relationships: item()<br>- Scopes: scopeExpired(), scopeExpiringWithin($days)<br>- Appends: ['is_expired', 'days_until_expiry']<br>- Methods: getDaysUntilExpiryAttribute() calculates days remaining | | |
| TASK-011 | Create model `src/Models/SerialNumber.php`:<br>- Use traits: BelongsToTenant<br>- Fillable: item_id, serial_number, warehouse_id, status, purchase_date, warranty_expiry_date, notes<br>- Casts: status => SerialStatus::class, purchase_date => 'date', warranty_expiry_date => 'date'<br>- Relationships: item(), warehouse()<br>- Scopes: scopeInStock(), scopeIssued(), scopeByItem(), scopeByWarehouse()<br>- Methods: markAsIssued(), markAsInStock(), isWarrantyValid()<br>- Validation: Prevent duplicate serial numbers within tenant (unique constraint) | | |
| TASK-012 | Update `src/Models/StockBalance.php` from PLAN01:<br>- Add `version` INTEGER NOT NULL DEFAULT 1 to $fillable<br>- Add `version` => 'integer' to $casts<br>- Add method `incrementVersion(): void` that increments version<br>- Add method `checkVersion(int $expectedVersion): void` that throws OptimisticLockException if version mismatch<br>This enables optimistic locking for concurrent updates | | |
| TASK-013 | Create exception `src/Exceptions/OptimisticLockException.php`:<br>```php<br>class OptimisticLockException extends RuntimeException {<br>    public function __construct(StockBalance $balance, int $expectedVersion) {<br>        parent::__construct(<br>            "Stock balance version mismatch. Expected {$expectedVersion}, got {$balance->version}"<br>        );<br>    }<br>}<br>```<br>Use in stock balance updates | | |
| TASK-014 | Create exception `src/Exceptions/InsufficientStockException.php`:<br>```php<br>class InsufficientStockException extends RuntimeException {<br>    public function __construct(InventoryItem $item, float $requested, float $available) {<br>        parent::__construct(<br>            "Insufficient stock for item {$item->item_code}. Requested: {$requested}, Available: {$available}"<br>        );<br>    }<br>}<br>```<br>Use when issuing stock exceeds available | | |

### GOAL-003: Implement Stock Receipt and Issue Actions

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-INV-004 | Stock receipt and issue operations | | |
| BR-INV-001, BR-INV-002 | Business rule enforcement | | |
| SR-INV-001 | Optimistic locking for concurrency | | |
| EV-INV-001, EV-INV-002 | Event dispatching | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-015 | Create action `src/Actions/ReceiveStockAction.php`:<br>```php<br>class ReceiveStockAction {<br>    use AsAction;<br>    public function __construct(<br>        private readonly StockBalanceRepositoryContract $balanceRepository,<br>        private readonly ActivityLoggerContract $activityLogger<br>    ) {}<br>    public function handle(array $data): StockMovement {<br>        return DB::transaction(function () use ($data) {<br>            // 1. Validate input<br>            // 2. Find or create stock balance<br>            // 3. Create stock movement record<br>            // 4. Update stock balance with optimistic locking<br>            // 5. Update batch expiry if provided<br>            // 6. Update serial number status if provided<br>            // 7. Log activity<br>            // 8. Dispatch StockReceivedEvent<br>            return $movement;<br>        });<br>    }<br>}<br>```<br>Required $data keys: item_id, warehouse_id, quantity, uom_id, document_type, document_number, movement_date, unit_cost (optional), batch_number (optional), serial_number (optional), expiry_date (optional)<br>Validations:<br>- Item exists and is active<br>- Warehouse exists<br>- Quantity > 0<br>- If item requires batch tracking, batch_number must be provided<br>- If item requires serial tracking, serial_number must be provided<br>- Serial number must not already exist (unique constraint) | | |
| TASK-016 | Create action `src/Actions/IssueStockAction.php`:<br>```php<br>class IssueStockAction {<br>    use AsAction;<br>    public function __construct(<br>        private readonly StockBalanceRepositoryContract $balanceRepository,<br>        private readonly ActivityLoggerContract $activityLogger<br>    ) {}<br>    public function handle(array $data): StockMovement {<br>        return DB::transaction(function () use ($data) {<br>            // 1. Validate input<br>            // 2. Find stock balance (must exist)<br>            // 3. Check allow_negative_stock flag<br>            // 4. Check available quantity >= requested<br>            // 5. Create stock movement record<br>            // 6. Update stock balance (decrement) with optimistic locking<br>            // 7. Update serial number status to 'issued'<br>            // 8. Log activity<br>            // 9. Dispatch StockIssuedEvent<br>            // 10. Check reorder point, dispatch LowStockAlertEvent if needed<br>            return $movement;<br>        });<br>    }<br>}<br>```<br>Required $data keys: item_id, warehouse_id, quantity, uom_id, document_type, document_number, movement_date, batch_number (if tracked), serial_number (if tracked)<br>Validations:<br>- Stock balance exists<br>- Available quantity >= requested (unless allow_negative_stock)<br>- Batch/serial provided if required<br>Throws InsufficientStockException if insufficient stock | | |
| TASK-017 | Create event `src/Events/StockReceivedEvent.php`:<br>```php<br>class StockReceivedEvent {<br>    public function __construct(<br>        public readonly StockMovement $movement,<br>        public readonly InventoryItem $item,<br>        public readonly float $quantity,<br>        public readonly int $warehouseId<br>    ) {}<br>}<br>```<br>Dispatched after successful stock receipt | | |
| TASK-018 | Create event `src/Events/StockIssuedEvent.php`:<br>```php<br>class StockIssuedEvent {<br>    public function __construct(<br>        public readonly StockMovement $movement,<br>        public readonly InventoryItem $item,<br>        public readonly float $quantity,<br>        public readonly int $warehouseId<br>    ) {}<br>}<br>```<br>Dispatched after successful stock issue | | |
| TASK-019 | Create event `src/Events/LowStockAlertEvent.php`:<br>```php<br>class LowStockAlertEvent {<br>    public function __construct(<br>        public readonly InventoryItem $item,<br>        public readonly float $currentBalance,<br>        public readonly float $reorderPoint,<br>        public readonly int $warehouseId<br>    ) {}<br>}<br>```<br>Dispatched when balance < reorder point after issue | | |
| TASK-020 | Create listener `src/Listeners/SendLowStockAlertListener.php`:<br>```php<br>#[Listen(LowStockAlertEvent::class)]<br>class SendLowStockAlertListener {<br>    public function handle(LowStockAlertEvent $event): void {<br>        // Log low stock alert<br>        Log::warning('Low stock alert', [<br>            'item_code' => $event->item->item_code,<br>            'warehouse_id' => $event->warehouseId,<br>            'current_balance' => $event->currentBalance,<br>            'reorder_point' => $event->reorderPoint,<br>        ]);<br>        // Future: Send notification via SUB22<br>    }<br>}<br>```<br>Use Laravel 11 event attribute for listener registration | | |

### GOAL-004: Implement Warehouse Transfer and Adjustment Actions

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-INV-004 | Warehouse transfers with matching issue/receipt | | |
| FR-INV-004 | Inventory adjustment operations | | |
| EV-INV-003 | Stock adjusted event | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-021 | Create action `src/Actions/TransferStockAction.php`:<br>```php<br>class TransferStockAction {<br>    use AsAction;<br>    public function __construct(<br>        private readonly IssueStockAction $issueStock,<br>        private readonly ReceiveStockAction $receiveStock,<br>        private readonly ActivityLoggerContract $activityLogger<br>    ) {}<br>    public function handle(array $data): array {<br>        return DB::transaction(function () use ($data) {<br>            // 1. Validate input (from_warehouse_id != to_warehouse_id)<br>            // 2. Issue stock from source warehouse<br>            $issueMovement = $this->issueStock->handle([<br>                'item_id' => $data['item_id'],<br>                'warehouse_id' => $data['from_warehouse_id'],<br>                'quantity' => $data['quantity'],<br>                'document_type' => 'transfer_note',<br>                'document_number' => $data['transfer_number'],<br>                // ... other fields<br>            ]);<br>            // 3. Receive stock to destination warehouse<br>            $receiptMovement = $this->receiveStock->handle([<br>                'item_id' => $data['item_id'],<br>                'warehouse_id' => $data['to_warehouse_id'],<br>                'quantity' => $data['quantity'],<br>                'document_type' => 'transfer_note',<br>                'document_number' => $data['transfer_number'],<br>                // ... other fields<br>            ]);<br>            // 4. Log transfer activity<br>            return ['issue' => $issueMovement, 'receipt' => $receiptMovement];<br>        });<br>    }<br>}<br>```<br>Required $data keys: item_id, from_warehouse_id, to_warehouse_id, quantity, uom_id, transfer_number, movement_date<br>Validations:<br>- from_warehouse_id != to_warehouse_id<br>- Both warehouses exist<br>- Sufficient stock in source warehouse<br>Returns array with both movement records | | |
| TASK-022 | Create action `src/Actions/AdjustStockAction.php`:<br>```php<br>class AdjustStockAction {<br>    use AsAction;<br>    public function __construct(<br>        private readonly StockBalanceRepositoryContract $balanceRepository,<br>        private readonly ActivityLoggerContract $activityLogger<br>    ) {}<br>    public function handle(array $data): StockMovement {<br>        return DB::transaction(function () use ($data) {<br>            // 1. Validate input (reason required, cannot be empty)<br>            // 2. Find stock balance (create if not exists for positive adjustment)<br>            // 3. Calculate adjustment quantity (new_quantity - current_quantity)<br>            // 4. Create stock movement record (adjustment type)<br>            // 5. Update stock balance with optimistic locking<br>            // 6. Log activity with before/after quantities<br>            // 7. Dispatch StockAdjustedEvent<br>            return $movement;<br>        });<br>    }<br>}<br>```<br>Required $data keys: item_id, warehouse_id, new_quantity, uom_id, document_type = 'adjustment', document_number, movement_date, reason (notes field)<br>Validations:<br>- reason field cannot be null or empty<br>- new_quantity >= 0 (unless allow_negative_stock)<br>- document_number must be unique (prevent duplicate adjustments)<br>Supports both positive (cycle count increase) and negative (shrinkage) adjustments | | |
| TASK-023 | Create event `src/Events/StockAdjustedEvent.php`:<br>```php<br>class StockAdjustedEvent {<br>    public function __construct(<br>        public readonly StockMovement $movement,<br>        public readonly InventoryItem $item,<br>        public readonly float $adjustmentQuantity,<br>        public readonly float $previousQuantity,<br>        public readonly float $newQuantity,<br>        public readonly string $reason<br>    ) {}<br>}<br>```<br>Include before/after quantities for audit | | |
| TASK-024 | Create repository method in `StockBalanceRepository`:<br>```php<br>public function updateWithOptimisticLock(<br>    StockBalance $balance,<br>    float $newQuantity,<br>    int $expectedVersion<br>): StockBalance {<br>    $updated = StockBalance::where('id', $balance->id)<br>        ->where('version', $expectedVersion)<br>        ->update([<br>            'quantity' => $newQuantity,<br>            'version' => $expectedVersion + 1,<br>            'last_movement_date' => now(),<br>        ]);<br>    if ($updated === 0) {<br>        throw new OptimisticLockException($balance, $expectedVersion);<br>    }<br>    return $balance->fresh();<br>}<br>```<br>Implements optimistic locking pattern | | |
| TASK-025 | Create factory `database/factories/StockMovementFactory.php`:<br>- Generate random movement_type<br>- Generate document_type and document_number<br>- Associate with item_id, warehouse_id<br>- Generate quantity (1-100)<br>- Set movement_date to today<br>- State methods: receipt(), issue(), transfer(), adjustment(), withBatch($batch), withSerial($serial) | | |

### GOAL-005: Create Integration Listeners and Tests

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-INV-002, IR-INV-003 | Integration with Purchasing and Sales modules | | |
| ARCH-INV-002 | Event sourcing for movement history | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-026 | Create listener `src/Listeners/PostGoodsReceiptListener.php`:<br>```php<br>#[Listen(PurchaseOrderReceivedEvent::class)]<br>class PostGoodsReceiptListener {<br>    public function __construct(<br>        private readonly ReceiveStockAction $receiveStock<br>    ) {}<br>    public function handle(PurchaseOrderReceivedEvent $event): void {<br>        // Post stock receipt for each line item in purchase order<br>        foreach ($event->lineItems as $lineItem) {<br>            $this->receiveStock->handle([<br>                'item_id' => $lineItem->item_id,<br>                'warehouse_id' => $event->warehouse_id,<br>                'quantity' => $lineItem->quantity,<br>                'uom_id' => $lineItem->uom_id,<br>                'unit_cost' => $lineItem->unit_cost,<br>                'document_type' => 'grn',<br>                'document_number' => $event->grnNumber,<br>                'movement_date' => $event->receiptDate,<br>            ]);<br>        }<br>    }<br>}<br>```<br>Listens to SUB16 (Purchasing) events | | |
| TASK-027 | Create listener `src/Listeners/PostGoodsIssueListener.php`:<br>```php<br>#[Listen(SalesOrderDeliveredEvent::class)]<br>class PostGoodsIssueListener {<br>    public function __construct(<br>        private readonly IssueStockAction $issueStock<br>    ) {}<br>    public function handle(SalesOrderDeliveredEvent $event): void {<br>        // Post stock issue for each line item in sales order<br>        foreach ($event->lineItems as $lineItem) {<br>            $this->issueStock->handle([<br>                'item_id' => $lineItem->item_id,<br>                'warehouse_id' => $event->warehouse_id,<br>                'quantity' => $lineItem->quantity,<br>                'uom_id' => $lineItem->uom_id,<br>                'document_type' => 'delivery_note',<br>                'document_number' => $event->deliveryNoteNumber,<br>                'movement_date' => $event->deliveryDate,<br>            ]);<br>        }<br>    }<br>}<br>```<br>Listens to SUB17 (Sales) events | | |
| TASK-028 | Create unit test `tests/Unit/Actions/ReceiveStockActionTest.php`:<br>- Test: successful stock receipt creates movement and updates balance<br>- Test: batch tracking requirement enforced (missing batch_number throws exception)<br>- Test: serial tracking requirement enforced (missing serial_number throws exception)<br>- Test: duplicate serial number throws exception<br>- Test: StockReceivedEvent dispatched after successful receipt<br>- Test: activity log captured with correct details<br>- Test: batch expiry tracking record created when expiry_date provided<br>- Test: transaction rollback on error (balance not updated)<br>Use Pest syntax, mock repositories | | |
| TASK-029 | Create unit test `tests/Unit/Actions/IssueStockActionTest.php`:<br>- Test: successful stock issue creates movement and decrements balance<br>- Test: insufficient stock throws InsufficientStockException<br>- Test: allow_negative_stock flag bypasses insufficient stock check<br>- Test: LowStockAlertEvent dispatched when balance < reorder_point<br>- Test: serial number status updated to 'issued'<br>- Test: StockIssuedEvent dispatched after successful issue<br>- Test: optimistic locking prevents concurrent issues (version mismatch)<br>- Test: transaction rollback on error<br>Use Pest syntax | | |
| TASK-030 | Create unit test `tests/Unit/Actions/TransferStockActionTest.php`:<br>- Test: transfer creates two movements (issue + receipt)<br>- Test: transfer fails if from_warehouse_id == to_warehouse_id<br>- Test: transfer fails if insufficient stock in source warehouse<br>- Test: batch/serial numbers transferred correctly<br>- Test: transaction rollback on error (neither movement created)<br>Use Pest syntax | | |
| TASK-031 | Create unit test `tests/Unit/Actions/AdjustStockActionTest.php`:<br>- Test: adjustment creates movement with correct quantity<br>- Test: adjustment updates balance to new_quantity<br>- Test: positive adjustment (cycle count increase)<br>- Test: negative adjustment (shrinkage)<br>- Test: reason field required (throws exception if empty)<br>- Test: StockAdjustedEvent includes before/after quantities<br>- Test: duplicate document_number prevented<br>Use Pest syntax | | |
| TASK-032 | Create feature test `tests/Feature/Actions/StockMovementsConcurrencyTest.php`:<br>- Test: 1000 concurrent receipt operations (PR-INV-002)<br>- Test: optimistic locking prevents double-posting<br>- Test: race condition handled gracefully (retry logic)<br>Use parallel execution with Pest | | |

---

## 3. Alternatives

### ALT-001: Use database locks (SELECT FOR UPDATE) instead of optimistic locking
**Rejected Reason:** Pessimistic locking with SELECT FOR UPDATE can cause deadlocks under high concurrency (1000+ concurrent movements). Optimistic locking with version column is lock-free and scales better. Requires retry logic but prevents blocking.

### ALT-002: Store movements and balances in same transaction log table (event sourcing pure)
**Rejected Reason:** Pure event sourcing requires replaying all movements to calculate current balance (slow for queries). Hybrid approach (movements + aggregated balances) provides both audit trail and fast queries. Balance table is materialized view of movement history.

### ALT-003: Allow updates to stock_movements records for corrections
**Rejected Reason:** Immutable movement records preserve audit trail integrity. Corrections should be new adjustment movements (reversing entries). This matches accounting best practices where journal entries are never modified, only reversed.

### ALT-004: Auto-generate document numbers in actions instead of requiring input
**Rejected Reason:** Document numbers come from source systems (GRN from Purchasing, Delivery Note from Sales). Auto-generation would create duplicate sequences. Actions should accept document numbers provided by calling modules.

### ALT-005: Use queued jobs for stock movements to improve response time
**Rejected Reason:** Stock movements must be immediate and synchronous within originating transaction. If purchase order receipt fails, movement should not be posted. Async processing would complicate error handling and rollback scenarios.

---

## 4. Dependencies

### Internal Dependencies (from PLAN01)

- **DEP-001**: InventoryItem model and repository from PLAN01
- **DEP-002**: StockBalance model and repository from PLAN01
- **DEP-003**: InventoryItemRepositoryContract interface
- **DEP-004**: StockBalanceRepositoryContract interface
- **DEP-005**: ActivityLoggerContract for audit logging
- **DEP-006**: BelongsToTenant trait for multi-tenancy

### External Module Dependencies

- **DEP-007**: SUB01 (Multi-Tenancy) - tenants table and middleware
- **DEP-008**: SUB06 (UOM) - uoms table for unit_of_measure conversions
- **DEP-009**: SUB15 (Backoffice) - warehouses table
- **DEP-010**: SUB16 (Purchasing) - PurchaseOrderReceivedEvent (optional integration)
- **DEP-011**: SUB17 (Sales) - SalesOrderDeliveredEvent (optional integration)
- **DEP-012**: SUB02 (Authentication) - users table for created_by FK

### External Package Dependencies

- **DEP-013**: Laravel Framework ^12.0 (DB transactions, events)
- **DEP-014**: lorisleiva/laravel-actions ^2.0 (AsAction trait)
- **DEP-015**: pestphp/pest ^4.0 (testing framework)

---

## 5. Files

### Database Migrations

- **database/migrations/2025_01_01_000004_create_stock_movements_table.php** - Movement audit trail
- **database/migrations/2025_01_01_000005_add_version_to_stock_balances.php** - Optimistic locking
- **database/migrations/2025_01_01_000006_create_batch_expiry_tracking_table.php** - Batch expiry dates
- **database/migrations/2025_01_01_000007_create_serial_numbers_table.php** - Serial number registry

### Enums

- **src/Enums/MovementType.php** - Receipt, Issue, Transfer, Adjustment
- **src/Enums/SerialStatus.php** - In Stock, Issued, In Transit, Defective

### Models

- **src/Models/StockMovement.php** - Immutable movement records
- **src/Models/BatchExpiryTracking.php** - Batch expiry dates
- **src/Models/SerialNumber.php** - Serial number registry
- **src/Models/StockBalance.php** - Updated with version column (from PLAN01)

### Exceptions

- **src/Exceptions/OptimisticLockException.php** - Version mismatch error
- **src/Exceptions/InsufficientStockException.php** - Stock shortage error

### Actions

- **src/Actions/ReceiveStockAction.php** - Stock receipt operation
- **src/Actions/IssueStockAction.php** - Stock issue operation
- **src/Actions/TransferStockAction.php** - Warehouse transfer operation
- **src/Actions/AdjustStockAction.php** - Inventory adjustment operation

### Events

- **src/Events/StockReceivedEvent.php** - After stock receipt
- **src/Events/StockIssuedEvent.php** - After stock issue
- **src/Events/StockAdjustedEvent.php** - After adjustment
- **src/Events/LowStockAlertEvent.php** - Balance below reorder point

### Listeners

- **src/Listeners/SendLowStockAlertListener.php** - Log low stock alerts
- **src/Listeners/PostGoodsReceiptListener.php** - Listen to Purchasing events
- **src/Listeners/PostGoodsIssueListener.php** - Listen to Sales events

### Repositories

- **src/Repositories/StockBalanceRepository.php** - Add updateWithOptimisticLock() method

### Factories

- **database/factories/StockMovementFactory.php** - Test data generation
- **database/factories/BatchExpiryTrackingFactory.php** - Test data for batches
- **database/factories/SerialNumberFactory.php** - Test data for serials

### Tests (Unit)

- **tests/Unit/Actions/ReceiveStockActionTest.php** - 8 tests for receipt logic
- **tests/Unit/Actions/IssueStockActionTest.php** - 8 tests for issue logic
- **tests/Unit/Actions/TransferStockActionTest.php** - 5 tests for transfer logic
- **tests/Unit/Actions/AdjustStockActionTest.php** - 7 tests for adjustment logic

### Tests (Feature)

- **tests/Feature/Database/StockMovementsMigrationTest.php** - Schema validation
- **tests/Feature/Actions/StockMovementsConcurrencyTest.php** - Concurrency testing

---

## 6. Testing

### Unit Tests (28 tests)

**Receipt Action (8 tests):**
- **TEST-001**: Successful stock receipt creates StockMovement record
- **TEST-002**: Receipt updates stock_balances.quantity correctly
- **TEST-003**: Receipt with batch tracking creates BatchExpiryTracking record
- **TEST-004**: Receipt with serial tracking creates SerialNumber record
- **TEST-005**: Missing batch_number throws exception when item requires batch tracking
- **TEST-006**: Missing serial_number throws exception when item requires serial tracking
- **TEST-007**: Duplicate serial_number throws exception (unique constraint)
- **TEST-008**: StockReceivedEvent dispatched after successful receipt

**Issue Action (8 tests):**
- **TEST-009**: Successful stock issue creates StockMovement record
- **TEST-010**: Issue decrements stock_balances.quantity correctly
- **TEST-011**: InsufficientStockException thrown when available_quantity < requested
- **TEST-012**: allow_negative_stock flag bypasses insufficient stock check
- **TEST-013**: Serial number status updated to 'issued' after issue
- **TEST-014**: LowStockAlertEvent dispatched when balance < reorder_point
- **TEST-015**: StockIssuedEvent dispatched after successful issue
- **TEST-016**: Optimistic locking prevents concurrent issues (OptimisticLockException)

**Transfer Action (5 tests):**
- **TEST-017**: Transfer creates two StockMovement records (issue + receipt)
- **TEST-018**: Transfer fails when from_warehouse_id equals to_warehouse_id
- **TEST-019**: Transfer fails when insufficient stock in source warehouse
- **TEST-020**: Batch number transferred correctly to destination warehouse
- **TEST-021**: Serial number warehouse_id updated after transfer

**Adjustment Action (7 tests):**
- **TEST-022**: Positive adjustment increases stock_balances.quantity
- **TEST-023**: Negative adjustment decreases stock_balances.quantity
- **TEST-024**: Adjustment creates StockMovement with correct adjustment quantity
- **TEST-025**: Empty reason field throws exception
- **TEST-026**: StockAdjustedEvent includes before/after quantities
- **TEST-027**: Duplicate document_number prevented (unique constraint)
- **TEST-028**: Transaction rollback on error leaves balance unchanged

### Feature Tests (10 tests)

**Database Schema (3 tests):**
- **TEST-029**: stock_movements table created with correct columns and indexes
- **TEST-030**: CHECK constraint validates movement_type enum values
- **TEST-031**: version column added to stock_balances table

**Concurrency (3 tests):**
- **TEST-032**: 1000 concurrent receipt operations complete successfully (PR-INV-002)
- **TEST-033**: Optimistic locking prevents double-posting of same document
- **TEST-034**: Race condition handled with retry logic (up to 3 retries)

**Integration (4 tests):**
- **TEST-035**: PostGoodsReceiptListener creates stock receipt on PurchaseOrderReceivedEvent
- **TEST-036**: PostGoodsIssueListener creates stock issue on SalesOrderDeliveredEvent
- **TEST-037**: Batch expiry tracking records created during receipt
- **TEST-038**: Serial number status transitions (in_stock → issued → in_stock)

### Integration Tests (5 tests)

- **TEST-039**: Complete receipt flow: Purchase order → GRN → Stock receipt → Balance update
- **TEST-040**: Complete issue flow: Sales order → Delivery note → Stock issue → Balance update
- **TEST-041**: Complete transfer flow: Transfer note → Issue source → Receipt destination
- **TEST-042**: Complete adjustment flow: Cycle count → Adjustment → Balance correction
- **TEST-043**: Event sourcing: Replaying movements recreates current balance

### Performance Tests (2 tests)

- **TEST-044**: 1000 concurrent movements complete in < 60 seconds (PR-INV-002)
- **TEST-045**: Optimistic locking retry success rate > 95% under high contention

---

## 7. Risks & Assumptions

### Risks

- **RISK-001**: Optimistic locking may cause high retry rates under extreme concurrency. Mitigation: Implement exponential backoff retry logic (3 attempts with 100ms, 200ms, 400ms delays).
- **RISK-002**: Serial number unique constraint may be too restrictive if items reissued after return. Mitigation: Add serial_number_history table tracking full lifecycle in future enhancement.
- **RISK-003**: Large batch movements (1000+ items) may cause long transaction times. Mitigation: Add batch size limit (100 items per movement), split large movements into multiple transactions.
- **RISK-004**: Movement immutability prevents error correction without reversal. Mitigation: Document correction procedure, add AdjustStockAction for corrections.
- **RISK-005**: Event listeners may fail silently (e.g., GRN posted but stock not received). Mitigation: Use Laravel event broadcasting with queue retry logic.

### Assumptions

- **ASSUMPTION-001**: Purchase order and sales order events (SUB16, SUB17) will be implemented with required event structures.
- **ASSUMPTION-002**: Warehouse table exists in SUB15 (Backoffice) before this implementation.
- **ASSUMPTION-003**: Document numbers (GRN, delivery note) are unique per document type within tenant.
- **ASSUMPTION-004**: Unit cost provided during receipt; if null, use item's standard_cost.
- **ASSUMPTION-005**: Batch expiry dates are provided by user during receipt (no auto-calculation).
- **ASSUMPTION-006**: Serial numbers are provided by user or external system (no auto-generation in this plan).
- **ASSUMPTION-007**: Movement date can be backdated (historical corrections allowed).
- **ASSUMPTION-008**: Reserved quantity (for sales orders) will be managed in PLAN03 (not implemented here).

---

## 8. KIV for Future Implementations

### KIV-001: Automated Movement Approval Workflow
Require approval for adjustments above certain value threshold. Add approval_status enum (pending, approved, rejected) and approver_id FK to stock_movements. Prevents unauthorized adjustments.

### KIV-002: Movement Reversal Action
Explicit reversal operation creating offsetting movement (quantity * -1) linked to original. Add reversed_movement_id FK on stock_movements. Currently, use AdjustStockAction for corrections.

### KIV-003: Batch Cost Tracking (FIFO/LIFO Layers)
Track unit cost per batch for accurate COGS calculation. Requires inventory_valuation_layers table (implemented in PLAN03). Not needed for movements, only for valuation.

### KIV-004: Movement Templates
Pre-configured movement types (e.g., "Production Output", "Scrap", "Sample") with default document types and reason codes. Add movement_templates table. Improves data consistency.

### KIV-005: Bulk Movement Import
CSV import for large cycle count adjustments (1000+ items). Add ImportStockMovementsAction with validation and progress tracking. Needed when onboarding clients with large inventories.

### KIV-006: Movement Scheduling
Schedule future-dated movements (e.g., planned receipts from PO). Add scheduled_date and scheduled_status fields. Execute movements automatically on scheduled date.

### KIV-007: Movement Photos/Attachments
Upload photos of damaged goods, packing lists, delivery receipts. Add stock_movement_attachments table with file_path and file_type columns. Enhances audit trail.

### KIV-008: Cross-Tenant Transfers
Transfer inventory between different tenants (inter-company transfers). Requires two movements in different tenant contexts. Complex multi-tenancy scenario.

---

## 9. Related PRD / Further Reading

### Related PRDs

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **PRD01-SUB14 (Inventory Management)**: [../prd/prd-01/PRD01-SUB14-INVENTORY-MANAGEMENT.md](../prd/prd-01/PRD01-SUB14-INVENTORY-MANAGEMENT.md)
- **PRD01-SUB06 (UOM)**: [../prd/prd-01/PRD01-SUB06-UOM-MANAGEMENT.md](../prd/prd-01/PRD01-SUB06-UOM-MANAGEMENT.md)
- **PRD01-SUB16 (Purchasing)**: [../prd/prd-01/PRD01-SUB16-PURCHASING.md](../prd/prd-01/PRD01-SUB16-PURCHASING.md) - GRN integration
- **PRD01-SUB17 (Sales)**: [../prd/prd-01/PRD01-SUB17-SALES.md](../prd/prd-01/PRD01-SUB17-SALES.md) - Delivery note integration

### Implementation Plans

- **PRD01-SUB14-PLAN01**: Inventory foundation (this builds upon) - Prerequisite
- **PRD01-SUB14-PLAN03**: Inventory valuation and reporting - Next plan

### Architecture Documentation

- **Laravel Actions Pattern**: [../../.github/copilot-instructions.md#action-pattern](../../.github/copilot-instructions.md#action-pattern)
- **Event-Driven Architecture**: [../../CODING_GUIDELINES.md#event-driven-architecture](../../CODING_GUIDELINES.md#event-driven-architecture)
- **Optimistic Locking**: [../../docs/architecture/CONCURRENCY-PATTERNS.md](../../docs/architecture/CONCURRENCY-PATTERNS.md)
- **Database Transactions**: [../../CODING_GUIDELINES.md#database-transactions](../../CODING_GUIDELINES.md#database-transactions)

### External Documentation

- **Laravel Database Transactions**: https://laravel.com/docs/12.x/database#database-transactions
- **Laravel Events**: https://laravel.com/docs/12.x/events
- **Optimistic Locking Pattern**: https://en.wikipedia.org/wiki/Optimistic_concurrency_control
- **Event Sourcing**: https://martinfowler.com/eaaDev/EventSourcing.html
