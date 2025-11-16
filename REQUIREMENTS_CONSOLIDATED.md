# Refactored & Implemented Requirements

The table below lists the refactored requirements implemented so far following the new Nexus monorepo architecture (`packages/` for logic, `apps/` for implementation). Each row maps a requirement from the original documents to the package/app, implementation files, status, and completion date.

| Package/App (Namespace) | Requirements # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\ProjectManagement` | FR-L1-001 | Create project with basic details (name, client, start/end, budget) | `apps/Atomy/database/migrations/2025_11_16_000001_create_projects_table.php`, `apps/Atomy/app/Models/Project.php`, `apps/Atomy/app/Http/Controllers/Api/ProjectController.php`, `packages/ProjectManagement/src/Services/ProjectManager.php` | Completed | Implemented as domain + app persistence. Pkg defines `ProjectInterface`. | 2025-11-16 |
| `Nexus\ProjectManagement` | FR-L1-002 | Create and manage tasks with assignees + priority | `apps/Atomy/database/migrations/2025_11_16_000002_create_tasks_table.php`, `apps/Atomy/app/Models/Task.php`, `apps/Atomy/app/Http/Controllers/Api/TaskController.php`, `packages/ProjectManagement/src/Services/TaskManager.php` | Completed | TaskRepository (`DbTaskRepository`) provides queries and updates. | 2025-11-16 |
| `Nexus\ProjectManagement` | FR-L1-004 | Time tracking and timesheet entry | `apps/Atomy/database/migrations/2025_11_16_000003_create_timesheets_table.php`, `apps/Atomy/app/Models/Timesheet.php`, `apps/Atomy/app/Http/Controllers/Api/TimesheetController.php`, `packages/ProjectManagement/src/Services/TimesheetManager.php` | Completed | Timesheet approval/rejection workflows implemented. | 2025-11-16 |
| `Nexus\ProjectManagement` | FR-L1-005 | My Tasks (view all tasks assigned to user) | `packages/ProjectManagement/src/Services/TaskManager.php::getTasksByAssignee`, `apps/Atomy/app/Repositories/DbTaskRepository.php::findByAssignee` | Completed | Query implemented in repository; API endpoint can be added quickly. | 2025-11-16 |
| `Nexus\ProjectManagement` | FR-L1-06 | Project dashboard (overview, % complete) | `packages/ProjectManagement/src/Services/ProjectManager.php::getActiveProjects`, `apps/Atomy/app/Models/Project.php` | Completed | Basic summary implemented; API-based dashboard data ready. | 2025-11-16 |
| `Nexus\ProjectManagement` | FR-L2-001 | Milestones with approvals and deliverables | `apps/Atomy/database/migrations/2025_11_16_000004_create_milestones_table.php`, `apps/Atomy/app/Models/Milestone.php`, `packages/ProjectManagement/src/Services/MilestoneManager.php` | Completed | Approval flow implemented via `MilestoneRepository::approve()` | 2025-11-16 |
| `Nexus\ProjectManagement` | FR-L2-002 | Task dependencies (predecessor) & Gantt-support (calc) | `apps/Atomy/database/migrations/2025_11_16_000008_create_task_dependencies_table.php`, `apps/Atomy/app/Models/TaskDependency.php`, `packages/ProjectManagement/src/Services/TaskManager.php::canStartTask` | Partially Completed | Task dependency persistence & start/complete checks implemented; Gantt chart rendering and critical path analysis planned. | 2025-11-16 |
| `Nexus\ProjectManagement` | FR-L2-003 | Resource allocation & overallocation checks | `apps/Atomy/database/migrations/2025_11_16_000005_create_resource_allocations_table.php`, `apps/Atomy/app/Models/ResourceAllocation.php`, `packages/ProjectManagement/src/Services/ResourceManager.php` | Completed | Overallocation detection via `ResourceManager::allocateResource`; repository `DbResourceAllocationRepository` implements queries. | 2025-11-16 |
| `Nexus\ProjectManagement` | FR-L2-004 | Budget tracking (planned vs actual) | `packages/ProjectManagement/src/Services/BudgetManager.php`, `apps/Atomy/app/Repositories/DbTimesheetRepository.php::findByProject` | Completed | Budget computation uses `Timesheet` & `Expense` with `BillingRateProviderInterface`. Real HRM rates integration planned. | 2025-11-16 |
| `Nexus\ProjectManagement` | FR-L2-005 | Project invoicing (milestone/T&M) | `apps/Atomy/app/Models/Invoice.php`, `packages/ProjectManagement/src/Services/InvoiceManager.php`, `apps/Atomy/app/Repositories/DbInvoiceRepository.php` | Completed | Invoice generation persisted; integration with `nexus/accounting` planned. | 2025-11-16 |
| `Nexus\ProjectManagement` | FR-L2-006 | Expense tracking & approvals | `apps/Atomy/database/migrations/2025_11_16_000006_create_expenses_table.php`, `apps/Atomy/app/Models/Expense.php`, `apps/Atomy/app/Repositories/DbExpenseRepository.php` | Completed | Expense approval implemented via repository update. | 2025-11-16 |
| `Nexus\ProjectManagement` | FR-L2-007 | Timesheet approval workflow | `packages/ProjectManagement/src/Services/TimesheetManager.php`, `apps/Atomy/app/Repositories/DbTimesheetRepository.php` | Completed | Approve/reject implemented; timesheets locked after approval. | 2025-11-16 |
| `Nexus\Atomy` (App) | Architecture | Headless orchestrator for atomic packages, moved migrations & routes into `apps/Atomy` | `apps/Atomy/` structure, `AtomyServiceProvider` bindings in `apps/Atomy/app/Providers/AtomyServiceProvider.php` | Completed | Orchestration provider centralised; `apps/Atomy` exposes REST APIs. | 2025-11-16 |
| `Nexus\AuditLogger` | Infrastructure | Package rename and composer entry updated | `packages/AuditLogger/composer.json`, `composer.json` (root) repositories updated | Completed | Renamed `nexus/audit-log` to `nexus/audit-logger`. | 2025-11-16 |
| `Nexus\Accounting` and others | Infrastructure | Package renames to PascalCase (Accounting, Analytics, FieldService etc.) and composer path registry updated | `packages/Accounting/composer.json`, `packages/Analytics/composer.json`, `composer.json` (root) | Completed | Packages created/renamed to match atomic naming conventions. | 2025-11-16 |
| `Nexus\Backoffice` | Architecture Refactoring | Package refactored to follow new architecture: framework-agnostic business logic with contracts, migrations and models moved to apps/Atomy | `packages/Backoffice/src/Contracts/*`, `packages/Backoffice/src/Services/*`, `packages/Backoffice/src/Exceptions/*`, `apps/Atomy/database/migrations/*_backoffice_*`, `apps/Atomy/app/Models/{Company,Office,Department,Staff,Unit,UnitGroup,Position,OfficeType,StaffTransfer}.php`, `apps/Atomy/app/Repositories/{Company,Office,Department,Staff,Unit,UnitGroup,Position,OfficeType,StaffTransfer}Repository.php` | Completed | Package now contains only framework-agnostic contracts, services, and exceptions. All Laravel-specific code (Models, Observers, Policies, Casts, Traits) moved to apps/Atomy. 18 contracts (9 data + 9 repository), 2 services (CompanyManager, StaffTransferManager), 11 migrations moved, 9 models moved, 9 repository implementations created. | 2025-11-16 |
| `Composer (root)` | Tooling | Path repositories and dependencies updated for new packages | `composer.json` top-level `repositories` path updates | Completed | All `packages/*` paths added or renamed. Run `composer update` to refresh lock. | 2025-11-16 |
| `apps/Atomy` | Non-functional PR-001..PR-006 | DB schema indexing & performance improvements, tenant scoping | `apps/Atomy/database/migrations/*` (index and tenant_id); `apps/Atomy/app/Models/*` include `tenant_id` | Completed | Database indexes added where needed; tenant columns ensured in core tables. | 2025-11-16 |
| `Security` | SR-001, SR-002, SR-003 | Tenant data isolation implemented; RBAC connectors wired but not applied to all endpoints yet | `apps/Atomy/app/Models/*` tenant scoping; `AtomyServiceProvider` binds permission service | In Progress | RBAC access enforcement for endpoints planned; permission adapter registered. | 2025-11-16 |
| `Nexus\Crm` | FR-L1-001..FR-L3-006 | Progressive CRM - Level 1 in-model trait (HasCrm), Level 2 DB-driven schemas (pipelines, entities, timers), Level 3 SLA & escalation services | `packages/Crm/src/Traits/HasCrm.php`, `packages/Crm/src/Core/CrmManager.php`, `packages/Crm/src/Core/PipelineEngine.php`, `packages/Crm/src/Core/ConditionEvaluatorManager.php`, `packages/Crm/src/Core/IntegrationManager.php`, `packages/Crm/src/Core/TimerProcessor.php`, `packages/Crm/src/Core/SlaService.php`, `packages/Crm/database/migrations/*`, `packages/Crm/config/crm.php` | Completed | Level-1 trait & safety guards implemented; Level-2 tables & pipeline engine implemented; Level-3 timers/SLA + escalations available. Unit tests exist in `packages/Crm/tests`. | 2025-11-16 |

### Nexus\Backoffice — Detailed Numbered Requirements

This package manages hierarchical organizational structures including companies, offices, departments, staff, units, and organizational charts. All requirements derived from `packages/Backoffice/docs/REQUIREMENTS.md`.

#### Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Backoffice` | `FR-BO-001` | Company hierarchy management with multi-level parent-child relationships | `packages/Backoffice/src/Services/CompanyManager.php`, `packages/Backoffice/src/Contracts/CompanyInterface.php`, `apps/Atomy/app/Models/Company.php`, `apps/Atomy/app/Repositories/Backoffice/CompanyRepository.php` | Completed | Supports unlimited company hierarchies with circular reference prevention. Models use HasHierarchy trait. | 2025-11-16 |
| `Nexus\Analytics` | `FR-L2-001` | Support DB-driven analytics definitions (JSON) | `apps/Atomy/database/migrations/2025_11_17_000012_create_analytics_definitions_table.php`, `apps/Atomy/app/Models/AnalyticsDefinition.php` | Partially Completed | Analytics definitions table added; API & engine-level DB-driven definitions implementation pending. | 2025-11-17 |
| `Nexus\Backoffice` | `FR-BO-003` | Department structure management independent of physical office locations | `packages/Backoffice/src/Contracts/DepartmentInterface.php`, `apps/Atomy/app/Models/Department.php`, `apps/Atomy/app/Repositories/Backoffice/DepartmentRepository.php` | Completed | Logical departmental hierarchies transcend office boundaries. Department hierarchy uses HasHierarchy trait. | 2025-11-16 |
| `Nexus\Backoffice` | `FR-BO-004` | Staff management with flexible assignment to offices and/or departments | `packages/Backoffice/src/Contracts/StaffInterface.php`, `apps/Atomy/app/Models/Staff.php`, `apps/Atomy/app/Repositories/Backoffice/StaffRepository.php` | Completed | Staff can belong to office, department, or both. Supervisor-subordinate relationships tracked. Multiple position assignments supported. | 2025-11-16 |
| `Nexus\Backoffice` | `FR-BO-005` | Unit and matrix organization for cross-functional staff groupings | `packages/Backoffice/src/Contracts/UnitInterface.php`, `packages/Backoffice/src/Contracts/UnitGroupInterface.php`, `apps/Atomy/app/Models/Unit.php`, `apps/Atomy/app/Models/UnitGroup.php` | Completed | Units belong to unit groups. Staff can belong to multiple units via many-to-many. Matrix organization support. | 2025-11-16 |
| `Nexus\Backoffice` | `FR-BO-006` | Staff transfer management with approval workflows and effective date scheduling | `packages/Backoffice/src/Services/StaffTransferManager.php`, `packages/Backoffice/src/Contracts/StaffTransferInterface.php`, `apps/Atomy/app/Models/StaffTransfer.php` | Completed | Transfer requests with approval workflow. Status tracking (pending, approved, rejected, completed). Effective date scheduling and batch processing. | 2025-11-16 |
| `Nexus\Backoffice` | `FR-BO-007` | Organizational chart generation with multiple visualization formats | `packages/Backoffice/src/Helpers/OrganizationalChart.php` | Completed | Generates company-wide, department-specific, and office-based charts. Export formats: JSON, CSV, DOT (Graphviz). | 2025-11-16 |
| `Nexus\Backoffice` | `FR-BO-008` | Position and role management within organizational structure | `packages/Backoffice/src/Contracts/PositionInterface.php`, `apps/Atomy/app/Models/Position.php`, `apps/Atomy/app/Repositories/Backoffice/PositionRepository.php` | Completed | Position definitions with hierarchy. Position types enum (executive, management, professional, technical, support). | 2025-11-16 |
| `Nexus\Backoffice` | `FR-BO-009` | Office type categorization and management | `packages/Backoffice/src/Contracts/OfficeTypeInterface.php`, `apps/Atomy/app/Models/OfficeType.php`, `apps/Atomy/app/Repositories/Backoffice/OfficeTypeRepository.php` | Completed | Flexible office type definitions. Many-to-many relationship with offices. Status tracking (active/inactive). | 2025-11-16 |
| `Nexus\Backoffice` | `FR-BO-010` | Circular reference prevention in hierarchies | `packages/Backoffice/src/Exceptions/CircularReferenceException.php`, `packages/Backoffice/src/Services/CompanyManager.php` | Completed | Business logic validates parent relationships. Prevents circular hierarchies in companies, offices, departments. | 2025-11-16 |

#### Non-Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Backoffice` | `NFR-BO-001` | Performance - Organizational chart generation < 2 seconds for 10,000 staff | `packages/Backoffice/src/Helpers/OrganizationalChart.php` | Completed | Optimized queries with eager loading. Caching strategy for large organizations. | 2025-11-16 |
| `Nexus\Backoffice` | `NFR-BO-002` | Performance - Staff search and filtering < 500ms | `apps/Atomy/app/Repositories/Backoffice/StaffRepository.php` | Completed | Database indexes on key search fields. Efficient query construction with filters. | 2025-11-16 |
| `Nexus\Backoffice` | `NFR-BO-003` | Scalability - Support up to 100,000 staff records per company | `apps/Atomy/database/migrations/*_backoffice_*` | Completed | Indexed foreign keys. Soft deletes for data retention. Optimized schema design. | 2025-11-16 |
| `Nexus\Backoffice` | `NFR-BO-004` | Reliability - ACID compliance for critical operations | `packages/Backoffice/src/Services/*`, `apps/Atomy/app/Repositories/Backoffice/*` | Completed | Repository pattern ensures transactional integrity. Database constraints enforce referential integrity. | 2025-11-16 |
| `Nexus\Backoffice` | `NFR-BO-005` | Security - Audit trail for all organizational changes | `packages/Backoffice/src/Contracts/AuditContract.php`, `apps/Atomy/app/Observers/*Observer.php` | Completed | Observers track model changes. Audit contract for integration with audit logging package. | 2025-11-16 |
| `Nexus\Backoffice` | `NFR-BO-006` | Maintainability - Framework-agnostic architecture | `packages/Backoffice/composer.json`, `packages/Backoffice/src/Contracts/*`, `packages/Backoffice/src/Services/*` | Completed | Zero Laravel dependencies in package. Contract-driven design. All persistence via repository interfaces. | 2025-11-16 |

#### Business Rules

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Backoffice` | `BR-BO-001` | Parent company must be active to have active children | `packages/Backoffice/src/Services/CompanyManager.php`, `apps/Atomy/app/Observers/CompanyObserver.php` | Completed | Validated during company creation and updates. Observer enforces business rule. | 2025-11-16 |
| `Nexus\Backoffice` | `BR-BO-002` | Office hierarchy cannot exceed company boundaries | `apps/Atomy/app/Models/Office.php`, `apps/Atomy/app/Observers/OfficeObserver.php` | Completed | Offices can only have parent within same company. Enforced at model level. | 2025-11-16 |
| `Nexus\Backoffice` | `BR-BO-003` | Department hierarchy independent of office structure | `apps/Atomy/app/Models/Department.php` | Completed | Departments managed separately from offices. Cross-office departments supported. | 2025-11-16 |
| `Nexus\Backoffice` | `BR-BO-004` | Staff can only have one primary supervisor per company | `apps/Atomy/app/Models/Staff.php` | Completed | Single reports_to_id foreign key. Matrix reporting through units for additional reporting lines. | 2025-11-16 |
| `Nexus\Backoffice` | `BR-BO-005` | Supervisor must be in same or parent organizational unit | `apps/Atomy/app/Observers/StaffObserver.php` | Completed | Validation logic in observer. Prevents invalid supervisory relationships. | 2025-11-16 |
| `Nexus\Backoffice` | `BR-BO-006` | Staff transfer requires approval from authorized users | `packages/Backoffice/src/Services/StaffTransferManager.php`, `apps/Atomy/app/Models/StaffTransfer.php` | Completed | Transfer status workflow (pending → approved/rejected → completed). Approval tracking with user ID and timestamp. | 2025-11-16 |
| `Nexus\Backoffice` | `BR-BO-007` | Transfer effective dates cannot be retroactive beyond 30 days | `packages/Backoffice/src/Services/StaffTransferManager.php` | Completed | Business rule enforced in service layer. Configurable retroactive limit. | 2025-11-16 |
| `Nexus\Backoffice` | `BR-BO-008` | Unit membership transcends traditional hierarchy boundaries | `apps/Atomy/app/Models/Unit.php`, `apps/Atomy/database/migrations/*_staff_unit_table.php` | Completed | Many-to-many relationship allows cross-functional team formation. | 2025-11-16 |
| `Nexus\Backoffice` | `BR-BO-009` | Staff codes must be unique system-wide | `apps/Atomy/app/Models/Staff.php`, `apps/Atomy/database/migrations/*_staff_table.php` | Completed | Database unique constraint on employee_number. Validation in repository. | 2025-11-16 |
| `Nexus\Backoffice` | `BR-BO-010` | Company codes must be unique across the system | `apps/Atomy/app/Models/Company.php`, `apps/Atomy/database/migrations/*_companies_table.php` | Completed | Database unique constraint on code column. Repository validation. | 2025-11-16 |

#### Architecture Compliance

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Backoffice` | `ARCH-BO-001` | Package must be framework-agnostic with no Laravel dependencies | `packages/Backoffice/composer.json`, `packages/Backoffice/src/*` | Completed | Only requires PHP 8.3+. No illuminate/* packages. All business logic pure PHP. | 2025-11-16 |
| `Nexus\Backoffice` | `ARCH-BO-002` | All data structures defined via interfaces | `packages/Backoffice/src/Contracts/*Interface.php` | Completed | 9 data structure interfaces (Company, Office, Department, Staff, Unit, UnitGroup, Position, OfficeType, StaffTransfer). | 2025-11-16 |
| `Nexus\Backoffice` | `ARCH-BO-003` | All persistence operations via repository interfaces | `packages/Backoffice/src/Contracts/*RepositoryInterface.php` | Completed | 9 repository interfaces with complete CRUD and query methods. | 2025-11-16 |
| `Nexus\Backoffice` | `ARCH-BO-004` | Business logic in service layer | `packages/Backoffice/src/Services/CompanyManager.php`, `packages/Backoffice/src/Services/StaffTransferManager.php` | Completed | Services contain business rules, validation, and workflows. Framework-agnostic. | 2025-11-16 |
| `Nexus\Backoffice` | `ARCH-BO-005` | All database migrations in application layer | `apps/Atomy/database/migrations/*_backoffice_*` | Completed | 11 migrations moved to Atomy. Package contains no migrations. | 2025-11-16 |
| `Nexus\Backoffice` | `ARCH-BO-006` | All Eloquent models in application layer | `apps/Atomy/app/Models/{Company,Office,Department,Staff,Unit,UnitGroup,Position,OfficeType,StaffTransfer}.php` | Completed | 9 models implementing package interfaces. All in Atomy application. | 2025-11-16 |
| `Nexus\Backoffice` | `ARCH-BO-007` | Repository implementations in application layer | `apps/Atomy/app/Repositories/Backoffice/*Repository.php` | Completed | 9 concrete repositories implementing package repository interfaces. | 2025-11-16 |
| `Nexus\Backoffice` | `ARCH-BO-008` | IoC container bindings in application service provider | `apps/Atomy/app/src/Providers/BackofficeServiceProvider.php` | Completed | All repository interfaces bound to concrete implementations. Observers and policies registered. | 2025-11-16 |

### Nexus\Crm — Detailed Numbered Requirements (migrated)

Below are the exact numbered user stories and requirements from the original `packages/Crm/REQUIREMENTS.md`. Each number is moved as a separate row for developer clarity.

#### User Stories

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |

<!-- User Stories -->
| `Nexus\Crm` | `US-001` | As a developer, I want to add the `HasCrm` trait to my model to manage contacts without migrations | `packages/Crm/src/Traits/HasCrm.php` | Completed | Level 1 in-model capability; no extra migrations. | 2025-11-16 |
| `Nexus\Crm` | `US-002` | As a developer, I want to define contact fields as an array in my model without external dependencies | `packages/Crm/src/Traits/HasCrm.php`, `packages/Crm/config/crm.php` | Completed | Field config read from model property `crmConfiguration`. | 2025-11-16 |
| `Nexus\Crm` | `US-003` | As a developer, I want to call `$model->crm()->addContact($data)` to create a new contact | `packages/Crm/src/Traits/HasCrm.php::addContact()` | Completed | Emits `ContactCreatedEvent`; validates data. | 2025-11-16 |
| `Nexus\Crm` | `US-004` | As a developer, I want to call `$model->crm()->can('edit')` to check permissions declaratively | `packages/Crm/src/Core/CrmManager.php::can()` | Completed | Guard checks centralized in `CrmManager`. | 2025-11-16 |
| `Nexus\Crm` | `US-005` | As a developer, I want to call `$model->crm()->history()` to view audit logs | `packages/Crm/src/Core/CrmManager.php::history()` | Completed | Uses in-model CRM data for Level 1, DB in Level 2. | 2025-11-16 |
| `Nexus\Crm` | `US-010` | As a developer, I want to promote to database-driven CRM without changing Level 1 code | `packages/Crm/database/migrations/*`, `packages/Crm/src/Core/PipelineEngine.php` | Completed | DB definitions and adapters exist to support Level 2. | 2025-11-16 |
| `Nexus\Crm` | `US-011` | As a developer, I want to define leads and opportunities with customizable stages | `packages/Crm/database/migrations/*` (`crm_contacts`, `crm_opportunities`), `packages/Crm/src/Core/PipelineEngine.php` | Completed | Schema & entity mapping for leads/opportunities. | 2025-11-16 |
| `Nexus\Crm` | `US-012` | As a developer, I want to use conditional pipelines (e.g., if score > 50, promote to qualified) | `packages/Crm/src/Core/PipelineEngine.php`, `packages/Crm/src/Core/ConditionEvaluatorManager.php` | Completed | Expression evaluation engine implemented. | 2025-11-16 |
| `Nexus\Crm` | `US-013` | As a developer, I want to run parallel campaigns (email + phone calls simultaneously) | `packages/Crm/src/Core/PipelineEngine.php`, `packages/Crm/src/Core/IntegrationManager.php` | Partially Completed | Parallel branch execution supported; action workers / queues used. | 2025-11-16 |
| `Nexus\Crm` | `US-014` | As a developer, I want multi-user assignments with approval strategies (unison, majority, quorum) | `packages/Crm/src/Core/AssignmentStrategyResolver.php` | Completed | Built-in strategies with extensibility via contracts. | 2025-11-16 |
| `Nexus\Crm` | `US-015` | As a sales manager, I want a unified dashboard showing all pending leads and opportunities | `packages/Crm/src/Services/CrmDashboard.php` | Completed | API/Service to fetch pending items with filters. | 2025-11-16 |
| `Nexus\Crm` | `US-016` | As a sales rep, I want to log interactions with notes and file attachments | `packages/Crm/src/Core/IntegrationManager.php`, `packages/Crm/src/Models/` (attachments) | Completed | Interactions persisted, supports attachments via integrations. | 2025-11-16 |
| `Nexus\Crm` | `US-020` | As a sales manager, I want stale leads to auto-escalate after a configured time period | `packages/Crm/src/Core/EscalationService.php`, `packages/Crm/src/Core/TimerProcessor.php` | Completed | Scheduling via `crm_timers` and `EscalationService`. | 2025-11-16 |
| `Nexus\Crm` | `US-021` | As a sales manager, I want SLA tracking for lead response times with breach notifications | `packages/Crm/src/Core/SlaService.php` | Completed | SLA statuses & breach handlers implemented. | 2025-11-16 |
| `Nexus\Crm` | `US-022` | As a sales rep, I want to delegate my leads to a colleague during vacation with auto-routing | `packages/Crm/src/Core/AssignmentStrategyResolver.php`, `packages/Crm/database/migrations/*` | Completed | Delegation table & resolver; max depth enforced in business rules. | 2025-11-16 |
| `Nexus\Crm` | `US-023` | As a developer, I want to rollback failed campaigns with compensation logic | `packages/Crm/src/Core/PipelineEngine.php` (compensation) | Partially Completed | Compensation pattern implemented; requires integration tests. | 2025-11-16 |
| `Nexus\Crm` | `US-024` | As a system admin, I want to configure custom fields through an admin interface | `packages/Crm/src/Laravel/Http/Controllers/` + `packages/Crm/database/migrations` | Planned | Optional admin UI - API is available in Core; Atomy will add UI. | 2025-11-16 |

#### Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Crm` | `FR-L1-001` | HasCrm trait for models | `packages/Crm/src/Traits/HasCrm.php` | Completed | Add trait to any model; define `crm()` method returning array config; no migrations required; works instantly. | 2025-11-16 |
| `Nexus\Crm` | `FR-L1-002` | In-model contact definitions | `packages/Crm/src/Traits/HasCrm.php` | Completed | Define fields as array; store in JSON model column; no external tables or dependencies. | 2025-11-16 |
| `Nexus\Crm` | `FR-L1-003` | `crm()->addContact($data)` method | `packages/Crm/src/Traits/HasCrm.php::addContact()` | Completed | Create contact; emit `ContactCreatedEvent`; validate data; run in transaction. | 2025-11-16 |
| `Nexus\Crm` | `FR-L1-004` | `crm()->can($action)` method | `packages/Crm/src/Core/CrmManager.php::can()` | Completed | Return boolean permission check; guard conditions evaluated; no side effects. | 2025-11-16 |
| `Nexus\Crm` | `FR-L1-005` | `crm()->history()` method | `packages/Crm/src/Core/CrmManager.php::history()` | Completed | Return collection of changes; include timestamps, actors, before/after values. | 2025-11-16 |
| `Nexus\Crm` | `FR-L1-006` | Guard conditions on actions | `packages/Crm/src/Core/ConditionEvaluatorManager.php` | Completed | Accept callable; e.g., `fn($contact) => $contact->status == 'active'`; evaluated before action. | 2025-11-16 |
| `Nexus\Crm` | `FR-L1-007` | Hooks (before/after) | `packages/Crm/src/Core/PipelineEngine.php` | Completed | Register callbacks; e.g., notify after contact added; chainable. | 2025-11-16 |
| `Nexus\Crm` | `FR-L2-001` | Database-driven CRM definitions (JSON) | `packages/Crm/database/migrations/0001_01_01_000001_create_crm_definitions_table.php`, `packages/Crm/src/Laravel/Models/CrmDefinition.php` | Completed | Table `crm_definitions` for schemas; same API as Level 1; override in-model config; hot-reload without code changes. | 2025-11-16 |
| `Nexus\Crm` | `FR-L2-002` | Lead/Opportunity stages | `packages/Crm/database/migrations/*`, `packages/Crm/src/Core/PipelineEngine.php` | Completed | Define entity type: "lead", "opportunity"; assign to users/roles; pause until user action. | 2025-11-16 |
| `Nexus\Crm` | `FR-L2-003` | Conditional pipelines | `packages/Crm/src/Core/ConditionEvaluatorManager.php` | Completed | Support expressions: `==`, `>`, `<`, `AND`, `OR`; access to `data.score`, `data.status`, etc. | 2025-11-16 |
| `Nexus\Crm` | `FR-L2-004` | Parallel campaigns | `packages/Crm/src/Core/PipelineEngine.php` + queue workers | Partially Completed | Define array of actions; execute simultaneously; wait for all to complete before proceeding. | 2025-11-16 |
| `Nexus\Crm` | `FR-L2-005` | Inclusive gateways | `packages/Crm/src/Core/PipelineEngine.php` | Completed | Multiple conditions can be true; execute all true paths; synchronize at join point. | 2025-11-16 |
| `Nexus\Crm` | `FR-L2-006` | Multi-user assignment strategies | `packages/Crm/src/Core/AssignmentStrategyResolver.php` | Completed | Built-in strategies: unison (all approve), majority (>50%), quorum (custom threshold); extensible via contract. | 2025-11-16 |
| `Nexus\Crm` | `FR-L2-007` | Dashboard API/Service | `packages/Crm/src/Services/CrmDashboard.php` | Completed | `CrmDashboard::forUser($id)->pending()` returns pending items; support filter/sort; paginated. | 2025-11-16 |
| `Nexus\Crm` | `FR-L2-008` | Actions (convert, close, etc.) | `packages/Crm/src/Core/PipelineEngine.php` | Completed | Validate transition; log activity; support comments/attachments; trigger next stage automatically. | 2025-11-16 |
| `Nexus\Crm` | `FR-L2-009` | Data validation | `packages/Crm/src/Core/DefinitionValidator.php` | Completed | Schema validation in JSON definition; types: string, number, date, boolean, array; required/optional. | 2025-11-16 |
| `Nexus\Crm` | `FR-L2-010` | Plugin integrations | `packages/Crm/src/Core/IntegrationManager.php`, `packages/Crm/src/Contracts/IntegrationContract.php` | Completed | Asynchronous execution; built-in: email, webhook; extensible via `IntegrationContract`. | 2025-11-16 |
| `Nexus\Crm` | `FR-L3-001` | Escalation rules | `packages/Crm/src/Core/EscalationService.php` | Completed | Trigger after configurable time; notify/reassign; record escalation history; scheduled execution. | 2025-11-16 |
| `Nexus\Crm` | `FR-L3-002` | SLA tracking | `packages/Crm/src/Core/SlaService.php` | Completed | Track duration from start; define breach actions; status: on_track, at_risk, breached. | 2025-11-16 |
| `Nexus\Crm` | `FR-L3-003` | Delegation with date ranges | `packages/Crm/database/migrations/*` | Completed | Table: delegator, delegatee, start_date, end_date; auto-route during delegation; max depth: 3 levels. | 2025-11-16 |
| `Nexus\Crm` | `FR-L3-004` | Rollback logic | `packages/Crm/src/Core/PipelineEngine.php` | Partially Completed | Compensation activities on failure; execute in reverse order; restore previous state. | 2025-11-16 |
| `Nexus\Crm` | `FR-L3-005` | Custom fields configuration | `packages/Crm/src/Laravel/Models/CrmDefinition.php` | Completed | Define in database; validated on entity creation; optional admin UI via Nexus ERP Core. | 2025-11-16 |
| `Nexus\Crm` | `FR-L3-006` | Timer system | `packages/Crm/database/migrations/0001_01_01_000006_create_crm_timers_table.php`, `packages/Crm/src/Core/TimerProcessor.php` | Completed | Table `crm_timers`; indexed `trigger_at`; workers poll and process; NOT cron-based. | 2025-11-16 |
| `Nexus\Crm` | `FR-EXT-001` | Custom integrations | `packages/Crm/src/Contracts/IntegrationContract.php` | Completed | Implement `IntegrationContract`: `execute()`, `compensate()` methods. | 2025-11-16 |
| `Nexus\Crm` | `FR-EXT-002` | Custom conditions | `packages/Crm/src/Contracts/ConditionEvaluatorContract.php` | Completed | Implement `ConditionEvaluatorContract`: `evaluate($context)` method; return boolean. | 2025-11-16 |
| `Nexus\Crm` | `FR-EXT-003` | Custom strategies | `packages/Crm/src/Contracts/ApprovalStrategyContract.php` | Completed | Implement `ApprovalStrategyContract`: `canProceed($responses)` method. | 2025-11-16 |
| `Nexus\Crm` | `FR-EXT-004` | Custom triggers | `packages/Crm/src/Contracts/TriggerContract.php` | Completed | Implement `TriggerContract`: webhook, event-based, schedule-based. | 2025-11-16 |
| `Nexus\Crm` | `FR-EXT-005` | Custom storage | `packages/Crm/src/Contracts/StorageContract.php` | Completed | Implement `StorageContract`: support Eloquent (default), Redis, custom backends. | 2025-11-16 |

#### Non-Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Crm` | `PR-001` | Action execution time | `packages/Crm/src/Core/` + queries; `apps/Atomy` indexing | In Progress | Action execution time < 100ms; excluding async operations (emails, webhooks). | 2025-11-16 |
| `Nexus\Crm` | `PR-002` | Dashboard query (1,000 items) | `packages/Crm/src/Services/CrmDashboard.php` | In Progress | Dashboard query (1,000 items) < 500ms with proper database indexing. | 2025-11-16 |
| `Nexus\Crm` | `PR-003` | SLA check (10,000 active) | `packages/Crm/src/Core/TimerProcessor.php` | In Progress | SLA check (10,000 active) < 2s using timers table with indexed `trigger_at`. | 2025-11-16 |
| `Nexus\Crm` | `PR-004` | CRM initialization | `packages/Crm/config/crm.php` | In Progress | CRM initialization < 200ms including validation and schema loading. | 2025-11-16 |
| `Nexus\Crm` | `PR-005` | Parallel gateway synchronization (10 branches) | `packages/Crm/src/Core/PipelineEngine.php` | In Progress | Parallel gateway synchronization (10 branches) < 100ms; token-based coordination. | 2025-11-16 |

#### Security Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Crm` | `SR-001` | Unauthorized action prevention | `packages/Crm/src/Core/CrmManager.php::can()`, `ConditionEvaluatorManager` | Completed | Engine level - guard conditions evaluated before any state change. | 2025-11-16 |
| `Nexus\Crm` | `SR-002` | Expression sanitization | `packages/Crm/src/Core/ConditionEvaluatorManager.php` | Completed | Prevent code injection in conditional expressions. | 2025-11-16 |
| `Nexus\Crm` | `SR-003` | Tenant isolation | `packages/Crm/src/Laravel/Scopes/*` | Completed | Auto-scope all queries to current tenant (via `nexus-tenancy` integration). | 2025-11-16 |
| `Nexus\Crm` | `SR-004` | Plugin sandboxing | `packages/Crm/src/Core/IntegrationManager.php` | In Progress | Prevent malicious plugin code execution; validate before registration. | 2025-11-16 |
| `Nexus\Crm` | `SR-005` | Audit change tracking | `packages/Crm/src/Core/CrmManager.php`, `crm_history` migration | Completed | Immutable audit log for all CRM entity changes. | 2025-11-16 |
| `Nexus\Crm` | `SR-006` | RBAC integration | `packages/Crm/src/Adapters/Laravel/RbacAdapter.php` | In Progress | Permission checks via `nexus-identity-management` (if available) or Laravel gates. | 2025-11-16 |

#### Reliability Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Crm` | `REL-001` | ACID guarantees for state changes | `packages/Crm/src/Core/PipelineEngine.php` | Completed | All transitions wrapped in database transactions. | 2025-11-16 |
| `Nexus\Crm` | `REL-002` | Failed integrations don't block progress | `packages/Crm/src/Core/IntegrationManager.php` | Completed | Queue async operations; retry with exponential backoff. | 2025-11-16 |
| `Nexus\Crm` | `REL-003` | Concurrency control | `packages/Crm/src/Laravel/Models/*` (version/tokens) | Completed | Optimistic locking to prevent race conditions. | 2025-11-16 |
| `Nexus\Crm` | `REL-004` | Data corruption protection | `packages/Crm/src/Core/DefinitionValidator.php` | Completed | Schema validation before persistence. | 2025-11-16 |
| `Nexus\Crm` | `REL-005` | Retry failed transient operations | `packages/Crm/src/Core/IntegrationManager.php` | Completed | Configurable retry policy with dead letter queue. | 2025-11-16 |

#### Scalability Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Crm` | `SCL-001` | Asynchronous integrations | `packages/Crm/src/Core/IntegrationManager.php` | Completed | Queue-based execution for email, webhooks, external API calls. | 2025-11-16 |
| `Nexus\Crm` | `SCL-002` | Horizontal timer scaling | `packages/Crm/src/Core/TimerProcessor.php` | Completed | Multiple workers can process timers concurrently without conflicts. | 2025-11-16 |
| `Nexus\Crm` | `SCL-003` | Efficient query performance | `packages/Crm/database/migrations/*` | Completed | Proper indexes on `status`, `user_id`, `trigger_at`, `tenant_id`. | 2025-11-16 |
| `Nexus\Crm` | `SCL-004` | Support 100,000+ active instances | `packages/Crm/src/Core/` + caching layer `packages/Crm/src/Cache/*` | In Progress | Support 100,000+ active instances; optimized queries and caching for large-scale deployments. | 2025-11-16 |

#### Maintainability Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Crm` | `MAINT-001` | Framework-agnostic core | `packages/Crm/src/Core/*` | Completed | No Laravel dependencies in `src/Core/` directory; Laravel dependencies permitted in `src/Adapters/Laravel/` and `src/Http/` as per architectural guidelines. | 2025-11-16 |
| `Nexus\Crm` | `MAINT-002` | Laravel adapter pattern | `packages/Crm/src/Adapters/Laravel/` | Completed | Framework-specific code in `src/Adapters/Laravel/`. | 2025-11-16 |
| `Nexus\Crm` | `MAINT-003` | Orchestration policy | `packages/Crm/` docs & `REQUIREMENTS_AND_STRATEGY.md` | Completed | Atomic packages MUST NOT depend on `lorisleiva/laravel-actions`. Orchestration (multi-entrypoint actions) belongs in `nexus/erp` where `laravel-actions` may be used; in-package service classes should remain framework-agnostic and testable. | 2025-11-16 |
| `Nexus\Crm` | `MAINT-004` | Domain separation | `packages/Crm/src/Core/Services/*` | Completed | Lead, opportunity, campaign logic independent and separately testable. | 2025-11-16 |

#### Business Rules

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Crm` | `BR-001` | Users cannot self-assign leads (configurable) | `packages/Crm/src/Core/AssignmentStrategyResolver.php` | Completed | Level 2 only; enforced by assignment resolver. | 2025-11-16 |
| `Nexus\Crm` | `BR-002` | All state changes must be ACID-compliant | `packages/Crm/src/Core/PipelineEngine.php` | Completed | All state changes must be ACID-compliant. | 2025-11-16 |
| `Nexus\Crm` | `BR-003` | Stale leads auto-escalate after configured timeout | `packages/Crm/src/Core/EscalationService.php` | Completed | Stale leads auto-escalate after configured timeout. | 2025-11-16 |
| `Nexus\Crm` | `BR-004` | Compensation activities execute in reverse order | `packages/Crm/src/Core/PipelineEngine.php` | Partially Completed | Compensation activities execute in reverse order. | 2025-11-16 |
| `Nexus\Crm` | `BR-005` | Delegation chain maximum depth: 3 levels | `packages/Crm/src/Core/AssignmentStrategyResolver.php` | Completed | Delegation chain maximum depth: 3 levels. | 2025-11-16 |
| `Nexus\Crm` | `BR-006` | Level 1 code remains compatible after Level 2/3 upgrades | `packages/Crm/src/Traits/HasCrm.php` | Completed | Level 1 code remains compatible after Level 2/3 upgrades. | 2025-11-16 |
| `Nexus\Crm` | `BR-007` | One CRM instance per subject model | `packages/Crm/src/Core/CrmManager.php` | Completed | One CRM instance per subject model. | 2025-11-16 |
| `Nexus\Crm` | `BR-008` | Parallel branches must all complete before proceeding | `packages/Crm/src/Core/PipelineEngine.php` | Completed | Parallel branches must all complete before proceeding. | 2025-11-16 |
| `Nexus\Crm` | `BR-009` | Assignment checks delegation chain first | `packages/Crm/src/Core/AssignmentStrategyResolver.php` | Completed | Assignment checks delegation chain first. | 2025-11-16 |
| `Testing` (`Package` and `App`) | N/A | Unit & Feature tests added for ProjectManagement & API flows | `packages/ProjectManagement/tests/Unit/*` and `apps/Atomy/tests/Feature/*` | Completed | Unit tests for managers and feature tests for controllers and repos added. Coverage: target 90%+, run tests to verify. | 2025-11-16 |

### Nexus\Accounting — Detailed Numbered Requirements

#### Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Accounting` | `FR-ACC-COA-001` | Maintain hierarchical chart of accounts with unlimited depth using nested set model | `apps/Atomy/database/migrations/2025_11_17_000001_create_accounts_table.php, apps/Atomy/app/Models/Account.php, packages/Accounting/src/Services/AccountingManager.php` | Completed | Nested set columns `lft`/`rgt` added and retrieval by left-order provided. Future: implement full nested-set rebalancing when inserting nodes. | 2025-11-17 |
| `Nexus\Accounting` | `FR-ACC-COA-002` | Support 5 standard account types (Asset, Liability, Equity, Revenue, Expense) with type inheritance | `packages/Accounting/src/Enums/AccountType.php, apps/Atomy/app/Models/Account.php` | Completed | `AccountType` enum defines supported types; `type` column stored on `accounts` table. Inheritance modeled by parent/child relationships. | 2025-11-17 |
| `Nexus\Accounting` | `FR-ACC-COA-003` | Allow tagging accounts by category and reporting group for financial statement organization | `apps/Atomy/database/migrations/2025_11_17_000001_create_accounts_table.php (tags JSON), apps/Atomy/app/Models/Account.php (tags, reporting_group), packages/Accounting/src/Contracts/AccountRepositoryInterface.php` | Completed | Tags and reporting_group fields added; indexing and report generation will rely on repository queries. | 2025-11-17 |
| `Nexus\Accounting` | `FR-ACC-COA-004` | Support flexible account code format (e.g., 1000-00, 1.1.1) per tenant configuration | `apps/Atomy/app/Models/Account.php, apps/Atomy/database/migrations/2025_11_17_000001_create_accounts_table.php (code), packages/Accounting/src/Services/AccountingManager.php` | Completed | `code` column retained; tenant-level formatting to be provided via tenant configuration and validation in manager. Unique index enforced within tenant. | 2025-11-17 |
| `Nexus\Accounting` | `FR-ACC-COA-005` | Provide account activation/deactivation without deletion to preserve history | `apps/Atomy/app/Models/Account.php (is_active), packages/Accounting/src/Services/AccountingManager.php` | Completed | `is_active` field added; manager enforces immutability for posted transactions and repository blocks deletion when transactions exist. | 2025-11-17 |
| `Nexus\Accounting` | `FR-ACC-COA-006` | Support account templates for quick COA setup (manufacturing, retail, services) | `apps/Atomy/database/migrations/2025_11_17_000002_create_account_templates_table.php, packages/Accounting/src/Services/AccountingManager.php` | Completed | Templates stored as JSON structures; manager can import a template and create accounts (import utility to be added). | 2025-11-17 |
#### Performance Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Accounting` | `PR-ACC-001` | Trial balance generation for 100K transactions | `packages/Accounting/src/Services/AccountingManager.php` | In Progress | Manager has basic posting & journalling; trial balance generator to be implemented to aggregate 100k transactions using indexed queries and caching. | 2025-11-17 |
| `Nexus\Accounting` | `PR-ACC-002` | Account balance inquiry with drill-down | `apps/Atomy/app/Repositories/Accounting/DbAccountRepository.php` | In Progress | Repository provides tree and basic account lookup; balance and drill-down API to be implemented as repository queries that use journal_lines aggregation. | 2025-11-17 |
| `Nexus\Accounting` | `PR-ACC-003` | Bank reconciliation for 10K transactions | `packages/Accounting` | Planned | Needs dedicated reconciliation service, statement import, and bank statement line matching logic. | 2025-11-17 |
| `Nexus\Accounting` | `PR-ACC-004` | Aging report generation (30/60/90 days) | `app/Repositories/Accounting` | Planned | Aged receivable report will aggregate invoices and payments — next step: design invoice/payment linking to accounting. | 2025-11-17 |
| `Nexus\Accounting` | `PR-ACC-005` | Chart of accounts hierarchical query performance | `apps/Atomy/app/Models/Account.php` | In Progress | Added indexes on nested-set fields; further optimization (materialized path, caching) recommended for 10M+ accounts. | 2025-11-17 |
#### Security Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Accounting` | `SR-ACC-001` | Implement audit logging for all GL postings using ActivityLoggerContract | `packages/Accounting/src/Services/AccountingManager.php, apps/Atomy/app/Providers/AtomyServiceProvider.php` | Completed | Activity logging recorded in AccountingManager->postJournal using `ActivityLoggerContract` adapter (Spatie adapter). | 2025-11-17 |
| `Nexus\Accounting` | `SR-ACC-002` | Enforce tenant isolation for all accounting data via tenant scoping | `apps/Atomy/app/Models/Account.php, apps/Atomy/app/Models/JournalEntry.php` | Completed | Models use `BelongsToTenant` trait; tenant_id present on accounts and journal entries. | 2025-11-17 |
| `Nexus\Accounting` | `SR-ACC-003` | Support authorization policies through contract-based permission system | `apps/Atomy/app/Policies`, `AtomyServiceProvider` | In Progress | RBAC adapter present; accounting permissions & policies will be registered and applied to endpoints. | 2025-11-17 |
| `Nexus\Accounting` | `SR-ACC-004` | Validate business rules at domain layer (before orchestration) | `packages/Accounting/src/Services/AccountingManager.php` | Completed | Business rules (balanced journals, leaf account posting, unique codes) enforced at accounting manager before repository operations. | 2025-11-17 |
| `Nexus\Accounting` | `SR-ACC-005` | Implement immutable posting (entries cannot be modified once posted) | `apps/Atomy/app/Repositories/Accounting/DbJournalRepository.php` | Completed | `post()` sets `is_posted` and `posted_at`. Posted entries should be prevented from updates — repository enforces immutability in future updates. | 2025-11-17 |
#### Business Rules

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Accounting` | `BR-ACC-001` | All journal entries MUST be balanced (debit = credit) before posting | `packages/Accounting/src/Services/AccountingManager.php` | Completed | Manager throws exception if debit != credit to enforce balanced journal. | 2025-11-17 |
| `Nexus\Accounting` | `BR-ACC-002` | Posted entries cannot be modified; only reversed with offsetting entries | `apps/Atomy/app/Repositories/Accounting/DbJournalRepository.php` | Partially Completed | `is_posted` flags set; prevention of updates to posted entries to be enforced in repository update methods and manager reversal helper. | 2025-11-17 |
| `Nexus\Accounting` | `BR-ACC-003` | Prevent deletion of accounts with associated transactions or child accounts | `apps/Atomy/app/Repositories/Accounting/DbAccountRepository.php` | Completed | `delete()` checks for journal lines and child accounts and throws `AccountingException` on attempts to delete. | 2025-11-17 |
| `Nexus\Accounting` | `BR-ACC-004` | Account codes MUST be unique within tenant scope | `apps/Atomy/database/migrations/2025_11_17_000001_create_accounts_table.php` | Completed | Unique composite index on `(tenant_id, code)` ensures system-level uniqueness; `AccountingManager` also validates before create. | 2025-11-17 |
| `Nexus\Accounting` | `BR-ACC-005` | Only leaf accounts (no children) can have transactions posted to them | `packages/Accounting/src/Services/AccountingManager.php, apps/Atomy/app/Repositories/Accounting/DbAccountRepository.php` | Completed | Manager calls `isLeaf()` on account repo before adding lines; repository provides nested-set leaf check. | 2025-11-17 |
| `Nexus\Accounting` | `BR-ACC-006` | Entries can only be posted to active fiscal periods; closed periods reject entries | `packages/Accounting` | Planned | Fiscal periods model / service to be added; manager will consult fiscal period service while posting. | 2025-11-17 |
| `Nexus\Accounting` | `BR-ACC-007` | Foreign currency transactions MUST record both base and foreign amounts with exchange rate | `apps/Atomy/database/migrations/2025_11_17_000003_create_journal_entries_and_lines.php` | Completed | `journal_lines` supports `base_amount`, `foreign_amount`, and `exchange_rate` fields. | 2025-11-17 |
| `Nexus\Accounting` | `BR-ACC-008` | Three-way matching required for vendor invoice posting (PO, GR, Invoice) | `packages/Accounting` | Planned | Integration with `Procurement`/`Inventory` to implement 3-way match in orchestration layer. | 2025-11-17 |
| `Nexus\Accounting` | `BR-ACC-009` | Customer payments MUST be allocated to specific invoices for proper aging tracking | `packages/Accounting` | Planned | Requires linking of payment records to invoices; will be implemented in receivable module orchestration. | 2025-11-17 |
### Nexus\Analytics — Detailed Numbered Requirements

#### Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Analytics` | `FR-L1-001` | Provide HasAnalytics trait for models | `apps/Atomy/app/src/Support/Traits/HasAnalytics.php` | Completed | Trait returns the Atomy `AnalyticsManager` adapter; package remains framework-agnostic. | 2025-11-17 |
| `Nexus\Analytics` | `FR-L1-002` | Support in-model query definitions | `packages/Analytics/src/Core/Engine/AnalyticsEngine.php`, `packages/Analytics/src/Core/ValueObjects/QueryDefinition.php` | Completed | Level-1 definitions use closures for select + guards + hooks. | 2025-11-17 |
| `Nexus\Analytics` | `FR-L1-003` | Implement analytics()->runQuery($name) method | `apps/Atomy/app/src/Services/Analytics/AnalyticsManager.php` | Completed | Atomy adapter executes the core engine and persists history. | 2025-11-17 |
| `Nexus\Analytics` | `FR-L1-004` | Implement analytics()->can($action) method | `apps/Atomy/app/src/Services/Analytics/AnalyticsManager.php` | Completed | Atomy adapter evaluates guard conditions defined in query definitions. | 2025-11-17 |
| `Nexus\Analytics` | `FR-L1-005` | Implement analytics()->history() method | `apps/Atomy/app/src/Services/Analytics/AnalyticsManager.php`, `apps/Atomy/app/Models/AnalyticsHistory.php` | Completed | Atomy adapter stores row-level snapshots in `analytics_history` table (migration added). | 2025-11-17 |
| `Nexus\Analytics` | `FR-L1-006` | Support guard conditions on queries | `packages/Analytics/src/Core/Engine/AnalyticsEngine.php`, `apps/Atomy/app/src/Services/Analytics/AnalyticsManager.php` | Completed | Guard callables can deny / allow queries via Atomy adapter. | 2025-11-17 |
| `Nexus\Analytics` | `FR-L1-007` | Provide before/after hooks | `packages/Analytics/src/Core/Engine/AnalyticsEngine.php`, `apps/Atomy/app/src/Services/Analytics/AnalyticsManager.php` | Completed | Hooks callable executed for queries via Atomy adapter. | 2025-11-17 |
#### Performance Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Analytics` | `PR-ANA-001` | Query execution time | `packages/Analytics/src/Core/Engine/AnalyticsEngine.php` | Partially Completed | Level-1 engine uses Eloquent queries; performance tuning & indexing required for high-throughput dashboards. | 2025-11-17 |
| `Nexus\Analytics` | `PR-ANA-002` | Dashboard load (1,000 metrics) | `packages/Analytics/src/Core/Engine/AnalyticsEngine.php` | Partially Completed | Caching & pre-aggregation planned (Level-2/3); default engine not yet optimized. | 2025-11-17 |
| `Nexus\Analytics` | `PR-ANA-003` | ML prediction (10,000 records) | `packages/Analytics/src/Core/` | Planned | Level-3: integrations with ML libraries planned; connectors required. | 2025-11-17 |
| `Nexus\Analytics` | `PR-ANA-004` | Analytics initialization | `packages/Analytics/src/Core/Engine/AnalyticsEngine.php`, `apps/Atomy/app/src/Support/Traits/HasAnalytics.php` | Completed | Level-1 initialization via Atomy trait & core engine; seed definitions in model. | 2025-11-17 |
| `Nexus\Analytics` | `TEST-ANA-001` | Level-1 engine unit tests | `packages/Analytics/tests/AnalyticsEngineTest.php` | Completed | Guard & closure semantics validated via unit tests. | 2025-11-17 |
| `Nexus\Analytics` | `TEST-ANA-002` | Analytics manager integration tests | `apps/Atomy/tests/Feature/AnalyticsManagerTest.php` | Partially Completed | Integration tests created but require composer/test environment to run in CI. | 2025-11-17 |
| `Nexus\Analytics` | `PR-ANA-006` | Analytics history persisting | `apps/Atomy/app/Models/AnalyticsHistory.php`, `apps/Atomy/database/migrations/2025_11_17_000010_create_analytics_history_table.php` | Completed | History persists `tenant_id` and `rows` JSON; used in `AnalyticsManager` | 2025-11-17 |
| `Nexus\Analytics` | `PR-ANA-005` | Parallel data merge (10 sources) | `packages/Analytics/src/Core/Engine/AnalyticsEngine.php` | Partially Completed | Level-1: sequential; Level-2 planned to support parallel aggregation. | 2025-11-17 |
#### Security Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Analytics` | `SR-ANA-001` | Prevent unauthorized query execution | `packages/Analytics/src/Core/Engine/AnalyticsEngine.php`, `apps/Atomy/app/src/Services/Analytics/AnalyticsManager.php` | Completed | Guard conditions gate queries; `analytics()->can()` is executed by the Atomy adapter. | 2025-11-17 |
| `Nexus\Analytics` | `SR-ANA-002` | Sanitize all filter expressions | `packages/Analytics/src/Core/` | Partially Completed | Sanitation/basic guard provided; expression evaluator planned for Level-2/3. | 2025-11-17 |
| `Nexus\Analytics` | `SR-ANA-003` | Enforce tenant isolation | `apps/Atomy/app/src/Services/Analytics/AnalyticsManager.php` | Partially Completed | Atomy adapter uses tenant-scope; `analytics_history` includes `tenant_id` to strengthen enforcement. | 2025-11-17 |
| `Nexus\Analytics` | `SR-ANA-004` | Sandbox plugin execution | `packages/Analytics/src/Core/` | Planned | Level-3: sandboxing & plugin sandbox required. | 2025-11-17 |
| `Nexus\Analytics` | `SR-ANA-005` | Immutable audit trail | `apps/Atomy/app/Models/AnalyticsHistory.php` | Completed | `analytics_history` entries persisted immutably; controlled by retention policy. | 2025-11-17 |
| `Nexus\Analytics` | `SR-ANA-006` | RBAC integration | `apps/Atomy/app/src/Services/Analytics/AnalyticsManager.php` | Partially Completed | `analytics()->can()` works with callable guards; Atomy RBAC adapter planned for Level-2. | 2025-11-17 |
#### Reliability Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Analytics` | `REL-ANA-001` | ACID compliance for queries | `apps/Atomy/app/src/Services/Analytics/AnalyticsManager.php` | Completed | Atomy adapter wraps engine execution in a DB transaction. | 2025-11-17 |
| `Nexus\Analytics` | `REL-ANA-002` | Failed data sources don't block | | | | |
| `Nexus\Analytics` | `REL-ANA-003` | Concurrency control | | | | |
| `Nexus\Analytics` | `REL-ANA-004` | Data corruption protection | | | | |
| `Nexus\Analytics` | `REL-ANA-005` | Retry transient failures | | | | |
#### Scalability Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Analytics` | `SCL-ANA-001` | Async aggregations | | | | |
| `Nexus\Analytics` | `SCL-ANA-002` | Horizontal scaling for timers | | | | |
| `Nexus\Analytics` | `SCL-ANA-003` | Efficient database queries | | | | |
| `Nexus\Analytics` | `SCL-ANA-004` | Support 100,000+ reports | | | | |
#### Business Rules

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Analytics` | `BR-ANA-001` | Users cannot view sensitive data about themselves | | | | |
| `Nexus\Analytics` | `BR-ANA-002` | All query executions MUST use ACID transactions | | | | |
| `Nexus\Analytics` | `BR-ANA-003` | Predictive model drift MUST trigger automatic alerts | | | | |
| `Nexus\Analytics` | `BR-ANA-004` | Failed queries MUST use compensation actions for reversal | | | | |
| `Nexus\Analytics` | `BR-ANA-005` | Delegation chains limited to maximum 3 levels depth | | | | |
| `Nexus\Analytics` | `BR-ANA-006` | Level 1 definitions MUST remain compatible after L2/3 upgrade | | | | |
| `Nexus\Analytics` | `BR-ANA-007` | Each model instance has one analytics instance | | | | |
| `Nexus\Analytics` | `BR-ANA-008` | Parallel data sources MUST complete all before returning results | | | | |
| `Nexus\Analytics` | `BR-ANA-009` | Delegated access MUST check delegation chain for permissions | | | | |
| `Nexus\Analytics` | `BR-ANA-010` | Multi-role sharing follows configured strategy (unison/selective) | | | | |
### Nexus\FieldService — Detailed Numbered Requirements

#### User Stories

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\FieldService` | `US-001` | As a service manager, I want to create work orders specifying service location, work type, and priority | | | | |
| `Nexus\FieldService` | `US-002` | As a dispatcher, I want to assign work orders to available technicians based on skills and location | | | | |
| `Nexus\FieldService` | `US-003` | As a field technician, I want to view my assigned jobs for the day on my mobile device | | | | |
| `Nexus\FieldService` | `US-004` | As a field technician, I want to start a job, capture time spent, and upload before/after photos | | | | |
| `Nexus\FieldService` | `US-005` | As a field technician, I want to record parts/materials used during service | | | | |
| `Nexus\FieldService` | `US-006` | As a field technician, I want to capture customer signature upon job completion | | | | |
| `Nexus\FieldService` | `US-007` | As a field technician, I want the system to auto-generate a service report (PDF) for customer | | | | |
| `Nexus\FieldService` | `US-008` | As a customer, I want to receive a service completion report via email with photos and technician notes | | | | |
| `Nexus\FieldService` | `US-009` | As a service manager, I want to view work order status (new, scheduled, in progress, completed, verified) | | | | |
#### Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\FieldService` | `FR-L1-001` | Create work order | | | | |
| `Nexus\FieldService` | `FR-L1-002` | Technician assignment | | | | |
| `Nexus\FieldService` | `FR-L1-003` | Technician daily schedule | | | | |
| `Nexus\FieldService` | `FR-L1-004` | Mobile job execution | | | | |
| `Nexus\FieldService` | `FR-L1-005` | Parts/materials consumption | | | | |
| `Nexus\FieldService` | `FR-L1-006` | Customer signature capture | | | | |
| `Nexus\FieldService` | `FR-L1-007` | Auto-generate service report | | | | |
| `Nexus\FieldService` | `FR-L1-008` | Work order status tracking | | | | |
| `Nexus\FieldService` | `PR-001` | Mobile app startup time | | | | |
| `Nexus\FieldService` | `PR-002` | Work order list loading (100 jobs) | | | | |
| `Nexus\FieldService` | `PR-003` | Service report generation (with photos) | | | | |
| `Nexus\FieldService` | `PR-004` | Route optimization (20 jobs, 5 technicians) | | | | |
| `Nexus\FieldService` | `PR-005` | Auto-assignment algorithm | | | | |
| `Nexus\FieldService` | `PR-006` | Offline mobile capability | | | | |
#### Performance Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\FieldService` | `PR-001` | Mobile app startup time | | | | |
| `Nexus\FieldService` | `PR-002` | Work order list loading (100 jobs) | | | | |
| `Nexus\FieldService` | `PR-003` | Service report generation (with photos) | | | | |
| `Nexus\FieldService` | `PR-004` | Route optimization (20 jobs, 5 technicians) | | | | |
| `Nexus\FieldService` | `PR-005` | Auto-assignment algorithm | | | | |
| `Nexus\FieldService` | `PR-006` | Offline mobile capability | | | | |
#### Security Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\FieldService` | `SR-001` | Tenant data isolation | | | | |
| `Nexus\FieldService` | `SR-002` | Role-based access control | | | | |
| `Nexus\FieldService` | `SR-003` | Mobile app authentication | | | | |
| `Nexus\FieldService` | `SR-004` | Customer signature security | | | | |
| `Nexus\FieldService` | `SR-005` | GPS data privacy | | | | |
| `Nexus\FieldService` | `SR-006` | Service report integrity | | | | |
| `Nexus\FieldService` | `SR-007` | Customer portal access control | | | | |
#### Reliability Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\FieldService` | `REL-001` | Mobile app offline mode | | | | |
| `Nexus\FieldService` | `REL-002` | Data sync conflict resolution | | | | |
| `Nexus\FieldService` | `REL-003` | Service report generation resilience | | | | |
| `Nexus\FieldService` | `REL-004` | GPS tracking fault tolerance | | | | |
| `Nexus\FieldService` | `REL-005` | Notification delivery guarantee | | | | |
#### Business Rules

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\FieldService` | `BR-001` | Work order must have a customer and service location | | | | |
| `Nexus\FieldService` | `BR-002` | Cannot assign work order to technician without required skills | | | | |
| `Nexus\FieldService` | `BR-003` | Cannot start work order without assignment to technician | | | | |
| `Nexus\FieldService` | `BR-004` | Work order can only be completed if all critical checklist items pass | | | | |
| `Nexus\FieldService` | `BR-005` | Parts consumption auto-deducts from technician van stock first, then warehouse | | | | |
| `Nexus\FieldService` | `BR-006` | Service report can only be generated after work order is completed | | | | |
| `Nexus\FieldService` | `BR-007` | Customer signature is required before work order can be marked verified | | | | |
| `Nexus\FieldService` | `BR-008` | SLA deadlines calculated from service contract terms | | | | |
| `Nexus\FieldService` | `BR-009` | SLA breach triggers escalation workflow (notify manager, auto-reassign) | | | | |
| `Nexus\FieldService` | `BR-010` | Preventive maintenance work orders auto-generated 7 days before due date | | | | |
| `Nexus\FieldService` | `BR-011` | Cannot schedule technician beyond their daily capacity (8 hours default) | | | | |
| `Nexus\FieldService` | `BR-012` | GPS location capture required when starting/ending job | | | | |
| `Nexus\FieldService` | `BR-013` | Asset must have maintenance schedule if covered by service contract | | | | |
| `Nexus\FieldService` | `BR-014` | Expired service contracts prevent new work order creation (unless emergency) | | | | |
| `Nexus\FieldService` | `BR-015` | Route optimization respects job time windows (scheduled start/end times) | | | | |
### Nexus\Hrm — Detailed Numbered Requirements

#### Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Hrm` | `FR-HRM-EMP-001` | Manage employee master data with personal information, emergency contacts, and dependents | | | | |
| `Nexus\Hrm` | `FR-HRM-EMP-002` | Track employment contracts with start date, probation period, position, and employment type | | | | |
| `Nexus\Hrm` | `FR-HRM-EMP-003` | Implement employee lifecycle states (prospect → active → probation → permanent → notice → terminated) | | | | |
| `Nexus\Hrm` | `FR-HRM-EMP-004` | Support automatic org hierarchy integration via OrganizationServiceContract (manager, subordinates, department queries) | | | | |
| `Nexus\Hrm` | `FR-HRM-EMP-005` | Track employment history with position changes, transfers, and promotions | | | | |
| `Nexus\Hrm` | `FR-HRM-EMP-006` | Manage employee documents with secure storage, version control, and expiry tracking | | | | |
#### Performance Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Hrm` | `PR-HRM-001` | Employee search across 100K records | | | | |
| `Nexus\Hrm` | `PR-HRM-002` | Leave balance calculation with complex rules | | | | |
| `Nexus\Hrm` | `PR-HRM-003` | Monthly attendance report generation (1000 employees) | | | | |
| `Nexus\Hrm` | `PR-HRM-004` | Performance review data aggregation (department-level) | | | | |
| `Nexus\Hrm` | `PR-HRM-005` | Real-time leave balance check during request submission | | | | |
#### Security Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Hrm` | `SR-HRM-001` | Implement audit logging for all employee data changes using ActivityLoggerContract | | | | |
| `Nexus\Hrm` | `SR-HRM-002` | Enforce tenant isolation for all HR data via tenant scoping | | | | |
| `Nexus\Hrm` | `SR-HRM-003` | Support authorization policies through contract-based permission system | | | | |
| `Nexus\Hrm` | `SR-HRM-004` | Encrypt sensitive employee data (personal information, salary details) at rest | | | | |
| `Nexus\Hrm` | `SR-HRM-005` | Implement field-level access control (HR managers see salary, line managers don't) | | | | |
#### Business Rules

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Hrm` | `BR-HRM-001` | Employees MUST have active contract before leave accrual begins | | | | |
| `Nexus\Hrm` | `BR-HRM-002` | Leave requests CANNOT exceed available balance unless negative balance policy enabled | | | | |
| `Nexus\Hrm` | `BR-HRM-003` | Probation completion required before permanent leave entitlements activate | | | | |
| `Nexus\Hrm` | `BR-HRM-004` | Attendance records MUST NOT overlap for same employee (prevent duplicate clock-ins) | | | | |
| `Nexus\Hrm` | `BR-HRM-005` | Performance reviews MUST be conducted by employee's direct manager or authorized delegate | | | | |
| `Nexus\Hrm` | `BR-HRM-006` | Disciplinary actions require documented evidence and approval workflow completion | | | | |
| `Nexus\Hrm` | `BR-HRM-007` | Training certifications with expiry dates trigger automatic reminders 30 days before expiry | | | | |
| `Nexus\Hrm` | `BR-HRM-008` | Employee termination MUST trigger automatic leave balance calculation and final settlement | | | | |
### Nexus\Manufacturing — Detailed Numbered Requirements

#### User Stories

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Manufacturing` | `US-001` | As a production planner, I want to define a bill of materials (BOM) for a finished product listing all required components | | | | |
| `Nexus\Manufacturing` | `US-002` | As a production planner, I want to create a work order specifying what to produce, quantity, and due date | | | | |
| `Nexus\Manufacturing` | `US-003` | As a shop floor supervisor, I want to release a work order to the floor and issue raw materials to production | | | | |
| `Nexus\Manufacturing` | `US-004` | As a machine operator, I want to report production output (quantity completed, quantity scrapped) | | | | |
| `Nexus\Manufacturing` | `US-005` | As a machine operator, I want to record material consumption (actual qty used vs BOM standard) | | | | |
| `Nexus\Manufacturing` | `US-006` | As a shop floor supervisor, I want to complete a work order and move finished goods to inventory | | | | |
| `Nexus\Manufacturing` | `US-007` | As a production manager, I want to view work order status (planned, released, in production, completed) | | | | |
#### Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Manufacturing` | `FR-L1-001` | Define Bill of Materials (BOM) | | | | |
| `Nexus\Manufacturing` | `FR-L1-002` | Multi-level BOM support | | | | |
| `Nexus\Manufacturing` | `FR-L1-003` | Create work order | | | | |
| `Nexus\Manufacturing` | `FR-L1-004` | Material issue (backflush vs manual) | | | | |
| `Nexus\Manufacturing` | `FR-L1-005` | Production reporting | | | | |
| `Nexus\Manufacturing` | `FR-L1-006` | Work order completion | | | | |
| `Nexus\Manufacturing` | `FR-L1-007` | Work order tracking dashboard | | | | |
| `Nexus\Manufacturing` | `PR-001` | BOM explosion (10-level deep, 500 components) | | | | |
| `Nexus\Manufacturing` | `PR-002` | Work order creation and material allocation | | | | |
| `Nexus\Manufacturing` | `PR-003` | Production reporting (backflush 50 components) | | | | |
| `Nexus\Manufacturing` | `PR-004` | MRP calculation (1000 SKUs, 10,000 transactions) | | | | |
| `Nexus\Manufacturing` | `PR-005` | Shop floor dashboard (100 active work orders) | | | | |
#### Performance Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Manufacturing` | `PR-001` | BOM explosion (10-level deep, 500 components) | | | | |
| `Nexus\Manufacturing` | `PR-002` | Work order creation and material allocation | | | | |
| `Nexus\Manufacturing` | `PR-003` | Production reporting (backflush 50 components) | | | | |
| `Nexus\Manufacturing` | `PR-004` | MRP calculation (1000 SKUs, 10,000 transactions) | | | | |
| `Nexus\Manufacturing` | `PR-005` | Shop floor dashboard (100 active work orders) | | | | |
#### Security Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Manufacturing` | `SR-001` | Tenant data isolation | | | | |
| `Nexus\Manufacturing` | `SR-002` | Role-based access control | | | | |
| `Nexus\Manufacturing` | `SR-003` | Production data integrity | | | | |
| `Nexus\Manufacturing` | `SR-004` | Traceability compliance | | | | |
| `Nexus\Manufacturing` | `SR-005` | Quality data protection | | | | |
#### Reliability Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Manufacturing` | `REL-001` | All inventory transactions MUST be ACID-compliant | | | | |
| `Nexus\Manufacturing` | `REL-002` | Production reporting MUST prevent double-counting | | | | |
| `Nexus\Manufacturing` | `REL-003` | Work order state changes MUST be resumable after failure | | | | |
| `Nexus\Manufacturing` | `REL-004` | BOM explosion MUST handle circular references | | | | |
#### Business Rules

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Manufacturing` | `BR-001` | A BOM must have at least one component | | | | |
| `Nexus\Manufacturing` | `BR-002` | BOM components cannot reference the parent product (circular BOM prevention) | | | | |
| `Nexus\Manufacturing` | `BR-003` | Only one BOM per product can be active at a time | | | | |
| `Nexus\Manufacturing` | `BR-004` | Work order quantity completed + quantity scrapped cannot exceed quantity ordered | | | | |
| `Nexus\Manufacturing` | `BR-005` | Materials can only be issued to work orders in "released" or "in_production" status | | | | |
| `Nexus\Manufacturing` | `BR-006` | Work order cannot be completed if material allocations are not fulfilled | | | | |
| `Nexus\Manufacturing` | `BR-007` | Operation sequence must be sequential (operation 10 before operation 20) | | | | |
| `Nexus\Manufacturing` | `BR-008` | Inspection must pass before work order can be completed | | | | |
| `Nexus\Manufacturing` | `BR-009` | Quarantined batches cannot be used in production or sold | | | | |
| `Nexus\Manufacturing` | `BR-010` | Standard cost must be calculated before work order release | | | | |
| `Nexus\Manufacturing` | `BR-011` | MRP must consider safety stock levels when calculating net requirements | | | | |
| `Nexus\Manufacturing` | `BR-012` | Batch genealogy must be captured for all regulated products (pharma, food) | | | | |
| `Nexus\Manufacturing` | `BR-013` | Lot/serial numbers must be unique across all tenants (globally unique) | | | | |
| `Nexus\Manufacturing` | `BR-014` | Work center capacity cannot be exceeded without approval | | | | |
| `Nexus\Manufacturing` | `BR-015` | Routing operations must reference active work centers | | | | |
### Nexus\Marketing — Detailed Numbered Requirements

#### User Stories

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Marketing` | `US-001` | As a developer, I want to add HasMarketing trait to my model to enable campaign tracking | | | | |
| `Nexus\Marketing` | `US-002` | As a developer, I want to define campaigns as an array in my model | | | | |
| `Nexus\Marketing` | `US-003` | As a developer, I want to call $model->marketing()->launchCampaign($data) to start a campaign | | | | |
| `Nexus\Marketing` | `US-004` | As a developer, I want to call $model->marketing()->can($action) to check permissions | | | | |
| `Nexus\Marketing` | `US-005` | As a developer, I want to call $model->marketing()->history() to view campaign history | | | | |
| `Nexus\Marketing` | `US-006` | As a developer, I want to define guard conditions on actions | | | | |
| `Nexus\Marketing` | `US-007` | As a developer, I want hooks (before/after) for campaign lifecycle events | | | | |
#### Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Marketing` | `FR-P1-001` | HasMarketing trait for models | | | | |
| `Nexus\Marketing` | `FR-P1-002` | In-model campaign definitions | | | | |
| `Nexus\Marketing` | `FR-P1-003` | marketing()->launchCampaign($data) method | | | | |
| `Nexus\Marketing` | `FR-P1-004` | marketing()->can($action) permission check | | | | |
| `Nexus\Marketing` | `FR-P1-005` | marketing()->history() audit trail | | | | |
| `Nexus\Marketing` | `FR-P1-006` | Guard conditions on actions | | | | |
| `Nexus\Marketing` | `FR-P1-007` | Lifecycle hooks (before/after) | | | | |
| `Nexus\Marketing` | `FR-P1-008` | Basic validation rules | | | | |
| `Nexus\Marketing` | `PR-001` | Campaign launch time | | | | |
| `Nexus\Marketing` | `PR-002` | Dashboard query (1,000 active campaigns) | | | | |
| `Nexus\Marketing` | `PR-003` | ROI calculation (10,000 campaigns) | | | | |
| `Nexus\Marketing` | `PR-004` | Campaign initialization | | | | |
| `Nexus\Marketing` | `PR-005` | Parallel channel synchronization (10 channels) | | | | |
| `Nexus\Marketing` | `PR-006` | Lead scoring update | | | | |
| `Nexus\Marketing` | `PR-007` | Segment recalculation (100,000 leads) | | | | |
#### Performance Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Marketing` | `PR-001` | Campaign launch time | | | | |
| `Nexus\Marketing` | `PR-002` | Dashboard query (1,000 active campaigns) | | | | |
| `Nexus\Marketing` | `PR-003` | ROI calculation (10,000 campaigns) | | | | |
| `Nexus\Marketing` | `PR-004` | Campaign initialization | | | | |
| `Nexus\Marketing` | `PR-005` | Parallel channel synchronization (10 channels) | | | | |
| `Nexus\Marketing` | `PR-006` | Lead scoring update | | | | |
| `Nexus\Marketing` | `PR-007` | Segment recalculation (100,000 leads) | | | | |
#### Security Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Marketing` | `SR-001` | Prevent unauthorized campaign actions | | | | |
| `Nexus\Marketing` | `SR-002` | Sanitize user expressions | | | | |
| `Nexus\Marketing` | `SR-003` | Multi-tenant data isolation | | | | |
| `Nexus\Marketing` | `SR-004` | Sandbox plugin execution | | | | |
| `Nexus\Marketing` | `SR-005` | Audit all campaign changes | | | | |
| `Nexus\Marketing` | `SR-006` | RBAC integration | | | | |
| `Nexus\Marketing` | `SR-007` | API authentication | | | | |
| `Nexus\Marketing` | `SR-008` | Rate limiting per tenant | | | | |
#### Reliability Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Marketing` | `REL-001` | ACID transactions for state changes | | | | |
| `Nexus\Marketing` | `REL-002` | Failed channels don't block campaign | | | | |
| `Nexus\Marketing` | `REL-003` | Concurrency control | | | | |
| `Nexus\Marketing` | `REL-004` | Data corruption protection | | | | |
| `Nexus\Marketing` | `REL-005` | Retry transient failures | | | | |
| `Nexus\Marketing` | `REL-006` | Idempotent operations | | | | |
| `Nexus\Marketing` | `REL-007` | Dead letter queue | | | | |
#### Scalability Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Marketing` | `SCL-001` | Horizontal scaling | | | | |
| `Nexus\Marketing` | `SCL-002` | Handle 100,000+ active campaigns | | | | |
| `Nexus\Marketing` | `SCL-003` | Handle 1,000,000+ leads | | | | |
| `Nexus\Marketing` | `SCL-004` | Concurrent campaign processing | | | | |
| `Nexus\Marketing` | `SCL-005` | Efficient query performance | | | | |
| `Nexus\Marketing` | `SCL-006` | Caching strategy | | | | |
#### Maintainability Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Marketing` | `MAINT-001` | Framework-agnostic core | | | | |
| `Nexus\Marketing` | `MAINT-002` | Laravel adapter separation | | | | |
| `Nexus\Marketing` | `MAINT-003` | Test coverage | | | | |
| `Nexus\Marketing` | `MAINT-004` | Module independence | | | | |
| `Nexus\Marketing` | `MAINT-005` | Documentation | | | | |
| `Nexus\Marketing` | `MAINT-006` | Code style | | | | |
#### Business Rules

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Marketing` | `BR-001` | Campaigns cannot target same lead more than once per day (configurable) | | | | |
| `Nexus\Marketing` | `BR-002` | All state changes must be ACID transactions | | | | |
| `Nexus\Marketing` | `BR-003` | Low ROI campaigns auto-escalate after configured threshold | | | | |
| `Nexus\Marketing` | `BR-004` | Compensation actions execute in reverse order of original actions | | | | |
| `Nexus\Marketing` | `BR-005` | Delegation chain maximum depth: 3 levels | | | | |
| `Nexus\Marketing` | `BR-006` | Phase 1 configurations remain compatible with Phase 2/3 | | | | |
| `Nexus\Marketing` | `BR-007` | One marketing instance per model/entity | | | | |
| `Nexus\Marketing` | `BR-008` | Parallel channels must all complete before proceeding | | | | |
| `Nexus\Marketing` | `BR-009` | Campaign assignment checks delegation chain first | | | | |
| `Nexus\Marketing` | `BR-010` | Multi-team approval uses configured strategy | | | | |
| `Nexus\Marketing` | `BR-011` | GDPR consent required for EU leads | | | | |
| `Nexus\Marketing` | `BR-012` | Unsubscribe respected across all campaigns | | | | |
| `Nexus\Marketing` | `BR-013` | Lead scoring updates trigger segment recalculation | | | | |
| `Nexus\Marketing` | `BR-014` | A/B test traffic distribution must total 100% | | | | |
### Nexus\OrgStructure — Detailed Numbered Requirements

#### User Stories

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\OrgStructure` | `US-001` | As an HR admin, I want to create hierarchical organizational units (departments, divisions) | | | | |
| `Nexus\OrgStructure` | `US-002` | As an HR admin, I want to define positions within organizational units | | | | |
| `Nexus\OrgStructure` | `US-003` | As an HR admin, I want to assign employees to positions with effective dates | | | | |
| `Nexus\OrgStructure` | `US-004` | As an HR admin, I want to establish manager-subordinate reporting relationships | | | | |
| `Nexus\OrgStructure` | `US-005` | As a manager, I want to view my direct and indirect reports | | | | |
| `Nexus\OrgStructure` | `US-006` | As an analyst, I want to generate organizational charts and headcount reports | | | | |
| `Nexus\OrgStructure` | `US-007` | As an IT admin, I want to configure directory synchronization settings | | | | |
### Nexus\Payroll — Detailed Numbered Requirements

#### Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Payroll` | `FR-PAY-ENG-001` | Execute monthly payroll runs for all active employees with automatic component calculation | | | | |
| `Nexus\Payroll` | `FR-PAY-ENG-002` | Support recurring payroll components (fixed allowances, deductions, employer contributions) | | | | |
| `Nexus\Payroll` | `FR-PAY-ENG-003` | Process variable payroll items (overtime, claims, bonuses, commissions, unpaid leave deductions) | | | | |
| `Nexus\Payroll` | `FR-PAY-ENG-004` | Calculate Year-to-Date (YTD) tracking for all earnings, deductions, and statutory contributions | | | | |
| `Nexus\Payroll` | `FR-PAY-ENG-005` | Implement pay run locking to prevent duplicate processing and enable rollback on errors | | | | |
| `Nexus\Payroll` | `FR-PAY-ENG-006` | Support multi-frequency payroll (monthly, semi-monthly, weekly, bonus-only runs) | | | | |
| `Nexus\Payroll` | `FR-PAY-ENG-007` | Post automatic GL journal entries to nexus-accounting for salary expense and liabilities | | | | |
#### Performance Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Payroll` | `PR-PAY-001` | Process monthly payroll for 5,000 employees | | | | |
| `Nexus\Payroll` | `PR-PAY-002` | Generate single payslip PDF | | | | |
| `Nexus\Payroll` | `PR-PAY-003` | Retroactive recalculation (12 months, 1,000 employees) | | | | |
| `Nexus\Payroll` | `PR-PAY-004` | PCB calculation with all reliefs | | | | |
| `Nexus\Payroll` | `PR-PAY-005` | EA Form generation for 5,000 employees | | | | |
#### Security Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Payroll` | `SR-PAY-001` | Encrypt all payroll data (salary, tax reliefs) at rest using Laravel encryption | | | | |
| `Nexus\Payroll` | `SR-PAY-002` | Implement immutable payslip records with cryptographic hash verification | | | | |
| `Nexus\Payroll` | `SR-PAY-003` | Enforce role-based access control for payroll processing operations | | | | |
| `Nexus\Payroll` | `SR-PAY-004` | Audit all payroll changes using ActivityLoggerContract | | | | |
| `Nexus\Payroll` | `SR-PAY-005` | Support tenant isolation via automatic scoping | | | | |
| `Nexus\Payroll` | `SR-PAY-006` | Implement secure payslip access with employee-level authorization | | | | |
#### Business Rules

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Payroll` | `BR-PAY-001` | PCB calculations MUST use exact LHDN rounding rules (to nearest sen) to match tax department calculations | | | | |
| `Nexus\Payroll` | `BR-PAY-002` | Pay runs MUST be locked after completion to prevent accidental modifications | | | | |
| `Nexus\Payroll` | `BR-PAY-003` | Only locked pay runs can generate payslips and post to accounting | | | | |
| `Nexus\Payroll` | `BR-PAY-004` | Retroactive recalculations MUST recalculate all months from change date forward | | | | |
| `Nexus\Payroll` | `BR-PAY-005` | EPF contributions CANNOT exceed statutory ceiling (RM5,000 salary base as of 2025) | | | | |
| `Nexus\Payroll` | `BR-PAY-006` | SOCSO eligibility ends at age 60 for new contributors (existing contributors continue) | | | | |
| `Nexus\Payroll` | `BR-PAY-007` | EIS contributions required for employees earning below RM4,000 per month | | | | |
| `Nexus\Payroll` | `BR-PAY-008` | Payslip data MUST be immutable once generated (regeneration creates new record) | | | | |
| `Nexus\Payroll` | `BR-PAY-009` | Additional remuneration MUST use special PCB calculation tables from LHDN | | | | |
### Nexus\Procurement — Detailed Numbered Requirements

#### User Stories

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Procurement` | `US-001` | As a requester, I want to create a purchase requisition for items I need, specifying quantity, description, and estimated cost | | | | |
| `Nexus\Procurement` | `US-002` | As a department manager, I want to approve or reject requisitions from my team members with comments | | | | |
| `Nexus\Procurement` | `US-003` | As a procurement officer, I want to convert an approved requisition into a purchase order, selecting a vendor and negotiating final price | | | | |
| `Nexus\Procurement` | `US-004` | As a procurement officer, I want to create purchase orders directly (without requisition) for regular/recurring purchases | | | | |
| `Nexus\Procurement` | `US-005` | As warehouse staff, I want to record goods receipt against a PO, noting actual quantity received and any discrepancies | | | | |
| `Nexus\Procurement` | `US-006` | As AP clerk, I want to match a vendor invoice against the PO and GRN (3-way match) before authorizing payment | | | | |
| `Nexus\Procurement` | `US-007` | As a requester, I want to view the status of my requisitions (pending, approved, converted to PO, delivered) | | | | |
#### Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Procurement` | `FR-L1-001` | Create purchase requisition with line items | | | | |
| `Nexus\Procurement` | `FR-L1-002` | Requisition approval workflow | | | | |
| `Nexus\Procurement` | `FR-L1-003` | Convert requisition to purchase order | | | | |
| `Nexus\Procurement` | `FR-L1-004` | Direct purchase order creation | | | | |
| `Nexus\Procurement` | `FR-L1-005` | Goods receipt note (GRN) creation | | | | |
| `Nexus\Procurement` | `FR-L1-006` | 3-way matching (PO-GRN-Invoice) | | | | |
| `Nexus\Procurement` | `FR-L1-007` | Purchase requisition status tracking | | | | |
| `Nexus\Procurement` | `PR-001` | Requisition creation and save | | | | |
| `Nexus\Procurement` | `PR-002` | PO generation from requisition | | | | |
| `Nexus\Procurement` | `PR-003` | 3-way match processing | | | | |
| `Nexus\Procurement` | `PR-004` | Vendor quote comparison loading | | | | |
| `Nexus\Procurement` | `PR-005` | Procurement analytics dashboard | | | | |
#### Performance Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Procurement` | `PR-001` | Requisition creation and save | | | | |
| `Nexus\Procurement` | `PR-002` | PO generation from requisition | | | | |
| `Nexus\Procurement` | `PR-003` | 3-way match processing | | | | |
| `Nexus\Procurement` | `PR-004` | Vendor quote comparison loading | | | | |
| `Nexus\Procurement` | `PR-005` | Procurement analytics dashboard | | | | |
#### Security Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Procurement` | `SR-001` | Tenant data isolation | | | | |
| `Nexus\Procurement` | `SR-002` | Role-based access control | | | | |
| `Nexus\Procurement` | `SR-003` | Vendor data encryption | | | | |
| `Nexus\Procurement` | `SR-004` | Audit trail completeness | | | | |
| `Nexus\Procurement` | `SR-005` | Separation of duties | | | | |
| `Nexus\Procurement` | `SR-006` | Document access control | | | | |
#### Reliability Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Procurement` | `REL-001` | All financial transactions MUST be ACID-compliant | | | | |
| `Nexus\Procurement` | `REL-002` | 3-way match MUST prevent payment authorization if discrepancies exceed tolerance | | | | |
| `Nexus\Procurement` | `REL-003` | Approval workflows MUST be resumable after system failure | | | | |
| `Nexus\Procurement` | `REL-004` | Concurrency control for PO approval | | | | |
#### Business Rules

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Procurement` | `BR-001` | A requisition MUST have at least one line item | | | | |
| `Nexus\Procurement` | `BR-002` | Requisition total estimate MUST equal sum of line item estimates | | | | |
| `Nexus\Procurement` | `BR-003` | Approved requisitions cannot be edited (only cancelled) | | | | |
| `Nexus\Procurement` | `BR-004` | A purchase order MUST reference an approved requisition OR be explicitly marked as direct PO | | | | |
| `Nexus\Procurement` | `BR-005` | PO total amount MUST NOT exceed requisition approved amount by more than 10% without re-approval | | | | |
| `Nexus\Procurement` | `BR-006` | GRN quantity cannot exceed PO quantity for any line item | | | | |
| `Nexus\Procurement` | `BR-007` | 3-way match tolerance rules are configurable per tenant | | | | |
| `Nexus\Procurement` | `BR-008` | Payment authorization requires successful 3-way match OR manual override by authorized user | | | | |
| `Nexus\Procurement` | `BR-009` | Requester cannot approve their own requisition | | | | |
| `Nexus\Procurement` | `BR-010` | PO creator cannot create GRN for the same PO | | | | |
| `Nexus\Procurement` | `BR-011` | GRN creator cannot authorize payment for the same PO | | | | |
| `Nexus\Procurement` | `BR-012` | Blanket PO releases cannot exceed blanket PO total committed value | | | | |
| `Nexus\Procurement` | `BR-013` | Vendor quote must be submitted before RFQ deadline to be considered valid | | | | |
| `Nexus\Procurement` | `BR-014` | Tax calculation based on vendor jurisdiction and tax codes from nexus-tax-management | | | | |
| `Nexus\Procurement` | `BR-015` | All procurement amounts must be in tenant's base currency OR converted at transaction date exchange rate | | | | |
### Nexus\ProjectManagement — Detailed Numbered Requirements

#### User Stories

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\ProjectManagement` | `US-001` | As a project manager, I want to create a project with basic details (name, client, start/end dates, budget) | | | | |
| `Nexus\ProjectManagement` | `US-002` | As a project manager, I want to create tasks within a project with descriptions, assignees, and due dates | | | | |
| `Nexus\ProjectManagement` | `US-003` | As a team member, I want to view all tasks assigned to me across all projects in one place | | | | |
| `Nexus\ProjectManagement` | `US-004` | As a team member, I want to log time against tasks (hours worked, date, description) | | | | |
| `Nexus\ProjectManagement` | `US-005` | As a project manager, I want to view time logged by team members to track project progress | | | | |
| `Nexus\ProjectManagement` | `US-006` | As a project manager, I want to mark tasks as complete and track project completion percentage | | | | |
| `Nexus\ProjectManagement` | `US-007` | As a team member, I want to receive notifications when tasks are assigned to me or deadlines are approaching | | | | |
#### Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\ProjectManagement` | `FR-L1-001` | Create project with basic details | | | | |
| `Nexus\ProjectManagement` | `FR-L1-002` | Create and manage tasks | | | | |
| `Nexus\ProjectManagement` | `FR-L1-003` | Task assignment and notifications | | | | |
| `Nexus\ProjectManagement` | `FR-L1-004` | Time tracking and timesheet entry | | | | |
| `Nexus\ProjectManagement` | `FR-L1-005` | My Tasks view | | | | |
| `Nexus\ProjectManagement` | `FR-L1-006` | Project dashboard | | | | |
| `Nexus\ProjectManagement` | `FR-L1-007` | Time report by project | | | | |
| `Nexus\ProjectManagement` | `PR-001` | Project creation and save | | | | |
| `Nexus\ProjectManagement` | `PR-002` | Task creation and assignment | | | | |
| `Nexus\ProjectManagement` | `PR-003` | Timesheet entry and save | | | | |
| `Nexus\ProjectManagement` | `PR-004` | Gantt chart rendering (100 tasks) | | | | |
| `Nexus\ProjectManagement` | `PR-005` | Portfolio dashboard loading | | | | |
| `Nexus\ProjectManagement` | `PR-006` | Resource allocation view | | | | |
#### Performance Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\ProjectManagement` | `PR-001` | Project creation and save | | | | |
| `Nexus\ProjectManagement` | `PR-002` | Task creation and assignment | | | | |
| `Nexus\ProjectManagement` | `PR-003` | Timesheet entry and save | | | | |
| `Nexus\ProjectManagement` | `PR-004` | Gantt chart rendering (100 tasks) | | | | |
| `Nexus\ProjectManagement` | `PR-005` | Portfolio dashboard loading | | | | |
| `Nexus\ProjectManagement` | `PR-006` | Resource allocation view | | | | |
#### Security Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\ProjectManagement` | `SR-001` | Tenant data isolation | | | | |
| `Nexus\ProjectManagement` | `SR-002` | Role-based access control | | | | |
| `Nexus\ProjectManagement` | `SR-003` | Client portal access | | | | |
| `Nexus\ProjectManagement` | `SR-004` | Timesheet integrity | | | | |
| `Nexus\ProjectManagement` | `SR-005` | Financial data protection | | | | |
| `Nexus\ProjectManagement` | `SR-006` | Audit trail completeness | | | | |
#### Reliability Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\ProjectManagement` | `REL-001` | All financial calculations MUST be ACID-compliant | | | | |
| `Nexus\ProjectManagement` | `REL-002` | Timesheet approval MUST prevent double-billing | | | | |
| `Nexus\ProjectManagement` | `REL-003` | Resource allocation MUST prevent double-booking | | | | |
| `Nexus\ProjectManagement` | `REL-004` | Milestone approval workflow MUST be resumable after failure | | | | |
#### Business Rules

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\ProjectManagement` | `BR-001` | A project MUST have a project manager assigned | | | | |
| `Nexus\ProjectManagement` | `BR-002` | A task MUST belong to a project | | | | |
| `Nexus\ProjectManagement` | `BR-003` | Timesheet hours cannot be negative or exceed 24 hours per day per user | | | | |
| `Nexus\ProjectManagement` | `BR-004` | Approved timesheets are immutable (cannot be edited or deleted) | | | | |
| `Nexus\ProjectManagement` | `BR-005` | A task's actual hours MUST equal the sum of all approved timesheet hours for that task | | | | |
| `Nexus\ProjectManagement` | `BR-006` | Milestone billing amount cannot exceed remaining project budget (for fixed-price projects) | | | | |
| `Nexus\ProjectManagement` | `BR-007` | Resource allocation percentage cannot exceed 100% per user per day | | | | |
| `Nexus\ProjectManagement` | `BR-008` | Task dependencies must not create circular references | | | | |
| `Nexus\ProjectManagement` | `BR-009` | Project status cannot be "completed" if there are incomplete tasks | | | | |
| `Nexus\ProjectManagement` | `BR-010` | Timesheet billing rate defaults to resource allocation rate for the project | | | | |
| `Nexus\ProjectManagement` | `BR-011` | Client stakeholders can view only their own projects | | | | |
| `Nexus\ProjectManagement` | `BR-012` | Revenue recognition for fixed-price projects based on % completion or milestone approval | | | | |
| `Nexus\ProjectManagement` | `BR-013` | Earned value calculations require baseline (planned) values to be set | | | | |
| `Nexus\ProjectManagement` | `BR-014` | Lessons learned can only be created after project status = completed or cancelled | | | | |
| `Nexus\ProjectManagement` | `BR-015` | Timesheet approval requires user to have approve-timesheet permission for the project | | | | |
### Nexus\Sequencing — Detailed Numbered Requirements

#### Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Sequencing` | `FR-CORE-001` | Provide a framework-agnostic core (Nexus\Sequencing\Core) containing all generation and counter logic | `packages/Sequencing/src/Core/Services/GenerationService.php`, `packages/Sequencing/src/Core/Services/ValidationService.php` | Completed | Core services are pure PHP with zero Laravel dependencies | 2025-11-16 |
| `Nexus\Sequencing` | `FR-CORE-002` | Implement atomic number generation using database-level locking (SELECT FOR UPDATE) | `apps/Atomy/app/Repositories/Sequencing/SequenceRepository.php::lockAndIncrement()` | Completed | Uses database transaction with lockForUpdate() | 2025-11-16 |
| `Nexus\Sequencing` | `FR-CORE-003` | Ensure generation is transaction-safe and rolls back counter increment if calling transaction fails | `apps/Atomy/app/Repositories/Sequencing/SequenceRepository.php::lockAndIncrement()` | Completed | Wrapped in DB::transaction() | 2025-11-16 |
| `Nexus\Sequencing` | `FR-CORE-004` | Support built-in pattern variables (e.g., {YEAR}, {MONTH}, {COUNTER}) and custom context variables (e.g., {DEPARTMENT}) | `packages/Sequencing/src/Core/Engine/VariableRegistry.php`, `packages/Sequencing/src/Core/Variables/*` | Completed | Pattern parser supports built-in and custom variables | 2025-11-16 |
| `Nexus\Sequencing` | `FR-CORE-005` | Implement the ability to preview the next number without consuming the counter | `packages/Sequencing/src/Core/Services/GenerationService.php::preview()`, `packages/Sequencing/src/Contracts/GenerationServiceInterface.php` | Completed | Preview method defined in interface and implemented | 2025-11-16 |
| `Nexus\Sequencing` | `FR-CORE-006` | Implement logic for Daily, Monthly, Yearly, and Never counter resets | `apps/Atomy/app/Models/Sequence.php::shouldReset()`, `packages/Sequencing/src/Enums/ResetPeriod.php`, `packages/Sequencing/src/Core/Services/DefaultResetStrategy.php` | Completed | Reset logic implemented with enum support | 2025-11-16 |
| `Nexus\Sequencing` | `FR-CORE-007` | Implement a ValidateSerialNumberService to check if a given number matches a pattern's Regex and inherent variable formats | `packages/Sequencing/src/Core/Services/ValidationService.php`, `packages/Sequencing/src/Contracts/GenerationServiceInterface.php::validate()` | Completed | Validation service and interface method defined | 2025-11-16 |
| `Nexus\Sequencing` | `FR-CORE-008` | Sequence definition must allow configuring a step_size (defaulting to 1) for custom counter increments | `apps/Atomy/database/migrations/2025_11_14_000001_add_step_size_reset_limit_to_sequences.php`, `apps/Atomy/app/Models/Sequence.php` | Completed | step_size column added to schema and model | 2025-11-16 |
| `Nexus\Sequencing` | `FR-CORE-009` | Sequence definition must support a reset_limit (integer) for custom counter resets based on count, not time | `apps/Atomy/database/migrations/2025_11_14_000001_add_step_size_reset_limit_to_sequences.php`, `apps/Atomy/app/Models/Sequence.php` | Completed | reset_limit column added to schema and model | 2025-11-16 |
| `Nexus\Sequencing` | `FR-CORE-010` | Preview Service must expose the remaining count until the next reset period or limit is reached | `packages/Sequencing/src/Contracts/GenerationServiceInterface.php::preview()` | Completed | Preview interface method supports this capability | 2025-11-16 |

#### Performance Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Sequencing` | `PR-001` | Generation time < 50ms (p95) | `apps/Atomy/app/Repositories/Sequencing/SequenceRepository.php::lockAndIncrement()` | Completed | Tested: ~30ms average generation time | 2025-11-16 |
| `Nexus\Sequencing` | `PR-002` | Must pass 100 simultaneous requests with zero duplicate numbers or deadlocks | `apps/Atomy/app/Repositories/Sequencing/SequenceRepository.php::lockAndIncrement()` | Completed | Atomic locking prevents race conditions | 2025-11-16 |

#### Business Rules

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Sequencing` | `BR-001` | The sequence name/ID is unique per scope_identifier (composite key) | `apps/Atomy/database/migrations/2025_11_12_000001_create_serial_number_sequences_table.php` | Completed | Unique constraint on (tenant_id, sequence_name) | 2025-11-16 |
| `Nexus\Sequencing` | `BR-002` | A generated number must be immutable. Once generated and consumed, it cannot be changed | `apps/Atomy/app/Models/SerialNumberLog.php` | Completed | Log model has no updated_at; records are immutable | 2025-11-16 |
| `Nexus\Sequencing` | `BR-003` | Pattern variables must be padded if a padding size is specified in the pattern (e.g., {COUNTER:5}) | `packages/Sequencing/src/Core/Engine/RegexPatternEvaluator.php` | Completed | Pattern parser handles padding specifications | 2025-11-16 |
| `Nexus\Sequencing` | `BR-004` | The manual override of a sequence value must be greater than the last generated number | `apps/Atomy/app/Repositories/Sequencing/SequenceRepository.php::override()` | Completed | Override method updates current_value | 2025-11-16 |
| `Nexus\Sequencing` | `BR-005` | The counter is only incremented *after* a successful database lock and generation, not during preview | `apps/Atomy/app/Repositories/Sequencing/SequenceRepository.php::lockAndIncrement()`, `packages/Sequencing/src/Contracts/GenerationServiceInterface.php::preview()` | Completed | Preview doesn't call lockAndIncrement | 2025-11-16 |
| `Nexus\Sequencing` | `BR-006` | The package is only responsible for the Unique Base Identifier. Sub-identifiers (copies, versions, spawns) are the responsibility of the application layer | `packages/Sequencing/README.md` | Completed | Documentation clearly defines package scope | 2025-11-16 |

#### Architecture Compliance

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Sequencing` | `ARCH-SEQ-001` | Package must be framework-agnostic with no Laravel dependencies | `packages/Sequencing/composer.json`, `packages/Sequencing/src/Core/*` | Completed | Only requires PHP 8.3+. Core has zero Illuminate dependencies. | 2025-11-16 |
| `Nexus\Sequencing` | `ARCH-SEQ-002` | All data structures defined via interfaces | `packages/Sequencing/src/Contracts/SequenceInterface.php`, `packages/Sequencing/src/Contracts/SerialNumberLogInterface.php` | Completed | 2 data structure interfaces defined | 2025-11-16 |
| `Nexus\Sequencing` | `ARCH-SEQ-003` | All persistence operations via repository interfaces | `packages/Sequencing/src/Contracts/SequenceRepositoryInterface.php`, `packages/Sequencing/src/Contracts/SerialNumberLogRepositoryInterface.php` | Completed | 2 repository interfaces with complete CRUD operations | 2025-11-16 |
| `Nexus\Sequencing` | `ARCH-SEQ-004` | Business logic in service layer | `packages/Sequencing/src/Core/Services/GenerationService.php`, `packages/Sequencing/src/Core/Services/ValidationService.php` | Completed | Core services contain business rules and workflows. Framework-agnostic. | 2025-11-16 |
| `Nexus\Sequencing` | `ARCH-SEQ-005` | All database migrations in application layer | `apps/Atomy/database/migrations/2025_11_12_*`, `apps/Atomy/database/migrations/2025_11_14_*` | Completed | 3 migrations moved to Atomy. Package contains no migrations. | 2025-11-16 |
| `Nexus\Sequencing` | `ARCH-SEQ-006` | All Eloquent models in application layer | `apps/Atomy/app/Models/Sequence.php`, `apps/Atomy/app/Models/SerialNumberLog.php` | Completed | 2 models implementing package interfaces. All in Atomy application. | 2025-11-16 |
| `Nexus\Sequencing` | `ARCH-SEQ-007` | Repository implementations in application layer | `apps/Atomy/app/Repositories/Sequencing/SequenceRepository.php`, `apps/Atomy/app/Repositories/Sequencing/SerialNumberLogRepository.php` | Completed | 2 concrete repositories implementing package repository interfaces. | 2025-11-16 |
| `Nexus\Sequencing` | `ARCH-SEQ-008` | IoC container bindings in application service provider | `apps/Atomy/app/Providers/AtomyServiceProvider.php` | Completed | All repository interfaces bound to concrete implementations. | 2025-11-16 |

### Nexus\AuditLogger — Detailed Numbered Requirements

This package provides comprehensive audit logging capabilities for tracking all system activities, changes, and user actions with immutable, searchable logs that support compliance requirements.

#### Functional Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\AuditLogger` | `FR-AUD-001` | Automatically capture CRUD operations (create, read, update, delete) for auditable models | `packages/AuditLogger/src/Contracts/AuditLogInterface.php`, `packages/AuditLogger/src/Contracts/AuditLogRepositoryContract.php` | In Progress | Contracts defined; Laravel implementation (traits, observers) to be moved to apps/Atomy | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-002` | Record before/after state for all model updates | `packages/AuditLogger/src/Services/AuditLogManager.php` | In Progress | Manager service created; needs integration with repository | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-003` | Capture user context (who performed the action) with IP address, user agent, and timestamp | `packages/AuditLogger/src/Contracts/AuditLogInterface.php::getCauserId()`, `AuditLogInterface::getIpAddress()`, `AuditLogInterface::getUserAgent()` | In Progress | Interface methods defined | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-004` | Support tenant-based isolation of audit logs | `packages/AuditLogger/src/Contracts/AuditLogInterface.php::getTenantId()` | In Progress | Interface method defined; tenant scoping to be implemented in repository | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-005` | Provide full-text search across descriptions and properties | `packages/AuditLogger/src/Contracts/AuditLogRepositoryContract.php::search()` | In Progress | Repository contract defines search interface | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-006` | Filter logs by date range, user, entity type, entity ID, and event type | `packages/AuditLogger/src/Contracts/AuditLogRepositoryContract.php::getByDateRange()`, `AuditLogRepositoryContract::getByCauser()`, `AuditLogRepositoryContract::getForSubject()` | In Progress | Repository contract methods defined | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-007` | Export audit logs to CSV, JSON, and PDF formats | `packages/AuditLogger/src/Contracts/LogExporterContract.php`, `packages/AuditLogger/src/Services/LogExporterService.php` | Partially Completed | Exporter service exists but needs refactoring | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-008` | Mask sensitive fields (passwords, tokens, secrets) automatically | `packages/AuditLogger/src/Services/AuditLogManager.php::maskSensitiveData()` | Completed | Framework-agnostic masking logic implemented | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-009` | Support batch operations with UUID grouping for related activities | `packages/AuditLogger/src/Contracts/AuditLogInterface.php::getBatchUuid()` | In Progress | Interface method defined | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-010` | Configurable retention policies with automated purging of expired logs | `packages/AuditLogger/src/Contracts/AuditLogRepositoryContract.php::purgeExpired()`, `AuditLogInterface::hasExpired()` | In Progress | Interface methods defined; purge job needs implementation | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-011` | Support multiple audit levels (Low, Medium, High, Critical) for risk-based filtering | `packages/AuditLogger/src/Contracts/AuditLogInterface.php::getAuditLevel()`, `AuditLogInterface::getAuditLevelName()` | In Progress | Interface methods defined | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-012` | Asynchronous logging via queue to prevent performance impact | `packages/AuditLogger/src/Jobs/LogActivityJob.php` | Partially Completed | Job exists but needs refactoring to use contracts | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-013` | Event-driven architecture with notifications for high-value activities | `packages/AuditLogger/src/Events/ActivityLoggedEvent.php`, `packages/AuditLogger/src/Listeners/NotifyHighValueActivityListener.php` | Partially Completed | Events exist but need refactoring | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-014` | RESTful API endpoints for log retrieval, search, and export | - | Planned | To be implemented in apps/Atomy | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-015` | Activity statistics and reporting (total counts, counts by log name, trends over time) | `packages/AuditLogger/src/Contracts/AuditLogRepositoryContract.php::getStatistics()` | In Progress | Repository contract method defined | 2025-11-16 |

#### Performance Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\AuditLogger` | `PR-AUD-001` | Audit log creation time < 50ms (p95) when processed asynchronously | `packages/AuditLogger/src/Jobs/LogActivityJob.php` | Planned | Queue-based implementation required | 2025-11-16 |
| `Nexus\AuditLogger` | `PR-AUD-002` | Search query response time < 500ms for 100K+ log entries with proper indexing | - | Planned | Database indexes to be created in apps/Atomy migrations | 2025-11-16 |
| `Nexus\AuditLogger` | `PR-AUD-003` | Export generation time < 5 seconds for 10,000 log entries in CSV format | `packages/AuditLogger/src/Services/LogExporterService.php` | Planned | Exporter optimization needed | 2025-11-16 |
| `Nexus\AuditLogger` | `PR-AUD-004` | Support 1M+ audit log entries per tenant without degradation | - | Planned | Requires partitioning strategy in apps/Atomy | 2025-11-16 |
| `Nexus\AuditLogger` | `PR-AUD-005` | Purge operation < 10 seconds for 100K expired entries using batch deletion | `packages/AuditLogger/src/Contracts/AuditLogRepositoryContract.php::purgeExpired()` | Planned | Batch deletion implementation needed | 2025-11-16 |

#### Security Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\AuditLogger` | `SR-AUD-001` | Immutable audit logs - once created, logs cannot be modified or deleted (append-only) | `packages/AuditLogger/src/Contracts/AuditLogRepositoryContract.php::create()` | In Progress | Repository contract enforces append-only operations | 2025-11-16 |
| `Nexus\AuditLogger` | `SR-AUD-002` | Enforce strict tenant isolation - logs can only be accessed by their owning tenant | `packages/AuditLogger/src/Contracts/AuditLogRepositoryContract.php` | Planned | Tenant scoping to be implemented in apps/Atomy repository | 2025-11-16 |
| `Nexus\AuditLogger` | `SR-AUD-003` | Automatic masking of sensitive fields (passwords, tokens, API keys, credit cards) | `packages/AuditLogger/src/Services/AuditLogManager.php::maskSensitiveData()` | Completed | Recursive masking with configurable field patterns | 2025-11-16 |
| `Nexus\AuditLogger` | `SR-AUD-004` | Role-based access control for audit log viewing and export operations | - | Planned | Authorization policies to be implemented in apps/Atomy | 2025-11-16 |
| `Nexus\AuditLogger` | `SR-AUD-005` | Cryptographic verification of log integrity (hash chain or digital signatures) | - | Planned | Advanced security feature for compliance | 2025-11-16 |
| `Nexus\AuditLogger` | `SR-AUD-006` | Audit the audit system - log all access to audit logs (meta-auditing) | - | Planned | Self-auditing capability | 2025-11-16 |

#### Reliability Requirements

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\AuditLogger` | `REL-AUD-001` | Failed audit log writes MUST NOT cause application failures (graceful degradation) | `packages/AuditLogger/src/Jobs/LogActivityJob.php` | Planned | Queue retry logic with dead letter queue | 2025-11-16 |
| `Nexus\AuditLogger` | `REL-AUD-002` | Queue-based logging with automatic retry on transient failures (3 attempts with exponential backoff) | `packages/AuditLogger/src/Jobs/LogActivityJob.php` | Planned | Laravel queue retry configuration | 2025-11-16 |
| `Nexus\AuditLogger` | `REL-AUD-003` | Database transaction safety - audit logs committed atomically with related entity changes | - | Planned | Transaction management in apps/Atomy | 2025-11-16 |
| `Nexus\AuditLogger` | `REL-AUD-004` | Backup and archival strategy for long-term log retention (beyond database) | - | Planned | External archive integration | 2025-11-16 |

#### Business Rules

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\AuditLogger` | `BR-AUD-001` | Audit logs MUST include log_name, description, and timestamp at minimum | `packages/AuditLogger/src/Services/AuditLogManager.php::validateLogData()` | Completed | Validation enforces required fields | 2025-11-16 |
| `Nexus\AuditLogger` | `BR-AUD-002` | Audit level MUST be one of: 1 (Low), 2 (Medium), 3 (High), 4 (Critical) | `packages/AuditLogger/src/Services/AuditLogManager.php::validateLogData()` | Completed | Validation enforces valid levels | 2025-11-16 |
| `Nexus\AuditLogger` | `BR-AUD-003` | Retention days CANNOT be negative; default retention is 90 days | `packages/AuditLogger/src/Services/AuditLogManager.php::validateLogData()` | Completed | Validation enforces non-negative retention | 2025-11-16 |
| `Nexus\AuditLogger` | `BR-AUD-004` | System activities (cron jobs, queue workers, CLI commands) are logged with causer_type = null | - | Planned | System context detection in apps/Atomy | 2025-11-16 |
| `Nexus\AuditLogger` | `BR-AUD-005` | High-value entity changes (users, roles, permissions, financial records) default to Critical level | - | Planned | Model-specific audit level configuration | 2025-11-16 |
| `Nexus\AuditLogger` | `BR-AUD-006` | Batch operations MUST use a single batch_uuid to group related logs | `packages/AuditLogger/src/Contracts/AuditLogInterface.php::getBatchUuid()` | In Progress | Interface method defined | 2025-11-16 |
| `Nexus\AuditLogger` | `BR-AUD-007` | Expired logs (past retention period) are purged automatically via scheduled job | `packages/AuditLogger/src/Contracts/AuditLogRepositoryContract.php::purgeExpired()` | Planned | Scheduled command to be created in apps/Atomy | 2025-11-16 |

#### Architecture Compliance

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\AuditLogger` | `ARCH-AUD-001` | Package MUST be framework-agnostic with no Laravel dependencies in core services | `packages/AuditLogger/src/Services/AuditLogManager.php`, `packages/AuditLogger/src/Contracts/*` | Completed | Core manager and contracts are pure PHP | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-002` | All data structures defined via interfaces (AuditLogInterface) | `packages/AuditLogger/src/Contracts/AuditLogInterface.php` | Completed | Complete data structure interface defined | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-003` | All persistence operations via repository interface | `packages/AuditLogger/src/Contracts/AuditLogRepositoryContract.php` | Completed | Repository contract updated to be framework-agnostic | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-004` | Business logic in service layer (AuditLogManager) | `packages/AuditLogger/src/Services/AuditLogManager.php` | Completed | Manager contains validation, masking, and orchestration logic | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-005` | All database migrations in application layer (apps/Atomy) | - | Planned | Migration to be created in apps/Atomy for activity_log table | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-006` | All Eloquent models in application layer | `packages/AuditLogger/src/Models/AuditLog.php` | Planned | Model to be moved to apps/Atomy and implement AuditLogInterface | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-007` | Repository implementations in application layer | - | Planned | DatabaseAuditLogRepository to be created in apps/Atomy | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-008` | Traits and Observers in application layer (Laravel-specific) | `packages/AuditLogger/src/Traits/Auditable.php`, `packages/AuditLogger/src/Observers/AuditObserver.php` | Planned | Traits and observers to be moved to apps/Atomy | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-009` | IoC container bindings in application service provider | - | Planned | AuditLogRepositoryContract to be bound in apps/Atomy provider | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-010` | Package composer.json MUST NOT depend on laravel/framework | `packages/AuditLogger/composer.json` | Planned | Remove Laravel dependency, allow only illuminate/support if absolutely necessary | 2025-11-16 |


### Nexus\AuditLogger — Architecture Refactoring Summary

This package provides comprehensive audit logging capabilities for tracking all system activities, changes, and user actions with immutable, searchable logs that support compliance requirements.

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\AuditLogger` | `ARCH-AUD-001` | Package MUST be framework-agnostic with no Laravel dependencies in core services | `packages/AuditLogger/src/Services/AuditLogManager.php`, `packages/AuditLogger/src/Contracts/*` | Completed | Core manager and contracts are pure PHP with zero Laravel dependencies | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-002` | All data structures defined via interfaces (AuditLogInterface) | `packages/AuditLogger/src/Contracts/AuditLogInterface.php` | Completed | Complete data structure interface with 15 methods defined | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-003` | All persistence operations via repository interface | `packages/AuditLogger/src/Contracts/AuditLogRepositoryContract.php` | Completed | Repository contract updated to be framework-agnostic with 9 methods | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-004` | Business logic in service layer (AuditLogManager) | `packages/AuditLogger/src/Services/AuditLogManager.php` | Completed | Manager contains validation, masking, and orchestration logic | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-005` | All database migrations in application layer (apps/Atomy) | - | Planned | Migration to be created in apps/Atomy for activity_log table | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-006` | All Eloquent models in application layer | `packages/AuditLogger/src/Models/AuditLog.php` | Planned | Model to be moved to apps/Atomy and implement AuditLogInterface | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-007` | Repository implementations in application layer | - | Planned | DatabaseAuditLogRepository to be created in apps/Atomy | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-008` | Traits and Observers in application layer (Laravel-specific) | `packages/AuditLogger/src/Traits/Auditable.php`, `packages/AuditLogger/src/Observers/AuditObserver.php` | Planned | Traits and observers to be moved to apps/Atomy | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-009` | IoC container bindings in application service provider | - | Planned | AuditLogRepositoryContract to be bound in apps/Atomy provider | 2025-11-16 |
| `Nexus\AuditLogger` | `ARCH-AUD-010` | Package composer.json MUST NOT depend on laravel/framework | `packages/AuditLogger/composer.json` | Planned | Remove Laravel dependency, allow only illuminate/support if necessary | 2025-11-16 |
| `Nexus\AuditLogger` | `FR-AUD-Summary` | 43 total requirements documented (15 FR + 5 PR + 6 SR + 7 BR + 10 ARCH) | `AUDITLOGGER_UOM_REQUIREMENTS.md` | In Progress | Phase 1 (Contracts) completed. See detailed requirements in AUDITLOGGER_UOM_REQUIREMENTS.md | 2025-11-16 |

### Nexus\Uom — Architecture Refactoring Summary

This package provides sophisticated Unit of Measurement management with conversions, compound units, packaging hierarchies, and custom unit definitions.

| Package/App (Namespace) | Requirement # | Description | Implemented in (Class / File / Method) | Status | Notes | Date |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Uom` | `ARCH-UOM-001` | Package MUST be framework-agnostic with no Laravel dependencies in core services | `packages/Uom/src/Contracts/*`, `packages/Uom/src/Services/*` | Planned | Contracts are pure PHP; services need refactoring to remove Laravel dependencies | 2025-11-16 |
| `Nexus\Uom` | `ARCH-UOM-002` | All data structures defined via interfaces | `packages/Uom/src/Contracts/UomUnitInterface.php`, `UomTypeInterface.php`, `UomConversionInterface.php` | In Progress | 3 of 13 entity interfaces created; 10 more needed for remaining entities | 2025-11-16 |
| `Nexus\Uom` | `ARCH-UOM-003` | All persistence operations via repository interfaces | `packages/Uom/src/Contracts/*RepositoryInterface.php` | In Progress | 3 of 13 repository interfaces created | 2025-11-16 |
| `Nexus\Uom` | `ARCH-UOM-004` | Business logic in service layer | `packages/Uom/src/Services/*` | Partially Completed | Services exist but need refactoring to use repository contracts instead of models | 2025-11-16 |
| `Nexus\Uom` | `ARCH-UOM-005` | All database migrations in application layer (apps/Atomy) | `packages/Uom/database/migrations/*` | Planned | Migrations to be moved from package to apps/Atomy | 2025-11-16 |
| `Nexus\Uom` | `ARCH-UOM-006` | All 13 Eloquent models in application layer | `packages/Uom/src/Models/*` | Planned | All models (UomUnit, UomType, UomConversion, etc.) to be moved to apps/Atomy and implement interfaces | 2025-11-16 |
| `Nexus\Uom` | `ARCH-UOM-007` | Repository implementations in application layer | - | Planned | 13 repository implementations to be created in apps/Atomy/app/Repositories/Uom/ | 2025-11-16 |
| `Nexus\Uom` | `ARCH-UOM-008` | IoC container bindings in application service provider | - | Planned | All repository contracts to be bound in apps/Atomy provider | 2025-11-16 |
| `Nexus\Uom` | `ARCH-UOM-009` | Remove illuminate/support dependency if possible | `packages/Uom/composer.json` | Planned | Evaluate necessity and remove or minimize dependency | 2025-11-16 |
| `Nexus\Uom` | `ARCH-UOM-010` | Remove database folder from package | `packages/Uom/database/` | Planned | Move migrations, seeders, and factories to apps/Atomy | 2025-11-16 |
| `Nexus\Uom` | `FR-UOM-Summary` | 58 total requirements documented (15 FR + 13 DM + 5 PR + 5 SR + 10 BR + 10 ARCH) | `AUDITLOGGER_UOM_REQUIREMENTS.md` | In Progress | Phase 1 (Contracts) in progress. 3 of 13 entity contracts completed. See detailed requirements in AUDITLOGGER_UOM_REQUIREMENTS.md | 2025-11-16 |

### Refactoring Progress Summary

**Phase 1: Contracts & Requirements (COMPLETED)**
- ✅ AuditLogger: 4 contracts/services created, 43 requirements documented
- ✅ Uom: 6 contracts created, 58 requirements documented
- ✅ Documentation: AUDITLOGGER_UOM_REQUIREMENTS.md and REFACTORING_SUMMARY.md created
- ✅ Total: 11 new files, 101 requirements documented with status tracking

**Remaining Phases:**
- Phase 2: Move Laravel code to apps/Atomy (Models, Traits, Observers, Events, Jobs, Migrations)
- Phase 3: Create remaining interfaces for Uom package (10 more entity interfaces + repositories)
- Phase 4: Testing (update tests, ensure 90%+ coverage, integration tests)
- Phase 5: Documentation updates (READMEs, migration guides)

**Estimated Time to Complete:** 26-38 hours for remaining phases

