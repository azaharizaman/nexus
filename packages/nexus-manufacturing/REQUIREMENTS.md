# nexus-manufacturing Package Requirements

**Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Initial Requirements - Cohesive Bounded Context Model

---

## Executive Summary

**nexus-manufacturing** (namespace: `Nexus\Manufacturing`) is a **cohesive manufacturing execution system** for PHP/Laravel that manages the complete production lifecycle—from bill of materials (BOM) definition through work order creation, production floor execution, material consumption, quality control, to finished goods output.

### The Domain Complexity We Embrace

Unlike atomic core packages (tenancy, settings, workflow), manufacturing is a **vertical business domain** where **atomicity must yield to cohesion**:

1. **Statutory Coupling:** Manufacturing regulations (ISO standards, FDA compliance for pharma, HACCP for food) are industry-specific and jurisdiction-specific. These cannot be externalized to core without losing domain coherence.
2. **Workflow Specificity:** Production workflows (work order release → material issue → production execution → quality inspection → goods receipt) are domain-specific state machines that belong within the bounded context.
3. **Data Ownership:** BOMs, work orders, production logs, material consumption, and quality records form a cohesive aggregate where splitting across packages would create integration hell and violate manufacturing traceability requirements.

### Core Philosophy

1. **Bounded Context Coherence** - All manufacturing entities and rules live together in one package
2. **Domain-Driven Design** - Rich domain models with encapsulated manufacturing logic
3. **Progressive Complexity** - Start simple (basic work orders), grow to advanced (MRP, capacity planning, quality management)
4. **Industry Flexibility** - Supports discrete manufacturing (assembly), process manufacturing (batch/continuous), and make-to-order scenarios
5. **Traceability First** - Complete lot/serial traceability for compliance (pharma, food, automotive)

### Why This Approach Works

**For Small Manufacturers (60%):**
- Simple BOM management (single-level BOMs)
- Basic work order creation and execution
- Manual material issue and consumption
- Simple production reporting (units produced, material used)

**For Mid-Market (30%):**
- Multi-level BOMs with routing/operations
- Capacity planning and shop floor scheduling
- Barcode scanning for material tracking
- Quality control with inspection plans
- Production costing (standard vs actual)

**For Enterprise (10%):**
- Material Requirements Planning (MRP)
- Advanced Planning and Scheduling (APS)
- Real-time machine integration (IoT/SCADA)
- Statistical Process Control (SPC)
- Lean manufacturing (JIT, Kanban)
- Compliance tracking (batch genealogy, full traceability)

---

## Architectural Position in Nexus ERP

### Relationship to Core Packages

| Core Package | Relationship | Usage Pattern |
|-------------|--------------|---------------|
| **nexus-tenancy** | Depends On | All manufacturing data is tenant-scoped via `BelongsToTenant` trait |
| **nexus-workflow** | Leverages | Uses workflow engine for work order approvals but defines manufacturing-specific states |
| **nexus-sequencing** | Depends On | Uses sequence generation for work order numbers, batch numbers, lot numbers |
| **nexus-settings** | Depends On | Retrieves manufacturing settings (lead times, scrap rates, costing methods) |
| **nexus-audit-log** | Depends On | Comprehensive audit trail for all manufacturing operations (traceability) |
| **nexus-backoffice** | Depends On | Links to production departments/work centers for capacity planning |
| **nexus-inventory** | Tightly Integrated | Consumes raw materials, produces finished goods, tracks lot/serial numbers |
| **nexus-accounting** | Consumes From | Posts production costs (material, labor, overhead) to WIP and finished goods |
| **nexus-procurement** | Integrates With | Links purchase orders to production requirements (MRP-driven procurement) |

### Why This Is NOT an Atomic Package

**Manufacturing violates the independence criterion:**
- Cannot be meaningfully subdivided (splitting BOM from work orders would break coherence)
- Industry regulations (GMP, HACCP, ISO) are manufacturing-specific and change frequently
- Workflow states are domain-specific (not generic state machine patterns)
- Testing requires manufacturing context (cannot test material consumption without BOM/work order context)
- Traceability requirements demand tight coupling between BOMs, work orders, production logs, and quality records

**This is a COHESIVE VERTICAL** where domain logic density justifies consolidation.

---

## Personas & User Stories

### Personas

| ID | Persona | Role | Primary Goal |
|-----|---------|------|--------------|
| **P1** | Production Planner | Planning team | "Create work orders based on sales orders and maintain optimal inventory levels" |
| **P2** | Shop Floor Supervisor | Production floor lead | "Execute work orders efficiently, manage material flow, track production output" |
| **P3** | Machine Operator | Line worker | "Know what to produce, log actual quantities produced and consumed" |
| **P4** | Quality Inspector | QC team | "Inspect production output, record defects, approve/reject batches" |
| **P5** | Production Manager | Department head | "Monitor production performance, identify bottlenecks, optimize capacity" |
| **P6** | Cost Accountant | Finance team | "Track production costs accurately (material, labor, overhead), calculate product costing" |

### User Stories

#### Level 1: Basic Manufacturing (Simple Work Orders)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-001** | P1 | As a production planner, I want to define a bill of materials (BOM) for a finished product listing all required components | **High** |
| **US-002** | P1 | As a production planner, I want to create a work order specifying what to produce, quantity, and due date | **High** |
| **US-003** | P2 | As a shop floor supervisor, I want to release a work order to the floor and issue raw materials to production | **High** |
| **US-004** | P3 | As a machine operator, I want to report production output (quantity completed, quantity scrapped) | **High** |
| **US-005** | P3 | As a machine operator, I want to record material consumption (actual qty used vs BOM standard) | **High** |
| **US-006** | P2 | As a shop floor supervisor, I want to complete a work order and move finished goods to inventory | **High** |
| **US-007** | P5 | As a production manager, I want to view work order status (planned, released, in production, completed) | Medium |

#### Level 2: Advanced Manufacturing (Routing & Quality)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-010** | P1 | As a production planner, I want to define routing/operations for a BOM (sequence of work centers and times) | **High** |
| **US-011** | P3 | As a machine operator, I want to report operation completion (operation start/end time, labor hours) | **High** |
| **US-012** | P4 | As a quality inspector, I want to define inspection plans (what to check, acceptance criteria) for products | **High** |
| **US-013** | P4 | As a quality inspector, I want to perform inspections during production and record results | **High** |
| **US-014** | P4 | As a quality inspector, I want to quarantine defective batches and prevent them from moving to finished goods | **High** |
| **US-015** | P1 | As a production planner, I want to track work center capacity and load to avoid overloading machines | Medium |
| **US-016** | P2 | As a shop floor supervisor, I want to see a production schedule (what to produce next on each work center) | Medium |
| **US-017** | P5 | As a production manager, I want to calculate production costing (standard vs actual cost per unit) | **High** |

#### Level 3: Enterprise Manufacturing (MRP & Compliance)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-020** | P1 | As a production planner, I want MRP to calculate material requirements based on demand and generate purchase requisitions | **High** |
| **US-021** | P1 | As a production planner, I want to run capacity planning to identify bottlenecks before releasing work orders | Medium |
| **US-022** | P5 | As a production manager, I want to track batch genealogy (which raw material batches went into which finished goods batches) | **High** |
| **US-023** | P5 | As a production manager, I want full traceability (from raw material lot to finished product serial number) | **High** |
| **US-024** | P5 | As a production manager, I want to implement Kanban/JIT (pull-based production triggered by consumption) | Medium |
| **US-025** | P4 | As a quality inspector, I want to perform statistical process control (SPC) to detect production drift | Medium |
| **US-026** | P6 | As a cost accountant, I want to allocate overhead costs to work orders based on activity-based costing | Medium |

---

## Functional Requirements

### FR-L1: Level 1 - Basic Manufacturing (Essential MVP)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L1-001** | Define Bill of Materials (BOM) | **High** | • Single-level BOM: list of component items with quantities<br>• Unit of measure per component<br>• Scrap allowance % (to account for waste)<br>• BOM status (draft, active, obsolete)<br>• Effective date and expiry date |
| **FR-L1-002** | Multi-level BOM support | **High** | • Nested BOMs (sub-assemblies within assemblies)<br>• BOM explosion (flatten multi-level BOM to component list)<br>• Where-used query (which BOMs use this component?) |
| **FR-L1-003** | Create work order | **High** | • Work order number (auto-generated via nexus-sequencing)<br>• Product to produce (link to BOM)<br>• Quantity to produce<br>• Planned start and end dates<br>• Work order status (planned, released, in_production, completed, cancelled)<br>• Auto-calculate required raw materials from BOM |
| **FR-L1-004** | Material issue (backflush vs manual) | **High** | • Manual issue: operator selects materials and quantities<br>• Backflush: auto-deduct materials when production is reported<br>• Track material lot/batch number for traceability<br>• Update inventory: deduct from raw materials, allocate to WIP |
| **FR-L1-005** | Production reporting | **High** | • Report quantity completed (good units)<br>• Report quantity scrapped (defective units)<br>• Record production date and shift<br>• Optional: operator who performed work |
| **FR-L1-006** | Work order completion | **High** | • Close work order after all production reported<br>• Move finished goods to inventory (increase finished goods stock)<br>• Calculate actual material consumption vs BOM standard<br>• Generate variance report (material yield variance) |
| **FR-L1-007** | Work order tracking dashboard | Medium | • List of all work orders with status<br>• Filter by status, product, date range<br>• Sortable by due date, quantity<br>• Quick action: release, complete work order |

### FR-L2: Level 2 - Advanced Manufacturing (Routing & Quality)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L2-001** | Routing/operations management | **High** | • Define sequence of operations for a BOM<br>• Each operation: work center, setup time, run time per unit<br>• Labor required (number of operators)<br>• Move time between operations<br>• Critical vs non-critical operations |
| **FR-L2-002** | Work center master | **High** | • Work center code, description, department<br>• Capacity (units per hour, shifts per day)<br>• Cost center for overhead allocation<br>• Active/inactive status |
| **FR-L2-003** | Operation execution tracking | **High** | • Report operation start (clock-in)<br>• Report operation completion (clock-out)<br>• Record labor hours per operation<br>• Track setup time vs run time<br>• Move work order to next operation |
| **FR-L2-004** | Inspection plan management | **High** | • Define inspection checkpoints (incoming, in-process, final)<br>• Inspection characteristics (dimension, weight, visual, functional test)<br>• Acceptance criteria (tolerance ranges, pass/fail)<br>• Sampling plan (how many units to inspect) |
| **FR-L2-005** | Quality inspection execution | **High** | • Perform inspection based on inspection plan<br>• Record measurement results<br>• Pass/fail decision per characteristic<br>• Overall batch approval/rejection<br>• Attach defect photos/notes |
| **FR-L2-006** | Quarantine management | **High** | • Quarantine failed batches (block from use/sale)<br>• Disposition: scrap, rework, return to vendor, use as-is with waiver<br>• Record disposition approval and reason |
| **FR-L2-007** | Work center capacity planning | Medium | • Calculate work center load (scheduled hours vs available hours)<br>• Identify overloaded work centers (>100% capacity)<br>• What-if analysis: "Can we take on this new order?"<br>• Visual capacity chart (Gantt-style) |
| **FR-L2-008** | Production scheduling | Medium | • Generate production schedule (work orders sequenced by priority)<br>• Schedule work orders to work centers based on routing<br>• Consider work center capacity constraints<br>• Alert on schedule conflicts |
| **FR-L2-009** | Production costing | **High** | • Calculate standard cost (BOM material cost + routing labor cost + overhead)<br>• Calculate actual cost (actual material consumed + actual labor hours + overhead)<br>• Variance analysis (standard vs actual)<br>• Cost rollup for multi-level BOMs |

### FR-L3: Level 3 - Enterprise Manufacturing (MRP & Compliance)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L3-001** | Material Requirements Planning (MRP) | **High** | • Input: demand forecast + sales orders + current inventory<br>• Calculate net requirements (demand - on hand - on order)<br>• Generate planned work orders (what to produce)<br>• Generate planned purchase requisitions (what to buy)<br>• Consider lead times (procurement + production)<br>• Time-phased requirements (weekly/monthly buckets) |
| **FR-L3-002** | Capacity Requirements Planning (CRP) | Medium | • Calculate capacity requirements per work center<br>• Compare required capacity vs available capacity<br>• Identify bottleneck work centers<br>• Recommend capacity adjustments (add shifts, outsource) |
| **FR-L3-003** | Batch genealogy tracking | **High** | • Link raw material lots to work orders<br>• Link work orders to finished goods batches<br>• Bi-directional traceability (forward: where did this material go? backward: where did this product come from?)<br>• Regulatory compliance (FDA 21 CFR Part 11, ISO 9001) |
| **FR-L3-004** | Full lot/serial traceability | **High** | • Assign lot numbers to production batches<br>• Assign serial numbers to individual units (if applicable)<br>• Capture expiry dates for perishable goods<br>• Recall management (identify affected batches/units) |
| **FR-L3-005** | Kanban/JIT production | Medium | • Define Kanban cards (product, quantity, reorder point)<br>• Trigger work order when Kanban consumed<br>• Pull-based production (produce only what's needed)<br>• Visual Kanban board |
| **FR-L3-006** | Statistical Process Control (SPC) | Medium | • Define control charts (X-bar, R-chart, p-chart)<br>• Capture measurement data from production<br>• Plot control charts in real-time<br>• Alert on out-of-control conditions (violation of control limits)<br>• Root cause analysis workflow |
| **FR-L3-007** | Activity-Based Costing (ABC) | Medium | • Define cost pools (setup, machine time, material handling)<br>• Allocate overhead based on activity drivers<br>• More accurate product costing than traditional overhead allocation |
| **FR-L3-008** | IoT/SCADA integration | Low | • Capture real-time machine data (cycle time, downtime, defect rate)<br>• Auto-report production output from machines<br>• OEE (Overall Equipment Effectiveness) calculation<br>• Predictive maintenance alerts |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Notes |
|----|-------------|--------|-------|
| **PR-001** | BOM explosion (10-level deep, 500 components) | < 2 seconds | Recursive query with caching |
| **PR-002** | Work order creation and material allocation | < 1 second | Including BOM explosion and inventory check |
| **PR-003** | Production reporting (backflush 50 components) | < 3 seconds | Inventory update + cost calculation |
| **PR-004** | MRP calculation (1000 SKUs, 10,000 transactions) | < 60 seconds | Batch processing, async job |
| **PR-005** | Shop floor dashboard (100 active work orders) | < 2 seconds | Real-time status aggregation |

### Security Requirements

| ID | Requirement | Scope |
|----|-------------|-------|
| **SR-001** | Tenant data isolation | All manufacturing data MUST be tenant-scoped (via nexus-tenancy) |
| **SR-002** | Role-based access control | Enforce permissions: create-work-order, approve-work-order, report-production, perform-inspection, view-costing |
| **SR-003** | Production data integrity | Completed work orders and inspection records are immutable (audit trail) |
| **SR-004** | Traceability compliance | ALL material movements MUST be logged for regulatory compliance (FDA, ISO) |
| **SR-005** | Quality data protection | Inspection results visible only to authorized roles (QC, management) |

### Reliability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **REL-001** | All inventory transactions MUST be ACID-compliant | Wrapped in database transactions |
| **REL-002** | Production reporting MUST prevent double-counting | Idempotency check on production output |
| **REL-003** | Work order state changes MUST be resumable after failure | Use nexus-workflow persistence |
| **REL-004** | BOM explosion MUST handle circular references | Detect and prevent infinite loops |

### Compliance Requirements

| ID | Requirement | Jurisdiction/Industry |
|----|-------------|----------------------|
| **COMP-001** | Batch traceability | FDA 21 CFR Part 11 (pharma), HACCP (food) |
| **COMP-002** | Electronic signatures on quality records | FDA 21 CFR Part 11 |
| **COMP-003** | ISO 9001 quality management | Document control, corrective actions |
| **COMP-004** | Lot/serial recall capability | Consumer product safety regulations |
| **COMP-005** | Retention: production records for 10 years | Industry best practice, regulatory requirement |

---

## Domain Model

### Core Entities

```
Product (Finished Goods and Components)
├── id (UUID)
├── tenant_id (UUID)
├── product_code (string) - unique SKU
├── description (string)
├── product_type (enum: raw_material, component, sub_assembly, finished_good)
├── unit_of_measure (string)
├── standard_cost (decimal)
├── lead_time_days (int) - procurement or production lead time
└── BillOfMaterial (hasOne or hasMany if multiple versions)

BillOfMaterial (BOM)
├── id (UUID)
├── tenant_id (UUID)
├── product_id (UUID) - what this BOM produces
├── bom_version (string) - e.g., "v1.0"
├── status (enum: draft, active, obsolete)
├── effective_date (date)
├── expiry_date (date, nullable)
├── notes (text)
└── Components (hasMany BOMItem)
└── Routing (hasOne Routing, optional)

BOMItem (Component in a BOM)
├── id (UUID)
├── bom_id (UUID)
├── component_product_id (UUID) - the component being consumed
├── quantity_required (decimal) - per unit of parent
├── unit_of_measure (string)
├── scrap_allowance_pct (decimal) - waste factor (e.g., 5%)
├── line_number (int) - display order
└── component_type (enum: regular, phantom, reference) - phantom = transient sub-assembly

Routing (Sequence of Operations)
├── id (UUID)
├── tenant_id (UUID)
├── bom_id (UUID)
├── routing_version (string)
├── status (enum: draft, active, obsolete)
└── Operations (hasMany RoutingOperation)

RoutingOperation
├── id (UUID)
├── routing_id (UUID)
├── operation_number (int) - sequence (10, 20, 30...)
├── work_center_id (UUID)
├── operation_description (string)
├── setup_time_minutes (decimal)
├── run_time_per_unit_minutes (decimal)
├── labor_required (int) - number of operators
├── move_time_minutes (decimal) - time to move to next operation
└── is_critical (boolean) - critical path indicator

WorkCenter
├── id (UUID)
├── tenant_id (UUID)
├── work_center_code (string)
├── description (string)
├── department_id (UUID) - from nexus-backoffice
├── capacity_units_per_hour (decimal)
├── shifts_per_day (int)
├── overhead_rate_per_hour (decimal) - for costing
├── status (enum: active, inactive, maintenance)
└── cost_center_code (string) - for GL posting

WorkOrder
├── id (UUID)
├── tenant_id (UUID)
├── work_order_number (string) - via nexus-sequencing
├── product_id (UUID) - what to produce
├── bom_id (UUID) - which BOM to use
├── routing_id (UUID, nullable)
├── quantity_ordered (decimal)
├── quantity_completed (decimal) - good units
├── quantity_scrapped (decimal) - defective units
├── planned_start_date (date)
├── planned_end_date (date)
├── actual_start_date (date, nullable)
├── actual_end_date (date, nullable)
├── status (enum: planned, released, in_production, on_hold, completed, cancelled)
├── priority (enum: low, normal, high, urgent)
├── source_type (enum: sales_order, stock_replenishment, mrp)
├── source_reference (string) - e.g., sales order number
└── created_by (UUID)
└── Material Allocations (hasMany MaterialAllocation)
└── Production Reports (hasMany ProductionReport)
└── Operation Logs (hasMany OperationLog)

MaterialAllocation
├── id (UUID)
├── work_order_id (UUID)
├── component_product_id (UUID)
├── quantity_required (decimal) - from BOM with scrap allowance
├── quantity_issued (decimal) - actually issued to floor
├── quantity_consumed (decimal) - reported as consumed
├── lot_number (string, nullable) - for traceability
├── issued_from_location (string) - warehouse location
├── issued_at (datetime, nullable)
└── issued_by (UUID, nullable)

ProductionReport
├── id (UUID)
├── work_order_id (UUID)
├── operation_id (UUID, nullable) - if routing-based
├── reported_by (UUID) - operator
├── report_date (date)
├── shift (enum: day, night, graveyard)
├── quantity_completed (decimal) - good units
├── quantity_scrapped (decimal) - defective units
├── scrap_reason (string, nullable)
├── labor_hours (decimal, nullable)
└── notes (text, nullable)

OperationLog
├── id (UUID)
├── work_order_id (UUID)
├── operation_id (UUID)
├── operator_id (UUID)
├── start_time (datetime)
├── end_time (datetime, nullable)
├── status (enum: in_progress, paused, completed)
├── setup_time_actual (decimal) - actual setup time
├── run_time_actual (decimal) - actual production time
└── notes (text, nullable)

InspectionPlan
├── id (UUID)
├── tenant_id (UUID)
├── product_id (UUID) - what product this inspects
├── inspection_type (enum: incoming, in_process, final)
├── sampling_plan (string) - e.g., "AQL 2.5, sample size 10"
└── Characteristics (hasMany InspectionCharacteristic)

InspectionCharacteristic
├── id (UUID)
├── inspection_plan_id (UUID)
├── characteristic_name (string) - e.g., "Length", "Weight"
├── specification (string) - e.g., "100mm ± 0.5mm"
├── measurement_method (string)
├── pass_fail_criteria (text)
└── display_order (int)

QualityInspection
├── id (UUID)
├── tenant_id (UUID)
├── work_order_id (UUID)
├── inspection_plan_id (UUID)
├── inspector_id (UUID)
├── inspection_date (datetime)
├── lot_number (string)
├── sample_size (int)
├── result (enum: passed, failed, conditional_pass)
├── disposition (enum: accept, reject, rework, quarantine, use_as_is)
├── notes (text)
└── Measurements (hasMany InspectionMeasurement)

InspectionMeasurement
├── id (UUID)
├── quality_inspection_id (UUID)
├── characteristic_id (UUID)
├── measured_value (string)
├── pass_fail (boolean)
└── notes (text, nullable)

BatchGenealogy
├── id (UUID)
├── tenant_id (UUID)
├── finished_goods_lot (string) - produced batch
├── work_order_id (UUID)
└── Raw Material Lots (manyToMany via batch_genealogy_materials pivot)
    - raw_material_product_id
    - raw_material_lot_number
    - quantity_consumed

ProductionCosting
├── id (UUID)
├── work_order_id (UUID)
├── standard_material_cost (decimal)
├── actual_material_cost (decimal)
├── standard_labor_cost (decimal)
├── actual_labor_cost (decimal)
├── standard_overhead_cost (decimal)
├── actual_overhead_cost (decimal)
├── total_standard_cost (decimal)
├── total_actual_cost (decimal)
├── variance_total (decimal)
├── cost_per_unit (decimal)
└── costing_date (date) - when calculated
```

### Aggregate Relationships

```
Product (Aggregate Root)
  └─> BillOfMaterial (Entity)
      └─> BOMItem (Value Object)
  └─> Routing (Entity)
      └─> RoutingOperation (Value Object)
  └─> InspectionPlan (Entity)
      └─> InspectionCharacteristic (Value Object)

WorkOrder (Aggregate Root)
  └─> MaterialAllocation (Entity)
  └─> ProductionReport (Entity)
  └─> OperationLog (Entity)
  └─> QualityInspection (Entity)
      └─> InspectionMeasurement (Value Object)
  └─> ProductionCosting (Value Object)
  └─> BatchGenealogy (Value Object)
```

---

## Business Rules

| ID | Rule | Level |
|----|------|-------|
| **BR-001** | A BOM must have at least one component | All levels |
| **BR-002** | BOM components cannot reference the parent product (circular BOM prevention) | All levels |
| **BR-003** | Only one BOM per product can be active at a time | All levels |
| **BR-004** | Work order quantity completed + quantity scrapped cannot exceed quantity ordered | All levels |
| **BR-005** | Materials can only be issued to work orders in "released" or "in_production" status | All levels |
| **BR-006** | Work order cannot be completed if material allocations are not fulfilled | Level 1 |
| **BR-007** | Operation sequence must be sequential (operation 10 before operation 20) | Level 2 |
| **BR-008** | Inspection must pass before work order can be completed | Level 2 |
| **BR-009** | Quarantined batches cannot be used in production or sold | Level 2 |
| **BR-010** | Standard cost must be calculated before work order release | Level 2 |
| **BR-011** | MRP must consider safety stock levels when calculating net requirements | Level 3 |
| **BR-012** | Batch genealogy must be captured for all regulated products (pharma, food) | Level 3 |
| **BR-013** | Lot/serial numbers must be unique across all tenants (globally unique) | Level 3 |
| **BR-014** | Work center capacity cannot be exceeded without approval | Level 2 |
| **BR-015** | Routing operations must reference active work centers | Level 2 |

---

## Workflow State Machines

### Work Order Workflow

```
States:
  - planned (initial, draft state)
  - released (sent to shop floor)
  - in_production (active production)
  - on_hold (temporarily paused)
  - completed (finished)
  - cancelled (terminated)

Transitions:
  release: planned → released
    - Validates: BOM is active, materials available or ordered
    - Action: allocate materials, generate material pick list
    - Triggers: notification to shop floor supervisor
    
  start_production: released → in_production
    - Guard: materials issued
    - Action: start operation logging
    
  pause: in_production → on_hold
    - Guard: user has pause-work-order permission
    - Requires: reason for hold
    
  resume: on_hold → in_production
    - Action: resume operation logging
    
  complete: in_production → completed
    - Validates: quantity completed + scrapped = quantity ordered
    - Validates: all operations completed (if routing-based)
    - Validates: final inspection passed
    - Action: move finished goods to inventory
    - Action: calculate production costing
    - Triggers: accounting posting (WIP → finished goods)
    
  cancel: [planned, released, in_production, on_hold] → cancelled
    - Guard: user has cancel-work-order permission
    - Requires: cancellation reason
    - Action: reverse material allocations, update inventory
```

### Quality Inspection Workflow

```
States:
  - pending (inspection not yet performed)
  - in_progress (inspector working on it)
  - passed (approved)
  - failed (rejected)
  - conditional_pass (approved with waiver)

Transitions:
  start_inspection: pending → in_progress
    - Guard: inspector assigned
    
  pass: in_progress → passed
    - Validates: all critical characteristics within spec
    - Action: allow work order to proceed
    
  fail: in_progress → failed
    - Requires: failure reason
    - Action: quarantine batch, trigger disposition workflow
    
  conditional_pass: in_progress → conditional_pass
    - Guard: manager approval
    - Requires: waiver justification
    - Action: allow use with documented exception
```

### Batch Disposition Workflow

```
States:
  - quarantine (blocked from use)
  - rework (send back to production)
  - scrap (discard)
  - use_as_is (approve with waiver)
  - return_to_vendor (for incoming inspection)

Transitions:
  rework: quarantine → (create new work order)
  scrap: quarantine → (write off inventory)
  use_as_is: quarantine → (approve with waiver, move to inventory)
  return_to_vendor: quarantine → (create RMA, notify vendor)
```

---

## Integration Points

### With Core Packages

| Core Package | Integration Type | Usage |
|-------------|------------------|-------|
| **nexus-tenancy** | Direct Dependency | All models use `BelongsToTenant` trait for data isolation |
| **nexus-sequencing** | Service Call | Generate work order numbers, batch numbers, lot numbers, serial numbers |
| **nexus-settings** | Service Call | Retrieve manufacturing settings (lead times, scrap rates, costing methods) |
| **nexus-audit-log** | Event Listener | Log ALL manufacturing operations for traceability and compliance |
| **nexus-workflow** | Engine Usage | Leverage workflow engine for work order and inspection approvals |
| **nexus-backoffice** | Data Reference | Link work centers to departments for capacity planning and costing |

### With Business Domain Packages

| Package | Integration Type | Data Flow |
|---------|------------------|-----------|
| **nexus-inventory** | Tightly Integrated | Consume raw materials (material issue), produce finished goods (work order completion), track lot/serial numbers |
| **nexus-accounting** | Event-Driven | Post production costs to WIP account, move costs to finished goods on completion, variance posting |
| **nexus-procurement** | Service Call | MRP generates purchase requisitions for required materials |
| **nexus-sales** | Data Reference | Link work orders to sales orders (make-to-order production) |
| **nexus-project-management** | Optional | Track production as tasks/activities (for custom manufacturing projects) |

### External Integrations (Optional)

| System | Integration Method | Purpose |
|--------|-------------------|---------|
| **ERP Connectors** | REST API | Send production data to external ERP (SAP, Oracle) |
| **MES (Manufacturing Execution System)** | REST API/MQTT | Real-time machine data capture, OEE tracking |
| **SCADA Systems** | OPC-UA/MQTT | Industrial automation, sensor data ingestion |
| **Barcode Scanners** | Mobile app API | Material tracking, production reporting |
| **Label Printers** | ZPL/TSPL | Print lot labels, serial number labels |

---

## Testing Requirements

### Unit Tests
- BOM explosion algorithm (multi-level, circular reference detection)
- Material requirement calculation (with scrap allowance)
- Production costing (standard vs actual, variance calculation)
- Capacity planning algorithm
- MRP net requirements calculation

### Feature Tests
- Complete work order lifecycle (create → release → produce → complete)
- Material backflushing on production report
- Quality inspection flow (pass/fail, quarantine, disposition)
- Batch genealogy traceability (forward and backward)
- Multi-level BOM costing rollup

### Integration Tests
- nexus-inventory integration: verify material consumption and finished goods receipt
- nexus-accounting integration: verify WIP and finished goods cost posting
- nexus-procurement integration: verify MRP-driven purchase requisition creation
- nexus-sequencing integration: verify unique number generation

### Performance Tests
- Load test: 100 concurrent production reports
- Stress test: BOM explosion for product with 10-level BOM and 1000 components
- MRP calculation for 10,000 SKUs with 50,000 transactions

---

## Package Structure

```
packages/nexus-manufacturing/
├── src/
│   ├── Models/                      # Domain entities
│   │   ├── Product.php
│   │   ├── BillOfMaterial.php
│   │   ├── BOMItem.php
│   │   ├── Routing.php
│   │   ├── RoutingOperation.php
│   │   ├── WorkCenter.php
│   │   ├── WorkOrder.php
│   │   ├── MaterialAllocation.php
│   │   ├── ProductionReport.php
│   │   ├── OperationLog.php
│   │   ├── InspectionPlan.php
│   │   ├── QualityInspection.php
│   │   ├── BatchGenealogy.php
│   │   └── ProductionCosting.php
│   │
│   ├── Repositories/                # Data access layer
│   │   ├── BillOfMaterialRepository.php
│   │   ├── WorkOrderRepository.php
│   │   ├── ProductionReportRepository.php
│   │   └── QualityInspectionRepository.php
│   │
│   ├── Services/                    # Business logic services
│   │   ├── BOMExplosionService.php
│   │   ├── WorkOrderPlanningService.php
│   │   ├── ProductionExecutionService.php
│   │   ├── MaterialManagementService.php
│   │   ├── QualityManagementService.php
│   │   ├── ProductionCostingService.php
│   │   ├── MRPService.php
│   │   ├── CapacityPlanningService.php
│   │   └── TraceabilityService.php
│   │
│   ├── Contracts/                   # Interfaces for external consumption
│   │   ├── BillOfMaterialRepositoryContract.php
│   │   ├── WorkOrderServiceContract.php
│   │   ├── ProductionExecutionServiceContract.php
│   │   └── QualityManagementServiceContract.php
│   │
│   ├── Enums/                       # Domain enums
│   │   ├── BOMStatus.php
│   │   ├── WorkOrderStatus.php
│   │   ├── ProductType.php
│   │   ├── InspectionResult.php
│   │   └── DispositionType.php
│   │
│   ├── Events/                      # Domain events
│   │   ├── WorkOrderCreated.php
│   │   ├── WorkOrderReleased.php
│   │   ├── ProductionReported.php
│   │   ├── MaterialConsumed.php
│   │   ├── InspectionCompleted.php
│   │   └── WorkOrderCompleted.php
│   │
│   ├── Workflows/                   # Workflow definitions
│   │   ├── WorkOrderWorkflow.php
│   │   ├── QualityInspectionWorkflow.php
│   │   └── BatchDispositionWorkflow.php
│   │
│   ├── Rules/                       # Validation rules
│   │   ├── BOMCircularReferenceCheck.php
│   │   ├── CapacityConstraintValidation.php
│   │   └── TraceabilityRequirementCheck.php
│   │
│   └── ManufacturingServiceProvider.php
│
├── database/
│   └── migrations/
│       ├── 2025_11_15_000001_create_products_table.php
│       ├── 2025_11_15_000002_create_bill_of_materials_table.php
│       ├── 2025_11_15_000003_create_bom_items_table.php
│       ├── 2025_11_15_000004_create_routings_table.php
│       ├── 2025_11_15_000005_create_routing_operations_table.php
│       ├── 2025_11_15_000006_create_work_centers_table.php
│       ├── 2025_11_15_000007_create_work_orders_table.php
│       ├── 2025_11_15_000008_create_material_allocations_table.php
│       ├── 2025_11_15_000009_create_production_reports_table.php
│       ├── 2025_11_15_000010_create_operation_logs_table.php
│       ├── 2025_11_15_000011_create_inspection_plans_table.php
│       ├── 2025_11_15_000012_create_quality_inspections_table.php
│       └── 2025_11_15_000013_create_batch_genealogy_table.php
│
├── config/
│   └── manufacturing.php            # Package configuration
│
├── tests/
│   ├── Unit/
│   │   ├── Services/
│   │   │   ├── BOMExplosionServiceTest.php
│   │   │   ├── ProductionCostingServiceTest.php
│   │   │   └── MRPServiceTest.php
│   │   └── Rules/
│   │       └── BOMCircularReferenceCheckTest.php
│   │
│   └── Feature/
│       ├── WorkOrderLifecycleTest.php
│       ├── ProductionReportingTest.php
│       ├── QualityInspectionTest.php
│       ├── BatchTraceabilityTest.php
│       └── MRPCalculationTest.php
│
└── REQUIREMENTS.md                  # This document

Note: Controllers, Actions, Routes, and HTTP layer belong in Nexus\Erp orchestration layer.
See src/Actions/Manufacturing/ and src/Http/Controllers/Api/V1/Manufacturing/ in the main ERP package.
```

---

## Configuration

### manufacturing.php

```php
return [
    // Production settings
    'production' => [
        'allow_overproduction' => false,  // Prevent producing more than ordered
        'max_overproduction_pct' => 5.0,  // Allow 5% overproduction if enabled
        'scrap_default_pct' => 2.0,        // Default scrap allowance
        'backflush_material' => true,      // Auto-deduct materials on production report
    ],
    
    // Costing settings
    'costing' => [
        'method' => 'standard',  // standard, actual, or average
        'overhead_allocation' => 'labor_hours',  // labor_hours, machine_hours, activity_based
        'capture_labor_cost' => true,
        'capture_overhead_cost' => true,
    ],
    
    // Quality settings
    'quality' => [
        'require_inspection' => false,  // Make inspection mandatory for all work orders
        'auto_quarantine_on_fail' => true,
        'inspection_sampling_default' => 'AQL 2.5',
    ],
    
    // MRP settings
    'mrp' => [
        'enabled' => false,  // Enable for Level 3
        'planning_horizon_days' => 90,
        'safety_stock_days' => 7,
        'reorder_point_method' => 'fixed',  // fixed or calculated
    ],
    
    // Traceability settings
    'traceability' => [
        'require_lot_tracking' => false,  // Make lot tracking mandatory
        'require_serial_tracking' => false,  // Make serial tracking mandatory
        'lot_number_format' => 'LOT-{YYYY}{MM}{DD}-{0000}',
        'batch_genealogy_enabled' => false,  // Enable for regulated industries
    ],
    
    // Capacity planning
    'capacity' => [
        'enabled' => false,  // Enable for Level 2
        'default_shifts_per_day' => 1,
        'default_hours_per_shift' => 8,
        'allow_overtime' => true,
        'alert_on_overload' => true,
    ],
];
```

---

## Success Metrics

| Metric | Target | Measurement Period | Why It Matters |
|--------|--------|-------------------|----------------|
| **Adoption Rate** | > 200 installations | 12 months | Validates manufacturing solution viability |
| **Production Efficiency** | > 85% OEE (Overall Equipment Effectiveness) | Ongoing | Measures production performance |
| **On-Time Delivery** | > 90% of work orders completed on/before due date | Ongoing | Measures planning accuracy |
| **Material Yield** | < 5% material waste (actual vs BOM standard) | Ongoing | Measures material efficiency |
| **Quality Pass Rate** | > 95% first-time pass rate | Ongoing | Measures quality management effectiveness |
| **Traceability Compliance** | 100% batch genealogy for regulated products | Ongoing | Critical for FDA/ISO compliance |
| **User Satisfaction** | > 4.0/5 rating | Quarterly survey | Overall usability and effectiveness |

---

## Development Phases

### Phase 1: Core Manufacturing (Weeks 1-8)
- Implement BOM management (single-level and multi-level)
- Implement basic work order creation and execution
- Implement material issue and consumption
- Implement production reporting
- Basic inventory integration
- Unit and feature tests for core flows

### Phase 2: Routing & Quality (Weeks 9-14)
- Implement routing/operations management
- Implement work center capacity tracking
- Implement inspection plans and quality inspection
- Implement quarantine management
- Implement production costing (standard vs actual)
- Feature tests for routing and quality scenarios

### Phase 3: Advanced Planning (Weeks 15-20)
- Implement Material Requirements Planning (MRP)
- Implement capacity requirements planning
- Implement production scheduling
- Integration with nexus-procurement (MRP-driven requisitions)
- Integration with nexus-accounting (cost posting)

### Phase 4: Compliance & Traceability (Weeks 21-24)
- Implement batch genealogy tracking
- Implement lot/serial traceability
- Implement recall management
- Regulatory compliance features (FDA, ISO)
- Security audit and penetration testing

### Phase 5: Optimization & Launch (Weeks 25-28)
- Performance tuning (BOM explosion optimization, query optimization)
- IoT/SCADA integration framework (optional)
- Comprehensive documentation
- Video tutorials and user guides
- Beta testing with 3-5 manufacturers
- Production deployment

---

## Dependencies

### Required
- PHP ≥ 8.3
- Laravel ≥ 12.x
- nexus-tenancy (multi-tenant isolation)
- nexus-sequencing (document numbering)
- nexus-settings (configuration management)
- nexus-audit-log (activity tracking and traceability)
- nexus-workflow (approval processes)
- nexus-backoffice (work center/department structure)
- nexus-inventory (CRITICAL: material consumption and finished goods receipt)

### Optional (for full functionality)
- nexus-accounting (production cost posting, variance posting)
- nexus-procurement (MRP-driven purchase requisitions)
- nexus-sales (link work orders to sales orders)
- nexus-project-management (custom manufacturing projects)

---

## Glossary

- **Bill of Materials (BOM):** List of components and quantities required to manufacture a product
- **Work Order:** Authorization to produce a specific quantity of a product
- **Routing:** Sequence of operations (steps) required to manufacture a product
- **Work Center:** Physical location or machine where operations are performed
- **Backflushing:** Automatic deduction of materials from inventory when production is reported
- **Lot Number:** Identifier for a batch of material produced or received together
- **Serial Number:** Unique identifier for an individual unit of product
- **Batch Genealogy:** Traceability record showing which raw material lots went into which finished goods batches
- **MRP (Material Requirements Planning):** Calculation of material needs based on demand
- **OEE (Overall Equipment Effectiveness):** Metric combining availability, performance, and quality
- **Standard Cost:** Predetermined cost per unit based on BOM and routing
- **Actual Cost:** Real cost incurred based on actual material consumed and labor hours
- **Variance:** Difference between standard and actual cost

---

**Document Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Ready for Review and Implementation Planning

---

## Notes on Bounded Context Coherence

This package intentionally violates the **Maximum Atomicity** principle because:

1. **Manufacturing is a tightly coupled business process** - splitting BOM, work orders, production reporting, and quality inspection into separate packages would create excessive orchestration overhead and violate manufacturing traceability requirements.

2. **Regulatory compliance demands tight coupling** - FDA 21 CFR Part 11, ISO 9001, and HACCP require complete traceability from raw materials to finished goods, which requires all manufacturing entities to reference each other.

3. **Workflow states are manufacturing-specific** - while we leverage nexus-workflow engine, the actual states (released, in_production, inspection_passed) are domain concepts that belong in the bounded context.

4. **Data ownership is clear** - BOMs, work orders, production reports, and quality inspections form a cohesive aggregate where entities reference each other tightly (work order references BOM, production report references work order, quality inspection references production report).

**This is intentional and aligns with Domain-Driven Design principles** where bounded contexts should be cohesive even if it means sacrificing some atomicity. The package remains **independently deployable and testable**, but it is **not atomically subdivided** into smaller packages.
