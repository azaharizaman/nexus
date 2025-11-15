nexus-supply-chain Package Requirements
Version: 1.0.0 Last Updated: November 15, 2025 Status: Initial Draft - Progressive Disclosure Model

Executive Summary
nexus-supply-chain is a progressive supply chain management engine for PHP/Laravel that scales from basic inventory tracking to enterprise SCM optimization.
The Problem We Solve
SCM packages often force choices:
	•	Simple tools (basic trackers) lack scale for complex chains.
	•	Enterprise systems (SAP SCM, Oracle SCM) are complex, costly, vendor-locked.
We solve both with progressive disclosure:
	1	Level 1: Basic SCM (5 minutes) - Add HasSupplyChain trait. Manage stock in-app. No extra tables.
	2	Level 2: Chain Automation (1 hour) - Database-driven suppliers, orders, logistics, scheduled.
	3	Level 3: Enterprise SCM (Production-ready) - AI forecasting, real-time optimization, compliance.
Core Philosophy
	1	Progressive Disclosure - Learn as needed.
	2	Backwards Compatible - Level 1 works post-upgrade.
	3	Headless Backend - API-only, no UI.
	4	Framework Agnostic Core - No Laravel in core.
	5	Extensible - Plugins for suppliers, logistics, algorithms.
Why This Approach Wins
For Mass Market (80%):
	•	Quick setup.
	•	No DB for basics.
	•	Easy learning.
	•	Fits existing models.
For Enterprise (20%):
	•	Demand planning, vendor management.
	•	Integrations (EDI, API).
	•	Risk analytics, compliance.
	•	Scalable flows.

Personas & User Stories
Personas
ID
Persona
Role
Primary Goal
P1
Mass Market Developer
Full-stack dev at startup
“Add inventory tracking to my Product model in 5 minutes”
P2
In-House SCM Developer
Backend dev at mid-size firm
“Build procurement-to-delivery flow integrated with data”
P3
End-User (Planner/Manager)
Business user
“Track suppliers, stock, shipments in one place”
P4
System Administrator
IT/DevOps
“Configure vendors, alerts without code”
User Stories
Level 1: Basic SCM (Mass Appeal)
ID
Persona
Story
Priority
US-001
P1
As a developer, add HasSupplyChain trait to manage stock
High
US-002
P1
Define inventory as array in model, no DB tables
High
US-003
P1
Call $model->scm()->updateStock($data) to adjust
High
US-004
P1
Call $model->scm()->can('adjust') for permissions
High
US-005
P1
Call $model->scm()->history() for logs
Medium
Level 2: Chain Automation
ID
Persona
Story
Priority
US-010
P2
Promote to DB-driven SCM without code changes
High
US-011
P2
Define suppliers, orders with stages
High
US-012
P2
Use conditional routing (e.g., qty < min)
High
US-013
P2
Parallel logistics (ship + track)
High
US-014
P2
Multi-vendor assignments
High
US-015
P3
Unified dashboard for stock/orders
High
US-016
P3
Log shipments with notes/attachments
High
Level 3: Enterprise SCM
ID
Persona
Story
Priority
US-020
P2
Auto-reorder low stock
High
US-021
P2
Demand forecasting with ML
High
US-022
P3
Delegate orders during absences
High
US-023
P2
Rollback failed shipments
Medium
US-024
P4
Configure vendor rules via admin
Medium
US-025
P2
Report on chain efficiency
Medium

Functional Requirements
FR-L1: Level 1 - Basic SCM (Mass Appeal)
ID
Requirement
Priority
Acceptance Criteria
FR-L1-001
HasSupplyChain trait for models
High
Add trait; define scm() array; no migrate; works instantly
FR-L1-002
In-model inventory definitions
High
Array for levels; store in model column; no external
FR-L1-003
scm()->updateStock($data) method
High
Adjust stock; events; validate; transaction
FR-L1-004
scm()->can($action) method
High
Boolean check; guards; no effects
FR-L1-005
scm()->history() method
Medium
Collection of changes; timestamps, actors
FR-L1-006
Guard conditions on actions
Medium
Callable; e.g., fn($stock) => $stock->qty > 0
FR-L1-007
Hooks (before/after)
Medium
Callbacks; e.g., notify after update
FR-L2: Level 2 - Chain Automation
ID
Requirement
Priority
Acceptance Criteria
FR-L2-001
DB-driven SCM definitions (JSON)
High
Table for schemas; same API; override in-model; hot-reload
FR-L2-002
Supplier/order stages
High
Type: “order”; assign vendors/roles; pause until action
FR-L2-003
Conditional routing
High
Expressions: ==, >, AND; access data
FR-L2-004
Parallel logistics
High
Array; simultaneous; wait for all
FR-L2-005
Inclusive gateways
Medium
Multiple true paths; sync at join
FR-L2-006
Multi-vendor strategies
High
Preferred, alternate; extensible
FR-L2-007
Dashboard API/service
High
ScmDashboard::forUser($id)->pending(); filter/sort
FR-L2-008
Actions (ship, receive)
High
Validate; log; comments/attachments; trigger next
FR-L2-009
Data validation
Medium
Schema in JSON; types: string, number, date
FR-L2-010
Plugin integrations
High
Async; built-in: EDI, API; extensible
FR-L3: Level 3 - Enterprise SCM
ID
Requirement
Priority
Acceptance Criteria
FR-L3-001
Reorder rules
High
On threshold; notify/order; history; scheduled
FR-L3-002
Forecasting ML
High
Models; predict demand; status: accurate, adjust
FR-L3-003
Delegation with ranges
High
Table: delegator, delegatee, dates; route auto; depth 3
FR-L3-004
Rollback logic
Medium
Compensation on failure; reverse order
FR-L3-005
Vendor config
Medium
DB rules; apply on init; admin optional
FR-L3-006
Timer system
High
Table; index trigger_at; workers; not cron
FR-EXT: Extensibility
ID
Requirement
Priority
Acceptance Criteria
FR-EXT-001
Custom integrations
High
IntegrationContract: execute, compensate
FR-EXT-002
Custom conditions
High
ConditionEvaluatorContract: evaluate
FR-EXT-003
Custom strategies
High
VendorStrategyContract: select
FR-EXT-004
Custom triggers
Medium
TriggerContract: webhook, event
FR-EXT-005
Custom storage
Low
StorageContract: Eloquent, Redis

Non-Functional Requirements
Performance Requirements
ID
Requirement
Target
Notes
PR-001
Action execution
< 100ms
Excl async
PR-002
Dashboard query (1,000 items)
< 500ms
Indexed
PR-003
Forecast run (10,000)
< 2s
Timers table
PR-004
Init
< 200ms
Validation incl
PR-005
Parallel sync (10)
< 100ms
Flow coord
Security Requirements
ID
Requirement
Scope
SR-001
Unauthorized actions prevent
Engine level
SR-002
Sanitize expressions
No injection
SR-003
Tenant isolation
Auto-scope
SR-004
Plugin sandbox
No malicious
SR-005
Audit changes
Immutable log
SR-006
RBAC integration
Permissions
Reliability Requirements
ID
Requirement
Notes
REL-001
ACID changes
Transactions
REL-002
Failed integrations no block
Queue
REL-003
Concurrency control
Locking
REL-004
Corruption protection
Validate
REL-005
Retry transients
Policy config
Scalability Requirements
ID
Requirement
Notes
SCL-001
Async integrations
Queue
SCL-002
Horizontal timers
Concurrent workers
SCL-003
Efficient queries
Indexes
SCL-004
100,000+ instances
Optimized
Maintainability Requirements
ID
Requirement
Notes
MAINT-001
Agnostic core
No deps in src/Core
MAINT-002
Laravel adapter
In Adapters/Laravel
MAINT-003
Test coverage
>80%, >90% core
MAINT-004
Separation
Inventory, order, logistics indep

Business Rules
ID
Rule
Level
BR-001
No negative stock
Config (L2)
BR-002
ACID all changes
All
BR-003
Auto reorder low
L3
BR-004
Compensation reverse
L3
BR-005
Delegation max 3
L3
BR-006
L1 compat with L2/3
All
BR-007
Instance per model
All
BR-008
Parallel complete all
L2
BR-009
Assign check delegation
L3
BR-010
Multi-vendor per strategy
L2

Data Requirements
Core SCM Tables
Table
Purpose
Key Fields
scm_definitions
JSON schemas
id, name, schema, active, version
scm_instances
Running SCM
id, subject_type, subject_id, def_id, state, data, start, end
scm_history
Audit
id, instance_id, event, before, after, actor, payload
Entity Tables
Table
Purpose
Key Fields
scm_suppliers
Vendors
id, instance_id, name, rating, status
scm_orders
Purchases
id, supplier_id, qty, status, delivery_date
scm_shipments
Logistics
id, order_id, carrier, tracking, eta
Automation Tables
Table
Purpose
Key Fields
scm_timers
Events
id, instance_id, type, trigger_at, payload
scm_forecasts
Predictions
id, instance_id, item, demand, period
scm_escalations
History
id, entity_id, level, from, to, reason

JSON Schema Specification
Level 1: In-Model SCM
use Nexus\SCM\Traits\HasSupplyChain;

class Product extends Model
{
    use HasSupplyChain;
    
    public function scm(): array
    {
        return [
            'entities' => [
                'inventory' => ['fields' => ['qty', 'min_level']],
            ],
        ];
    }
}

// Usage
$product->scm()->updateStock(['qty' => 100]);
Level 2: DB SCM with Entities
{
  "id": "procure-flow",
  "label": "Procurement Flow",
  "version": "1.0.0",
  "dataSchema": {
    "min_qty": { "type": "number" }
  },
  "entities": {
    "order": {
      "stages": ["pending", "shipped", "received"]
    }
  },
  "transitions": {
    "reorder": {
      "from": "low",
      "to": "pending",
      "condition": "data.qty < min_qty"
    }
  }
}
Level 3: Automation
{
  "id": "enterprise-scm",
  "forecast": {
    "model": "time_series"
  },
  "entities": {
    "order": {
      "automation": {
        "reorder": [
          {"threshold": "min_qty", "action": "place_order"}
        ]
      }
    }
  }
}
Built-in Conditions: expression, qty_check, etc.
Strategies: preferred, alternate, etc.

Package Structure
packages/nexus-supply-chain/
├── src/
│   ├── Core/
│   │   ├── Contracts/
│   │   │   ├── ScmEngineContract.php
│   │   │   ├── IntegrationContract.php
│   │   │   ├── ConditionContract.php
│   │   │   ├── StrategyContract.php
│   │   ├── Engine/
│   │   │   ├── ScmEngine.php
│   │   │   ├── OrderManager.php
│   │   ├── Services/
│   │   │   ├── SupplierService.php
│   │   │   ├── InventoryService.php
│   │   │   ├── ReorderService.php
│   │   ├── DTOs/
│   │       ├── ScmDefinition.php
│   │       ├── ScmInstance.php
│   ├── Strategies/
│   │   ├── PreferredStrategy.php
│   │   ├── AlternateStrategy.php
│   ├── Conditions/
│   │   ├── ExpressionCondition.php
│   ├── Plugins/
│   │   ├── EdiIntegration.php
│   │   ├── ApiIntegration.php
│   ├── Timers/
│   │   ├── TimerQueue.php
│   ├── Http/
│   │   └── Controllers/
│   │       ├── ScmController.php
│   ├── Adapters/
│   │   └── Laravel/
│   │       ├── Traits/
│   │       │   └── HasSupplyChain.php
│   │       ├── Models/
│   │       │   ├── ScmDefinition.php
│   │       │   ├── ScmInstance.php
│   │       ├── Services/
│   │       │   ├── ScmDashboard.php
│   │       ├── Commands/
│   │       │   ├── ProcessTimersCommand.php
│   │       └── ScmServiceProvider.php
│   └── Events/
│       ├── OrderPlaced.php
│       ├── StockUpdated.php
├── database/
│   └── migrations/
│       ├── 2025_11_15_000001_create_scm_definitions_table.php
│       ├── 2025_11_15_000002_create_scm_instances_table.php
└── tests/
    ├── Unit/
    │   ├── StockUpdateTest.php
    └── Feature/
        ├── Level1ScmTest.php
        ├── Level2AutomationTest.php

Success Metrics
Metric
Target
Period
Why
Adoption
>2,000 installs
6m
Mass appeal
Hello World Time
<5min
Ongoing
DX
Promotion Rate
>10% to L2
6m
Growth
Enterprise Use
>5% forecasts
6m
Niche
Bugs
<5 P0
6m
Quality
Coverage
>85%
Ongoing
Engine
Docs Quality
<10 questions/wk
3m
Clarity

Development Phases
Phase 1: Level 1 (Weeks 1-3)
	•	Trait impl
	•	In-model parser
	•	Basic engine
	•	Tests
Phase 2: Level 2 (Weeks 4-8)
	•	DB defs
	•	Entities
	•	Routing
	•	Strategies
	•	Tests
Phase 3: Level 3 (Weeks 9-12)
	•	Timers
	•	Forecasting
	•	Delegation
	•	Tests
Phase 4: Extensibility (Weeks 13-14)
	•	Custom conditions
	•	Integrations
	•	Docs
Phase 5: Launch (Weeks 15-16)
	•	Docs
	•	Tutorials
	•	Optimization
	•	Audit
	•	Beta

Testing Requirements
Unit Tests
	•	Update logic
	•	Strategies
	•	Conditions
	•	Timers
	•	Delegation
Feature Tests
	•	L1 adjustments
	•	L2 orders
	•	L3 reorders
	•	Multi-vendor
	•	Custom
Integration Tests
	•	Laravel (Eloquent, Queue)
	•	Tenancy
	•	Audit
	•	Load
Acceptance Tests
	•	All US
	•	<5min hello
	•	Promotion no changes

Dependencies
Required
	•	PHP ≥8.2
	•	DB: MySQL 8+, PG 12+, SQLite, SQL Server
Optional
	•	Laravel ≥12
	•	nexus-tenancy
	•	nexus-audit-log
	•	Redis

Glossary
	•	Level 1: Basic trait for inventory
	•	Level 2: DB suppliers, orders
	•	Level 3: Automation, forecasting
	•	Order: Purchase entity
	•	Shipment: Logistics stage
	•	Reorder: Threshold action
	•	Forecast: Demand prediction
	•	Delegation: Temp routing
	•	Compensation: Failure rollback
	•	Gateway: Decision point

Document Version: 1.0.0 Last Updated: November 15, 2025 Status: Ready for Review
