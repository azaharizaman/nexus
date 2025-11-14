# Nexus Backoffice Package Requirements

## Package Overview

| Attribute | Value |
| :---- | :---- |
| **Package Name** | `nexus/backoffice` |
| **Namespace** | `Nexus\Backoffice` |
| **Domain Focus** | Hierarchical organizational structure management |
| **Business Purpose** | Manages company hierarchies, offices, departments, staff, units, and organizational reporting structures |
| **Atomic Classification** | **ATOMIC PACKAGE** - Core business logic package with no cross-package dependencies |
| **Target Framework** | Laravel 11+ / PHP 8.3+ |

## Business Domain Description

The Nexus Backoffice package provides comprehensive organizational structure management capabilities for enterprise applications. This package handles the complex hierarchical relationships between companies, physical office locations, logical departments, staff members, organizational units, and reporting lines.

### Core Business Entities

1. **Company Hierarchy** - Multi-level parent-child company relationships
2. **Office Management** - Physical office locations with hierarchical structures
3. **Department Management** - Logical departmental hierarchies independent of physical locations
4. **Staff Management** - Employee records with flexible assignment to offices and/or departments
5. **Unit Organization** - Logical staff groupings within unit groups for matrix management
6. **Position Management** - Job positions and roles within the organizational structure
7. **Staff Transfers** - Managed organizational movement and change tracking
8. **Organizational Charts** - Comprehensive reporting line visualization and analysis

### Key Business Capabilities

- **Dual Hierarchy Support**: Both physical (offices) and logical (departments) organizational structures
- **Flexible Assignment**: Staff can belong to offices, departments, or both simultaneously
- **Matrix Organization**: Unit-based groupings that cross traditional hierarchical boundaries
- **Transfer Management**: Structured staff movement between organizational units
- **Reporting Lines**: Supervisor-subordinate relationships with chart generation
- **Export Capabilities**: Organizational data export in JSON, CSV, and DOT (Graphviz) formats

## Maximum Atomicity Compliance Analysis

### ✅ Compliant Aspects

1. **Independent Business Domain**: Focuses solely on organizational structure management
2. **Self-Contained Logic**: All business rules are contained within the package
3. **Independent Testing**: Uses Orchestra Testbench for isolated testing
4. **No HTTP Layer**: Package contains no controllers, API endpoints, or presentation logic
5. **Framework Integration**: Proper Laravel service provider and event system usage
6. **Factory Support**: Comprehensive model factories for testing scenarios

### 🚨 Compliance Violations Requiring Refactoring

#### Critical Architectural Violations

1. **Console Commands in Atomic Package** ❌
   - **Violation**: Contains console commands (`InstallBackOfficeCommand`, `CreateOfficeTypesCommand`, `ProcessResignationsCommand`, `ProcessStaffTransfersCommand`)
   - **Impact**: Console commands are presentation layer concerns
   - **Required Action**: Move all console commands to orchestration layer (`Nexus\Erp\Console\Commands\Backoffice\*`)

2. **Observer Auto-Registration** ❌
   - **Violation**: Service provider auto-registers observers for all model events
   - **Impact**: Forces side effects in consuming applications
   - **Required Action**: Move observer registration to orchestration layer, make optional

3. **Policy Auto-Registration** ❌
   - **Violation**: Service provider auto-registers authorization policies
   - **Impact**: Imposes authorization structure on consuming applications
   - **Required Action**: Move policy registration to orchestration layer

### Package Boundary Corrections Required

| Component | Current Location | Required Location | Reason |
| :---- | :---- | :---- | :---- |
| Console Commands | `Nexus\Backoffice\Commands\*` | `Nexus\Erp\Console\Commands\Backoffice\*` | Presentation layer concerns |
| Observer Registration | `BackofficeServiceProvider` | `Nexus\Erp\Providers\BackofficeServiceProvider` | Optional side effects |
| Policy Registration | `BackofficeServiceProvider` | `Nexus\Erp\Providers\AuthServiceProvider` | Authorization orchestration |
| Helper Classes | `Nexus\Backoffice\Helpers\*` | Keep in package | Business logic utilities |

## Architectural Requirements

### Core Package Components (MUST KEEP)

- **Models**: All Eloquent models representing business entities
- **Traits**: Model behavior traits (`HasHierarchy`, etc.)
- **Enums**: Business value enumerations
- **Services**: Business logic services and calculations
- **Helpers**: Business logic utilities (`OrganizationalChart`, `StaffTransferHelper`)
- **Exceptions**: Domain-specific exceptions
- **Migrations**: Database schema definitions
- **Factories**: Model factories for testing
- **Events**: Domain events for business state changes

### Orchestration Layer Components (MUST MOVE)

- **Console Commands**: All Artisan commands
- **Observer Registration**: Model observer auto-registration
- **Policy Registration**: Authorization policy auto-registration
- **HTTP Middleware**: Any request/response handling (if present)
- **API Resources**: Presentation layer transformers (if present)

### Service Provider Refactoring

The current service provider MUST be refactored to focus only on:

- Model registration
- Event registration (not observer auto-registration)
- Configuration merging
- Migration path registration

Observer and policy registration should be moved to the orchestration layer where they can be optionally enabled.

## Laravel Actions Integration Requirements

### Required Orchestration Actions

The following Laravel Actions must be created in the orchestration layer (`Nexus\Erp\Actions\Backoffice\*`):

#### Company Management Actions
- `CreateCompanyAction` - Handle company creation with validation
- `UpdateCompanyHierarchyAction` - Manage parent-child relationships
- `GenerateOrganizationalChartAction` - Create organizational visualizations

#### Staff Management Actions  
- `CreateStaffAction` - Handle staff creation and assignment
- `TransferStaffAction` - Manage staff transfers between units
- `UpdateReportingLineAction` - Modify supervisor relationships
- `ProcessResignationAction` - Handle staff departure workflow

#### Office and Department Actions
- `CreateOfficeAction` - Manage office creation and hierarchy
- `CreateDepartmentAction` - Handle department structure
- `AssignStaffToUnitsAction` - Manage unit assignments

#### Reporting and Export Actions
- `ExportOrganizationalDataAction` - Handle data export in various formats
- `GenerateCompanyStatisticsAction` - Calculate organizational metrics

### Action Integration Pattern

```php
// Example Action orchestrating package services
namespace Nexus\Erp\Actions\Backoffice;

use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Services\StaffTransferService;
use Nexus\Erp\Actions\Action;

class TransferStaffAction extends Action
{
    public function handle(
        Staff $staff, 
        array $transferDetails
    ): StaffTransfer {
        // Orchestrate package services
        return app(StaffTransferService::class)
            ->processTransfer($staff, $transferDetails);
    }
}
```

## Dependencies and Contracts

### Allowed Dependencies

- **Laravel Framework**: Core framework components only
- **Standard PHP Libraries**: Native PHP functionality
- **Testing Framework**: Orchestra Testbench for independent testing
- **Development Tools**: PHPUnit, PHPStan for development workflow

### Forbidden Dependencies

- **Other Nexus Packages**: No direct dependencies on other atomic packages
- **HTTP Components**: No controllers, middleware, form requests
- **Authentication Components**: No Auth facades or user models
- **External Services**: No direct API integrations or third-party services

### Contract Definitions Required

The package should define contracts for external integrations:

```php
namespace Nexus\Backoffice\Contracts;

interface UserProviderContract
{
    public function findUser(int $userId): ?object;
    public function getUserPermissions(int $userId): array;
}

interface NotificationContract
{
    public function notifyStaffTransfer(StaffTransfer $transfer): void;
    public function notifyOrganizationalChange(array $changes): void;
}
```

## Testing Requirements

### Independent Testing Strategy

1. **Orchestra Testbench**: All tests must run independently using Orchestra Testbench
2. **In-Memory Database**: SQLite in-memory database for test isolation
3. **Factory Usage**: All test data created via package factories
4. **Mock External Dependencies**: Use contracts and mocking for external concerns

### Test Coverage Requirements

- **Unit Tests**: All models, services, helpers, and business logic
- **Feature Tests**: End-to-end business workflows
- **Integration Tests**: Database relationships and migrations
- **Factory Tests**: All model factories and states

### Validation Command

```bash
# Test package in complete isolation
cd packages/nexus-backoffice
composer install
composer test
```

## Migration and Refactoring Roadmap

### Phase 1: Extract Presentation Layer (High Priority)

1. **Move Console Commands**
   - Extract all commands to `Nexus\Erp\Console\Commands\Backoffice\*`
   - Update command registration in main application
   - Remove command registration from package service provider

2. **Refactor Service Provider**
   - Remove observer auto-registration
   - Remove policy auto-registration
   - Focus on core package registration only

### Phase 2: Create Action Orchestration (Medium Priority)

1. **Create Laravel Actions**
   - Implement all required orchestration actions
   - Map current console command functionality to actions
   - Create HTTP/CLI/Job integration points

2. **Update Integration Points**
   - Modify consuming code to use actions instead of direct package access
   - Update Edward demo application integration

### Phase 3: Enhance Contracts (Low Priority)

1. **Define External Contracts**
   - Create user provider contract
   - Create notification contract
   - Implement adapter patterns for external dependencies

2. **Documentation Updates**
   - Update package documentation
   - Create migration guide for consumers
   - Document new action-based integration patterns

## Compliance Verification

### Architecture Compliance Checklist

- [ ] **No HTTP Controllers**: Package contains no presentation layer components
- [ ] **No Route Definitions**: No web or API routes defined
- [ ] **No Middleware**: No request/response handling components
- [ ] **Independent Testing**: All tests pass with only package dependencies
- [ ] **Orchestration Separation**: All orchestration logic moved to `Nexus\Erp`
- [ ] **Contract-Based Integration**: External dependencies abstracted behind contracts

### Verification Commands

```bash
# Verify independent testability
cd packages/nexus-backoffice && composer test

# Verify no forbidden components
grep -r "Controller\|Route::\|Middleware" src/

# Verify package isolation
composer install --no-dev && php artisan test
```

## Breaking Changes Documentation

### Version 2.0.0 Breaking Changes

When implementing Maximum Atomicity compliance:

1. **Console Commands Moved**: All Artisan commands moved to orchestration layer
2. **Observer Registration**: No longer auto-registered, must be manually enabled
3. **Policy Registration**: Authorization policies moved to main application
4. **Service Provider**: Reduced scope, may affect auto-discovery features

### Migration Guide for Consumers

Consumers of this package will need to:

1. **Update Command Imports**: Change command namespace references
2. **Enable Observers**: Manually register observers if needed
3. **Register Policies**: Update authorization service provider
4. **Use Actions**: Adopt Laravel Actions for orchestration instead of direct package usage

## Package Maturity and Roadmap

### Current State: External Package Integration
- Originated as external package `azaharizaman/backoffice`  
- Well-structured with comprehensive features
- Requires architectural refactoring for Maximum Atomicity compliance

### Next Version Goals
- Full Maximum Atomicity compliance
- Laravel Actions integration
- Enhanced contract-based architecture
- Improved independent testability

### Long-term Vision
- Reference implementation for organizational management
- Reusable component for any Laravel ERP application
- Foundation for advanced HR and workforce management modules

## Functional Requirements

### FR-001: Company Hierarchy Management
**Priority**: High  
**Description**: The system must support multi-level company hierarchical structures with parent-child relationships.

**Acceptance Criteria**:
- [ ] Companies can have zero or one parent company
- [ ] Companies can have unlimited child companies  
- [ ] System must prevent circular hierarchies
- [ ] Maximum hierarchy depth configurable (default: 10 levels)
- [ ] Company codes must be unique across the system
- [ ] Soft deletion must be supported with cascade rules

**Business Rules**:
- Parent company must be active to have active children
- Deleting a parent company must handle child company reassignment
- Company codes follow configurable pattern validation

### FR-002: Office Structure Management  
**Priority**: High  
**Description**: The system must manage physical office locations with hierarchical relationships and type categorization.

**Acceptance Criteria**:
- [ ] Offices belong to exactly one company
- [ ] Offices can have parent-child relationships within company
- [ ] Multiple office types can be assigned to single office
- [ ] Office addresses and contact information stored
- [ ] Geographic coordinates support for mapping
- [ ] Office capacity and utilization tracking

**Business Rules**:
- Office hierarchy cannot exceed company boundaries
- Office types determine available facilities and capabilities
- Office status affects staff assignment eligibility

### FR-003: Department Structure Management
**Priority**: High
**Description**: The system must manage logical departmental structures independent of physical office locations.

**Acceptance Criteria**:
- [ ] Departments belong to exactly one company
- [ ] Departments can have hierarchical parent-child relationships
- [ ] Department codes unique within company scope
- [ ] Department budget and cost center tracking
- [ ] Department head assignment and delegation
- [ ] Cross-office department support

**Business Rules**:
- Department hierarchy independent of office structure
- Department heads must be staff members within same company
- Budget approval workflows based on department hierarchy

### FR-004: Staff Management and Assignment
**Priority**: Critical
**Description**: The system must manage staff records with flexible assignment to offices and/or departments.

**Acceptance Criteria**:
- [ ] Staff can be assigned to office, department, or both
- [ ] Supervisor-subordinate relationships managed
- [ ] Multiple position assignments within same company
- [ ] Employment status tracking (active, resigned, transferred)
- [ ] Staff personal and professional information storage
- [ ] Reporting line visualization and validation

**Business Rules**:
- Staff can only have one primary supervisor per company
- Supervisor must be in same or parent organizational unit
- Matrix reporting relationships supported through units
- Staff codes must be unique system-wide

### FR-005: Unit and Matrix Organization
**Priority**: Medium  
**Description**: The system must support logical staff groupings through units and unit groups for matrix management.

**Acceptance Criteria**:
- [ ] Units belong to exactly one unit group
- [ ] Staff can belong to multiple units simultaneously
- [ ] Unit leaders and unit member roles defined
- [ ] Cross-department and cross-office unit membership
- [ ] Unit objectives and performance tracking
- [ ] Unit hierarchy within unit groups

**Business Rules**:
- Unit membership transcends traditional hierarchy
- Unit leaders responsible for unit member coordination
- Unit performance affects member evaluations

### FR-006: Staff Transfer Management
**Priority**: High
**Description**: The system must manage structured staff movement between organizational units with approval workflows.

**Acceptance Criteria**:
- [ ] Transfer requests with approval workflow
- [ ] Multiple transfer types (permanent, temporary, secondment)
- [ ] Effective date scheduling and batch processing
- [ ] Transfer history and audit trail
- [ ] Impact analysis on reporting lines
- [ ] Automated notifications and alerts

**Business Rules**:
- Transfers require approval from both sending and receiving units
- Transfer effective dates cannot be retroactive beyond 30 days
- Temporary transfers have mandatory return dates
- Salary and benefit implications tracked

### FR-007: Organizational Chart Generation
**Priority**: Medium
**Description**: The system must generate comprehensive organizational charts with multiple visualization options.

**Acceptance Criteria**:
- [ ] Company-wide organizational charts
- [ ] Department-specific organizational charts
- [ ] Office-based organizational charts  
- [ ] Individual reporting line charts
- [ ] Multiple export formats (JSON, CSV, DOT/Graphviz)
- [ ] Interactive drill-down capabilities

**Business Rules**:
- Charts reflect current organizational state
- Charts include position information and staff details
- Charts support matrix relationships visualization

### FR-008: Position and Role Management
**Priority**: Medium
**Description**: The system must manage job positions and roles within the organizational structure.

**Acceptance Criteria**:
- [ ] Position definitions with job descriptions
- [ ] Position hierarchy and career progression paths
- [ ] Salary grades and compensation bands
- [ ] Required qualifications and competencies
- [ ] Position approval and budgeting workflow
- [ ] Vacant position tracking and recruitment integration

**Business Rules**:
- Positions must be approved before staff assignment
- Position hierarchy affects approval authorities
- Position changes trigger compensation review

### FR-009: Reporting and Analytics
**Priority**: Medium
**Description**: The system must provide comprehensive reporting and analytics capabilities.

**Acceptance Criteria**:
- [ ] Staff distribution reports across organizational units
- [ ] Transfer volume and pattern analysis
- [ ] Organizational span of control metrics
- [ ] Headcount and FTE calculation reports
- [ ] Hierarchy depth and structure analysis
- [ ] Custom report builder functionality

**Business Rules**:
- Reports respect data access permissions
- Real-time and historical reporting supported
- Export capabilities for external analysis

### FR-010: Data Import and Integration
**Priority**: Low
**Description**: The system must support bulk data operations and external system integration.

**Acceptance Criteria**:
- [ ] Bulk staff data import with validation
- [ ] Organizational structure import/export
- [ ] Integration APIs for external systems
- [ ] Data synchronization capabilities
- [ ] Error handling and rollback functionality
- [ ] Import audit trails and validation reports

**Business Rules**:
- Import operations validate business rules
- Failed imports provide detailed error reports
- Incremental updates supported for large datasets

## Non-Functional Requirements

### NFR-001: Performance Requirements
**Priority**: High

**Response Time**:
- [ ] Organizational chart generation: < 2 seconds for companies with 10,000 staff
- [ ] Staff search and filtering: < 500ms for any query
- [ ] Transfer processing: < 1 second per transfer
- [ ] Report generation: < 5 seconds for standard reports

**Throughput**:
- [ ] Support 1,000 concurrent users
- [ ] Process 10,000 transfers per hour during batch operations
- [ ] Handle 100,000 staff records per company
- [ ] Support 1,000 companies in single instance

**Resource Utilization**:
- [ ] Memory usage: < 512MB for typical operations
- [ ] Database queries: < 100 queries for complex organizational charts
- [ ] CPU usage: < 80% during peak operations

### NFR-002: Scalability Requirements  
**Priority**: High

**Horizontal Scaling**:
- [ ] Database queries optimized for read replicas
- [ ] Stateless package design for load balancing
- [ ] Cache-friendly data structures
- [ ] Partition-ready database schema

**Vertical Scaling**:
- [ ] Linear performance improvement with hardware upgrades
- [ ] Memory-efficient data structures
- [ ] Optimized database indexing strategy

**Data Volume Scaling**:
- [ ] Support up to 1 million staff records
- [ ] Handle 10-year historical data retention
- [ ] Efficient archiving of inactive records

### NFR-003: Reliability and Availability
**Priority**: High

**Uptime Requirements**:
- [ ] 99.9% uptime during business hours
- [ ] Graceful degradation during high load
- [ ] Fault tolerance for external dependencies

**Data Integrity**:
- [ ] ACID compliance for critical operations
- [ ] Referential integrity enforcement
- [ ] Automatic backup and recovery procedures
- [ ] Data corruption detection and prevention

**Error Handling**:
- [ ] Comprehensive error logging and monitoring
- [ ] User-friendly error messages
- [ ] Automatic retry mechanisms for transient failures
- [ ] Circuit breaker patterns for external integrations

### NFR-004: Security Requirements
**Priority**: Critical

**Authentication and Authorization**:
- [ ] Integration with corporate authentication systems
- [ ] Role-based access control (RBAC)
- [ ] Fine-grained permissions for organizational data
- [ ] Multi-factor authentication support

**Data Protection**:
- [ ] Encryption at rest for sensitive data
- [ ] Encryption in transit (TLS 1.3)
- [ ] Personal data anonymization capabilities
- [ ] GDPR compliance for EU operations

**Audit and Compliance**:
- [ ] Complete audit trail for all data changes
- [ ] Compliance with SOX and industry regulations
- [ ] Data retention policy enforcement
- [ ] Access logging and monitoring

### NFR-005: Maintainability and Extensibility
**Priority**: High

**Code Quality**:
- [ ] PHPStan level 8 static analysis compliance
- [ ] 100% test coverage for business logic
- [ ] PSR-12 coding standards compliance
- [ ] Comprehensive documentation coverage

**Architecture**:
- [ ] Maximum Atomicity compliance
- [ ] Modular design with clear boundaries
- [ ] Plugin architecture for extensions
- [ ] API-first design for integrations

**Deployment**:
- [ ] Zero-downtime deployment support
- [ ] Database migration automation
- [ ] Configuration management
- [ ] Environment-specific configuration

### NFR-006: Usability and Accessibility
**Priority**: Medium

**User Experience**:
- [ ] Intuitive organizational chart navigation
- [ ] Responsive design for mobile devices
- [ ] Progressive loading for large datasets
- [ ] Keyboard navigation support

**Accessibility**:
- [ ] WCAG 2.1 AA compliance
- [ ] Screen reader compatibility
- [ ] High contrast mode support
- [ ] Multi-language support

**Documentation**:
- [ ] Comprehensive user manuals
- [ ] API documentation with examples
- [ ] Video tutorials for complex operations
- [ ] Context-sensitive help system

### NFR-007: Compatibility and Integration  
**Priority**: Medium

**Platform Compatibility**:
- [ ] PHP 8.3+ compatibility
- [ ] Laravel 11+ framework compatibility
- [ ] MySQL 8.0+ and PostgreSQL 13+ support
- [ ] Redis caching support

**Integration Compatibility**:
- [ ] RESTful API compliance
- [ ] Webhook support for real-time notifications
- [ ] Standard export formats (CSV, JSON, XML)
- [ ] LDAP/Active Directory integration

**Browser Compatibility**:
- [ ] Modern browser support (Chrome 90+, Firefox 88+, Safari 14+)
- [ ] Mobile browser optimization
- [ ] Progressive web app capabilities

### NFR-008: Operational Requirements
**Priority**: Medium

**Monitoring and Logging**:
- [ ] Application performance monitoring (APM)
- [ ] Business metrics dashboards
- [ ] Error rate and response time alerts
- [ ] Custom metric collection

**Backup and Recovery**:
- [ ] Automated daily backups
- [ ] Point-in-time recovery capabilities
- [ ] Disaster recovery procedures
- [ ] Recovery time objective (RTO): 4 hours
- [ ] Recovery point objective (RPO): 1 hour

**Capacity Planning**:
- [ ] Growth projection modeling
- [ ] Resource utilization trending
- [ ] Automatic scaling triggers
- [ ] Performance baseline establishment

## Quality Attributes

### Testability
- **Independent Testing**: Package must be testable in complete isolation using Orchestra Testbench
- **Mock Support**: All external dependencies abstracted behind contracts for mocking
- **Test Data**: Comprehensive factory support for all business scenarios
- **Coverage**: Minimum 95% code coverage for business logic components

### Flexibility  
- **Configuration**: All business rules configurable through package config
- **Extension Points**: Plugin architecture for custom business logic
- **Integration**: Contract-based integration for external systems
- **Customization**: Model extension support for additional attributes

### Reusability
- **Framework Agnostic**: Core business logic independent of specific Laravel features
- **Composer Package**: Distributable as standalone composer package
- **Documentation**: Comprehensive usage examples and integration patterns
- **Backward Compatibility**: Semantic versioning with migration guides

## Compliance and Validation

### Maximum Atomicity Compliance Verification
```bash
# Verify package independence
cd packages/nexus-backoffice && composer test

# Verify no presentation layer components  
grep -r "Controller\|Route::\|Middleware" src/ || echo "✓ No presentation layer"

# Verify independent testability
composer install --no-dev && vendor/bin/phpunit
```

### Business Rule Validation
- [ ] All hierarchies prevent circular references
- [ ] Data integrity constraints enforced at database level
- [ ] Business rule violations throw appropriate domain exceptions
- [ ] Audit trails capture all state changes

### Performance Validation
- [ ] Load testing with 10,000+ staff records
- [ ] Memory profiling under typical workloads  
- [ ] Database query optimization verification
- [ ] Response time benchmarking
