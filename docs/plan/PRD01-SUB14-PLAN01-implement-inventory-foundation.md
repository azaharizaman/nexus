---
plan: Inventory Management Foundation - Item Master & Stock Balances
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, inventory, foundation, database, domain-model, milestone-5]
---

# Inventory Management Foundation - Item Master & Stock Balances

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

## Introduction

This implementation plan establishes the foundational infrastructure for the Inventory Management module (PRD01-SUB14), implementing the core data models, database schema, and repository pattern for inventory items, stock balances, and multi-warehouse tracking. This plan creates the essential building blocks that all subsequent inventory features (movements, valuation, reporting) will depend on.

**Key Deliverables:**
- Database tables for inventory_items, stock_balances, item_categories
- Eloquent models with relationships and validation
- Enums for valuation methods, item types, and stock tracking modes
- Repository pattern with contracts for data access
- Factory and seeder classes for testing and initial data
- Real-time stock balance query capabilities

**Success Criteria:**
- All database tables created with proper indexing and constraints
- Models implement BelongsToTenant, HasActivityLogging, IsSearchable traits
- Stock balance queries execute in < 50ms per item (PR-INV-001)
- Repository pattern abstracts all data access operations
- 100% test coverage for domain models and repositories

---

## 1. Requirements & Constraints

### Requirements from PRD

- **FR-INV-001**: Maintain item master data with multi-level categorization and attributes
- **FR-INV-002**: Support multi-warehouse inventory with location-specific balances
- **FR-INV-003**: Provide real-time stock balance queries by item, warehouse, and date
- **BR-INV-001**: Disallow negative stock balances for non-allowed items
- **BR-INV-003**: Prevent deletion of items with stock movement history
- **DR-INV-003**: Record costing data (purchase price, standard cost, latest cost) per item
- **IR-INV-001**: Integrate with SUB06 (UOM) for unit of measure conversions
- **SR-INV-002**: Enforce warehouse-specific access control (user can only access assigned warehouses)
- **PR-INV-001**: Stock balance query must return in < 50ms per item
- **SCR-INV-001**: Support 1,000,000+ item records with efficient indexing
- **ARCH-INV-003**: Use Redis cache for frequently-accessed stock balances

### Constraints

- **CON-001**: Must use PostgreSQL 14+ for generated columns (available_quantity)
- **CON-002**: Must follow Laravel 12 domain-driven design structure
- **CON-003**: Package namespace: `Azaharizaman\Erp\InventoryManagement`
- **CON-004**: All models must use strict types (`declare(strict_types=1);`)
- **CON-005**: Database tables must use tenant_id for multi-tenancy isolation
- **CON-006**: All date columns use TIMESTAMP type (not DATE) for precision
- **CON-007**: Decimal columns use DECIMAL(15,4) for quantities, DECIMAL(15,2) for costs
- **CON-008**: Foreign keys must have explicit names with CASCADE or RESTRICT behavior
- **CON-009**: Unique constraints must include tenant_id for proper tenant isolation

### Guidelines

- **GUD-001**: Follow repository pattern - no direct Model access in services/actions
- **GUD-002**: Use Laravel Actions (lorisleiva/laravel-actions) for all business operations
- **GUD-003**: Implement ActivityLogger contract for audit logging (not direct Spatie)
- **GUD-004**: Use backed enums for all enumeration types (PHP 8.2+)
- **GUD-005**: All public methods must have complete PHPDoc with @param and @return tags
- **GUD-006**: Use Pest v4+ syntax for all tests
- **GUD-007**: Models must use trait wrappers (HasActivityLogging, IsSearchable)
- **GUD-008**: Cache stock balances with Redis using 1-hour TTL

### Patterns to Follow

- **PAT-001**: Use BelongsToTenant trait for automatic tenant scoping on all models
- **PAT-002**: Use HasUuids trait for UUID primary keys where applicable (not for inventory_items)
- **PAT-003**: Use SoftDeletes trait for items that should not be permanently deleted
- **PAT-004**: Use Observer pattern for model lifecycle events (creating, updating, deleting)
- **PAT-005**: Use Factory pattern for test data generation (never raw insert statements)
- **PAT-006**: Use Scopes for common query patterns (active(), byWarehouse(), lowStock())
- **PAT-007**: Use Attribute casting for complex data types (enums, JSON, encrypted)

---

## 2. Implementation Steps

### GOAL-001: Create Database Schema for Inventory Items and Categories

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-INV-001, DR-INV-003 | Item master data with categorization and costing fields | | |
| SCR-INV-001 | Efficient indexing for 1M+ item records | | |
| BR-INV-001, BR-INV-003 | Constraints for negative stock prevention and deletion protection | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000001_create_item_categories_table.php` with schema:<br>- `id` BIGSERIAL PRIMARY KEY<br>- `tenant_id` UUID NOT NULL FK to tenants(id) ON DELETE CASCADE<br>- `parent_id` BIGINT NULL FK to item_categories(id) ON DELETE RESTRICT<br>- `category_code` VARCHAR(50) NOT NULL<br>- `category_name` VARCHAR(255) NOT NULL<br>- `description` TEXT NULL<br>- `is_active` BOOLEAN DEFAULT TRUE<br>- `created_at`, `updated_at`, `deleted_at` TIMESTAMP<br>- UNIQUE (tenant_id, category_code)<br>- INDEX idx_item_categories_tenant (tenant_id)<br>- INDEX idx_item_categories_parent (parent_id)<br>- INDEX idx_item_categories_active (is_active) | | |
| TASK-002 | Create migration `2025_01_01_000002_create_inventory_items_table.php` with schema:<br>- `id` BIGSERIAL PRIMARY KEY<br>- `tenant_id` UUID NOT NULL FK to tenants(id) ON DELETE CASCADE<br>- `item_code` VARCHAR(100) NOT NULL<br>- `item_name` VARCHAR(255) NOT NULL<br>- `description` TEXT NULL<br>- `category_id` BIGINT NULL FK to item_categories(id) ON DELETE RESTRICT<br>- `base_uom_id` BIGINT NOT NULL FK to uoms(id) ON DELETE RESTRICT<br>- `track_batch` BOOLEAN DEFAULT FALSE<br>- `track_serial` BOOLEAN DEFAULT FALSE<br>- `allow_negative_stock` BOOLEAN DEFAULT FALSE<br>- `reorder_point` DECIMAL(15,4) NULL<br>- `reorder_quantity` DECIMAL(15,4) NULL<br>- `standard_cost` DECIMAL(15,2) DEFAULT 0<br>- `latest_purchase_cost` DECIMAL(15,2) DEFAULT 0<br>- `valuation_method` VARCHAR(20) NOT NULL DEFAULT 'weighted_average'<br>- `is_active` BOOLEAN DEFAULT TRUE<br>- `metadata` JSONB NULL<br>- `created_at`, `updated_at`, `deleted_at` TIMESTAMP<br>- UNIQUE (tenant_id, item_code)<br>- INDEX idx_inv_items_tenant (tenant_id)<br>- INDEX idx_inv_items_category (category_id)<br>- INDEX idx_inv_items_uom (base_uom_id)<br>- INDEX idx_inv_items_active (is_active)<br>- INDEX idx_inv_items_code (item_code)<br>- CHECK (valuation_method IN ('fifo', 'lifo', 'weighted_average')) | | |
| TASK-003 | Verify migrations run successfully with `php artisan migrate` and rollback with `php artisan migrate:rollback` | | |
| TASK-004 | Add database indexes for performance optimization in migration file with comments explaining purpose:<br>- Composite index on (tenant_id, is_active, category_id) for filtered queries<br>- Composite index on (tenant_id, valuation_method) for valuation reports | | |
| TASK-005 | Create seeder `ItemCategorySeeder.php` with default categories:<br>- Raw Materials<br>- Finished Goods<br>- Work In Progress<br>- Packaging Materials<br>- Spare Parts<br>Each with appropriate category codes (e.g., RAW, FIN, WIP, PKG, SPR) | | |
| TASK-006 | Test migration schema with PostgreSQL-specific features:<br>- Verify JSONB column storage and querying<br>- Test CHECK constraint validation<br>- Verify CASCADE and RESTRICT FK behavior<br>Write Pest test in `tests/Feature/Database/InventoryItemsMigrationTest.php` | | |

### GOAL-002: Create Stock Balances Database Schema

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-INV-002, FR-INV-003 | Multi-warehouse stock balances with real-time queries | | |
| FR-INV-005, FR-INV-006 | Batch/lot and serial number tracking | | |
| PR-INV-001 | Stock balance query < 50ms performance target | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create migration `2025_01_01_000003_create_stock_balances_table.php` with schema:<br>- `id` BIGSERIAL PRIMARY KEY<br>- `tenant_id` UUID NOT NULL FK to tenants(id) ON DELETE CASCADE<br>- `item_id` BIGINT NOT NULL FK to inventory_items(id) ON DELETE RESTRICT<br>- `warehouse_id` BIGINT NOT NULL FK to warehouses(id) ON DELETE RESTRICT<br>- `batch_number` VARCHAR(100) NULL<br>- `serial_number` VARCHAR(100) NULL<br>- `quantity` DECIMAL(15,4) NOT NULL DEFAULT 0<br>- `reserved_quantity` DECIMAL(15,4) NOT NULL DEFAULT 0<br>- `available_quantity` DECIMAL(15,4) GENERATED ALWAYS AS (quantity - reserved_quantity) STORED<br>- `uom_id` BIGINT NOT NULL FK to uoms(id) ON DELETE RESTRICT<br>- `last_movement_date` TIMESTAMP NULL<br>- `created_at`, `updated_at` TIMESTAMP<br>- UNIQUE (tenant_id, item_id, warehouse_id, batch_number, serial_number)<br>- INDEX idx_stock_tenant (tenant_id)<br>- INDEX idx_stock_item (item_id)<br>- INDEX idx_stock_warehouse (warehouse_id)<br>- INDEX idx_stock_batch (batch_number)<br>- INDEX idx_stock_serial (serial_number)<br>- INDEX idx_stock_available (available_quantity) WHERE available_quantity > 0 | | |
| TASK-008 | Add composite indexes for complex queries in migration:<br>- Composite index on (tenant_id, item_id, warehouse_id) for item-warehouse lookups<br>- Composite index on (tenant_id, warehouse_id, item_id) for warehouse-item lookups<br>- Partial index on (tenant_id, item_id) WHERE available_quantity < reorder_point for low stock queries | | |
| TASK-009 | Test PostgreSQL GENERATED ALWAYS AS stored column with unit test to verify available_quantity = quantity - reserved_quantity automatically | | |
| TASK-010 | Create index analysis query to verify index usage with EXPLAIN ANALYZE:<br>Write query examples in migration comments for common patterns:<br>- Stock balance by item<br>- Stock balance by warehouse<br>- Available stock for sales orders<br>Expected: Index scan, not sequential scan | | |
| TASK-011 | Add CHECK constraints in migration:<br>- CHECK (quantity >= 0) -- Enforce non-negative quantities<br>- CHECK (reserved_quantity >= 0)<br>- CHECK (reserved_quantity <= quantity) -- Cannot reserve more than available | | |
| TASK-012 | Test constraint violations with Pest tests to ensure:<br>- Cannot insert negative quantities<br>- Cannot reserve more than quantity<br>- Unique constraint prevents duplicate balance records | | |

### GOAL-003: Implement Eloquent Models with Domain Logic

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-INV-001, FR-INV-002 | Domain models for items and stock balances | | |
| BR-INV-001, BR-INV-003 | Business rule enforcement in model layer | | |
| ARCH-INV-003 | Redis caching integration via trait | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-013 | Create enum `packages/inventory-management/src/Enums/ValuationMethod.php`:<br>```php<br>enum ValuationMethod: string {<br>    case FIFO = 'fifo';<br>    case LIFO = 'lifo';<br>    case WEIGHTED_AVERAGE = 'weighted_average';<br>    public function label(): string { /* implementation */ }<br>}<br>```<br>With label() method returning human-readable names | | |
| TASK-014 | Create enum `packages/inventory-management/src/Enums/ItemType.php`:<br>```php<br>enum ItemType: string {<br>    case RAW_MATERIAL = 'raw_material';<br>    case FINISHED_GOOD = 'finished_good';<br>    case WORK_IN_PROGRESS = 'wip';<br>    case PACKAGING = 'packaging';<br>    case SPARE_PART = 'spare_part';<br>    case SERVICE = 'service';<br>}<br>```<br>Use backing strings for database storage | | |
| TASK-015 | Create model `packages/inventory-management/src/Models/ItemCategory.php`:<br>- Use traits: BelongsToTenant, HasActivityLogging, IsSearchable, SoftDeletes<br>- Fillable: category_code, category_name, description, parent_id, is_active<br>- Casts: is_active => 'boolean'<br>- Relationships: parent(), children(), items()<br>- Scopes: scopeActive(), scopeRootCategories()<br>- Configure activitylog: log category_code, category_name changes<br>- Configure search: index category_code, category_name, description | | |
| TASK-016 | Create model `packages/inventory-management/src/Models/InventoryItem.php`:<br>- Use traits: BelongsToTenant, HasActivityLogging, IsSearchable, SoftDeletes<br>- Fillable: item_code, item_name, description, category_id, base_uom_id, track_batch, track_serial, allow_negative_stock, reorder_point, reorder_quantity, standard_cost, latest_purchase_cost, valuation_method, is_active, metadata<br>- Casts: track_batch => 'boolean', track_serial => 'boolean', allow_negative_stock => 'boolean', is_active => 'boolean', reorder_point => 'decimal:4', reorder_quantity => 'decimal:4', standard_cost => 'decimal:2', latest_purchase_cost => 'decimal:2', valuation_method => ValuationMethod::class, metadata => 'array'<br>- Relationships: category(), baseUom(), stockBalances(), movements()<br>- Scopes: scopeActive(), scopeByCategory(), scopeLowStock(), scopeByValuationMethod()<br>- Methods: getTotalStockQuantity(), getAvailableQuantity($warehouseId), isLowStock()<br>- Configure activitylog: log item_code, item_name, standard_cost, valuation_method changes<br>- Configure search: index item_code, item_name, description, tenant_id | | |
| TASK-017 | Create model `packages/inventory-management/src/Models/StockBalance.php`:<br>- Use traits: BelongsToTenant<br>- Fillable: item_id, warehouse_id, batch_number, serial_number, quantity, reserved_quantity, uom_id, last_movement_date<br>- Casts: quantity => 'decimal:4', reserved_quantity => 'decimal:4', available_quantity => 'decimal:4', last_movement_date => 'datetime'<br>- Relationships: item(), warehouse(), uom()<br>- Scopes: scopeByItem(), scopeByWarehouse(), scopeAvailable(), scopeByBatch(), scopeBySerial()<br>- Methods: reserve($quantity), release($quantity), adjust($quantity)<br>- Appends: ['available_quantity'] to JSON output<br>- Note: available_quantity is generated column, read-only | | |
| TASK-018 | Add validation methods to InventoryItem model:<br>- `canBeDeleted(): bool` - checks if item has movement history<br>- `canGoNegative(): bool` - returns allow_negative_stock value<br>- `requiresBatchTracking(): bool` - returns track_batch value<br>- `requiresSerialTracking(): bool` - returns track_serial value<br>All methods with PHPDoc @return annotations | | |
| TASK-019 | Create observer `packages/inventory-management/src/Observers/InventoryItemObserver.php`:<br>- Method: deleting() - Prevent deletion if item has movement history (throw exception)<br>- Method: updating() - Validate valuation_method changes (cannot change if movement history exists)<br>Register observer in InventoryManagementServiceProvider | | |

### GOAL-004: Implement Repository Pattern with Contracts

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| GUD-001 | Repository pattern abstraction for data access | | |
| PR-INV-001 | Optimized queries for < 50ms stock balance retrieval | | |
| ARCH-INV-003 | Redis caching for frequently-accessed balances | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-020 | Create contract `packages/inventory-management/src/Contracts/InventoryItemRepositoryContract.php`:<br>```php<br>interface InventoryItemRepositoryContract {<br>    public function findById(int $id): ?InventoryItem;<br>    public function findByCode(string $code): ?InventoryItem;<br>    public function create(array $data): InventoryItem;<br>    public function update(InventoryItem $item, array $data): InventoryItem;<br>    public function delete(InventoryItem $item): bool;<br>    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;<br>    public function getActiveItems(): Collection;<br>    public function getLowStockItems(): Collection;<br>}<br>```<br>All methods with complete PHPDoc | | |
| TASK-021 | Create repository `packages/inventory-management/src/Repositories/InventoryItemRepository.php`:<br>- Implement InventoryItemRepositoryContract<br>- Inject dependencies via constructor<br>- Method: findById() - Find item by ID with tenant scope<br>- Method: findByCode() - Find by item_code with tenant scope and caching (Redis, 1 hour TTL)<br>- Method: create() - Validate data, create item, invalidate cache<br>- Method: update() - Update item, invalidate cache<br>- Method: delete() - Soft delete item (throws exception if has movements)<br>- Method: paginate() - Support filters (category_id, is_active, search query)<br>- Method: getActiveItems() - Return all active items for current tenant<br>- Method: getLowStockItems() - Items where any warehouse balance < reorder_point<br>Use query builder, not raw SQL | | |
| TASK-022 | Create contract `packages/inventory-management/src/Contracts/StockBalanceRepositoryContract.php`:<br>```php<br>interface StockBalanceRepositoryContract {<br>    public function findByItemAndWarehouse(int $itemId, int $warehouseId, ?string $batchNumber = null, ?string $serialNumber = null): ?StockBalance;<br>    public function getItemBalances(int $itemId): Collection;<br>    public function getWarehouseBalances(int $warehouseId): Collection;<br>    public function updateQuantity(StockBalance $balance, float $quantity, float $reservedQuantity): StockBalance;<br>    public function createOrUpdate(array $data): StockBalance;<br>}<br>```<br>All methods with complete PHPDoc | | |
| TASK-023 | Create repository `packages/inventory-management/src/Repositories/StockBalanceRepository.php`:<br>- Implement StockBalanceRepositoryContract<br>- Method: findByItemAndWarehouse() - Find balance with unique constraint matching, use Redis cache (key: "stock_balance:{tenant_id}:{item_id}:{warehouse_id}:{batch}:{serial}", TTL: 1 hour)<br>- Method: getItemBalances() - Get all warehouse balances for an item with eager loading<br>- Method: getWarehouseBalances() - Get all item balances in warehouse with eager loading<br>- Method: updateQuantity() - Update balance quantities, invalidate cache<br>- Method: createOrUpdate() - Use updateOrCreate() with unique constraint columns<br>All queries must execute in < 50ms (add query time assertions in tests) | | |
| TASK-024 | Create contract `packages/inventory-management/src/Contracts/ItemCategoryRepositoryContract.php`:<br>```php<br>interface ItemCategoryRepositoryContract {<br>    public function findById(int $id): ?ItemCategory;<br>    public function findByCode(string $code): ?ItemCategory;<br>    public function getRootCategories(): Collection;<br>    public function getChildCategories(int $parentId): Collection;<br>    public function create(array $data): ItemCategory;<br>    public function update(ItemCategory $category, array $data): ItemCategory;<br>}<br>```<br>All methods with complete PHPDoc | | |
| TASK-025 | Create repository `packages/inventory-management/src/Repositories/ItemCategoryRepository.php`:<br>- Implement ItemCategoryRepositoryContract<br>- Method: findById() - Find by ID with tenant scope<br>- Method: findByCode() - Find by category_code with caching<br>- Method: getRootCategories() - Categories where parent_id IS NULL<br>- Method: getChildCategories() - Categories where parent_id = $parentId<br>- Method: create() - Create category with validation<br>- Method: update() - Update category, invalidate cache | | |
| TASK-026 | Register repository bindings in `packages/inventory-management/src/InventoryManagementServiceProvider.php`:<br>```php<br>public function register(): void {<br>    $this->app->bind(<br>        InventoryItemRepositoryContract::class,<br>        InventoryItemRepository::class<br>    );<br>    $this->app->bind(<br>        StockBalanceRepositoryContract::class,<br>        StockBalanceRepository::class<br>    );<br>    $this->app->bind(<br>        ItemCategoryRepositoryContract::class,<br>        ItemCategoryRepository::class<br>    );<br>}<br>```<br>Use singleton binding for repositories | | |

### GOAL-005: Create Factories, Seeders, and Initial Tests

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PAT-005 | Factory pattern for test data generation | | |
| GUD-006 | Pest v4+ testing framework | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-027 | Create factory `packages/inventory-management/database/factories/ItemCategoryFactory.php`:<br>- Generate unique category_code (e.g., CAT-{random})<br>- Generate category_name with Faker<br>- Set is_active randomly true/false<br>- State methods: withParent($parentId), inactive(), active() | | |
| TASK-028 | Create factory `packages/inventory-management/database/factories/InventoryItemFactory.php`:<br>- Generate unique item_code (e.g., ITEM-{random})<br>- Generate item_name with Faker<br>- Associate with random category_id (or create if not exists)<br>- Set base_uom_id to a seeded UOM (use UOM factory)<br>- Generate random reorder_point (10-100), reorder_quantity (50-200)<br>- Generate random standard_cost (10.00-1000.00)<br>- Set valuation_method randomly (use ValuationMethod enum)<br>- State methods: withBatchTracking(), withSerialTracking(), allowNegativeStock(), inactive(), withCategory($categoryId), withValuationMethod(ValuationMethod $method) | | |
| TASK-029 | Create factory `packages/inventory-management/database/factories/StockBalanceFactory.php`:<br>- Associate with inventory_item_id (create if not exists)<br>- Associate with warehouse_id (use Warehouse factory from SUB15)<br>- Set uom_id to item's base_uom_id<br>- Generate quantity (0-1000)<br>- Generate reserved_quantity (0-50, always <= quantity)<br>- Set batch_number NULL by default<br>- Set serial_number NULL by default<br>- State methods: withBatch($batchNumber), withSerial($serialNumber), withQuantity($qty), withReservation($reservedQty) | | |
| TASK-030 | Create seeder `packages/inventory-management/database/seeders/InventorySeeder.php`:<br>- Seed 5 default item categories (Raw Materials, Finished Goods, WIP, Packaging, Spare Parts)<br>- Seed 20 inventory items across different categories<br>- Seed 50 stock balances across 3 warehouses<br>- Use faker for realistic data<br>- Add comment explaining seeder is for development/testing only | | |
| TASK-031 | Create unit test `packages/inventory-management/tests/Unit/Models/InventoryItemTest.php`:<br>- Test: item creation with required fields<br>- Test: item relationships (category, baseUom, stockBalances)<br>- Test: getTotalStockQuantity() method calculates correctly<br>- Test: getAvailableQuantity() for specific warehouse<br>- Test: isLowStock() returns true when balance < reorder_point<br>- Test: canBeDeleted() returns false when movements exist<br>- Test: valuation_method enum casting works<br>- Test: metadata JSONB storage and retrieval<br>Use Pest syntax, RefreshDatabase trait | | |
| TASK-032 | Create unit test `packages/inventory-management/tests/Unit/Models/StockBalanceTest.php`:<br>- Test: balance creation with all fields<br>- Test: available_quantity generated column calculation<br>- Test: reserve() method updates reserved_quantity<br>- Test: release() method decreases reserved_quantity<br>- Test: adjust() method updates quantity<br>- Test: unique constraint validation (item + warehouse + batch + serial)<br>- Test: CHECK constraint prevents negative quantities<br>- Test: relationships (item, warehouse, uom)<br>Use Pest syntax | | |
| TASK-033 | Create feature test `packages/inventory-management/tests/Feature/Repositories/InventoryItemRepositoryTest.php`:<br>- Test: findById() returns correct item<br>- Test: findByCode() with caching (verify cache hit on second call)<br>- Test: create() creates item and logs activity<br>- Test: update() updates item and invalidates cache<br>- Test: delete() soft deletes item<br>- Test: delete() throws exception when item has movements<br>- Test: paginate() with filters (category, search query)<br>- Test: getLowStockItems() returns items below reorder point<br>Use Pest syntax, mock dependencies | | |
| TASK-034 | Create feature test `packages/inventory-management/tests/Feature/Repositories/StockBalanceRepositoryTest.php`:<br>- Test: findByItemAndWarehouse() with Redis caching<br>- Test: getItemBalances() returns all warehouse balances<br>- Test: getWarehouseBalances() eager loads items<br>- Test: updateQuantity() updates and invalidates cache<br>- Test: createOrUpdate() creates new balance if not exists<br>- Test: createOrUpdate() updates existing balance<br>- Test: query performance < 50ms (use microtime measurement)<br>Use Pest syntax | | |

---

## 3. Alternatives

### ALT-001: Use UUID for inventory_items.id instead of BIGSERIAL
**Rejected Reason:** BIGSERIAL (auto-increment) provides better performance for stock balance queries with foreign key joins. UUIDs would increase index size and slow down queries. Item codes are already unique strings for external identification.

### ALT-002: Store stock balances in Redis instead of PostgreSQL table
**Rejected Reason:** Redis is excellent for caching but not for source of truth. Stock balances need ACID guarantees, audit trails, and complex querying (by warehouse, by batch, by serial). PostgreSQL with Redis caching provides best of both worlds.

### ALT-003: Combine batch_number and serial_number into single tracking_identifier column
**Rejected Reason:** Batch and serial tracking serve different purposes. Batches group items with same production date/expiry (many items per batch), serials uniquely identify individual items (one item per serial). Separate columns enable better indexing and querying.

### ALT-004: Create separate tables for batched items and serialized items
**Rejected Reason:** Adds complexity with minimal benefit. Current design uses nullable batch_number and serial_number columns, allowing flexibility. Items can be non-tracked, batch-tracked, serial-tracked, or both. Separate tables would require union queries and complicate repository logic.

### ALT-005: Use stored procedure for stock balance calculations instead of application code
**Rejected Reason:** PostgreSQL stored procedures reduce testability and portability. Laravel's query builder with Redis caching achieves same performance while keeping logic in application layer (easier to test, debug, and modify).

---

## 4. Dependencies

### Internal Dependencies

- **DEP-001**: SUB01 (Multi-Tenancy) - tenants table must exist for FK constraint
- **DEP-002**: SUB06 (UOM) - uoms table must exist for base_uom_id FK
- **DEP-003**: SUB15 (Backoffice) - warehouses table must exist for warehouse_id FK
- **DEP-004**: Core package traits (BelongsToTenant, HasActivityLogging, IsSearchable)
- **DEP-005**: ActivityLoggerContract from app/Support/Contracts (for decoupled audit logging)
- **DEP-006**: SearchServiceContract from app/Support/Contracts (for Scout abstraction)

### External Dependencies

- **DEP-007**: PostgreSQL 14+ (for GENERATED ALWAYS AS stored columns)
- **DEP-008**: Redis 6+ (for stock balance caching with 1 hour TTL)
- **DEP-009**: Laravel Framework ^12.0
- **DEP-010**: lorisleiva/laravel-actions ^2.0 (for action pattern in GOAL-004)
- **DEP-011**: brick/math ^0.12 (for precise decimal calculations in valuation)
- **DEP-012**: pestphp/pest ^4.0 (for testing)

### Package Dependencies (composer.json)

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "azaharizaman/erp-core": "^1.0",
    "azaharizaman/laravel-uom-management": "^1.0",
    "azaharizaman/erp-backoffice": "^1.0",
    "lorisleiva/laravel-actions": "^2.0",
    "brick/math": "^0.12"
  }
}
```

---

## 5. Files

### Database Migrations

- **packages/inventory-management/database/migrations/2025_01_01_000001_create_item_categories_table.php** - Item categories with hierarchical structure (parent_id)
- **packages/inventory-management/database/migrations/2025_01_01_000002_create_inventory_items_table.php** - Item master data with costing and tracking flags
- **packages/inventory-management/database/migrations/2025_01_01_000003_create_stock_balances_table.php** - Multi-warehouse stock balances with generated available_quantity column

### Enums

- **packages/inventory-management/src/Enums/ValuationMethod.php** - FIFO, LIFO, Weighted Average enum with label() method
- **packages/inventory-management/src/Enums/ItemType.php** - Raw Material, Finished Good, WIP, Packaging, Spare Part, Service types

### Models

- **packages/inventory-management/src/Models/ItemCategory.php** - Item categorization with parent-child relationships
- **packages/inventory-management/src/Models/InventoryItem.php** - Item master with validation methods and relationships
- **packages/inventory-management/src/Models/StockBalance.php** - Multi-warehouse balances with reserve/release/adjust methods

### Observers

- **packages/inventory-management/src/Observers/InventoryItemObserver.php** - Prevent deletion if movements exist, validate valuation method changes

### Contracts

- **packages/inventory-management/src/Contracts/InventoryItemRepositoryContract.php** - Item repository interface (8 methods)
- **packages/inventory-management/src/Contracts/StockBalanceRepositoryContract.php** - Balance repository interface (5 methods)
- **packages/inventory-management/src/Contracts/ItemCategoryRepositoryContract.php** - Category repository interface (6 methods)

### Repositories

- **packages/inventory-management/src/Repositories/InventoryItemRepository.php** - Item data access with caching
- **packages/inventory-management/src/Repositories/StockBalanceRepository.php** - Balance queries with < 50ms performance
- **packages/inventory-management/src/Repositories/ItemCategoryRepository.php** - Category data access

### Factories

- **packages/inventory-management/database/factories/ItemCategoryFactory.php** - Test data generation for categories
- **packages/inventory-management/database/factories/InventoryItemFactory.php** - Test data generation for items (with state methods)
- **packages/inventory-management/database/factories/StockBalanceFactory.php** - Test data generation for balances

### Seeders

- **packages/inventory-management/database/seeders/ItemCategorySeeder.php** - Default categories (5 types)
- **packages/inventory-management/database/seeders/InventorySeeder.php** - Development data (20 items, 50 balances)

### Service Provider

- **packages/inventory-management/src/InventoryManagementServiceProvider.php** - Register repository bindings, observer registration

### Tests (Unit)

- **packages/inventory-management/tests/Unit/Models/InventoryItemTest.php** - 8 tests for item model logic
- **packages/inventory-management/tests/Unit/Models/StockBalanceTest.php** - 8 tests for balance calculations
- **packages/inventory-management/tests/Unit/Enums/ValuationMethodTest.php** - Enum value and label tests

### Tests (Feature)

- **packages/inventory-management/tests/Feature/Database/InventoryItemsMigrationTest.php** - Migration schema validation
- **packages/inventory-management/tests/Feature/Repositories/InventoryItemRepositoryTest.php** - 8 tests for item repository
- **packages/inventory-management/tests/Feature/Repositories/StockBalanceRepositoryTest.php** - 7 tests for balance repository

---

## 6. Testing

### Unit Tests (16 tests)

- **TEST-001**: InventoryItem creation with required fields validates correctly
- **TEST-002**: InventoryItem relationships (category, baseUom, stockBalances) load correctly
- **TEST-003**: getTotalStockQuantity() sums all warehouse quantities for item
- **TEST-004**: getAvailableQuantity($warehouseId) returns correct available stock
- **TEST-005**: isLowStock() returns true when any warehouse balance < reorder_point
- **TEST-006**: canBeDeleted() returns false when item has movement history
- **TEST-007**: ValuationMethod enum casts to/from database correctly
- **TEST-008**: metadata JSONB column stores and retrieves array data
- **TEST-009**: StockBalance creation validates unique constraint (item+warehouse+batch+serial)
- **TEST-010**: available_quantity generated column equals quantity - reserved_quantity
- **TEST-011**: reserve($quantity) increases reserved_quantity correctly
- **TEST-012**: release($quantity) decreases reserved_quantity correctly
- **TEST-013**: adjust($quantity) updates quantity correctly
- **TEST-014**: CHECK constraint prevents negative quantity values
- **TEST-015**: CHECK constraint prevents reserved_quantity > quantity
- **TEST-016**: ValuationMethod::label() returns correct human-readable names

### Feature Tests (15 tests)

- **TEST-017**: Migration creates item_categories table with correct schema
- **TEST-018**: Migration creates inventory_items table with correct indexes
- **TEST-019**: Migration creates stock_balances table with generated column
- **TEST-020**: InventoryItemRepository::findById() returns correct item with tenant scope
- **TEST-021**: InventoryItemRepository::findByCode() uses Redis cache (verify cache hit)
- **TEST-022**: InventoryItemRepository::create() creates item and logs activity
- **TEST-023**: InventoryItemRepository::update() updates item and invalidates cache
- **TEST-024**: InventoryItemRepository::delete() throws exception when item has movements
- **TEST-025**: InventoryItemRepository::paginate() supports category filter
- **TEST-026**: InventoryItemRepository::paginate() supports search query filter
- **TEST-027**: InventoryItemRepository::getLowStockItems() returns items below reorder point
- **TEST-028**: StockBalanceRepository::findByItemAndWarehouse() uses Redis cache correctly
- **TEST-029**: StockBalanceRepository::getItemBalances() eager loads relationships
- **TEST-030**: StockBalanceRepository::updateQuantity() invalidates cache after update
- **TEST-031**: StockBalanceRepository::createOrUpdate() creates new or updates existing balance

### Integration Tests (5 tests)

- **TEST-032**: ItemCategory parent-child relationships maintain referential integrity
- **TEST-033**: InventoryItem deletion prevented by observer when movements exist
- **TEST-034**: StockBalance query performance < 50ms for item-warehouse lookup (measure with microtime)
- **TEST-035**: Redis cache TTL expires after 1 hour for stock balance queries
- **TEST-036**: Multi-tenant isolation prevents cross-tenant data access (stock balances)

### Performance Tests (2 tests)

- **TEST-037**: Stock balance query for 1000 items completes in < 50ms (PR-INV-001)
- **TEST-038**: Database handles 1M+ inventory_items with efficient indexing (insert 1M records, query by code in < 50ms)

---

## 7. Risks & Assumptions

### Risks

- **RISK-001**: PostgreSQL GENERATED ALWAYS AS stored columns require PostgreSQL 12+. Mitigation: Document minimum version requirement, add version check in migration.
- **RISK-002**: Redis cache inconsistency if stock balances updated outside application (direct DB access). Mitigation: Use database triggers to invalidate cache, or document "do not modify stock_balances directly".
- **RISK-003**: Large metadata JSONB columns may slow down queries. Mitigation: Index specific JSONB keys using GIN indexes if querying metadata becomes common.
- **RISK-004**: Composite unique constraint on stock_balances (item+warehouse+batch+serial) may cause conflicts in high-concurrency scenarios. Mitigation: Use optimistic locking in PLAN02 (stock movements).
- **RISK-005**: Warehouse-specific access control not implemented in this plan. Mitigation: Add warehouse access validation in PLAN03 (API layer).

### Assumptions

- **ASSUMPTION-001**: Warehouses table exists in SUB15 (Backoffice) package before this implementation.
- **ASSUMPTION-002**: UOMs table exists in SUB06 package with seeded base units (ea, kg, m, L).
- **ASSUMPTION-003**: Redis is available and configured in Laravel application for caching.
- **ASSUMPTION-004**: Tenants table exists with UUID primary key (from SUB01 Multi-Tenancy).
- **ASSUMPTION-005**: Stock movements will be implemented in PLAN02, so movement history checks return false in this plan.
- **ASSUMPTION-006**: Item codes are managed manually or via external system (no auto-generation in this plan).
- **ASSUMPTION-007**: Batch expiry dates and serial warranty tracking will be added in PLAN02 (not in this foundation plan).

---

## 8. KIV for Future Implementations

### KIV-001: Item Images and Attachments
Support for uploading item images, technical drawings, and documentation. Would require file storage integration (S3/local) and images table with item_id FK. Not critical for MVP.

### KIV-002: Multi-Currency Costing
Support for items with costs in different currencies (standard_cost_currency, latest_purchase_cost_currency). Requires integration with SUB08 (General Ledger) exchange rates. Can be added when multi-currency GL is implemented.

### KIV-003: Alternate UOM Support
Allow items to be tracked in multiple UOMs with conversion factors (e.g., sell in boxes, track in pieces). Requires item_uoms table with conversion factors. Add when complex UOM scenarios arise.

### KIV-004: Item Variants/Configurations
Support for items with variants (size, color, style) using parent-child relationships. Requires variant_attributes table and parent_item_id FK on inventory_items. Add when variant management is needed.

### KIV-005: Location Tracking Within Warehouses
Support for bin/rack/shelf location tracking (e.g., Warehouse A, Aisle 1, Rack 3, Shelf B). Requires warehouse_locations table and location_id FK on stock_balances. Add when warehouse management complexity increases.

### KIV-006: Kit/Bundle Items
Support for items composed of other items (bill of materials). Requires item_components table with parent_item_id and child_item_id. Add when manufacturing/assembly features are needed.

### KIV-007: Automated Reorder Point Calculation
Machine learning-based reorder point recommendations based on historical demand patterns. Requires consumption history analysis. Add when sufficient transaction history exists.

### KIV-008: Item Lifecycle Status
Track items through lifecycle (New, Active, Obsolete, Discontinued) with status transitions. Requires item_status enum and status_changed_at timestamp. Add when product lifecycle management is needed.

---

## 9. Related PRD / Further Reading

### Related PRDs

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md) - Complete feature set and milestones
- **PRD01-SUB14 (Inventory Management)**: [../prd/prd-01/PRD01-SUB14-INVENTORY-MANAGEMENT.md](../prd/prd-01/PRD01-SUB14-INVENTORY-MANAGEMENT.md) - Complete inventory requirements
- **PRD01-SUB06 (UOM)**: [../prd/prd-01/PRD01-SUB06-UOM-MANAGEMENT.md](../prd/prd-01/PRD01-SUB06-UOM-MANAGEMENT.md) - Unit of measure system
- **PRD01-SUB15 (Backoffice)**: [../prd/prd-01/PRD01-SUB15-BACKOFFICE.md](../prd/prd-01/PRD01-SUB15-BACKOFFICE.md) - Warehouse management

### Implementation Plans (Same Module)

- **PRD01-SUB14-PLAN02**: Stock movements (receipt, issue, transfer, adjustment) - Next plan
- **PRD01-SUB14-PLAN03**: Inventory valuation and reporting - Final plan

### Architecture Documentation

- **Repository Pattern**: [../../CODING_GUIDELINES.md#repository-pattern](../../CODING_GUIDELINES.md#repository-pattern)
- **Package Decoupling**: [../architecture/PACKAGE-DECOUPLING-STRATEGY.md](../architecture/PACKAGE-DECOUPLING-STRATEGY.md)
- **Laravel Actions**: [../../.github/copilot-instructions.md#action-pattern](../../.github/copilot-instructions.md#action-pattern)
- **Multi-Tenancy**: [../../docs/SANCTUM_AUTHENTICATION.md](../../docs/SANCTUM_AUTHENTICATION.md)

### External Documentation

- **PostgreSQL Generated Columns**: https://www.postgresql.org/docs/14/ddl-generated-columns.html
- **Laravel Eloquent Relationships**: https://laravel.com/docs/12.x/eloquent-relationships
- **Redis Caching**: https://laravel.com/docs/12.x/cache#redis
- **Pest Testing**: https://pestphp.com/docs/
