---
plan: Inventory Management Valuation & Reporting - FIFO/LIFO/Average, Reports, API
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, inventory, valuation, reporting, api, rest, milestone-5]
---

# Inventory Management Valuation & Reporting - FIFO/LIFO/Average, Reports, API

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

## Introduction

This implementation plan completes the Inventory Management module (PRD01-SUB14) by implementing inventory valuation methods (FIFO, LIFO, Weighted Average), comprehensive reporting capabilities, and RESTful API endpoints for external system integration. This plan builds upon the foundation (PLAN01) and stock movements (PLAN02) to provide complete inventory management functionality.

**Key Deliverables:**
- FIFO/LIFO valuation with cost layer tracking
- Weighted Average cost calculation service
- Inventory valuation reports with drill-down capabilities
- Stock status, reorder, and aging reports
- Complete REST API (26 endpoints) with OpenAPI documentation
- Integration with General Ledger for inventory GL postings
- Performance-optimized queries (< 100ms balance queries, < 5s valuation calculation)

**Success Criteria:**
- Valuation calculations match accounting standards (IAS 2)
- Inventory valuation for 100k items completes in < 5 seconds (PR-INV-003)
- Stock balance queries execute in < 50ms (PR-INV-001)
- All API endpoints documented with OpenAPI 3.0 specification
- GL integration posts correct inventory asset and COGS entries
- Reports export to Excel with formatting

---

## 1. Requirements & Constraints

### Requirements from PRD

- **FR-INV-007**: Calculate inventory valuation using FIFO, LIFO, or Weighted Average methods
- **FR-INV-008**: Generate automated reorder recommendations based on min/max levels
- **FR-INV-009**: Support cycle counting and physical inventory reconciliation
- **FR-INV-010**: Provide inventory aging report (30/60/90 days)
- **IR-INV-004**: Integrate with SUB08 (General Ledger) for inventory valuation GL entries
- **PR-INV-001**: Stock balance query must return in < 50ms per item
- **PR-INV-003**: Inventory valuation calculation must complete in < 5 seconds for 100k items
- **SCR-INV-001**: Support 1,000,000+ item records with efficient indexing

### Constraints

- **CON-001**: Valuation method set per item (cannot mix FIFO/LIFO/Average for same item)
- **CON-002**: Cost layers (FIFO/LIFO) track by item + warehouse + batch combination
- **CON-003**: Weighted Average recalculated after each receipt (not periodically)
- **CON-004**: GL entries post in base currency (multi-currency in GL, not inventory)
- **CON-005**: Reports must support tenant isolation (no cross-tenant data)
- **CON-006**: API responses follow JSON:API specification format
- **CON-007**: All monetary values rounded to 2 decimal places (accounting standard)

### Guidelines

- **GUD-001**: Use brick/math for precise decimal calculations (no floating point errors)
- **GUD-002**: Cache valuation calculations with 15-minute TTL (invalidate on movement)
- **GUD-003**: Use repository pattern for all database access (no raw queries in services)
- **GUD-004**: Follow RESTful API conventions (proper HTTP verbs and status codes)
- **GUD-005**: Use API Resources for response transformation (not manual array building)
- **GUD-006**: Use Form Requests for validation (not inline controller validation)
- **GUD-007**: Export reports using PhpSpreadsheet (not manual CSV generation)
- **GUD-008**: Document all API endpoints with OpenAPI/Swagger annotations

### Patterns to Follow

- **PAT-001**: Service layer pattern for complex business logic (valuation calculations)
- **PAT-002**: Strategy pattern for valuation methods (FIFO/LIFO/Average strategies)
- **PAT-003**: Builder pattern for complex report queries
- **PAT-004**: Resource pattern for API response transformation
- **PAT-005**: Gate pattern for authorization (view-inventory, manage-inventory)
- **PAT-006**: Rate limiting for API endpoints (60 requests per minute per user)

---

## 2. Implementation Steps

### GOAL-001: Implement FIFO and LIFO Valuation with Cost Layer Tracking

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-INV-007 | FIFO and LIFO inventory valuation methods | | |
| PR-INV-003 | Valuation calculation performance < 5s for 100k items | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000008_create_inventory_valuation_layers_table.php`:<br>- `id` BIGSERIAL PRIMARY KEY<br>- `tenant_id` UUID NOT NULL FK to tenants(id) ON DELETE CASCADE<br>- `item_id` BIGINT NOT NULL FK to inventory_items(id) ON DELETE RESTRICT<br>- `warehouse_id` BIGINT NOT NULL FK to warehouses(id) ON DELETE RESTRICT<br>- `batch_number` VARCHAR(100) NULL<br>- `layer_date` DATE NOT NULL<br>- `receipt_quantity` DECIMAL(15,4) NOT NULL<br>- `remaining_quantity` DECIMAL(15,4) NOT NULL<br>- `unit_cost` DECIMAL(15,2) NOT NULL<br>- `total_cost` DECIMAL(15,2) GENERATED ALWAYS AS (remaining_quantity * unit_cost) STORED<br>- `created_at`, `updated_at` TIMESTAMP<br>- INDEX idx_valuation_tenant (tenant_id)<br>- INDEX idx_valuation_item (item_id)<br>- INDEX idx_valuation_warehouse (warehouse_id)<br>- INDEX idx_valuation_date (layer_date)<br>- Composite index on (item_id, warehouse_id, batch_number, layer_date) for FIFO/LIFO queries<br>- CHECK (remaining_quantity >= 0)<br>- CHECK (remaining_quantity <= receipt_quantity) | | |
| TASK-002 | Create model `src/Models/InventoryValuationLayer.php`:<br>- Use traits: BelongsToTenant<br>- Fillable: item_id, warehouse_id, batch_number, layer_date, receipt_quantity, remaining_quantity, unit_cost<br>- Casts: receipt_quantity => 'decimal:4', remaining_quantity => 'decimal:4', unit_cost => 'decimal:2', total_cost => 'decimal:2', layer_date => 'date'<br>- Relationships: item(), warehouse()<br>- Scopes: scopeByItem(), scopeByWarehouse(), scopeWithRemainingStock()<br>- Appends: ['total_cost']<br>- Methods: consume($quantity): float (returns COGS, updates remaining_quantity) | | |
| TASK-003 | Create contract `src/Contracts/ValuationServiceContract.php`:<br>```php<br>interface ValuationServiceContract {<br>    public function calculateIssueValue(InventoryItem $item, int $warehouseId, float $quantity, ?string $batchNumber = null): float;<br>    public function recordReceiptCost(InventoryItem $item, int $warehouseId, float $quantity, float $unitCost, ?string $batchNumber = null, Date $receiptDate): void;<br>    public function getTotalInventoryValue(int $warehouseId = null): float;<br>    public function getItemValuation(int $itemId, int $warehouseId = null): array;<br>}<br>```<br>All methods with complete PHPDoc | | |
| TASK-004 | Create service `src/Services/FIFOValuationService.php`:<br>```php<br>class FIFOValuationService implements ValuationServiceContract {<br>    use AsAction;<br>    public function __construct(<br>        private readonly InventoryValuationLayerRepository $layerRepository<br>    ) {}<br>    public function calculateIssueValue(<br>        InventoryItem $item,<br>        int $warehouseId,<br>        float $quantity,<br>        ?string $batchNumber = null<br>    ): float {<br>        // 1. Get oldest layers (layer_date ASC) with remaining_quantity > 0<br>        // 2. Consume layers FIFO order until quantity satisfied<br>        // 3. Calculate COGS: sum(consumed_quantity * unit_cost per layer)<br>        // 4. Update remaining_quantity on each layer<br>        // 5. Return total COGS<br>        $layers = $this->layerRepository->getOldestLayers($item, $warehouseId, $batchNumber);<br>        $remainingQty = $quantity;<br>        $totalCogs = new BigDecimal('0');<br>        foreach ($layers as $layer) {<br>            if ($remainingQty <= 0) break;<br>            $consumedQty = min($remainingQty, $layer->remaining_quantity);<br>            $cogs = BigDecimal::of($consumedQty)->multipliedBy($layer->unit_cost);<br>            $totalCogs = $totalCogs->plus($cogs);<br>            $layer->remaining_quantity -= $consumedQty;<br>            $layer->save();<br>            $remainingQty -= $consumedQty;<br>        }<br>        return $totalCogs->toFloat();<br>    }<br>}<br>```<br>Use brick/math for all calculations (no float arithmetic) | | |
| TASK-005 | Create service `src/Services/LIFOValuationService.php`:<br>```php<br>class LIFOValuationService implements ValuationServiceContract {<br>    public function calculateIssueValue(<br>        InventoryItem $item,<br>        int $warehouseId,<br>        float $quantity,<br>        ?string $batchNumber = null<br>    ): float {<br>        // Similar to FIFO but get newest layers (layer_date DESC)<br>        $layers = $this->layerRepository->getNewestLayers($item, $warehouseId, $batchNumber);<br>        // ... consume from newest to oldest<br>    }<br>}<br>```<br>Consume layers in reverse chronological order | | |
| TASK-006 | Create repository `src/Repositories/InventoryValuationLayerRepository.php`:<br>- Method: getOldestLayers($item, $warehouseId, $batchNumber) - ORDER BY layer_date ASC<br>- Method: getNewestLayers($item, $warehouseId, $batchNumber) - ORDER BY layer_date DESC<br>- Method: createLayer($data) - Create new cost layer<br>- Method: updateLayerQuantity($layerId, $newQuantity) - Update remaining_quantity<br>- Method: getItemLayers($itemId, $warehouseId) - Get all layers for reporting<br>All queries with tenant scope | | |
| TASK-007 | Update `src/Actions/IssueStockAction.php` from PLAN02:<br>Add FIFO/LIFO valuation calculation:<br>```php<br>// After creating StockMovement record<br>if ($item->valuation_method === ValuationMethod::FIFO) {<br>    $cogs = $this->fifoValuation->calculateIssueValue($item, $warehouseId, $quantity, $batchNumber);<br>    $movement->unit_cost = $cogs / $quantity;<br>    $movement->total_cost = $cogs;<br>    $movement->save();<br>}<br>```<br>Store calculated COGS on movement record | | |

### GOAL-002: Implement Weighted Average Valuation and Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-INV-007 | Weighted Average inventory valuation | | |
| IR-INV-004 | Integration with General Ledger for GL postings | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-008 | Create service `src/Services/WeightedAverageValuationService.php`:<br>```php<br>class WeightedAverageValuationService implements ValuationServiceContract {<br>    public function recordReceiptCost(<br>        InventoryItem $item,<br>        int $warehouseId,<br>        float $quantity,<br>        float $unitCost,<br>        ?string $batchNumber,<br>        Date $receiptDate<br>    ): void {<br>        // 1. Get current balance and weighted average cost<br>        $balance = $this->balanceRepository->findByItemAndWarehouse($item->id, $warehouseId, $batchNumber);<br>        $currentQty = $balance ? $balance->quantity : 0;<br>        $currentAvgCost = $balance ? $balance->average_unit_cost : 0;<br>        // 2. Calculate new weighted average<br>        // Formula: ((currentQty * currentAvgCost) + (receiptQty * receiptCost)) / (currentQty + receiptQty)<br>        $totalCurrentValue = BigDecimal::of($currentQty)->multipliedBy($currentAvgCost);<br>        $totalReceiptValue = BigDecimal::of($quantity)->multipliedBy($unitCost);<br>        $totalValue = $totalCurrentValue->plus($totalReceiptValue);<br>        $totalQty = BigDecimal::of($currentQty)->plus($quantity);<br>        $newAvgCost = $totalValue->dividedBy($totalQty, 4, RoundingMode::HALF_UP);<br>        // 3. Update balance with new average cost<br>        $balance->average_unit_cost = $newAvgCost->toFloat();<br>        $balance->save();<br>    }<br>    public function calculateIssueValue(/* ... */): float {<br>        // Simply use current weighted average cost<br>        $balance = $this->balanceRepository->findByItemAndWarehouse($item->id, $warehouseId, $batchNumber);<br>        return $quantity * $balance->average_unit_cost;<br>    }<br>}<br>```<br>Recalculate average on every receipt | | |
| TASK-009 | Add average_unit_cost column to stock_balances:<br>Create migration `2025_01_01_000009_add_average_cost_to_stock_balances.php`:<br>- Add `average_unit_cost` DECIMAL(15,2) DEFAULT 0<br>- Update existing records to set average_unit_cost = 0 (will be calculated on next receipt)<br>- Add INDEX idx_stock_balances_avg_cost (average_unit_cost) for valuation reports | | |
| TASK-010 | Create valuation service factory `src/Services/InventoryValuationServiceFactory.php`:<br>```php<br>class InventoryValuationServiceFactory {<br>    public function __construct(<br>        private readonly FIFOValuationService $fifo,<br>        private readonly LIFOValuationService $lifo,<br>        private readonly WeightedAverageValuationService $weightedAvg<br>    ) {}<br>    public function getService(ValuationMethod $method): ValuationServiceContract {<br>        return match($method) {<br>            ValuationMethod::FIFO => $this->fifo,<br>            ValuationMethod::LIFO => $this->lifo,<br>            ValuationMethod::WEIGHTED_AVERAGE => $this->weightedAvg,<br>        };<br>    }<br>}<br>```<br>Strategy pattern for valuation method selection | | |
| TASK-011 | Update `src/Actions/ReceiveStockAction.php` from PLAN02:<br>Add valuation layer/average update:<br>```php<br>// After updating stock balance<br>$valuationService = $this->valuationFactory->getService($item->valuation_method);<br>$valuationService->recordReceiptCost(<br>    $item,<br>    $warehouseId,<br>    $quantity,<br>    $unitCost,<br>    $batchNumber,<br>    $movementDate<br>);<br>```<br>Record cost on receipt for all valuation methods | | |
| TASK-012 | Create action `src/Actions/PostInventoryGLEntryAction.php`:<br>```php<br>class PostInventoryGLEntryAction {<br>    use AsAction;<br>    public function __construct(<br>        private readonly GLEntryRepositoryContract $glRepository<br>    ) {}<br>    public function handle(StockMovement $movement, float $value): void {<br>        // Post GL entry for inventory transaction<br>        if ($movement->isReceipt()) {<br>            // DR: Inventory Asset, CR: GRN Clearing/AP<br>            $this->glRepository->createEntry([<br>                'entry_date' => $movement->movement_date,<br>                'reference' => $movement->document_number,<br>                'lines' => [<br>                    ['account_code' => '1200', 'debit' => $value, 'credit' => 0],<br>                    ['account_code' => '2100', 'debit' => 0, 'credit' => $value],<br>                ],<br>            ]);<br>        } elseif ($movement->isIssue()) {<br>            // DR: COGS, CR: Inventory Asset<br>            $this->glRepository->createEntry([<br>                'entry_date' => $movement->movement_date,<br>                'reference' => $movement->document_number,<br>                'lines' => [<br>                    ['account_code' => '5100', 'debit' => $value, 'credit' => 0],<br>                    ['account_code' => '1200', 'debit' => 0, 'credit' => $value],<br>                ],<br>            ]);<br>        }<br>        // Note: Transfers and adjustments may not post GL entries<br>    }<br>}<br>```<br>Integrate with SUB08 (General Ledger) | | |
| TASK-013 | Create listener `src/Listeners/PostInventoryGLListener.php`:<br>```php<br>#[Listen(StockReceivedEvent::class)]<br>#[Listen(StockIssuedEvent::class)]<br>class PostInventoryGLListener {<br>    public function __construct(<br>        private readonly PostInventoryGLEntryAction $postGLEntry<br>    ) {}<br>    public function handle(StockReceivedEvent|StockIssuedEvent $event): void {<br>        // Calculate movement value<br>        $value = $event->movement->total_cost ?? ($event->movement->quantity * $event->item->standard_cost);<br>        // Post GL entry<br>        $this->postGLEntry->handle($event->movement, $value);<br>    }<br>}<br>```<br>Auto-post GL entries on inventory movements | | |

### GOAL-003: Implement Inventory Reporting Services

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-INV-008 | Automated reorder recommendations | | |
| FR-INV-009, FR-INV-010 | Cycle counting and aging reports | | |
| PR-INV-003 | Valuation calculation < 5s for 100k items | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-014 | Create service `src/Services/InventoryReportingService.php`:<br>```php<br>class InventoryReportingService {<br>    public function getStockStatusReport(array $filters = []): Collection {<br>        // Query: item_code, item_name, warehouse, quantity, reserved_quantity, available_quantity<br>        // Filters: warehouse_id, category_id, is_active<br>        // Group by item + warehouse<br>        // Include: valuation (quantity * unit_cost)<br>        return $this->itemRepository->getStockStatus($filters);<br>    }<br>    public function getReorderRecommendations(): Collection {<br>        // Query items where available_quantity < reorder_point<br>        // Calculate recommended order quantity (reorder_quantity)<br>        // Include: item, warehouse, current_qty, reorder_point, reorder_qty, last_receipt_date<br>        return $this->balanceRepository->getLowStockItems();<br>    }<br>    public function getInventoryAgingReport(int $warehouseId = null): Collection {<br>        // Calculate age buckets: 0-30 days, 31-60 days, 61-90 days, >90 days<br>        // Group by item using last_movement_date<br>        // Include: item_code, quantity, valuation, age_bucket<br>        $items = $this->balanceRepository->getItemsWithMovementDate($warehouseId);<br>        return $items->groupBy(function ($item) {<br>            $days = now()->diffInDays($item->last_movement_date);<br>            if ($days <= 30) return '0-30 days';<br>            if ($days <= 60) return '31-60 days';<br>            if ($days <= 90) return '61-90 days';<br>            return '>90 days';<br>        });<br>    }<br>    public function getInventoryValuationReport(int $warehouseId = null): array {<br>        // Calculate total valuation by item using valuation method<br>        // Support filtering by warehouse, category<br>        // Performance target: < 5s for 100k items (use aggregation queries)<br>        $items = $this->itemRepository->getActiveItems();<br>        $totalValue = new BigDecimal('0');<br>        foreach ($items as $item) {<br>            $valuationService = $this->valuationFactory->getService($item->valuation_method);<br>            $value = $valuationService->getItemValuation($item->id, $warehouseId);<br>            $totalValue = $totalValue->plus($value['total_value']);<br>        }<br>        return [<br>            'total_inventory_value' => $totalValue->toFloat(),<br>            'items' => $items,<br>            'generated_at' => now(),<br>        ];<br>    }<br>}<br>```<br>Implement all report methods with caching (15 min TTL) | | |
| TASK-015 | Create service `src/Services/InventoryExportService.php`:<br>```php<br>class InventoryExportService {<br>    public function exportStockStatusToExcel(Collection $data): string {<br>        // Use PhpSpreadsheet to generate Excel file<br>        $spreadsheet = new Spreadsheet();<br>        $sheet = $spreadsheet->getActiveSheet();<br>        $sheet->setTitle('Stock Status');<br>        // Headers: Item Code, Item Name, Warehouse, Quantity, Reserved, Available, Unit Cost, Total Value<br>        $sheet->fromArray([<br>            ['Item Code', 'Item Name', 'Warehouse', 'Quantity', 'Reserved', 'Available', 'Unit Cost', 'Total Value'],<br>            ...$data->map(fn($item) => [<br>                $item->item_code,<br>                $item->item_name,<br>                $item->warehouse_name,<br>                $item->quantity,<br>                $item->reserved_quantity,<br>                $item->available_quantity,<br>                $item->unit_cost,<br>                $item->quantity * $item->unit_cost,<br>            ])->toArray(),<br>        ]);<br>        // Format as currency, add totals row<br>        $writer = new Xlsx($spreadsheet);<br>        $filePath = storage_path('app/exports/stock-status-' . now()->format('YmdHis') . '.xlsx');<br>        $writer->save($filePath);<br>        return $filePath;<br>    }<br>}<br>```<br>Excel export with formatting (currency, bold headers, totals) | | |
| TASK-016 | Add caching to report queries:<br>```php<br>public function getStockStatusReport(array $filters = []): Collection {<br>    $cacheKey = 'stock_status_' . md5(json_encode($filters));<br>    return Cache::remember($cacheKey, 900, function () use ($filters) {<br>        return $this->itemRepository->getStockStatus($filters);<br>    });<br>}<br>```<br>Cache all reports for 15 minutes (900 seconds)<br>Invalidate cache on stock movements | | |
| TASK-017 | Create report query builder `src/Repositories/StockBalanceReportRepository.php`:<br>- Method: getStockStatus($filters) - Complex query with joins, aggregations<br>- Method: getLowStockItems() - Items below reorder point<br>- Method: getItemsWithMovementDate($warehouseId) - For aging report<br>- Method: getMovementHistory($itemId, $dateRange) - Audit trail<br>All methods optimized with proper indexes and eager loading | | |

### GOAL-004: Implement RESTful API with OpenAPI Documentation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| API Requirements | Complete REST API for inventory management | | |
| PR-INV-001 | Stock balance query < 50ms | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-018 | Create controller `src/Http/Controllers/Api/V1/InventoryItemController.php`:<br>```php<br>#[Prefix('api/v1/inventory')]<br>#[Middleware(['auth:sanctum', 'tenant'])]<br>#[Resource('items')]<br>class InventoryItemController extends Controller {<br>    public function __construct(<br>        private readonly InventoryItemRepositoryContract $repository<br>    ) {}<br>    #[Get('/items', name: 'inventory.items.index')]<br>    public function index(Request $request): AnonymousResourceCollection {<br>        $validated = $request->validate([<br>            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],<br>            'category_id' => ['nullable', 'integer', 'exists:item_categories,id'],<br>            'search' => ['nullable', 'string', 'max:255'],<br>            'is_active' => ['nullable', 'boolean'],<br>        ]);<br>        $items = $this->repository->paginate($validated['per_page'] ?? 15, $validated);<br>        return InventoryItemResource::collection($items);<br>    }<br>    // ... other CRUD methods<br>}<br>```<br>Use route attributes for all endpoints<br>15 endpoints: index, store, show, update, destroy, balances, movements, valuation, batch actions | | |
| TASK-019 | Create controller `src/Http/Controllers/Api/V1/StockBalanceController.php`:<br>```php<br>#[Prefix('api/v1/inventory')]<br>#[Middleware(['auth:sanctum', 'tenant'])]<br>class StockBalanceController extends Controller {<br>    #[Get('/stock-balances', name: 'inventory.balances.index')]<br>    public function index(Request $request): AnonymousResourceCollection {<br>        // Query stock balances with filters: item_id, warehouse_id, date<br>        // Support pagination<br>        // Performance: < 50ms per query (PR-INV-001)<br>    }<br>    #[Get('/items/{id}/balances', name: 'inventory.items.balances')]<br>    public function itemBalances(int $id): AnonymousResourceCollection {<br>        // Get all warehouse balances for specific item<br>    }<br>    #[Get('/warehouses/{id}/stock', name: 'inventory.warehouses.stock')]<br>    public function warehouseStock(int $id): AnonymousResourceCollection {<br>        // Get all item balances in specific warehouse<br>    }<br>}<br>```<br>3 endpoints for balance queries | | |
| TASK-020 | Create controller `src/Http/Controllers/Api/V1/StockMovementController.php`:<br>```php<br>#[Prefix('api/v1/inventory')]<br>#[Middleware(['auth:sanctum', 'tenant'])]<br>class StockMovementController extends Controller {<br>    #[Post('/movements/receipt', name: 'inventory.movements.receipt')]<br>    public function receipt(StockReceiptRequest $request): JsonResponse {<br>        // Use ReceiveStockAction<br>    }<br>    #[Post('/movements/issue', name: 'inventory.movements.issue')]<br>    public function issue(StockIssueRequest $request): JsonResponse {<br>        // Use IssueStockAction<br>    }<br>    #[Post('/movements/transfer', name: 'inventory.movements.transfer')]<br>    public function transfer(StockTransferRequest $request): JsonResponse {<br>        // Use TransferStockAction<br>    }<br>    #[Post('/movements/adjustment', name: 'inventory.movements.adjustment')]<br>    public function adjustment(StockAdjustmentRequest $request): JsonResponse {<br>        // Use AdjustStockAction<br>    }<br>    #[Get('/movements', name: 'inventory.movements.index')]<br>    public function index(Request $request): AnonymousResourceCollection {<br>        // List movements with filtering<br>    }<br>}<br>```<br>5 endpoints for movements | | |
| TASK-021 | Create controller `src/Http/Controllers/Api/V1/InventoryReportController.php`:<br>```php<br>#[Prefix('api/v1/inventory')]<br>#[Middleware(['auth:sanctum', 'tenant'])]<br>class InventoryReportController extends Controller {<br>    #[Get('/reports/stock-status', name: 'inventory.reports.stock-status')]<br>    public function stockStatus(Request $request): JsonResponse {<br>        // Use InventoryReportingService::getStockStatusReport()<br>    }<br>    #[Get('/reports/reorder-recommendations', name: 'inventory.reports.reorder')]<br>    public function reorderRecommendations(): JsonResponse {<br>        // Use InventoryReportingService::getReorderRecommendations()<br>    }<br>    #[Get('/reports/aging', name: 'inventory.reports.aging')]<br>    public function agingReport(Request $request): JsonResponse {<br>        // Use InventoryReportingService::getInventoryAgingReport()<br>    }<br>    #[Get('/reports/valuation', name: 'inventory.reports.valuation')]<br>    public function valuationReport(Request $request): JsonResponse {<br>        // Use InventoryReportingService::getInventoryValuationReport()<br>    }<br>    #[Get('/reports/export/stock-status', name: 'inventory.reports.export.stock-status')]<br>    public function exportStockStatus(Request $request): Response {<br>        // Use InventoryExportService, return file download<br>    }<br>}<br>```<br>5 endpoints for reports | | |
| TASK-022 | Create Form Requests for validation:<br>- `src/Http/Requests/StoreInventoryItemRequest.php` - Validate item creation<br>- `src/Http/Requests/UpdateInventoryItemRequest.php` - Validate item updates<br>- `src/Http/Requests/StockReceiptRequest.php` - Validate stock receipt<br>- `src/Http/Requests/StockIssueRequest.php` - Validate stock issue<br>- `src/Http/Requests/StockTransferRequest.php` - Validate transfer<br>- `src/Http/Requests/StockAdjustmentRequest.php` - Validate adjustment<br>All with complete validation rules, authorization checks, and custom error messages | | |
| TASK-023 | Create API Resources for transformation:<br>- `src/Http/Resources/InventoryItemResource.php` - Transform item<br>- `src/Http/Resources/StockBalanceResource.php` - Transform balance<br>- `src/Http/Resources/StockMovementResource.php` - Transform movement<br>- `src/Http/Resources/InventoryItemCollection.php` - Collection with pagination meta<br>All following JSON:API specification format | | |
| TASK-024 | Add OpenAPI documentation annotations:<br>```php<br>/**<br> * @OA\Get(<br> *     path="/api/v1/inventory/items",<br> *     tags={"Inventory Items"},<br> *     summary="List inventory items",<br> *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer")),<br> *     @OA\Parameter(name="category_id", in="query", description="Filter by category", @OA\Schema(type="integer")),<br> *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/InventoryItemCollection")),<br> *     @OA\Response(response=401, description="Unauthenticated"),<br> *     security={{"bearerAuth": {}}}<br> * )<br> */<br>```<br>Document all 26 API endpoints with request/response schemas | | |

### GOAL-005: Comprehensive Testing and Performance Validation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PR-INV-001, PR-INV-003 | Performance requirements validation | | |
| GUD-006 | Complete test coverage | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-025 | Create unit test `tests/Unit/Services/FIFOValuationServiceTest.php`:<br>- Test: FIFO consumes oldest layers first<br>- Test: Multiple layers consumed for large issue<br>- Test: COGS calculation matches manual calculation<br>- Test: Remaining quantity updated correctly on each layer<br>- Test: Layer exhausted (remaining_quantity = 0) after full consumption<br>- Test: brick/math precision (no floating point errors)<br>Use Pest syntax, 8 tests | | |
| TASK-026 | Create unit test `tests/Unit/Services/LIFOValuationServiceTest.php`:<br>- Test: LIFO consumes newest layers first<br>- Test: Layer selection in reverse chronological order<br>- Test: COGS differs from FIFO for same movements<br>6 tests similar to FIFO | | |
| TASK-027 | Create unit test `tests/Unit/Services/WeightedAverageValuationServiceTest.php`:<br>- Test: Weighted average calculation after receipt<br>- Test: Issue uses current average cost<br>- Test: Multiple receipts update average correctly<br>- Test: Formula validation: ((qty1 * cost1) + (qty2 * cost2)) / (qty1 + qty2)<br>- Test: Precision maintained with brick/math<br>8 tests | | |
| TASK-028 | Create unit test `tests/Unit/Services/InventoryReportingServiceTest.php`:<br>- Test: Stock status report returns correct data<br>- Test: Reorder recommendations include items below reorder_point<br>- Test: Aging report groups items by age buckets correctly<br>- Test: Valuation report calculates total inventory value<br>- Test: Cache invalidation on stock movement<br>8 tests | | |
| TASK-029 | Create feature test `tests/Feature/Api/InventoryItemControllerTest.php`:<br>- Test: GET /items returns paginated list<br>- Test: POST /items creates item with validation<br>- Test: GET /items/{id} returns item details<br>- Test: PATCH /items/{id} updates item<br>- Test: DELETE /items/{id} soft deletes item<br>- Test: GET /items/{id}/balances returns warehouse balances<br>- Test: Authentication required (401 without token)<br>- Test: Authorization enforced (403 without permission)<br>12 tests for all item endpoints | | |
| TASK-030 | Create feature test `tests/Feature/Api/StockMovementControllerTest.php`:<br>- Test: POST /movements/receipt creates receipt movement<br>- Test: POST /movements/issue creates issue movement<br>- Test: POST /movements/transfer creates two movements<br>- Test: POST /movements/adjustment creates adjustment<br>- Test: GET /movements returns movement history<br>- Test: Validation errors return 422 with details<br>10 tests | | |
| TASK-031 | Create feature test `tests/Feature/Api/InventoryReportControllerTest.php`:<br>- Test: GET /reports/stock-status returns report data<br>- Test: GET /reports/reorder-recommendations returns low stock items<br>- Test: GET /reports/aging returns age buckets<br>- Test: GET /reports/valuation returns total value<br>- Test: GET /reports/export/stock-status returns Excel file<br>8 tests | | |
| TASK-032 | Create performance test `tests/Feature/Performance/InventoryValuationPerformanceTest.php`:<br>- Test: Valuation for 100k items completes in < 5s (PR-INV-003)<br>- Test: Stock balance query for 1000 items < 50ms per item (PR-INV-001)<br>- Test: Report generation with 50k items < 10s<br>Use microtime measurements, assert performance targets<br>5 tests | | |
| TASK-033 | Create integration test `tests/Feature/Integration/InventoryGLIntegrationTest.php`:<br>- Test: Stock receipt posts GL entry (DR Inventory, CR AP)<br>- Test: Stock issue posts GL entry (DR COGS, CR Inventory)<br>- Test: GL entry amounts match valuation calculations<br>- Test: Failed GL posting rolls back inventory movement<br>6 tests | | |

---

## 3. Alternatives

### ALT-001: Use standard average instead of weighted average
**Rejected Reason:** Weighted average is accounting standard (IAS 2) providing more accurate cost representation. Standard average (simple mean) ignores quantity differences between receipts, leading to incorrect valuations.

### ALT-002: Calculate valuation on-demand without caching
**Rejected Reason:** Real-time calculation for 100k items would violate PR-INV-003 (< 5s). Caching with 15-minute TTL balances freshness and performance. Cache invalidates on movements ensuring accuracy.

### ALT-003: Store COGS on movement record only (no valuation layers)
**Rejected Reason:** FIFO/LIFO require cost layer tracking to determine which costs are consumed. Without layers, cannot accurately calculate COGS or audit valuation. Layers essential for compliance.

### ALT-004: Use GraphQL API instead of REST
**Rejected Reason:** REST API is simpler, more widely adopted, and sufficient for inventory operations. GraphQL adds complexity without significant benefit for this use case. JSON:API spec provides structure.

### ALT-005: Generate reports asynchronously with queued jobs
**Rejected Reason:** Reports need to be real-time for operational decisions (reorder recommendations, stock status). Async would delay critical information. Caching provides adequate performance.

---

## 4. Dependencies

### Internal Dependencies (from PLAN01, PLAN02)

- **DEP-001**: InventoryItem model, InventoryItemRepository from PLAN01
- **DEP-002**: StockBalance model, StockBalanceRepository from PLAN01
- **DEP-003**: StockMovement model from PLAN02
- **DEP-004**: ReceiveStockAction, IssueStockAction from PLAN02
- **DEP-005**: StockReceivedEvent, StockIssuedEvent from PLAN02
- **DEP-006**: All enums (ValuationMethod, MovementType) from PLAN01/PLAN02

### External Module Dependencies

- **DEP-007**: SUB08 (General Ledger) - GL entry posting for inventory transactions
- **DEP-008**: SUB07 (Chart of Accounts) - Account codes for inventory asset, COGS accounts
- **DEP-009**: SUB01 (Multi-Tenancy) - Tenant isolation for all queries
- **DEP-010**: SUB02 (Authentication) - Sanctum for API authentication
- **DEP-011**: SUB15 (Backoffice) - Warehouses for stock location

### External Package Dependencies

- **DEP-012**: brick/math ^0.12 (MANDATORY for valuation precision)
- **DEP-013**: phpoffice/phpspreadsheet ^1.29 (Excel export)
- **DEP-014**: darkaonline/l5-swagger ^8.5 (OpenAPI documentation)
- **DEP-015**: lorisleiva/laravel-actions ^2.0
- **DEP-016**: pestphp/pest ^4.0

### Package Dependencies (composer.json)

```json
{
  "require": {
    "brick/math": "^0.12",
    "phpoffice/phpspreadsheet": "^1.29",
    "darkaonline/l5-swagger": "^8.5"
  }
}
```

---

## 5. Files

### Database Migrations

- **database/migrations/2025_01_01_000008_create_inventory_valuation_layers_table.php** - FIFO/LIFO cost layers
- **database/migrations/2025_01_01_000009_add_average_cost_to_stock_balances.php** - Weighted average support

### Models

- **src/Models/InventoryValuationLayer.php** - Cost layer tracking for FIFO/LIFO

### Contracts

- **src/Contracts/ValuationServiceContract.php** - Valuation method interface

### Services

- **src/Services/FIFOValuationService.php** - FIFO valuation implementation
- **src/Services/LIFOValuationService.php** - LIFO valuation implementation
- **src/Services/WeightedAverageValuationService.php** - Weighted Average implementation
- **src/Services/InventoryValuationServiceFactory.php** - Strategy pattern factory
- **src/Services/InventoryReportingService.php** - All report generation
- **src/Services/InventoryExportService.php** - Excel export functionality

### Actions

- **src/Actions/PostInventoryGLEntryAction.php** - GL integration for inventory

### Repositories

- **src/Repositories/InventoryValuationLayerRepository.php** - Layer data access
- **src/Repositories/StockBalanceReportRepository.php** - Report query builder

### Listeners

- **src/Listeners/PostInventoryGLListener.php** - Auto-post GL entries on movements

### Controllers (API)

- **src/Http/Controllers/Api/V1/InventoryItemController.php** - 15 endpoints
- **src/Http/Controllers/Api/V1/StockBalanceController.php** - 3 endpoints
- **src/Http/Controllers/Api/V1/StockMovementController.php** - 5 endpoints
- **src/Http/Controllers/Api/V1/InventoryReportController.php** - 5 endpoints

### Form Requests

- **src/Http/Requests/StoreInventoryItemRequest.php** - Item creation validation
- **src/Http/Requests/UpdateInventoryItemRequest.php** - Item update validation
- **src/Http/Requests/StockReceiptRequest.php** - Receipt validation
- **src/Http/Requests/StockIssueRequest.php** - Issue validation
- **src/Http/Requests/StockTransferRequest.php** - Transfer validation
- **src/Http/Requests/StockAdjustmentRequest.php** - Adjustment validation

### API Resources

- **src/Http/Resources/InventoryItemResource.php** - Item transformation
- **src/Http/Resources/StockBalanceResource.php** - Balance transformation
- **src/Http/Resources/StockMovementResource.php** - Movement transformation
- **src/Http/Resources/InventoryItemCollection.php** - Collection with meta

### Tests (Unit - 30 tests)

- **tests/Unit/Services/FIFOValuationServiceTest.php** - 8 tests
- **tests/Unit/Services/LIFOValuationServiceTest.php** - 6 tests
- **tests/Unit/Services/WeightedAverageValuationServiceTest.php** - 8 tests
- **tests/Unit/Services/InventoryReportingServiceTest.php** - 8 tests

### Tests (Feature - 35 tests)

- **tests/Feature/Api/InventoryItemControllerTest.php** - 12 tests
- **tests/Feature/Api/StockMovementControllerTest.php** - 10 tests
- **tests/Feature/Api/InventoryReportControllerTest.php** - 8 tests
- **tests/Feature/Performance/InventoryValuationPerformanceTest.php** - 5 tests

### Tests (Integration - 6 tests)

- **tests/Feature/Integration/InventoryGLIntegrationTest.php** - 6 tests

---

## 6. Testing

### Unit Tests (30 tests total)

**FIFO Valuation (8 tests):**
- **TEST-001**: FIFO consumes oldest layers in chronological order
- **TEST-002**: Multiple layers consumed for large issue quantity
- **TEST-003**: COGS calculation accuracy (manual verification)
- **TEST-004**: Remaining quantity updated correctly on each layer
- **TEST-005**: Layer exhausted (remaining_quantity = 0) when fully consumed
- **TEST-006**: brick/math precision (no floating point rounding errors)
- **TEST-007**: Batch-specific FIFO (only layers for batch consumed)
- **TEST-008**: Insufficient layers throw exception

**LIFO Valuation (6 tests):**
- **TEST-009**: LIFO consumes newest layers first
- **TEST-010**: Layer selection in reverse chronological order
- **TEST-011**: COGS differs from FIFO for identical movements
- **TEST-012**: Most recent layer exhausted first
- **TEST-013**: Falls back to older layers when recent exhausted
- **TEST-014**: Batch-specific LIFO

**Weighted Average (8 tests):**
- **TEST-015**: Weighted average recalculated after each receipt
- **TEST-016**: Issue uses current weighted average cost
- **TEST-017**: Multiple receipts update average correctly
- **TEST-018**: Formula validation matches manual calculation
- **TEST-019**: Precision maintained with brick/math (4 decimal places)
- **TEST-020**: Zero initial cost handled correctly
- **TEST-021**: Single receipt sets initial average
- **TEST-022**: Average cost stored on stock_balances record

**Reporting Service (8 tests):**
- **TEST-023**: Stock status report includes all active items
- **TEST-024**: Reorder recommendations filter items below reorder_point
- **TEST-025**: Aging report groups items into correct buckets (0-30, 31-60, 61-90, >90 days)
- **TEST-026**: Valuation report calculates total inventory value
- **TEST-027**: Report caching works (cache hit on second call)
- **TEST-028**: Cache invalidated on stock movement
- **TEST-029**: Filters applied correctly (warehouse, category)
- **TEST-030**: Performance: 100k items valuation < 5s (PR-INV-003)

### Feature Tests (35 tests total)

**Item API (12 tests):**
- **TEST-031**: GET /items returns paginated list with correct structure
- **TEST-032**: GET /items filters by category_id
- **TEST-033**: GET /items filters by search query
- **TEST-034**: POST /items creates item with validation
- **TEST-035**: POST /items validates required fields (422 on missing)
- **TEST-036**: GET /items/{id} returns item details
- **TEST-037**: PATCH /items/{id} updates item attributes
- **TEST-038**: DELETE /items/{id} soft deletes item
- **TEST-039**: GET /items/{id}/balances returns warehouse balances
- **TEST-040**: Authentication required (401 without token)
- **TEST-041**: Authorization enforced (403 without view-inventory permission)
- **TEST-042**: Rate limiting enforced (429 after 60 requests/min)

**Movement API (10 tests):**
- **TEST-043**: POST /movements/receipt creates receipt and updates balance
- **TEST-044**: POST /movements/issue creates issue and decrements balance
- **TEST-045**: POST /movements/transfer creates two movements (issue + receipt)
- **TEST-046**: POST /movements/adjustment creates adjustment movement
- **TEST-047**: GET /movements returns movement history with pagination
- **TEST-048**: Validation: missing required fields return 422
- **TEST-049**: Validation: insufficient stock returns 422 with error message
- **TEST-050**: Validation: batch_number required for batch-tracked items
- **TEST-051**: Validation: serial_number required for serial-tracked items
- **TEST-052**: Events dispatched after successful movements

**Report API (8 tests):**
- **TEST-053**: GET /reports/stock-status returns report with correct data
- **TEST-054**: GET /reports/reorder-recommendations returns low stock items
- **TEST-055**: GET /reports/aging returns items grouped by age buckets
- **TEST-056**: GET /reports/valuation returns total inventory value
- **TEST-057**: GET /reports/export/stock-status returns Excel file
- **TEST-058**: Excel file has correct structure (headers, data, formatting)
- **TEST-059**: Report filtering by warehouse works correctly
- **TEST-060**: Report caching improves response time

**Performance (5 tests):**
- **TEST-061**: Stock balance query < 50ms per item (PR-INV-001)
- **TEST-062**: Valuation for 100k items < 5s (PR-INV-003)
- **TEST-063**: Report generation with 50k items < 10s
- **TEST-064**: API endpoint response time < 500ms (90th percentile)
- **TEST-065**: Database query count < 10 per API request (N+1 prevention)

### Integration Tests (6 tests)

**GL Integration (6 tests):**
- **TEST-066**: Stock receipt posts GL entry (DR Inventory 1200, CR AP 2100)
- **TEST-067**: Stock issue posts GL entry (DR COGS 5100, CR Inventory 1200)
- **TEST-068**: GL entry amounts match calculated COGS/valuation
- **TEST-069**: GL entry not posted for transfers (internal movement)
- **TEST-070**: GL entry not posted for adjustments (unless configured)
- **TEST-071**: Failed GL posting rolls back inventory movement (transaction integrity)

---

## 7. Risks & Assumptions

### Risks

- **RISK-001**: FIFO/LIFO cost layer explosion for high-volume items (1000+ receipts). Mitigation: Implement layer consolidation job (monthly) merging old layers with same cost.
- **RISK-002**: Weighted average calculation precision loss with very large quantities. Mitigation: Use brick/math with 4 decimal places (sufficient for 99.99% of use cases).
- **RISK-003**: Report generation timeout for extremely large datasets (1M+ items). Mitigation: Implement pagination for reports, background job for large exports.
- **RISK-004**: GL integration failure leaves inventory and GL out of sync. Mitigation: Use database transactions, implement reconciliation job to detect discrepancies.
- **RISK-005**: Cache invalidation may miss edge cases (direct DB updates). Mitigation: Document "always use actions", add database triggers for cache invalidation.

### Assumptions

- **ASSUMPTION-001**: GL account codes (1200, 2100, 5100) configured in SUB07 (Chart of Accounts) before use.
- **ASSUMPTION-002**: Unit cost always provided during receipt (no null/zero costs).
- **ASSUMPTION-003**: Valuation method set once and not changed after movements (changing method requires full inventory revaluation).
- **ASSUMPTION-004**: Reports generated in tenant's timezone (handled by application settings).
- **ASSUMPTION-005**: Excel exports limited to 100k rows (PhpSpreadsheet limitation).
- **ASSUMPTION-006**: API consumers handle pagination (no single-request full dataset).
- **ASSUMPTION-007**: Cost layers never deleted (audit trail preserved).
- **ASSUMPTION-008**: GL posting happens synchronously (not queued) for immediate accuracy.

---

## 8. KIV for Future Implementations

### KIV-001: Multi-Currency Inventory Valuation
Support items with costs in multiple currencies (receipt in USD, valuation in EUR). Requires currency_code on valuation layers and exchange rate integration with SUB08 (General Ledger).

### KIV-002: Standard Cost Variance Analysis
Compare actual costs (FIFO/LIFO/Average) vs. standard cost to identify variances. Add variance_analysis table tracking purchase price variance, usage variance.

### KIV-003: Inventory Forecasting
Machine learning-based demand forecasting using historical movement patterns. Adjust reorder points dynamically. Requires data science integration.

### KIV-004: ABC Analysis Report
Classify items by value contribution (A: 80% value, B: 15%, C: 5%). Help prioritize cycle counting and inventory management efforts.

### KIV-005: Inventory Reserve (Obsolescence)
Support inventory write-downs for obsolete/slow-moving stock. Add reserve_percentage field, post GL entries for inventory reserve account.

### KIV-006: Consignment Inventory
Track inventory owned by vendors but held in our warehouses (consignment stock). Requires ownership_type enum and special valuation rules.

### KIV-007: Real-Time Dashboard
WebSocket-based real-time inventory dashboard showing live stock levels, movements, alerts. Requires Laravel Broadcasting and frontend implementation.

### KIV-008: Mobile API Optimizations
Lightweight API endpoints for mobile barcode scanning apps. Reduce payload size, support offline operation with sync.

---

## 9. Related PRD / Further Reading

### Related PRDs

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **PRD01-SUB14 (Inventory Management)**: [../prd/prd-01/PRD01-SUB14-INVENTORY-MANAGEMENT.md](../prd/prd-01/PRD01-SUB14-INVENTORY-MANAGEMENT.md)
- **PRD01-SUB08 (General Ledger)**: [../prd/prd-01/PRD01-SUB08-GENERAL-LEDGER.md](../prd/prd-01/PRD01-SUB08-GENERAL-LEDGER.md) - GL integration
- **PRD01-SUB07 (Chart of Accounts)**: [../prd/prd-01/PRD01-SUB07-CHART-OF-ACCOUNTS.md](../prd/prd-01/PRD01-SUB07-CHART-OF-ACCOUNTS.md) - Account codes

### Implementation Plans

- **PRD01-SUB14-PLAN01**: Inventory foundation - Prerequisite (items, balances)
- **PRD01-SUB14-PLAN02**: Stock movements - Prerequisite (receipt, issue, transfer, adjustment)
- **PRD01-SUB08-PLAN01**: GL Core Posting - Dependency for GL integration

### Architecture Documentation

- **Repository Pattern**: [../../CODING_GUIDELINES.md#repository-pattern](../../CODING_GUIDELINES.md#repository-pattern)
- **Strategy Pattern**: [../../docs/architecture/DESIGN-PATTERNS.md#strategy-pattern](../../docs/architecture/DESIGN-PATTERNS.md#strategy-pattern)
- **RESTful API Design**: [../../docs/architecture/API-DESIGN-GUIDELINES.md](../../docs/architecture/API-DESIGN-GUIDELINES.md)
- **Performance Optimization**: [../../docs/architecture/PERFORMANCE-GUIDELINES.md](../../docs/architecture/PERFORMANCE-GUIDELINES.md)

### External Documentation

- **IAS 2 Inventories**: https://www.ifrs.org/issued-standards/list-of-standards/ias-2-inventories/ - Accounting standard
- **FIFO vs LIFO**: https://www.investopedia.com/terms/f/fifo.asp
- **brick/math Documentation**: https://github.com/brick/math
- **PhpSpreadsheet**: https://phpspreadsheet.readthedocs.io/
- **OpenAPI Specification**: https://swagger.io/specification/
- **JSON:API**: https://jsonapi.org/
