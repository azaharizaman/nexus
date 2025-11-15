# nexus-org-structure Package Requirements

**Version:** 1.0.0
**Last Updated:** November 15, 2025
**Status:** Initial Requirements - Atomic Package

---

## Executive Summary

**nexus-org-structure** (namespace: `Nexus\OrgStructure`) is an **atomic organizational structure management package** for PHP/Laravel that provides hierarchical organization units, positions, employee assignments, and reporting relationships with directory synchronization capabilities (LDAP/Active Directory/SCIM).

### The Domain We Own

Organizational structure is a **foundational domain** that other packages depend on for context:

1. **Employee Context:** All employee-related operations (HRM, payroll, project assignments) need organizational context
2. **Reporting Hierarchies:** Approval workflows, expense approvals, and managerial reporting require clear reporting lines
3. **Access Control:** Role-based permissions often align with organizational structure
4. **Directory Integration:** Enterprise organizations need synchronization with external directory services

### Core Philosophy

1. **Atomic Independence** - Complete organizational structure management without external dependencies
2. **Directory Synchronization** - Contract-based adapters for LDAP, AD, SCIM, and custom directory services
3. **Hierarchical Modeling** - Tree structures for org units with efficient querying and traversal
4. **Temporal Assignments** - Support for effective dating of position assignments and reporting relationships
5. **Metadata Flexibility** - JSON metadata fields for custom attributes and extensions

### Why This Approach Works

**For Small Organizations (60%):**
- Simple org unit hierarchies (departments, teams)
- Basic position definitions and employee assignments
- Manual reporting line management
- Simple organizational reporting

**For Mid-Market (30%):**
- Multi-level organizational hierarchies
- Position-based role assignments
- Automated reporting line calculations
- Directory synchronization (LDAP/AD)
- Organizational analytics and reporting

**For Enterprise (10%):**
- Complex matrix organizations
- Automated provisioning and deprovisioning
- Advanced directory integration (SCIM 2.0)
- Audit trails and compliance reporting
- Real-time organizational changes

---

## Architectural Position in Nexus ERP

### Relationship to Core Packages

| Core Package | Relationship | Usage Pattern |
|-------------|--------------|---------------|
| **nexus-tenancy** | Depends On | All organizational data is tenant-scoped via `BelongsToTenant` trait |
| **nexus-hrm** | Provides Context | HRM operations reference organizational positions and reporting lines |
| **nexus-workflow** | Provides Context | Approval workflows use organizational hierarchies for routing |
| **nexus-audit-log** | Depends On | Comprehensive audit trail for organizational changes |
| **nexus-settings** | Depends On | Directory sync settings and organizational preferences |

### Why This IS an Atomic Package

**Organizational structure meets the independence criterion:**
- Single, well-defined domain with clear boundaries
- No complex interdependencies with other business domains
- Can be tested independently with mock directory adapters
- Contract-based directory synchronization allows pluggable implementations
- Metadata fields provide extensibility without breaking changes

**This is an ATOMIC HORIZONTAL** where domain logic can be cleanly separated.

---

## Personas & User Stories

### Personas

| ID | Persona | Role | Primary Goal |
|-----|---------|------|--------------|
| **P1** | HR Administrator | HR team | "Maintain accurate organizational structure and employee assignments" |
| **P2** | IT Administrator | IT/Systems team | "Configure directory synchronization and manage system integrations" |
| **P3** | Department Manager | Line management | "View team structure and reporting relationships" |
| **P4** | System Integrator | External developer | "Integrate with external directory services (LDAP, AD, SCIM)" |
| **P5** | Organizational Analyst | HR Analytics | "Generate reports on organizational structure and headcount" |
| **P6** | Compliance Officer | Governance team | "Ensure organizational changes are properly audited and compliant" |

### User Stories

#### Level 1: Core Organizational Structure (Essential)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-001** | P1 | As an HR admin, I want to create hierarchical organizational units (departments, divisions) | **High** |
| **US-002** | P1 | As an HR admin, I want to define positions within organizational units | **High** |
| **US-003** | P1 | As an HR admin, I want to assign employees to positions with effective dates | **High** |
| **US-004** | P1 | As an HR admin, I want to establish manager-subordinate reporting relationships | **High** |
| **US-005** | P3 | As a manager, I want to view my direct and indirect reports | **High** |
| **US-006** | P5 | As an analyst, I want to generate organizational charts and headcount reports | **High** |
| **US-007** | P2 | As an IT admin, I want to configure directory synchronization settings | **High** |

#### Level 2: Directory Integration (Advanced)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-010** | P2 | As an IT admin, I want to sync organizational data from LDAP/Active Directory | **Medium** |
| **US-011** | P2 | As an IT admin, I want to sync organizational data via SCIM 2.0 protocol | **Medium** |
| **US-012** | P4 | As a system integrator, I want to implement custom directory adapters | **Medium** |
| **US-013** | P1 | As an HR admin, I want to handle conflicts between manual and synced data | **Medium** |
| **US-014** | P6 | As a compliance officer, I want audit trails for all organizational changes | **Medium** |

#### Level 3: Advanced Features (Enterprise)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-020** | P1 | As an HR admin, I want to manage matrix organizations with multiple reporting lines | **Low** |
| **US-021** | P1 | As an HR admin, I want to track position history and organizational changes over time | **Low** |
| **US-022** | P5 | As an analyst, I want advanced organizational analytics (span of control, hierarchy depth) | **Low** |
| **US-023** | P2 | As an IT admin, I want real-time synchronization with directory changes | **Low** |

---

## Functional Requirements

### FR-001: Organizational Unit Management

**Description:** Support hierarchical organizational units with flexible metadata.

**Requirements:**
- Create, read, update, delete organizational units
- Support hierarchical relationships (parent-child)
- Unique codes within tenant for identification
- JSON metadata for custom attributes
- Soft delete for audit compliance
- Prevent circular references in hierarchy

**Acceptance Criteria:**
- Org units can be nested to unlimited depth
- Codes must be unique within tenant
- Metadata supports arbitrary key-value pairs
- Changes are auditable

### FR-002: Position Management

**Description:** Define positions within organizational units.

**Requirements:**
- Create positions with titles and codes
- Associate positions with organizational units
- Unique position codes within tenant
- JSON metadata for custom attributes
- Soft delete capability

**Acceptance Criteria:**
- Positions are scoped to org units
- Codes are unique within tenant
- Metadata is flexible and extensible

### FR-003: Employee Assignment Management

**Description:** Assign employees to positions with temporal validity.

**Requirements:**
- Assign employees to positions and org units
- Support effective dating (from/to dates)
- Mark primary vs secondary assignments
- Handle overlapping assignments
- JSON metadata for assignment details

**Acceptance Criteria:**
- Multiple concurrent assignments supported
- Primary assignment clearly identified
- Date ranges prevent invalid overlaps
- Historical assignments preserved

### FR-004: Reporting Line Management

**Description:** Establish and maintain managerial reporting relationships.

**Requirements:**
- Define manager-subordinate relationships
- Support effective dating
- Associate with specific positions
- Prevent circular reporting relationships
- Calculate indirect reporting chains

**Acceptance Criteria:**
- Reporting hierarchies are acyclic
- Multiple managers supported (matrix orgs)
- Reporting chains can be traversed efficiently

### FR-005: Directory Synchronization

**Description:** Synchronize organizational data with external directory services.

**Requirements:**
- Contract-based adapter pattern
- Support LDAP, Active Directory, SCIM
- Incremental synchronization with cursors
- Conflict resolution strategies
- Error handling and retry logic

**Acceptance Criteria:**
- Adapters are pluggable and testable
- Synchronization is incremental and resumable
- Conflicts are logged and resolvable
- Performance doesn't degrade with large directories

### FR-006: Organizational Queries

**Description:** Provide efficient queries for organizational data.

**Requirements:**
- Get employee's current assignments
- Get employee's manager and subordinates
- Resolve complete reporting chain
- Query by organizational unit hierarchy
- Support for organizational analytics

**Acceptance Criteria:**
- Queries are optimized for performance
- Results include all relevant metadata
- Hierarchical traversals are efficient

---

## Technical Requirements

### TR-001: Data Model

**Core Entities:**

```php
// Organizational Unit
class OrgUnit {
    string $id;           // ULID
    string $tenant_id;    // ULID
    string $name;         // Display name
    string $code;         // Unique within tenant
    ?string $parent_id;   // Self-referencing
    array $metadata;      // JSON
    timestamps;
}

// Position
class Position {
    string $id;           // ULID
    string $tenant_id;    // ULID
    string $title;        // Job title
    string $code;         // Unique within tenant
    string $org_unit_id;  // Reference
    array $metadata;      // JSON
    timestamps;
}

// Assignment
class Assignment {
    string $id;           // ULID
    string $tenant_id;    // ULID
    string $employee_id;  // Reference to HRM
    string $position_id;  // Reference
    string $org_unit_id;  // Reference
    date $effective_from;
    ?date $effective_to;
    bool $is_primary;
    array $metadata;      // JSON
    timestamps;
}

// Reporting Line
class ReportingLine {
    string $id;           // ULID
    string $tenant_id;    // ULID
    string $manager_id;   // Employee ID
    string $subordinate_id; // Employee ID
    string $position_id;  // Reference
    date $effective_from;
    ?date $effective_to;
    array $metadata;      // JSON
    timestamps;
}
```

**Key Design Decisions:**
- ULID primary keys for scalability
- Tenant-scoped data isolation
- Soft deletes for audit compliance
- JSON metadata for flexibility
- Date ranges for temporal validity

### TR-002: Service Layer

**OrganizationServiceContract:**

```php
interface OrganizationServiceContract {
    // Core queries
    ?array getOrgUnit(string $orgUnitId);
    ?array getPosition(string $positionId);
    ?array getManager(string $employeeId);
    Collection getSubordinates(string $employeeId);
    Collection getAssignmentsForEmployee(string $employeeId);
    Collection resolveReportingChain(string $employeeId);

    // Management operations
    string createOrgUnit(array $data);
    void updateOrgUnit(string $id, array $data);
    void deleteOrgUnit(string $id);

    string createPosition(array $data);
    void updatePosition(string $id, array $data);
    void deletePosition(string $id);

    string createAssignment(array $data);
    void updateAssignment(string $id, array $data);
    void terminateAssignment(string $id, string $endDate);

    string createReportingLine(array $data);
    void updateReportingLine(string $id, array $data);
    void terminateReportingLine(string $id, string $endDate);
}
```

### TR-003: Directory Synchronization

**DirectorySyncAdapterContract:**

```php
interface DirectorySyncAdapterContract {
    void configure(array $settings);
    bool testConnection();
    iterable fetchChanges(?string $sinceCursor = null);
    ?string currentCursor();

    // Normalized record format
    array normalizeOrgUnit(array $external): array;
    array normalizePosition(array $external): array;
    array normalizeAssignment(array $external): array;
}
```

**Supported Adapters:**
- LDAP Adapter (OpenLDAP, Active Directory)
- SCIM 2.0 Adapter (Okta, Azure AD, OneLogin)
- Custom Adapter (extensible interface)

### TR-004: Performance Requirements

**Query Performance:**
- Org unit hierarchy traversal: < 100ms for 1000+ units
- Reporting chain resolution: < 50ms for 10-level hierarchies
- Employee assignment lookup: < 10ms
- Subordinate queries: < 100ms for 100+ subordinates

**Scalability Targets:**
- Support 10,000+ organizational units per tenant
- Handle 100,000+ employee assignments
- Process directory syncs for 50,000+ users
- Maintain performance under concurrent load

### TR-005: Security & Compliance

**Data Protection:**
- All data tenant-scoped and isolated
- Soft deletes preserve audit trails
- Encryption for sensitive metadata
- Access logging for all operations

**Compliance:**
- GDPR compliance for personal data
- SOX compliance for organizational changes
- Audit trails for all modifications
- Data retention policies

---

## Integration Points

### IP-001: Nexus HRM Integration

**Purpose:** Provide organizational context for employee management.

**Contract:** `OrganizationServiceContract`

**Usage Patterns:**
- HRM retrieves employee assignments for payroll
- Position changes trigger assignment updates
- Reporting lines used for approval workflows

### IP-002: Nexus Workflow Integration

**Purpose:** Use organizational hierarchies for workflow routing.

**Events:**
- `OrgStructureChanged` - Triggers workflow updates
- `AssignmentChanged` - Updates approval chains

### IP-003: Nexus Audit Log Integration

**Purpose:** Comprehensive audit trail for organizational changes.

**Audit Events:**
- Org unit creation/modification/deletion
- Position changes
- Assignment changes
- Reporting line changes
- Directory synchronization events

### IP-004: Directory Service Integration

**Supported Protocols:**
- LDAP v3 (RFC 4511)
- SCIM 2.0 (RFC 7644)
- Custom REST APIs

**Authentication Methods:**
- Simple Bind (LDAP)
- SASL (LDAP)
- OAuth 2.0 (SCIM)
- API Keys (Custom)

---

## Testing Strategy

### TS-001: Unit Testing

**Coverage Targets:**
- 100% model method coverage
- 100% service method coverage
- 100% contract implementation coverage
- 100% validation rule coverage

**Test Categories:**
- Model factories and relationships
- Service business logic
- Contract compliance
- Error handling and edge cases

### TS-002: Integration Testing

**Test Scenarios:**
- Directory adapter integration
- Cross-package integration (HRM, Workflow)
- Database migration testing
- Performance benchmarking

### TS-003: Contract Testing

**Adapter Testing:**
- Mock directory adapters for unit tests
- Real adapter testing in integration environment
- Contract compliance validation

### TS-004: Performance Testing

**Load Testing:**
- Large organization hierarchies (10,000+ units)
- High-frequency directory syncs
- Concurrent user operations
- Reporting query performance

---

## Deployment & Operations

### DO-001: Package Installation

**Composer Dependencies:**
```json
{
    "require": {
        "nexus/org-structure": "^1.0"
    }
}
```

**Service Registration:**
```php
// config/app.php
'providers' => [
    Nexus\OrgStructure\OrgStructureServiceProvider::class,
],
```

### DO-002: Database Migrations

**Migration Order:**
1. `create_org_org_units_table`
2. `create_org_positions_table`
3. `create_org_assignments_table`
4. `create_org_reporting_lines_table`

**Migration Safety:**
- Zero-downtime migrations
- Backward compatibility
- Data preservation during upgrades

### DO-003: Configuration

**Required Settings:**
```php
// config/org-structure.php
return [
    'directory_sync' => [
        'enabled' => env('ORG_STRUCTURE_SYNC_ENABLED', false),
        'adapter' => env('ORG_STRUCTURE_ADAPTER', 'ldap'),
        'settings' => [
            'host' => env('LDAP_HOST'),
            'port' => env('LDAP_PORT', 389),
            'base_dn' => env('LDAP_BASE_DN'),
            // ... adapter-specific settings
        ],
    ],
];
```

### DO-004: Monitoring & Observability

**Key Metrics:**
- Directory sync success/failure rates
- Organizational change frequency
- Query performance histograms
- Error rates by operation type

**Logging:**
- Structured logging for all operations
- Error tracking with context
- Performance monitoring
- Audit trail completeness

---

## Implementation Roadmap

### Phase 1: Core Structure (Current)
- ✅ Basic org unit, position, assignment models
- ✅ Reporting line model
- ✅ Organization service contract and implementation
- ✅ Directory sync adapter contract
- ⏳ Basic directory sync implementation
- ⏳ Unit and integration tests

### Phase 2: Advanced Features (Next)
- Advanced directory synchronization
- Organizational analytics
- Matrix organization support
- Performance optimizations

### Phase 3: Enterprise Features (Future)
- Real-time synchronization
- Advanced conflict resolution
- Multi-tenant directory isolation
- Compliance reporting

---

## Risk Assessment

### Technical Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Complex hierarchy queries | High | Optimize with closure tables or recursive CTEs |
| Directory sync conflicts | Medium | Implement conflict resolution strategies |
| Large organization performance | Medium | Database indexing and query optimization |
| Adapter compatibility | Low | Comprehensive contract testing |

### Business Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Directory service downtime | Medium | Implement retry logic and fallback modes |
| Data synchronization delays | Low | Incremental sync with change detection |
| Organizational change frequency | Low | Efficient change detection and processing |

---

## Success Metrics

### Functional Metrics
- 100% core organizational operations working
- Directory sync success rate > 99%
- Query performance within SLA limits
- Zero data loss in synchronization

### Quality Metrics
- Test coverage > 90%
- Performance benchmarks met
- Security audit passed
- Documentation completeness > 95%

### Adoption Metrics
- Integration with HRM package
- Integration with Workflow package
- Directory adapter implementations
- Production deployment success</content>
<parameter name="filePath">/home/conrad/Dev/azaharizaman/nexus-erp/packages/nexus-org-structure/REQUIREMENTS.md