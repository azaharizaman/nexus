# AuditLogger and Uom Package Requirements

This document outlines the comprehensive requirements for refactoring both `Nexus\AuditLogger` and `Nexus\Uom` packages to comply with the new architecture documented in `.github/copilot-instructions.md`.

## Architecture Summary

**Key Principles:**
1. Packages must be framework-agnostic (pure PHP logic)
2. No Laravel-specific code (Models, Migrations, Observers, Policies) in packages
3. Define persistence needs via Contracts (interfaces)
4. All concrete implementations in `apps/Atomy`

---

## Nexus\AuditLogger Package Requirements

### Overview
Comprehensive audit logging system for tracking all system activities, changes, and user actions with immutable, searchable logs that support compliance requirements.

### Functional Requirements

| ID | Requirement | Implementation Status | Files/Classes | Notes |
|----|-------------|----------------------|---------------|-------|
| FR-AUD-001 | Automatically capture CRUD operations for auditable models | In Progress | `AuditLogInterface`, `AuditLogRepositoryContract` | Contracts defined; traits/observers to move to apps/Atomy |
| FR-AUD-002 | Record before/after state for all model updates | In Progress | `AuditLogManager` | Manager service created |
| FR-AUD-003 | Capture user context (who, IP, user agent, timestamp) | In Progress | `AuditLogInterface::getCauserId()`, `getIpAddress()`, `getUserAgent()` | Interface methods defined |
| FR-AUD-004 | Support tenant-based isolation of audit logs | In Progress | `AuditLogInterface::getTenantId()` | Tenant scoping in repository needed |
| FR-AUD-005 | Provide full-text search across descriptions and properties | In Progress | `AuditLogRepositoryContract::search()` | Repository contract defines interface |
| FR-AUD-006 | Filter logs by date range, user, entity type, entity ID, event type | In Progress | `AuditLogRepositoryContract` methods | Multiple query methods defined |
| FR-AUD-007 | Export audit logs to CSV, JSON, and PDF formats | Partially Completed | `LogExporterContract`, `LogExporterService` | Needs refactoring |
| FR-AUD-008 | Mask sensitive fields (passwords, tokens, secrets) automatically | Completed | `AuditLogManager::maskSensitiveData()` | Framework-agnostic |
| FR-AUD-009 | Support batch operations with UUID grouping | In Progress | `AuditLogInterface::getBatchUuid()` | Interface method defined |
| FR-AUD-010 | Configurable retention policies with automated purging | In Progress | `AuditLogRepositoryContract::purgeExpired()` | Purge job needed |
| FR-AUD-011 | Support multiple audit levels (Low, Medium, High, Critical) | In Progress | `AuditLogInterface::getAuditLevel()` | Interface methods defined |
| FR-AUD-012 | Asynchronous logging via queue | Partially Completed | `LogActivityJob` | Needs refactoring |
| FR-AUD-013 | Event-driven architecture with notifications | Partially Completed | `ActivityLoggedEvent`, Listeners | Needs refactoring |
| FR-AUD-014 | RESTful API endpoints for log retrieval | Planned | - | To be implemented in apps/Atomy |
| FR-AUD-015 | Activity statistics and reporting | In Progress | `AuditLogRepositoryContract::getStatistics()` | Interface defined |

### Performance Requirements

| ID | Requirement | Target | Implementation |
|----|-------------|--------|----------------|
| PR-AUD-001 | Audit log creation time (async) | < 50ms (p95) | Queue-based implementation |
| PR-AUD-002 | Search query response time | < 500ms for 100K+ entries | Database indexes needed |
| PR-AUD-003 | Export generation time | < 5s for 10K entries (CSV) | Exporter optimization |
| PR-AUD-004 | Scale to audit log entries | 1M+ per tenant | Partitioning strategy |
| PR-AUD-005 | Purge operation time | < 10s for 100K entries | Batch deletion |

### Security Requirements

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| SR-AUD-001 | Immutable audit logs (append-only) | In Progress | Repository contract enforces |
| SR-AUD-002 | Strict tenant isolation | Planned | Repository implementation needed |
| SR-AUD-003 | Automatic sensitive field masking | Completed | Configurable patterns |
| SR-AUD-004 | Role-based access control | Planned | Authorization policies needed |
| SR-AUD-005 | Cryptographic verification of log integrity | Planned | Hash chain or digital signatures |
| SR-AUD-006 | Meta-auditing (audit the audit system) | Planned | Self-auditing capability |

### Business Rules

| ID | Rule | Status | Implementation |
|----|------|--------|----------------|
| BR-AUD-001 | Logs MUST include log_name, description, timestamp | Completed | Validation in AuditLogManager |
| BR-AUD-002 | Audit level MUST be 1-4 (Low, Medium, High, Critical) | Completed | Validation enforces |
| BR-AUD-003 | Retention days CANNOT be negative | Completed | Validation enforces |
| BR-AUD-004 | System activities logged with causer_type = null | Planned | Context detection needed |
| BR-AUD-005 | High-value entities default to Critical level | Planned | Model-specific configuration |
| BR-AUD-006 | Batch operations MUST use single batch_uuid | In Progress | Interface method defined |
| BR-AUD-007 | Expired logs purged automatically via scheduled job | Planned | Command needed in apps/Atomy |

### Architecture Compliance

| ID | Requirement | Status | Location |
|----|-------------|--------|----------|
| ARCH-AUD-001 | Framework-agnostic core services | Completed | `AuditLogManager`, Contracts |
| ARCH-AUD-002 | All data structures via interfaces | Completed | `AuditLogInterface` |
| ARCH-AUD-003 | All persistence via repository interface | Completed | `AuditLogRepositoryContract` |
| ARCH-AUD-004 | Business logic in service layer | Completed | `AuditLogManager` |
| ARCH-AUD-005 | Migrations in application layer | Planned | apps/Atomy/database/migrations |
| ARCH-AUD-006 | Eloquent models in application layer | Planned | Move to apps/Atomy |
| ARCH-AUD-007 | Repository implementations in application | Planned | apps/Atomy/app/Repositories |
| ARCH-AUD-008 | Traits/Observers in application layer | Planned | Move to apps/Atomy |
| ARCH-AUD-009 | IoC bindings in application provider | Planned | apps/Atomy providers |
| ARCH-AUD-010 | No laravel/framework dependency | Planned | Update composer.json |

---

## Nexus\Uom Package Requirements

### Overview
Sophisticated Unit of Measurement management system providing conversions, compound units, packaging hierarchies, and custom unit definitions.

### Functional Requirements

| ID | Requirement | Implementation Status | Files/Classes | Notes |
|----|-------------|----------------------|---------------|-------|
| FR-UOM-001 | Define and manage unit types/categories (mass, length, volume, etc.) | In Progress | `UomTypeInterface`, `UomTypeRepositoryInterface` | Contracts defined |
| FR-UOM-002 | Define units with conversion factors to base unit | In Progress | `UomUnitInterface`, `UomUnitRepositoryInterface` | Contracts defined |
| FR-UOM-003 | Support direct conversion between units of same type | Planned | `UnitConverter` interface exists | Needs refactoring |
| FR-UOM-004 | Support conversion via base unit (from→base→to) | Planned | `DefaultUnitConverter` exists | Needs refactoring |
| FR-UOM-005 | Handle compound units (e.g., kg/m², N·m) | Planned | Models exist | Needs interface abstraction |
| FR-UOM-006 | Support unit aliases and alternate symbols | Planned | `UomAlias` model exists | Needs interface abstraction |
| FR-UOM-007 | Define packaging hierarchies (box, pallet, container) | Planned | `UomPackaging` model exists | Needs interface abstraction |
| FR-UOM-008 | Item-specific unit configurations | Planned | `UomItem` model exists | Needs interface abstraction |
| FR-UOM-009 | Custom user-defined units | Planned | `UomCustomUnit` model exists | Needs interface abstraction |
| FR-UOM-010 | Custom conversion rules | Planned | `UomCustomConversion` model exists | Needs interface abstraction |
| FR-UOM-011 | Conversion audit logging | Planned | `UomConversionLog` model exists | Needs interface abstraction |
| FR-UOM-012 | Unit groups/systems (metric, imperial, SI) | Planned | `UomUnitGroup` model exists | Needs interface abstraction |
| FR-UOM-013 | High-precision calculations using Brick\Math | Completed | `DefaultUnitConverter` | Already implemented |
| FR-UOM-014 | Configurable precision and rounding modes | Completed | `DefaultUnitConverter` | Already implemented |
| FR-UOM-015 | Offset-based conversions (for temperature) | Completed | `UomUnitInterface::getOffset()` | Interface method defined |

### Data Model Requirements

| ID | Entity | Requirement | Status |
|----|--------|-------------|--------|
| DM-UOM-001 | UomType | Type code, name, description, base unit, is_active | Interface defined |
| DM-UOM-002 | UomUnit | Code, name, type_id, conversion_factor, offset, precision, is_base, is_active | Interface defined |
| DM-UOM-003 | UomConversion | Source_unit_id, target_unit_id, factor, offset, formula, is_bidirectional | Interface defined |
| DM-UOM-004 | UomAlias | Unit_id, alias_code, alias_name | Needs interface |
| DM-UOM-005 | UomCompoundUnit | Type_id, code, name, formula | Needs interface |
| DM-UOM-006 | UomCompoundComponent | Compound_unit_id, unit_id, exponent, position | Needs interface |
| DM-UOM-007 | UomUnitGroup | Code, name, description | Needs interface |
| DM-UOM-008 | UomPackaging | Code, name, base_unit_id, package_unit_id, quantity | Needs interface |
| DM-UOM-009 | UomItem | Item_code, default_unit_id | Needs interface |
| DM-UOM-010 | UomItemPackaging | Item_id, packaging_id, is_default | Needs interface |
| DM-UOM-011 | UomCustomUnit | Tenant_id, code, name, base_unit_id, factor | Needs interface |
| DM-UOM-012 | UomCustomConversion | Tenant_id, source_unit_id, target_unit_id, factor | Needs interface |
| DM-UOM-013 | UomConversionLog | Tenant_id, from_unit_id, to_unit_id, value, result, timestamp | Needs interface |

### Performance Requirements

| ID | Requirement | Target | Implementation |
|----|-------------|--------|----------------|
| PR-UOM-001 | Simple unit conversion | < 10ms | Caching of base units |
| PR-UOM-002 | Compound unit explosion | < 50ms for 10 components | Optimized calculation |
| PR-UOM-003 | Packaging calculation | < 20ms for 5-level hierarchy | Recursive optimization |
| PR-UOM-004 | Batch conversions | 1000 conversions < 500ms | Bulk processing |
| PR-UOM-005 | Unit search/lookup | < 50ms | Indexed database queries |

### Security Requirements

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| SR-UOM-001 | Tenant isolation for custom units | Planned | Repository scoping needed |
| SR-UOM-002 | Prevent circular conversion definitions | Planned | Validation logic needed |
| SR-UOM-003 | Validate conversion factor ranges | Planned | Business rule validation |
| SR-UOM-004 | Audit custom unit modifications | Planned | Integration with AuditLogger |
| SR-UOM-005 | Role-based access for unit management | Planned | Authorization policies |

### Business Rules

| ID | Rule | Status | Implementation |
|----|------|--------|----------------|
| BR-UOM-001 | Each type MUST have exactly one base unit | Planned | Validation in service |
| BR-UOM-002 | Base unit MUST have conversion_factor = 1 | Planned | Validation in service |
| BR-UOM-003 | Conversions MUST be within same type | Planned | Type compatibility check |
| BR-UOM-004 | Compound unit components MUST be compatible | Planned | Component validation |
| BR-UOM-005 | Packaging hierarchy MUST not be circular | Planned | Circular reference check |
| BR-UOM-006 | Custom units CANNOT override system units | Planned | Name collision check |
| BR-UOM-007 | Precision MUST be 0-12 decimal places | Planned | Validation in service |
| BR-UOM-008 | Conversion factors MUST be positive | Planned | Validation in service |
| BR-UOM-009 | Unit codes MUST be unique within type | Planned | Unique constraint |
| BR-UOM-010 | Aliases MUST reference active units only | Planned | Active unit check |

### Architecture Compliance

| ID | Requirement | Status | Location |
|----|-------------|--------|----------|
| ARCH-UOM-001 | Framework-agnostic core services | Planned | Services need refactoring |
| ARCH-UOM-002 | All data structures via interfaces | In Progress | 3 interfaces defined, 10 more needed |
| ARCH-UOM-003 | All persistence via repository interfaces | In Progress | 3 repo interfaces defined |
| ARCH-UOM-004 | Business logic in service layer | Partially Completed | Services exist but need refactoring |
| ARCH-UOM-005 | Migrations in application layer | Planned | Move to apps/Atomy |
| ARCH-UOM-006 | All 13 Eloquent models in application | Planned | Move to apps/Atomy |
| ARCH-UOM-007 | Repository implementations in application | Planned | Create in apps/Atomy |
| ARCH-UOM-008 | IoC bindings in application provider | Planned | apps/Atomy providers |
| ARCH-UOM-009 | No illuminate/support dependency if possible | Planned | Evaluate necessity |
| ARCH-UOM-010 | Remove database folder from package | Planned | Move migrations and factories |

---

## Refactoring Implementation Plan

### Phase 1: AuditLogger (Priority: High)

1. **Move Laravel-specific code to apps/Atomy:**
   - Move `AuditLog` model → `apps/Atomy/app/Models/AuditLog.php`
   - Move `Auditable` trait → `apps/Atomy/app/Traits/Auditable.php`
   - Move `LogsSystemActivity` trait → `apps/Atomy/app/Traits/LogsSystemActivity.php`
   - Move `AuditObserver` → `apps/Atomy/app/Observers/AuditObserver.php`
   - Move Events/Jobs/Listeners to apps/Atomy

2. **Create repository implementation:**
   - Create `apps/Atomy/app/Repositories/DatabaseAuditLogRepository.php`
   - Implement `AuditLogRepositoryContract`
   - Make `AuditLog` model implement `AuditLogInterface`

3. **Update bindings:**
   - Bind repository contract in `apps/Atomy/app/Providers/AuditLogServiceProvider.php`

4. **Update composer.json:**
   - Remove `laravel/framework` dependency
   - Keep only necessary dependencies (e.g., `league/csv`)

### Phase 2: Uom (Priority: High)

1. **Create remaining interfaces:**
   - UomAliasInterface, UomPackagingInterface, UomItemInterface
   - UomCompoundUnitInterface, UomCustomUnitInterface
   - UomConversionLogInterface
   - Corresponding repository interfaces

2. **Move Laravel-specific code to apps/Atomy:**
   - Move all 13 models to `apps/Atomy/app/Models/Uom/`
   - Move migrations to `apps/Atomy/database/migrations/`
   - Move seeders and factories to apps/Atomy

3. **Refactor services:**
   - Make `DefaultUnitConverter` framework-agnostic
   - Use repository interfaces instead of direct model access
   - Remove Laravel config dependency (use injected configuration)

4. **Create repository implementations:**
   - Create repository for each entity in apps/Atomy
   - Implement all repository contracts

5. **Update bindings:**
   - Bind all repository contracts in service provider
   - Register services with dependency injection

6. **Update composer.json:**
   - Evaluate `illuminate/support` necessity
   - Remove if possible, or keep minimal dependency

### Phase 3: Testing & Documentation

1. **Update tests:**
   - Fix package unit tests to use contracts
   - Add integration tests in apps/Atomy
   - Ensure 90%+ coverage

2. **Documentation:**
   - Update package READMEs
   - Document architecture decisions
   - Provide migration guide for existing users

3. **Validation:**
   - Verify package isolation (no Laravel deps in package tests)
   - Verify apps/Atomy integration works
   - Performance benchmarking

---

## Success Metrics

- ✅ All contracts defined and documented
- ✅ All Laravel code moved to apps/Atomy
- ✅ Repository pattern fully implemented
- ✅ Package composer.json has no laravel/framework dependency
- ✅ All tests passing (unit + integration)
- ✅ Code coverage ≥ 90%
- ✅ Documentation complete
- ✅ Performance benchmarks meet targets

---

**Last Updated:** 2025-11-16
**Status:** In Progress - Contracts phase completed for both packages
