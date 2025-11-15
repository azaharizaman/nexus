# Nexus Manufacturing

**A comprehensive manufacturing execution system (MES) for the Nexus ERP platform.**

## Overview

The Nexus Manufacturing package provides complete manufacturing management functionality, from bill of materials (BOM) definition through work order execution, quality control, and traceability. It implements a cohesive bounded context following domain-driven design principles.

## Features

### 📋 Bill of Materials (BOM)
- **Multi-level BOM explosion** - Recursive component requirements calculation
- **Version control** - Multiple BOM versions with active/obsolete status
- **Phantom components** - Transient sub-assemblies
- **Scrap allowance** - Component quantity planning with waste factors
- **Where-used analysis** - Identify parent assemblies using a component
- **Circular reference detection** - Prevents invalid BOM structures
- **Cost rollup** - Automatic material cost calculation

### 🏭 Work Order Management
- **Complete lifecycle** - Planned → Released → In Production → Completed
- **Material allocation** - Automatic component requirement generation
- **Production tracking** - Real-time quantity reporting (completed/scrapped)
- **Lead time calculation** - Component procurement + production time
- **Overdue detection** - SLA monitoring and alerting
- **Pause/Resume** - Production hold management with reason tracking
- **Progress metrics** - Completion percentage, labor hours, scrap rates

### 📦 Material Management
- **Material issue** - Component withdrawal from inventory
- **Backflushing** - Automatic material consumption based on production
- **Material returns** - Unused component return processing
- **Variance tracking** - Planned vs actual consumption analysis
- **Availability checking** - Shortage identification before production

### ✅ Quality Management
- **Inspection plans** - Product-specific quality criteria
- **Characteristic measurements** - Upper/lower limit validation
- **Pass/fail logic** - Automatic inspection result determination
- **Disposition workflow** - Accept/Reject/Rework/Quarantine/Use-as-is
- **Lot quarantine** - Block defective material usage
- **Quality metrics** - Pass rates, first-pass yield, rejection analysis

### 💰 Production Costing
- **Standard costing** - Predetermined cost calculation
- **Actual costing** - Real consumption-based costing
- **Variance analysis** - Material/Labor/Overhead variance tracking
- **Cost per unit** - Unit cost calculation with scrap consideration
- **Favorable/Unfavorable** - Variance classification

### 🔍 Traceability
- **Batch genealogy** - Finished good to raw material linkage
- **Forward tracing** - Where did this lot go?
- **Backward tracing** - What materials were used?
- **Complete chain** - End-to-end traceability view
- **Recall impact analysis** - Identify all affected lots (recursive)

### 🔄 Workflow Management
- **State machine** - Work order lifecycle enforcement
- **Transition validation** - Business rule compliance
- **Status history** - Audit trail of status changes

## Architecture

### Bounded Context Coherence
Manufacturing implements a **cohesive bounded context** (NOT maximum atomicity) due to:
- **Statutory coupling** - Quality, traceability, and costing are legally mandated for manufacturing
- **Workflow specificity** - Work order lifecycle tightly couples BOM, material, and production execution
- **Data ownership** - All manufacturing data shares common access patterns and lifecycle

### Design Patterns
- **Repository Pattern** - Separation of data access from business logic
- **Service Layer** - Complex orchestration and workflow coordination
- **Contract-based DI** - Interface-driven dependency injection
- **Domain Events** - Decoupled event notification (WorkOrderCreated, ProductionReported, etc.)

### Package Structure
```
nexus-manufacturing/
├── src/
│   ├── Contracts/                 # All service and repository interfaces (flat structure)
│   │   ├── BOMExplosionServiceContract.php
│   │   ├── BillOfMaterialRepositoryContract.php
│   │   ├── MaterialManagementServiceContract.php
│   │   ├── ProductionCostingServiceContract.php
│   │   ├── ProductionExecutionServiceContract.php
│   │   ├── ProductionReportRepositoryContract.php
│   │   ├── QualityInspectionRepositoryContract.php
│   │   ├── QualityManagementServiceContract.php
│   │   ├── TraceabilityServiceContract.php
│   │   ├── WorkOrderPlanningServiceContract.php
│   │   └── WorkOrderRepositoryContract.php
│   ├── Enums/                     # Domain enums (5)
│   ├── Events/                    # Domain events (4)
│   ├── Models/                    # Eloquent models (16)
│   ├── Repositories/              # Repository implementations (4)
│   ├── Services/                  # Service implementations (7)
│   ├── Workflows/                 # Workflow definitions
│   └── ManufacturingServiceProvider.php
├── database/migrations/           # 17 database tables
├── config/manufacturing.php       # Package configuration
└── tests/
    ├── Unit/                      # Business rules, enums, services
    └── Feature/                   # Integration tests
```

## Installation

```bash
composer require nexus/manufacturing
```

The service provider is auto-discovered by Laravel.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=nexus-manufacturing-config
```

Key configuration options:
- **Production** - Backflush material consumption, enable MRP
- **Costing** - Labor rate, overhead rate, costing method
- **Quality** - Require inspection, auto-quarantine on failure
- **Traceability** - Lot tracking, batch genealogy recording
- **Capacity Planning** - Work center capacity calculations

## Usage

### Creating a Bill of Materials

```php
use Nexus\Manufacturing\Models\Product;
use Nexus\Manufacturing\Models\BillOfMaterial;
use Nexus\Manufacturing\Models\BOMItem;
use Nexus\Manufacturing\Enums\ProductType;
use Nexus\Manufacturing\Enums\BOMStatus;

// Create products
$finishedGood = Product::create([
    'code' => 'FG-001',
    'name' => 'Widget Assembly',
    'type' => ProductType::FinishedGood,
    'uom' => 'EA',
    'standard_cost' => 50.00,
]);

$component = Product::create([
    'code' => 'COMP-001',
    'name' => 'Widget Component',
    'type' => ProductType::Component,
    'uom' => 'EA',
    'standard_cost' => 10.00,
]);

// Create BOM
$bom = BillOfMaterial::create([
    'product_id' => $finishedGood->id,
    'version' => '1',
    'status' => BOMStatus::Draft,
]);

// Add components
BOMItem::create([
    'bill_of_material_id' => $bom->id,
    'component_product_id' => $component->id,
    'line_number' => 10,
    'quantity' => 5,
    'uom' => 'EA',
    'scrap_allowance_percentage' => 10,
]);

// Activate BOM (deactivates other BOMs for this product)
$bom->activate();
```

### Work Order Lifecycle

```php
use Nexus\Manufacturing\Services\WorkOrderPlanningService;
use Nexus\Manufacturing\Services\ProductionExecutionService;

// Create and release work order
$workOrder = app(WorkOrderPlanningService::class)->createWorkOrder([
    'product_id' => $product->id,
    'quantity_ordered' => 100,
    'planned_start_date' => now()->addDays(1)->format('Y-m-d'),
]);

// Release allocates materials
app(WorkOrderPlanningService::class)->releaseWorkOrder($workOrder->id);

// Start production
app(ProductionExecutionService::class)->startProduction($workOrder->id);

// Report production output
app(ProductionExecutionService::class)->reportProduction($workOrder->id, [
    'quantity_completed' => 95,
    'quantity_scrapped' => 5,
    'labor_hours' => 80,
    'shift' => 'Day',
]);

// Complete work order
app(ProductionExecutionService::class)->completeWorkOrder($workOrder->id);
```

### Material Backflushing

```php
use Nexus\Manufacturing\Services\MaterialManagementService;

// Backflush materials based on actual production
app(MaterialManagementService::class)->backflushMaterials(
    workOrderId: $workOrder->id,
    quantityProduced: 95
);

// Get material variance
$variance = app(MaterialManagementService::class)->getMaterialVariance($workOrder->id);

foreach ($variance as $material) {
    echo "{$material['component_name']}: {$material['variance']} units ({$material['variance_percentage']}%)";
}
```

### Quality Inspection

```php
use Nexus\Manufacturing\Services\QualityManagementService;
use Nexus\Manufacturing\Enums\DispositionType;

// Perform inspection
$inspection = app(QualityManagementService::class)->performInspection(
    workOrderId: $workOrder->id,
    lotNumber: 'LOT-001',
    measurements: [
        [
            'characteristic_id' => $characteristic->id,
            'measured_value' => 10.5,
            'notes' => 'Within spec',
        ],
    ]
);

// Set disposition for failed inspection
if ($inspection->result === InspectionResult::FAILED) {
    app(QualityManagementService::class)->setDisposition(
        inspectionId: $inspection->id,
        disposition: DispositionType::QUARANTINE,
        notes: 'Out of tolerance'
    );
}
```

### Production Costing

```php
use Nexus\Manufacturing\Services\ProductionCostingService;

// Calculate costing
$costing = app(ProductionCostingService::class)->calculateCosting($workOrder->id);

// Get variance analysis
$variance = app(ProductionCostingService::class)->getVarianceAnalysis($workOrder->id);

echo "Material Variance: {$variance['material_variance']['variance']} ";
echo "({$variance['material_variance']['variance_percentage']}%)";
echo $variance['material_variance']['is_favorable'] ? ' Favorable' : ' Unfavorable';
```

### Traceability & Recall

```php
use Nexus\Manufacturing\Services\TraceabilityService;

// Record batch genealogy
app(TraceabilityService::class)->recordBatchGenealogy(
    workOrderId: $workOrder->id,
    rawMaterialLots: [
        [
            'lot_number' => 'RAW-LOT-001',
            'product_id' => $component->id,
            'quantity_consumed' => 500,
        ],
    ]
);

// Forward tracing - where did this lot go?
$downstream = app(TraceabilityService::class)->traceForward('RAW-LOT-001');

// Backward tracing - what went into this lot?
$upstream = app(TraceabilityService::class)->traceBackward('FG-LOT-001');

// Recall impact analysis
$impact = app(TraceabilityService::class)->identifyRecallImpact('RAW-LOT-001');
echo "Total impacted lots: {$impact['total_impacted_lots']}";
echo "Total quantity: {$impact['recall_scope']['total_quantity']}";
```

## Database Schema

### Core Tables
- **manufacturing_products** - Product master data
- **manufacturing_bill_of_materials** - BOM headers
- **manufacturing_bom_items** - BOM line items (components)
- **manufacturing_work_orders** - Production orders
- **manufacturing_material_allocations** - Component requirements

### Execution Tables
- **manufacturing_production_reports** - Production output
- **manufacturing_operation_logs** - Operation tracking
- **manufacturing_production_costing** - Standard vs actual costs

### Quality Tables
- **manufacturing_inspection_plans** - Quality criteria templates
- **manufacturing_inspection_characteristics** - Measurement specs
- **manufacturing_quality_inspections** - Inspection records
- **manufacturing_inspection_measurements** - Actual measurements

### Traceability Tables
- **manufacturing_batch_genealogy** - Lot lineage
- **manufacturing_batch_genealogy_materials** - Raw material lots

### Routing Tables
- **manufacturing_routings** - Operation sequences
- **manufacturing_routing_operations** - Individual operations
- **manufacturing_work_centers** - Production resources

## Domain Events

- **WorkOrderCreated** - Dispatched on work order creation
- **ProductionReported** - Dispatched on production report submission
- **MaterialConsumed** - Dispatched on material issue/backflush
- **WorkOrderCompleted** - Dispatched on work order completion

## Testing

```bash
# Run all tests
composer test

# Run unit tests only
./vendor/bin/pest tests/Unit

# Run feature tests only
./vendor/bin/pest tests/Feature
```

Test coverage:
- ✅ BOM business rules (activation, scrap allowance, product types)
- ✅ Work order lifecycle (status transitions, completion validation)
- ✅ Quality management (inspection rules, disposition logic)
- ✅ BOM explosion (multi-level, cost rollup)
- ✅ Work order execution (planning → release → production → completion)
- ✅ Production costing (variance analysis, cost per unit)

## Dependencies

- **Laravel 12+** - Framework
- **PHP 8.3+** - Language
- **PestPHP 3.0** - Testing framework
- **Orchestra Testbench 9.0** - Package testing utilities

## License

Proprietary - Nexus ERP Platform

## Maintainer

Nexus ERP Development Team
