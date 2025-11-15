# Nexus SCM - Supply Chain Management

**Status:** 📋 Requirements Defined | ⏳ Phase 1 Development Planned

[![Latest Version](https://img.shields.io/packagist/v/nexus/scm.svg)](https://packagist.org/packages/nexus/scm)
[![License](https://img.shields.io/packagist/l/nexus/scm.svg)](LICENSE.md)

**Atomic, progressive supply chain management engine for PHP/Laravel that scales from basic inventory tracking to enterprise SCM optimization.**

---

## 🎯 What This Package Does

The **Nexus SCM** package provides **progressive supply chain management capabilities** with a unique progressive disclosure model that grows with your needs.

### ✅ This Package Handles:
- **Level 1: Basic SCM** - Simple trait-based inventory tracking (no database tables)
- **Level 2: Chain Automation** - Database-driven suppliers, orders, logistics scheduling
- **Level 3: Enterprise SCM** - AI forecasting, real-time optimization, compliance

### ❌ This Package Does NOT Handle:
- **Cross-package orchestration** - Handled by `Nexus\Erp` orchestration layer
- **UI/Frontend** - This is a headless backend package
- **Direct package-to-package calls** - Communication via Contracts and Events only

---

## 📚 Table of Contents

1. [Philosophy](#philosophy)
2. [Progressive Journey](#progressive-journey)
   - [Level 1: Basic SCM](#level-1-basic-scm-mass-appeal)
   - [Level 2: Chain Automation](#level-2-chain-automation)
   - [Level 3: Enterprise SCM](#level-3-enterprise-scm)
3. [Architecture](#architecture)
4. [Installation](#installation)
5. [Package Structure](#package-structure)
6. [Development Phases](#development-phases)
7. [Testing Requirements](#testing-requirements)
8. [Dependencies](#dependencies)

---

## Philosophy

### The Problem We Solve

SCM packages often force a choice:
- **Simple tools** (basic trackers) lack scale for complex chains
- **Enterprise systems** (SAP SCM, Oracle SCM) are complex, costly, vendor-locked

We solve both with **progressive disclosure**:

1. **Level 1**: Basic SCM (5 minutes) - Add `HasSupplyChain` trait, manage stock in-app, no extra tables
2. **Level 2**: Chain Automation (1 hour) - Database-driven suppliers, orders, logistics, scheduled
3. **Level 3**: Enterprise SCM (Production-ready) - AI forecasting, real-time optimization, compliance

### Core Principles

1. **Progressive Disclosure** - Learn as needed
2. **Backwards Compatible** - Level 1 works post-upgrade
3. **Headless Backend** - API-only, no UI
4. **Framework Agnostic Core** - No Laravel in `src/Core/`
5. **Maximum Atomicity** - Independent, testable, zero cross-package coupling
6. **Contract-Driven** - Communicate via Interfaces and Events

### Why This Approach Wins

**For Mass Market (80%):**
- Quick setup (5 minutes)
- No DB for basics
- Easy learning curve
- Fits existing models

**For Enterprise (20%):**
- Demand planning, vendor management
- Integrations (EDI, API)
- Risk analytics, compliance
- Scalable flows

---

## Progressive Journey

### Level 1: Basic SCM (Mass Appeal)

**For:** Mass market developers who just need basic inventory tracking  
**Complexity:** Simple trait addition  
**Use When:** You need stock management without complex supply chain logic

#### Implementation Location
- **Laravel Adapter**: `src/Adapters/Laravel/Traits/HasSupplyChain.php`
- **Why**: This is a Laravel-specific convenience feature, not core business logic

#### Basic Usage

```php
use Nexus\SCM\Adapters\Laravel\Traits\HasSupplyChain;

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
$product->scm()->can('adjust');  // Check permissions
$product->scm()->history();      // Get change log
```

#### User Stories (Level 1)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| US-001 | Developer | As a developer, add HasSupplyChain trait to manage stock | High |
| US-002 | Developer | Define inventory as array in model, no DB tables | High |
| US-003 | Developer | Call `$model->scm()->updateStock($data)` to adjust | High |
| US-004 | Developer | Call `$model->scm()->can('adjust')` for permissions | High |
| US-005 | Developer | Call `$model->scm()->history()` for logs | Medium |

---

### Level 2: Chain Automation

**For:** In-house SCM developers needing procurement-to-delivery flows  
**Complexity:** Database-driven configuration + core engine  
**Use When:** You need suppliers, purchase orders, logistics tracking

#### Implementation Location
- **Core Engine**: `src/Core/Engine/ScmEngine.php`
- **Core Services**: `src/Core/Services/` (SupplierService, InventoryService, ReorderService)
- **Laravel Adapter**: `src/Adapters/Laravel/Models/` (ScmDefinition, ScmInstance, etc.)
- **Why**: Core business logic in `Core/`, Laravel-specific persistence in `Adapters/Laravel/`

#### Database-Driven SCM Definition

```json
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
```

#### User Stories (Level 2)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| US-010 | Developer | Promote to DB-driven SCM without code changes | High |
| US-011 | Developer | Define suppliers, orders with stages | High |
| US-012 | Developer | Use conditional routing (e.g., qty < min) | High |
| US-013 | Developer | Parallel logistics (ship + track) | High |
| US-014 | Developer | Multi-vendor assignments | High |
| US-015 | End-User | Unified dashboard for stock/orders | High |
| US-016 | End-User | Log shipments with notes/attachments | High |

---

### Level 3: Enterprise SCM

**For:** Advanced users needing AI forecasting and real-time optimization  
**Complexity:** Enterprise features + ML integration  
**Use When:** Production systems requiring demand forecasting and compliance

#### Implementation Location
- **Core Services**: `src/Core/Services/ForecastingService.php`, `src/Core/Services/ReorderService.php`
- **Automation**: `src/Timers/TimerQueue.php`
- **Why**: Advanced business logic remains in core, can be tested independently

#### Enterprise Features

```json
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
```

#### User Stories (Level 3)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| US-020 | Developer | Auto-reorder low stock | High |
| US-021 | Developer | Demand forecasting with ML | High |
| US-022 | End-User | Delegate orders during absences | High |
| US-023 | Developer | Rollback failed shipments | Medium |
| US-024 | Admin | Configure vendor rules via admin | Medium |
| US-025 | Developer | Report on chain efficiency | Medium |

---

## Architecture

### Maximum Atomicity Compliance

This package adheres strictly to the **Maximum Atomicity** principle defined in the system architectural document:

1. **Independent Testability** ✅
   - Complete test suite runs with `composer test` in isolation
   - No Nexus ERP dependencies required for core functionality
   - Mock-based testing for all contracts

2. **Zero Cross-Package Coupling** ✅
   - No direct dependencies on other Nexus atomic packages
   - Communication via Contracts (Interfaces) defined in `Nexus\Erp`
   - Event-driven architecture for reactive updates

3. **Headless by Design** ✅
   - No Blade views or frontend logic
   - Pure API-driven functionality
   - UI implementation delegated to consumer applications

### Package Responsibilities

**✅ This Package Owns:**
- Supply chain business logic (inventory, suppliers, orders)
- SCM workflow engine and state management
- Demand forecasting algorithms
- Database schema for SCM entities

**❌ Not This Package's Responsibility:**
- Multi-package orchestration → `Nexus\Erp` orchestration layer
- Authentication/Authorization → `nexus-identity-management`
- Multi-tenancy → `nexus-tenancy`
- Document numbering → `nexus-sequencing`
- Audit logging → `nexus-audit-log`

### Communication Patterns

**With Other Packages (via Nexus\Erp):**
```php
// This package emits events
event(new OrderPlacedEvent($order));
event(new StockUpdatedEvent($item, $quantity));

// Nexus\Erp orchestration layer listens and coordinates
class HandleOrderPlaced
{
    public function __construct(
        private readonly SequencingContract $sequencing,
        private readonly TenantManagerContract $tenantManager
    ) {}
    
    public function handle(OrderPlacedEvent $event): void
    {
        // Orchestrate across packages
        $poNumber = $this->sequencing->generate('purchase-orders');
        // ... coordinate actions
    }
}
```

---

## Installation

### For Development (Monorepo Context)

This package is part of the Nexus ERP monorepo. No separate installation needed.

### For Standalone Use (Future)

```bash
composer require nexus/scm
```

```bash
php artisan vendor:publish --tag=scm-config
php artisan vendor:publish --tag=scm-migrations
php artisan migrate
```

---

## Package Structure

```
packages/nexus-scm/
├── src/
│   ├── Core/                           # ⭐ Framework-agnostic business logic
│   │   ├── Contracts/                  # Interfaces for DI
│   │   │   ├── ScmEngineContract.php
│   │   │   ├── IntegrationContract.php
│   │   │   ├── ConditionContract.php
│   │   │   └── StrategyContract.php
│   │   ├── Engine/                     # Core SCM workflow engine
│   │   │   ├── ScmEngine.php
│   │   │   └── OrderManager.php
│   │   ├── Services/                   # Pure business logic
│   │   │   ├── SupplierService.php
│   │   │   ├── InventoryService.php
│   │   │   └── ReorderService.php
│   │   └── DTOs/                       # Data transfer objects
│   │       ├── ScmDefinition.php
│   │       └── ScmInstance.php
│   ├── Strategies/                     # Pluggable strategies
│   │   ├── PreferredStrategy.php
│   │   └── AlternateStrategy.php
│   ├── Conditions/                     # Conditional logic
│   │   └── ExpressionCondition.php
│   ├── Plugins/                        # External integrations
│   │   ├── EdiIntegration.php
│   │   └── ApiIntegration.php
│   ├── Timers/                         # Scheduled automation
│   │   └── TimerQueue.php
│   ├── Events/                         # Domain events
│   │   ├── OrderPlacedEvent.php
│   │   └── StockUpdatedEvent.php
│   └── Adapters/                       # ⚡ Framework-specific implementations
│       └── Laravel/
│           ├── Traits/                 # Level 1: Laravel trait
│           │   └── HasSupplyChain.php
│           ├── Models/                 # Laravel Eloquent models
│           │   ├── ScmDefinition.php
│           │   ├── ScmInstance.php
│           │   ├── ScmSupplier.php
│           │   ├── ScmOrder.php
│           │   └── ScmShipment.php
│           ├── Services/               # Laravel-specific services
│           │   └── ScmDashboard.php
│           ├── Commands/               # Artisan commands
│           │   └── ProcessTimersCommand.php
│           ├── Controllers/            # Internal API endpoints (for package testing only, not public API)
│           │   └── ScmController.php
│           └── ScmServiceProvider.php  # Laravel service provider
├── database/
│   └── migrations/
│       ├── 2025_11_15_000001_create_scm_definitions_table.php
│       ├── 2025_11_15_000002_create_scm_instances_table.php
│       ├── 2025_11_15_000003_create_scm_suppliers_table.php
│       ├── 2025_11_15_000004_create_scm_orders_table.php
│       ├── 2025_11_15_000005_create_scm_shipments_table.php
│       ├── 2025_11_15_000006_create_scm_timers_table.php
│       └── 2025_11_15_000007_create_scm_forecasts_table.php
├── tests/
│   ├── Unit/                           # Core logic tests
│   │   ├── StockUpdateTest.php
│   │   └── SupplierStrategyTest.php
│   └── Feature/                        # Integration tests
│       ├── Level1ScmTest.php
│       ├── Level2AutomationTest.php
│       └── Level3EnterpriseTest.php
├── config/
│   └── scm.php                         # Package configuration
├── composer.json
├── README.md                           # This file
├── REQUIREMENTS.md                     # Redirect to requirements documents
└── REQUIREMENTS.legacy.md              # Detailed requirements (legacy)
```

### Key Structural Principles

1. **src/Core/**: Pure PHP, no Laravel dependencies, 100% testable in isolation
2. **src/Adapters/Laravel/**: All Laravel-specific code (Eloquent, Commands, Controllers)
3. **src/Core/Events/**: Domain events for cross-package communication (via Nexus\Erp)
4. **src/Core/Contracts/**: Interfaces for dependency inversion

---

## Functional Requirements

### FR-L1: Level 1 - Basic SCM (Mass Appeal)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| FR-L1-001 | HasSupplyChain trait for models | High | Add trait; define scm() array; no migrate; works instantly |
| FR-L1-002 | In-model inventory definitions | High | Array for levels; store in model column; no external |
| FR-L1-003 | scm()->updateStock($data) method | High | Adjust stock; events; validate; transaction |
| FR-L1-004 | scm()->can($action) method | High | Boolean check; guards; no effects |
| FR-L1-005 | scm()->history() method | Medium | Collection of changes; timestamps, actors |
| FR-L1-006 | Guard conditions on actions | Medium | Callable; e.g., fn($stock) => $stock->qty > 0 |
| FR-L1-007 | Hooks (before/after) | Medium | Callbacks; e.g., notify after update |

### FR-L2: Level 2 - Chain Automation

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| FR-L2-001 | DB-driven SCM definitions (JSON) | High | Table for schemas; same API; override in-model; hot-reload |
| FR-L2-002 | Supplier/order stages | High | Type: "order"; assign vendors/roles; pause until action |
| FR-L2-003 | Conditional routing | High | Expressions: ==, >, AND; access data |
| FR-L2-004 | Parallel logistics | High | Array; simultaneous; wait for all |
| FR-L2-005 | Inclusive gateways | Medium | Multiple true paths; sync at join |
| FR-L2-006 | Multi-vendor strategies | High | Preferred, alternate; extensible |
| FR-L2-007 | Dashboard API/service | High | ScmDashboard::forUser($id)->pending(); filter/sort |
| FR-L2-008 | Actions (ship, receive) | High | Validate; log; comments/attachments; trigger next |
| FR-L2-009 | Data validation | Medium | Schema in JSON; types: string, number, date |
| FR-L2-010 | Plugin integrations | High | Async; built-in: EDI, API; extensible |

### FR-L3: Level 3 - Enterprise SCM

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| FR-L3-001 | Reorder rules | High | On threshold; notify/order; history; scheduled |
| FR-L3-002 | Forecasting ML | High | Models; predict demand; status: accurate, adjust |
| FR-L3-003 | Delegation with ranges | High | Table: delegator, delegatee, dates; route auto; depth 3 |
| FR-L3-004 | Rollback logic | Medium | Compensation on failure; reverse order |
| FR-L3-005 | Vendor config | Medium | DB rules; apply on init; admin optional |
| FR-L3-006 | Timer system | High | Table; index trigger_at; workers; not cron |

### FR-EXT: Extensibility

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| FR-EXT-001 | Custom integrations | High | IntegrationContract: execute, compensate |
| FR-EXT-002 | Custom conditions | High | ConditionEvaluatorContract: evaluate |
| FR-EXT-003 | Custom strategies | High | VendorStrategyContract: select |
| FR-EXT-004 | Custom triggers | Medium | TriggerContract: webhook, event |
| FR-EXT-005 | Custom storage | Low | StorageContract: Eloquent, Redis |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Notes |
|----|-------------|--------|-------|
| PR-001 | Action execution | < 100ms | Excl async |
| PR-002 | Dashboard query (1,000 items) | < 500ms | Indexed |
| PR-003 | Forecast run (10,000) | < 2s | Timers table |
| PR-004 | Init | < 200ms | Validation incl |
| PR-005 | Parallel sync (10) | < 100ms | Flow coord |

### Security Requirements

| ID | Requirement | Scope |
|----|-------------|-------|
| SR-001 | Unauthorized actions prevent | Engine level |
| SR-002 | Sanitize expressions | No injection |
| SR-003 | Tenant isolation | Auto-scope |
| SR-004 | Plugin sandbox | No malicious |
| SR-005 | Audit changes | Immutable log |
| SR-006 | RBAC integration | Permissions |

### Reliability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| REL-001 | ACID changes | Transactions |
| REL-002 | Failed integrations no block | Queue |
| REL-003 | Concurrency control | Locking |
| REL-004 | Corruption protection | Validate |
| REL-005 | Retry transients | Policy config |

### Scalability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| SCL-001 | Async integrations | Queue |
| SCL-002 | Horizontal timers | Concurrent workers |
| SCL-003 | Efficient queries | Indexes |
| SCL-004 | 100,000+ instances | Optimized |

### Maintainability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| MAINT-001 | Agnostic core | No deps in src/Core |
| MAINT-002 | Laravel adapter | In Adapters/Laravel |
| MAINT-003 | Test coverage | >80%, >90% core |
| MAINT-004 | Separation | Inventory, order, logistics indep |

---

## Business Rules

| ID | Rule | Level |
|----|------|-------|
| BR-001 | No negative stock | Config (L2) |
| BR-002 | ACID all changes | All |
| BR-003 | Auto reorder low | L3 |
| BR-004 | Compensation reverse | L3 |
| BR-005 | Delegation max 3 | L3 |
| BR-006 | L1 compat with L2/3 | All |
| BR-007 | Instance per model | All |
| BR-008 | Parallel complete all | L2 |
| BR-009 | Assign check delegation | L3 |
| BR-010 | Multi-vendor per strategy | L2 |

---

## Data Requirements

### Core SCM Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| scm_definitions | JSON schemas | id, name, schema, active, version |
| scm_instances | Running SCM | id, subject_type, subject_id, def_id, state, data, start, end |
| scm_history | Audit | id, instance_id, event, before, after, actor, payload |

### Entity Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| scm_suppliers | Vendors | id, instance_id, name, rating, status |
| scm_orders | Purchases | id, supplier_id, qty, status, delivery_date |
| scm_shipments | Logistics | id, order_id, carrier, tracking, eta |

### Automation Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| scm_timers | Events | id, instance_id, type, trigger_at, payload |
| scm_forecasts | Predictions | id, instance_id, item, demand, period |
| scm_escalations | History | id, entity_id, level, from, to, reason |

---

## Development Phases

### Phase 1: Level 1 (Weeks 1-3)
- [ ] Trait implementation
- [ ] In-model parser
- [ ] Basic engine
- [ ] Unit tests (>90% coverage)

### Phase 2: Level 2 (Weeks 4-8)
- [ ] DB definitions
- [ ] Entities (suppliers, orders, shipments)
- [ ] Routing logic
- [ ] Strategy patterns
- [ ] Feature tests

### Phase 3: Level 3 (Weeks 9-12)
- [ ] Timers system
- [ ] Forecasting service
- [ ] Delegation logic
- [ ] Integration tests

### Phase 4: Extensibility (Weeks 13-14)
- [ ] Custom conditions
- [ ] External integrations (EDI, API)
- [ ] Documentation

### Phase 5: Launch (Weeks 15-16)
- [ ] API documentation
- [ ] Tutorials
- [ ] Performance optimization
- [ ] Security audit
- [ ] Beta release

---

## Testing Requirements

### Unit Tests (src/Core/)
- [ ] Stock update logic
- [ ] Supplier strategies
- [ ] Condition evaluators
- [ ] Timer scheduling
- [ ] Delegation rules

### Feature Tests (Laravel adapter)
- [ ] L1: Stock adjustments via trait
- [ ] L2: Purchase order flows
- [ ] L3: Auto-reorder triggers
- [ ] Multi-vendor selection
- [ ] Custom integration plugins

### Integration Tests (with Nexus\Erp)
- [ ] Event emission and handling
- [ ] Contract binding verification
- [ ] Tenant isolation
- [ ] Audit logging
- [ ] Load testing (1,000 concurrent orders)

### Acceptance Tests
- [ ] All user stories validated
- [ ] Hello World <5min (Level 1)
- [ ] Promotion L1→L2 with no code changes
- [ ] Performance targets met

### Test Coverage Goals
- **Core (src/Core/)**: >90%
- **Adapters (src/Adapters/)**: >80%
- **Overall**: >85%

---

## Dependencies

### Required
- PHP ≥ 8.2
- Database: MySQL 8+, PostgreSQL 12+, SQLite, SQL Server

### Optional (Laravel Context)
- Laravel ≥ 12
- `nexus/tenancy` (for multi-tenancy)
- `nexus/audit-log` (for audit trails)
- `nexus/sequencing` (for document numbering)
- Redis (for queue and caching)

### Development
- `pestphp/pest` ^4.0 (testing)
- `laravel/pint` ^1.0 (code style)

---

## Success Metrics

| Metric | Target | Period | Why |
|--------|--------|--------|-----|
| Adoption | >2,000 installs | 6m | Mass appeal |
| Hello World Time | <5min | Ongoing | Developer experience |
| Promotion Rate | >10% to L2 | 6m | Value progression |
| Enterprise Use | >5% using forecasts | 6m | Advanced features |
| Critical Bugs | <5 P0 | 6m | Quality |
| Test Coverage | >85% | Ongoing | Reliability |
| Docs Quality | <10 questions/wk | 3m | Clarity |

---

## Integration with Nexus ERP

### Event-Driven Communication

**This package emits:**
- `OrderPlacedEvent` - When a purchase order is created
- `StockUpdatedEvent` - When inventory level changes
- `ShipmentCreatedEvent` - When a shipment is dispatched
- `ForecastGeneratedEvent` - When demand forecast is updated

**Nexus\Erp orchestration layer listens and coordinates** with:
- `nexus-sequencing` - Generate PO numbers
- `nexus-tenancy` - Enforce tenant isolation
- `nexus-audit-log` - Record supply chain activities
- `nexus-accounting` - Update GL when orders placed

### Contract Interfaces (defined in Nexus\Erp)

```php
// Nexus\Erp defines contracts for SCM to implement
interface ScmEngineContract
{
    public function executeWorkflow(string $definitionId, array $data): ScmInstance;
    public function getOrderStatus(string $orderId): string;
}

// This package implements the contract
class ScmEngine implements ScmEngineContract
{
    // Implementation
}
```

---

## License

MIT License - See [LICENSE.md](LICENSE.md)

---

## Support

- **Documentation**: [REQUIREMENTS.legacy.md](REQUIREMENTS.legacy.md) (legacy detailed spec)
- **Issues**: GitHub Issues
- **Discussions**: GitHub Discussions

---

**Package Status:**
- 📋 **Requirements**: Complete and documented
- ⏳ **Phase 1**: Development planned (Q1 2026)
- 🎯 **Target**: Progressive disclosure SCM engine

*This package maintains **Maximum Atomicity** - it handles supply chain management logic independently while integrating seamlessly with Nexus ERP via contracts and events.*
