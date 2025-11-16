
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
