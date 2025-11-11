# PRD01-SUB18: Master Data Management

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Optional Feature Modules - Data Management  
**Related Sub-PRDs:** SUB01 (Multi-Tenancy), SUB03 (Audit Logging), SUB14 (Inventory), SUB16 (Purchasing), SUB17 (Sales)  
**Composer Package:** `azaharizaman/erp-mdm`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Master Data Management (MDM) module provides a centralized repository for managing critical business entities (customers, vendors, items, employees) with data quality enforcement, versioning, deduplication, and golden record management.

### Purpose

This module solves the challenge of maintaining consistent, high-quality master data across multiple systems and modules. It ensures data integrity through validation rules, prevents duplicates through matching algorithms, and provides complete change history for compliance and audit requirements.

### Scope

**Included:**
- Centralized master data repository for customers, vendors, items, employees
- Data quality rules with validation, deduplication, and enrichment
- Master data versioning with effective-dated changes
- Data lineage tracking showing source and transformations
- Bulk import/export with validation and error handling
- Data matching algorithms to detect duplicates
- Golden record creation from multiple source systems
- Data stewardship workflows for master data approval

**Excluded:**
- Real-time transaction processing (handled by transactional modules)
- Business intelligence and analytics dashboards (handled by SUB20 Financial Reporting)
- Workflow orchestration (handled by SUB21 Workflow Engine)

### Dependencies

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for master data
- **SUB02 (Authentication & Authorization)** - User access control
- **SUB03 (Audit Logging)** - Track all master data changes

**Optional Dependencies:**
- **SUB14 (Inventory Management)** - Item master data consumer
- **SUB16 (Purchasing)** - Vendor master data consumer
- **SUB17 (Sales)** - Customer master data consumer
- **SUB13 (HCM)** - Employee master data consumer
- **SUB24 (Integration Connectors)** - External system synchronization

### Composer Package Information

- **Package Name:** `azaharizaman/erp-mdm`
- **Namespace:** `Azaharizaman\Erp\Mdm`
- **Monorepo Location:** `/packages/mdm/`
- **Installation:** `composer require azaharizaman/erp-mdm` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB18 (Master Data Management). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-MDM-001** | Provide **centralized master data** repository for customers, vendors, items, and employees | High | Planned |
| **FR-MDM-002** | Support **data quality rules** with validation, deduplication, and enrichment | High | Planned |
| **FR-MDM-003** | Implement **master data versioning** with effective-dated changes | High | Planned |
| **FR-MDM-004** | Provide **data lineage tracking** showing source and transformations | Medium | Planned |
| **FR-MDM-005** | Support **bulk import/export** with validation and error handling | High | Planned |
| **FR-MDM-006** | Implement **data matching algorithms** to detect duplicates across systems | High | Planned |
| **FR-MDM-007** | Provide **golden record** creation from multiple source systems | Medium | Planned |
| **FR-MDM-008** | Support **data stewardship workflows** for master data approval | Medium | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-MDM-001** | Master data **cannot be deleted** if referenced by transactional data | Planned |
| **BR-MDM-002** | Duplicate records must be **merged, not overwritten** to preserve history | Planned |
| **BR-MDM-003** | **Data quality score** must exceed threshold before publishing to consuming systems | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-MDM-001** | Store **complete change history** for all master data with before/after snapshots | Planned |
| **DR-MDM-002** | Maintain **data quality metrics** (completeness, accuracy, timeliness) | Planned |
| **DR-MDM-003** | Record **data source mappings** for multi-system integration | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-MDM-001** | Integrate with **all transactional modules** as master data provider | Planned |
| **IR-MDM-002** | Provide **MDM API** for external system synchronization | Planned |
| **IR-MDM-003** | Support **bi-directional sync** with external CRM and ERP systems | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-MDM-001** | Implement **role-based access** to master data by entity type and sensitivity | Planned |
| **SR-MDM-002** | **Encrypt sensitive master data** (customer PII, vendor bank details) | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-MDM-001** | Master data queries must complete in **< 100ms** for single record | Planned |
| **PR-MDM-002** | Bulk import must process **10,000+ records in < 60 seconds** | Planned |
| **PR-MDM-003** | Real-time reporting API must return in **< 3 seconds** for datasets with < 10k rows | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-MDM-001** | Support **10 million+ master records** per tenant | Planned |

### Compliance Requirements (CR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **CR-MDM-001** | Comply with **GDPR** for customer and employee PII management | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-MDM-001** | Use **PostgreSQL Materialized Views** or **ClickHouse** for analytics offload | Planned |
| **ARCH-MDM-002** | Implement **CDC (Change Data Capture)** for real-time data synchronization | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-MDM-001** | `MasterDataCreatedEvent` | When new master record is created | Planned |
| **EV-MDM-002** | `MasterDataUpdatedEvent` | When master record is modified | Planned |
| **EV-MDM-003** | `DuplicateDetectedEvent` | When potential duplicate is identified | Planned |

---

## Technical Specifications

### Database Schema

**Master Data Entities Table:**

```sql
CREATE TABLE mdm_entities (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    entity_type VARCHAR(50) NOT NULL,  -- 'customer', 'vendor', 'item', 'employee'
    entity_code VARCHAR(100) NOT NULL,
    entity_name VARCHAR(255) NOT NULL,
    is_golden_record BOOLEAN DEFAULT FALSE,
    parent_entity_id BIGINT NULL REFERENCES mdm_entities(id),  -- For duplicates
    data_quality_score DECIMAL(5, 2) DEFAULT 0,
    completeness_score DECIMAL(5, 2) DEFAULT 0,
    accuracy_score DECIMAL(5, 2) DEFAULT 0,
    timeliness_score DECIMAL(5, 2) DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',  -- 'draft', 'pending_approval', 'approved', 'merged', 'deprecated'
    effective_from DATE NULL,
    effective_to DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE (tenant_id, entity_type, entity_code),
    INDEX idx_mdm_entities_tenant (tenant_id),
    INDEX idx_mdm_entities_type (entity_type),
    INDEX idx_mdm_entities_golden (is_golden_record),
    INDEX idx_mdm_entities_status (status),
    INDEX idx_mdm_entities_quality (data_quality_score),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Master Data Attributes Table:**

```sql
CREATE TABLE mdm_attributes (
    id BIGSERIAL PRIMARY KEY,
    entity_id BIGINT NOT NULL REFERENCES mdm_entities(id) ON DELETE CASCADE,
    attribute_name VARCHAR(100) NOT NULL,
    attribute_value TEXT NULL,
    attribute_type VARCHAR(50) NOT NULL,  -- 'string', 'number', 'date', 'boolean', 'json'
    source_system VARCHAR(100) NULL,
    confidence_score DECIMAL(5, 2) DEFAULT 100,
    is_encrypted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_mdm_attributes_entity (entity_id),
    INDEX idx_mdm_attributes_name (attribute_name)
);
```

**Master Data Change History Table:**

```sql
CREATE TABLE mdm_change_history (
    id BIGSERIAL PRIMARY KEY,
    entity_id BIGINT NOT NULL REFERENCES mdm_entities(id) ON DELETE CASCADE,
    change_type VARCHAR(20) NOT NULL,  -- 'created', 'updated', 'merged', 'deprecated'
    before_snapshot JSONB NULL,
    after_snapshot JSONB NULL,
    changed_fields JSONB NULL,
    changed_by BIGINT NOT NULL REFERENCES users(id),
    change_reason TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_mdm_history_entity (entity_id),
    INDEX idx_mdm_history_user (changed_by),
    INDEX idx_mdm_history_date (created_at)
);
```

**Duplicate Detection Rules Table:**

```sql
CREATE TABLE mdm_duplicate_rules (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    rule_name VARCHAR(255) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    matching_fields JSONB NOT NULL,  -- ['name', 'email', 'phone']
    matching_algorithm VARCHAR(50) NOT NULL,  -- 'exact', 'fuzzy', 'phonetic', 'levenshtein'
    match_threshold DECIMAL(5, 2) DEFAULT 80,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_mdm_dup_rules_tenant (tenant_id),
    INDEX idx_mdm_dup_rules_type (entity_type),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Duplicate Matches Table:**

```sql
CREATE TABLE mdm_duplicate_matches (
    id BIGSERIAL PRIMARY KEY,
    rule_id BIGINT NOT NULL REFERENCES mdm_duplicate_rules(id),
    entity_1_id BIGINT NOT NULL REFERENCES mdm_entities(id) ON DELETE CASCADE,
    entity_2_id BIGINT NOT NULL REFERENCES mdm_entities(id) ON DELETE CASCADE,
    match_score DECIMAL(5, 2) NOT NULL,
    match_details JSONB NULL,
    resolution_status VARCHAR(20) NOT NULL DEFAULT 'pending',  -- 'pending', 'confirmed', 'rejected', 'merged'
    resolved_by BIGINT NULL REFERENCES users(id),
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_mdm_matches_rule (rule_id),
    INDEX idx_mdm_matches_entity1 (entity_1_id),
    INDEX idx_mdm_matches_entity2 (entity_2_id),
    INDEX idx_mdm_matches_status (resolution_status)
);
```

**Data Quality Rules Table:**

```sql
CREATE TABLE mdm_quality_rules (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    rule_name VARCHAR(255) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    rule_type VARCHAR(50) NOT NULL,  -- 'completeness', 'accuracy', 'format', 'range'
    field_name VARCHAR(100) NOT NULL,
    validation_logic JSONB NOT NULL,
    error_message TEXT NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'error',  -- 'error', 'warning', 'info'
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_mdm_quality_rules_tenant (tenant_id),
    INDEX idx_mdm_quality_rules_type (entity_type),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Data Source Mappings Table:**

```sql
CREATE TABLE mdm_source_mappings (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    entity_id BIGINT NOT NULL REFERENCES mdm_entities(id) ON DELETE CASCADE,
    source_system VARCHAR(100) NOT NULL,
    source_entity_id VARCHAR(255) NOT NULL,
    source_entity_type VARCHAR(100) NULL,
    last_sync_at TIMESTAMP NULL,
    sync_status VARCHAR(20) NULL,  -- 'synced', 'pending', 'failed'
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (source_system, source_entity_id),
    INDEX idx_mdm_source_mappings_tenant (tenant_id),
    INDEX idx_mdm_source_mappings_entity (entity_id),
    INDEX idx_mdm_source_mappings_system (source_system),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Data Stewardship Workflows Table:**

```sql
CREATE TABLE mdm_stewardship_workflows (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    entity_id BIGINT NOT NULL REFERENCES mdm_entities(id) ON DELETE CASCADE,
    workflow_type VARCHAR(50) NOT NULL,  -- 'approval', 'merge', 'deprecation'
    requested_by BIGINT NOT NULL REFERENCES users(id),
    assigned_to BIGINT NULL REFERENCES users(id),
    status VARCHAR(20) NOT NULL DEFAULT 'pending',  -- 'pending', 'in_review', 'approved', 'rejected'
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_mdm_workflows_tenant (tenant_id),
    INDEX idx_mdm_workflows_entity (entity_id),
    INDEX idx_mdm_workflows_status (status),
    INDEX idx_mdm_workflows_assignee (assigned_to),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

### API Endpoints

All endpoints follow the RESTful pattern under `/api/v1/mdm/`:

**Master Data Entity Management:**
- `GET /api/v1/mdm/entities` - List master data entities with filtering
- `POST /api/v1/mdm/entities` - Create new master data record
- `GET /api/v1/mdm/entities/{id}` - Get entity details
- `PATCH /api/v1/mdm/entities/{id}` - Update entity
- `DELETE /api/v1/mdm/entities/{id}` - Soft delete entity
- `GET /api/v1/mdm/entities/{id}/history` - Get change history
- `GET /api/v1/mdm/entities/{id}/quality-score` - Get data quality metrics

**Data Quality:**
- `GET /api/v1/mdm/quality-rules` - List quality rules
- `POST /api/v1/mdm/quality-rules` - Create quality rule
- `PATCH /api/v1/mdm/quality-rules/{id}` - Update quality rule
- `POST /api/v1/mdm/entities/{id}/validate` - Validate entity against rules

**Duplicate Detection:**
- `GET /api/v1/mdm/duplicate-rules` - List duplicate detection rules
- `POST /api/v1/mdm/duplicate-rules` - Create duplicate rule
- `POST /api/v1/mdm/entities/detect-duplicates` - Run duplicate detection
- `GET /api/v1/mdm/duplicate-matches` - List potential duplicates
- `POST /api/v1/mdm/duplicate-matches/{id}/merge` - Merge duplicate records

**Golden Record Management:**
- `POST /api/v1/mdm/entities/{id}/promote-golden` - Promote to golden record
- `POST /api/v1/mdm/entities/create-golden` - Create golden from multiple sources

**Bulk Operations:**
- `POST /api/v1/mdm/import` - Bulk import master data
- `POST /api/v1/mdm/export` - Bulk export master data
- `GET /api/v1/mdm/import-jobs/{id}` - Check import job status

**Data Stewardship:**
- `GET /api/v1/mdm/workflows` - List stewardship workflows
- `POST /api/v1/mdm/workflows` - Create workflow
- `POST /api/v1/mdm/workflows/{id}/approve` - Approve workflow
- `POST /api/v1/mdm/workflows/{id}/reject` - Reject workflow

**Reporting:**
- `GET /api/v1/mdm/reports/quality-dashboard` - Data quality dashboard
- `GET /api/v1/mdm/reports/duplicate-summary` - Duplicate detection summary
- `GET /api/v1/mdm/reports/entity-lineage/{id}` - Entity lineage tree

### Events

**Domain Events Emitted:**

```php
namespace Azaharizaman\Erp\Mdm\Events;

class MasterDataCreatedEvent
{
    public function __construct(
        public readonly MdmEntity $entity,
        public readonly array $attributes,
        public readonly User $createdBy
    ) {}
}

class MasterDataUpdatedEvent
{
    public function __construct(
        public readonly MdmEntity $entity,
        public readonly array $changedFields,
        public readonly array $beforeSnapshot,
        public readonly array $afterSnapshot,
        public readonly User $updatedBy
    ) {}
}

class DuplicateDetectedEvent
{
    public function __construct(
        public readonly MdmEntity $entity1,
        public readonly MdmEntity $entity2,
        public readonly float $matchScore,
        public readonly string $matchingAlgorithm
    ) {}
}
```

### Event Listeners

**Events from Other Modules:**

This module listens to:
- `CustomerCreatedEvent` (SUB17) - Create MDM entity for customer
- `VendorCreatedEvent` (SUB16) - Create MDM entity for vendor
- `ItemCreatedEvent` (SUB14) - Create MDM entity for item
- `EmployeeHiredEvent` (SUB13) - Create MDM entity for employee

---

## Implementation Plans

**Note:** Implementation plans follow the naming convention `PLAN{number}-implement-{component}.md`

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN18-implement-mdm.md | FR-MDM-001 to FR-MDM-008, BR-MDM-001 to BR-MDM-003 | MILESTONE 12 | Not Started |

**Implementation plan will be created separately using:** `.github/prompts/create-implementation-plan.prompt.md`

---

## Acceptance Criteria

### Functional Acceptance

- [ ] Centralized master data repository operational for all entity types
- [ ] Data quality rules enforcement with validation working
- [ ] Master data versioning with effective dates functional
- [ ] Data lineage tracking showing complete history
- [ ] Bulk import/export with validation operational
- [ ] Duplicate detection algorithms working accurately
- [ ] Golden record creation from multiple sources functional
- [ ] Data stewardship workflows operational

### Technical Acceptance

- [ ] All API endpoints return correct responses per OpenAPI spec
- [ ] Master data queries complete in < 100ms for single record (PR-MDM-001)
- [ ] Bulk import processes 10,000+ records in < 60 seconds (PR-MDM-002)
- [ ] Real-time reporting API returns in < 3 seconds for < 10k rows (PR-MDM-003)
- [ ] System supports 10 million+ master records per tenant (SCR-MDM-001)
- [ ] CDC implementation for real-time synchronization functional (ARCH-MDM-002)

### Security Acceptance

- [ ] Role-based access to master data enforced (SR-MDM-001)
- [ ] Sensitive master data encrypted at rest (SR-MDM-002)
- [ ] GDPR compliance for PII management (CR-MDM-001)

### Integration Acceptance

- [ ] Integration with all transactional modules functional (IR-MDM-001)
- [ ] MDM API for external system synchronization operational (IR-MDM-002)
- [ ] Bi-directional sync with external systems working (IR-MDM-003)

---

## Testing Strategy

### Unit Tests

**Test Coverage Requirements:** Minimum 80% code coverage

**Key Test Areas:**
- Data quality rule validation logic
- Duplicate matching algorithms (exact, fuzzy, phonetic)
- Data quality score calculation
- Master data versioning logic
- Golden record merging algorithm

**Example Tests:**
```php
test('master data cannot be deleted if referenced by transactions', function () {
    $entity = MdmEntity::factory()->create(['entity_type' => 'customer']);
    SalesOrder::factory()->create(['customer_id' => $entity->entity_code]);
    
    expect(fn () => DeleteMasterDataAction::run($entity))
        ->toThrow(ReferencedEntityException::class);
});

test('duplicate matching returns high score for similar entities', function () {
    $entity1 = MdmEntity::factory()->create([
        'entity_name' => 'Acme Corp',
        'entity_type' => 'customer',
    ]);
    
    $entity2 = MdmEntity::factory()->create([
        'entity_name' => 'ACME Corporation',
        'entity_type' => 'customer',
    ]);
    
    $score = DetectDuplicatesAction::run($entity1, $entity2, 'fuzzy');
    
    expect($score)->toBeGreaterThan(80);
});
```

### Feature Tests

**API Integration Tests:**
- Complete CRUD operations for master data via API
- Bulk import with validation and error handling
- Duplicate detection and merging workflow
- Data stewardship approval workflow

**Example Tests:**
```php
test('can create and validate master data entity via API', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/mdm/entities', [
            'entity_type' => 'customer',
            'entity_code' => 'CUST001',
            'entity_name' => 'Test Customer',
            'attributes' => [
                ['name' => 'email', 'value' => 'test@example.com'],
                ['name' => 'phone', 'value' => '+1234567890'],
            ],
        ]);
    
    $response->assertCreated();
    
    $entityId = $response->json('data.id');
    
    $validateResponse = $this->actingAs($user)
        ->postJson("/api/v1/mdm/entities/{$entityId}/validate");
    
    $validateResponse->assertOk();
    expect($validateResponse->json('data.quality_score'))->toBeGreaterThan(0);
});
```

### Integration Tests

**Cross-Module Integration:**
- MDM entity creation from transactional modules
- Real-time synchronization with external systems
- Data lineage across module boundaries

### Performance Tests

**Load Testing Scenarios:**
- Master data queries: < 100ms for single record (PR-MDM-001)
- Bulk import: 10,000+ records in < 60 seconds (PR-MDM-002)
- Reporting API: < 3 seconds for < 10k rows (PR-MDM-003)
- 10 million+ master records handling

---

## Dependencies

### Feature Module Dependencies

**From Master PRD Section D.2.1:**

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for master data
- **SUB02 (Authentication & Authorization)** - User access control
- **SUB03 (Audit Logging)** - Track all master data changes

**Optional Dependencies:**
- **SUB14 (Inventory Management)** - Item master data consumer
- **SUB16 (Purchasing)** - Vendor master data consumer
- **SUB17 (Sales)** - Customer master data consumer
- **SUB13 (HCM)** - Employee master data consumer
- **SUB24 (Integration Connectors)** - External system synchronization

### External Package Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "azaharizaman/erp-core": "^1.0",
    "lorisleiva/laravel-actions": "^2.0",
    "brick/math": "^0.12"
  },
  "require-dev": {
    "pestphp/pest": "^4.0"
  }
}
```

### Infrastructure Dependencies

- **Database:** PostgreSQL 14+ (for JSONB, materialized views, CDC)
- **Cache:** Redis 6+ (for data quality score caching)
- **Queue:** Redis or database queue driver (for bulk import processing)
- **Analytics:** ClickHouse (optional, for large-scale analytics offload)

---

## Feature Module Structure

### Directory Structure (in Monorepo)

```
packages/mdm/
├── src/
│   ├── Actions/
│   │   ├── CreateMasterDataAction.php
│   │   ├── UpdateMasterDataAction.php
│   │   ├── DetectDuplicatesAction.php
│   │   ├── MergeEntitiesAction.php
│   │   ├── CalculateQualityScoreAction.php
│   │   └── BulkImportAction.php
│   ├── Contracts/
│   │   ├── MdmEntityRepositoryContract.php
│   │   ├── DuplicateDetectionServiceContract.php
│   │   └── DataQualityServiceContract.php
│   ├── Events/
│   │   ├── MasterDataCreatedEvent.php
│   │   ├── MasterDataUpdatedEvent.php
│   │   └── DuplicateDetectedEvent.php
│   ├── Listeners/
│   │   ├── SyncExternalSystemListener.php
│   │   ├── UpdateQualityScoreListener.php
│   │   └── CreateMdmEntityFromTransactionListener.php
│   ├── Models/
│   │   ├── MdmEntity.php
│   │   ├── MdmAttribute.php
│   │   ├── MdmChangeHistory.php
│   │   ├── MdmDuplicateRule.php
│   │   └── MdmDuplicateMatch.php
│   ├── Observers/
│   │   └── MdmEntityObserver.php
│   ├── Policies/
│   │   └── MdmEntityPolicy.php
│   ├── Repositories/
│   │   └── MdmEntityRepository.php
│   ├── Services/
│   │   ├── DuplicateDetectionService.php
│   │   ├── DataQualityService.php
│   │   ├── GoldenRecordService.php
│   │   └── DataLineageService.php
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Resources/
│   └── MdmServiceProvider.php
├── tests/
│   ├── Feature/
│   │   ├── MasterDataManagementTest.php
│   │   ├── DuplicateDetectionTest.php
│   │   └── DataQualityTest.php
│   └── Unit/
│       ├── DuplicateMatchingTest.php
│       └── QualityScoreCalculationTest.php
├── database/
│   ├── migrations/
│   │   ├── 2025_01_01_000001_create_mdm_entities_table.php
│   │   ├── 2025_01_01_000002_create_mdm_attributes_table.php
│   │   ├── 2025_01_01_000003_create_mdm_change_history_table.php
│   │   └── 2025_01_01_000004_create_mdm_duplicate_rules_table.php
│   └── factories/
│       └── MdmEntityFactory.php
├── routes/
│   └── api.php
├── config/
│   └── mdm.php
├── composer.json
└── README.md
```

---

## Migration Path

This is a new module with no existing functionality to migrate from.

**Initial Setup:**
1. Install package via Composer
2. Publish migrations and run `php artisan migrate`
3. Configure data quality rules for each entity type
4. Configure duplicate detection rules
5. Import existing master data from transactional modules
6. Run initial duplicate detection and resolution

---

## Success Metrics

From Master PRD Section B.3:

**Adoption Metrics:**
- Master data centralization > 90% (vs. distributed across modules)
- Data stewardship workflow utilization > 70%

**Performance Metrics:**
- Master data query time < 100ms for single record (PR-MDM-001)
- Bulk import processing < 60 seconds for 10,000 records (PR-MDM-002)

**Quality Metrics:**
- Average data quality score > 85%
- Duplicate detection accuracy > 90%
- Data completeness > 95%

**Operational Metrics:**
- Mean time to resolve duplicates < 24 hours
- Master data approval time < 4 hours

---

## Assumptions & Constraints

### Assumptions

1. Transactional modules will emit events for master data changes
2. External system APIs available for bi-directional synchronization
3. Data stewards assigned and trained on workflow processes
4. Data quality rules defined and validated before enforcement
5. Historical data migration completed before go-live

### Constraints

1. Master data cannot be deleted if referenced by transactional data
2. Duplicate records must be merged, not overwritten
3. Data quality score must exceed threshold before publishing
4. System supports 10 million+ master records per tenant
5. GDPR compliance required for PII management

---

## Monorepo Integration

### Development

- Lives in `/packages/mdm/` during development
- Main app uses Composer path repository to require locally:
  ```json
  {
    "repositories": [
      {
        "type": "path",
        "url": "./packages/mdm"
      }
    ],
    "require": {
      "azaharizaman/erp-mdm": "@dev"
    }
  }
  ```
- All changes committed to monorepo

### Release (v1.0)

- Tagged with monorepo version (e.g., v1.0.0)
- Published to Packagist as `azaharizaman/erp-mdm`
- Can be installed independently in external Laravel apps
- Semantic versioning: MAJOR.MINOR.PATCH

---

## References

- Master PRD: [../PRD01-MVP.md](../PRD01-MVP.md)
- Monorepo Strategy: [../PRD01-MVP.md#C.1](../PRD01-MVP.md#section-c1-core-architectural-strategy-the-monorepo)
- Feature Module Independence: [../PRD01-MVP.md#D.2.2](../PRD01-MVP.md#d22-feature-module-independence-requirements)
- Architecture Documentation: [../../architecture/](../../architecture/)
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- GitHub Copilot Instructions: [../../.github/copilot-instructions.md](../../.github/copilot-instructions.md)

---

**Next Steps:**
1. Review and approve this Sub-PRD
2. Create implementation plan: `PLAN18-implement-mdm.md` in `/docs/plan/`
3. Break down into GitHub issues
4. Assign to MILESTONE 12 from Master PRD Section F.2.4
5. Set up feature module structure in `/packages/mdm/`
