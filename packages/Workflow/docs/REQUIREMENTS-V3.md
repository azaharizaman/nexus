

## Personas & User Stories

### Personas

| ID | Persona | Role | Primary Goal |
|-----|---------|------|--------------|
| **P1** | Mass Market Developer | Full-stack dev at small agency | "Add `draft` → `published` state to my `Post` model in 5 minutes without reading docs" |
| **P2** | In-House ERP Developer | Backend dev at manufacturing company | "Build reliable purchase order approval workflow integrated with existing models and roles" |
| **P3** | End-User (Manager/Employee) | Business user | "See all pending tasks in one inbox, approve/reject, delegate when on vacation" |
| **P4** | System Administrator | IT/DevOps | "Configure approval matrices, SLA policies, escalation rules without touching code" |

### User Stories

#### Level 1: State Machine (Mass Appeal)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-001** | P1 | As a developer, I want to add a `HasWorkflow` trait to my Eloquent model to manage its `status` column | **High** |
| **US-002** | P1 | As a developer, I want to define a state machine (states and transitions) as an array inside my model, requiring zero database tables | **High** |
| **US-003** | P1 | As a developer, I want to call `$model->workflow()->apply('transition')` to execute a state change | **High** |
| **US-004** | P1 | As a developer, I want to call `$model->workflow()->can('transition')` to check if a transition is allowed for UI logic | **High** |
| **US-005** | P1 | As a developer, I want to call `$model->workflow()->history()` to see all state transitions | Medium |

#### Level 2: Approval Workflows

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-010** | P2 | As a developer, I want to promote my in-model workflow to a database-driven JSON definition without refactoring code | **High** |
| **US-011** | P2 | As a developer, I want to define "User Task" steps that halt the workflow and assign tasks to specific users or roles | **High** |
| **US-012** | P2 | As a developer, I want to use conditional routing (e.g., "if amount > 10,000, add Director approval") | **High** |
| **US-013** | P2 | As a developer, I want parallel approval flows (e.g., "Finance AND HR must both approve") | **High** |
| **US-014** | P2 | As a developer, I want to configure multi-approver strategies (unison vote, majority vote, quorum) for a single step | **High** |
| **US-015** | P3 | As an end-user, I want one inbox showing all my pending tasks across all workflows | **High** |
| **US-016** | P3 | As an end-user, I want to approve/reject tasks with comments and attachments | **High** |

#### Level 3: ERP Automation

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-020** | P2 | As a developer, I want to automatically escalate overdue tasks to a manager after 48 hours | **High** |
| **US-021** | P2 | As a developer, I want to define SLA policies for entire workflows with breach notifications | **High** |
| **US-022** | P3 | As a manager, I want to delegate my task inbox to an assistant for specific date ranges | **High** |
| **US-023** | P2 | As a developer, I want to define compensation/rollback logic for failed workflows | Medium |
| **US-024** | P4 | As an admin, I want to configure approval matrices based on amount thresholds without code changes | Medium |
| **US-025** | P2 | As a developer, I want to track and report on SLA compliance rates per workflow type | Medium |

---

## Functional Requirements

### FR-L1: Level 1 - State Machine (Mass Appeal)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L1-001** | Provide `HasWorkflow` trait for Eloquent models | **High** | • Add `use HasWorkflow` to model<br>• Define `workflow()` method returning array<br>• No `php artisan migrate` required<br>• Works immediately |
| **FR-L1-002** | Support in-model workflow definitions with zero database dependencies | **High** | • Define states as simple array<br>• Define transitions as simple array<br>• Store current state in model's `status` column (configurable)<br>• No external files or tables required |
| **FR-L1-003** | Provide `workflow()->apply($transition)` method | **High** | • Execute state transition<br>• Fire Laravel events (`TransitionStarted`, `TransitionCompleted`)<br>• Validate transition is allowed<br>• Wrap in database transaction |
| **FR-L1-004** | Provide `workflow()->can($transition)` method | **High** | • Return boolean for UI logic<br>• Check current state allows transition<br>• Check custom guard conditions<br>• No side effects |
| **FR-L1-005** | Provide `workflow()->history()` method | Medium | • Return collection of state changes<br>• Include timestamps, actors, comments<br>• Store in `workflow_history` table (auto-migrated on first use) |
| **FR-L1-006** | Support guard conditions on transitions | Medium | • Define `guard` callable in transition array<br>• Return `false` to block transition<br>• Example: `'guard' => fn($model) => $model->amount < 1000` |
| **FR-L1-007** | Support transition hooks (`before`, `after`) | Medium | • Execute callbacks before/after transition<br>• Example: `'after' => fn($model) => $model->notify(...)` |

### FR-L2: Level 2 - Approval Workflows

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L2-001** | Support database-driven workflow definitions (JSON format) | **High** | • Store definitions in `workflow_definitions` table<br>• Same API as Level 1 (`apply()`, `can()`)<br>• Override in-model definitions when DB definition exists<br>• Hot-reload on definition changes |
| **FR-L2-002** | Support User Task states that create inbox tasks | **High** | • Define `type: "task"` in state definition<br>• Automatically create record in `workflow_tasks` table<br>• Support assignment to `user_id` or `role` string<br>• Pause workflow until task is completed |
| **FR-L2-003** | Support conditional routing based on workflow data | **High** | • Evaluate `condition` expression on transitions<br>• Access workflow data via `data.field_name`<br>• Support operators: `==`, `!=`, `>`, `<`, `>=`, `<=`, `AND`, `OR`, `NOT`, `IN`<br>• Automatically select valid transition |
| **FR-L2-004** | Support parallel approval flows (AND gateways) | **High** | • Define multiple tasks in `parallel` array<br>• Create all tasks simultaneously<br>• Wait for ALL tasks to complete before proceeding<br>• Track completion status per task |
| **FR-L2-005** | Support inclusive gateways (OR routing) | Medium | • Evaluate multiple condition expressions<br>• Activate ALL paths where condition is true<br>• Synchronize at join point |
| **FR-L2-006** | Support multi-approver strategies on single task | **High** | • `strategy: "unison"` - ALL assignees must approve<br>• `strategy: "majority"` - >50% must approve<br>• `strategy: "quorum"` - Configurable threshold (e.g., 3 of 5)<br>• `strategy: "weighted"` - Votes have different weights<br>• `strategy: "first"` - First approval wins<br>• Extensible via `ApprovalStrategyContract` |
| **FR-L2-007** | Provide task inbox API/service | **High** | • Query: `WorkflowInbox::forUser($userId)->pending()`<br>• Support filtering by workflow type, priority, due date<br>• Support sorting<br>• Auto-check delegation rules |
| **FR-L2-008** | Support task actions (approve, reject, request changes) | **High** | • Validate user has permission to act<br>• Store action in `workflow_history`<br>• Support comments and attachments<br>• Trigger next workflow transition |
| **FR-L2-009** | Support workflow data schema validation | Medium | • Define `dataSchema` in JSON definition<br>• Validate on workflow instantiation<br>• Validate on data updates<br>• Type support: string, number, boolean, date, array, object |
| **FR-L2-010** | Support plugin activities via `onEntry`/`onExit` hooks | **High** | • Fire-and-forget execution (async via queue)<br>• Access workflow data as inputs<br>• Built-in plugins: email, Slack, webhook, database update<br>• Extensible via `ActivityContract` |

### FR-L3: Level 3 - ERP Automation

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L3-001** | Support task escalation rules | **High** | • Define `automation.escalation` array in state<br>• `after: "48 hours"` - Time threshold<br>• `action: "notify"` - Send reminder<br>• `action: "reassign"` - Change task assignee<br>• Store escalation history<br>• Process via scheduled command |
| **FR-L3-002** | Support SLA tracking per workflow instance | **High** | • Define `automation.sla` in state or workflow root<br>• `duration: "3 days"` - Total allowed time<br>• `onBreach` - Actions to fire (notifications, etc.)<br>• Track SLA status: `on_track`, `at_risk`, `breached`<br>• Calculate based on business hours (configurable) |
| **FR-L3-003** | Support user delegation with date ranges | **High** | • Store delegation in `workflow_delegations` table<br>• Fields: `delegator_id`, `delegatee_id`, `starts_at`, `ends_at`<br>• Automatically route new tasks to delegatee<br>• Log delegation in task history<br>• Max delegation chain depth: 3 levels |
| **FR-L3-004** | Support compensation/rollback logic | Medium | • Define `compensation` array on activities<br>• Execute in reverse order on failure<br>• Example: Delete created records, send cancellation emails<br>• Implement `ActivityContract::compensate()` method |
| **FR-L3-005** | Support approval matrix configuration | Medium | • Store threshold-based routing rules in database<br>• Example: Amount $0-$5K → Manager, $5K-$50K → Director, $50K+ → VP<br>• Apply automatically during workflow instantiation<br>• Admin UI for configuration (optional) |
| **FR-L3-006** | Support event-driven timer system | **High** | • Store timers in `workflow_timers` table<br>• Index on `trigger_at` timestamp<br>• Scheduled worker processes due timers<br>• Support: SLA checks, escalations, reminders, scheduled tasks<br>• NOT cron-based (event-driven for scalability) |

### FR-EXT: Extensibility

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-EXT-001** | Provide plugin interface for custom activities | **High** | • Implement `ActivityContract` interface<br>• Methods: `execute()`, `compensate()`, `getName()`, `getSchema()`<br>• Auto-discover in configured directories<br>• Register via service provider or config |
| **FR-EXT-002** | Provide plugin interface for custom conditions | **High** | • Implement `ConditionEvaluatorContract`<br>• Method: `evaluate($context, $expression): bool`<br>• Built-in: amount, role, attribute, date, custom<br>• Register custom evaluators via config |
| **FR-EXT-003** | Provide plugin interface for approval strategies | **High** | • Implement `ApprovalStrategyContract`<br>• Methods: `canProceed($task, $approvals): bool`<br>• Built-in: unison, majority, quorum, weighted, first<br>• Register custom strategies via config |
| **FR-EXT-004** | Provide plugin interface for custom triggers | Medium | • Implement `TriggerContract`<br>• Types: webhook, schedule, event, manual<br>• Auto-start workflows based on trigger rules |
| **FR-EXT-005** | Support custom storage backends | Low | • Implement `StorageContract` for different databases<br>• Built-in: Eloquent (MySQL, PostgreSQL, SQLite, SQL Server)<br>• Optional: Redis, MongoDB adapters |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Notes |
|----|-------------|--------|-------|
| **PR-001** | State transition execution time | < 100ms | Excluding async plugin activities |
| **PR-002** | Task inbox query (1,000 pending tasks) | < 500ms | With proper database indexes |
| **PR-003** | Escalation/SLA check job (10,000 active workflows) | < 2 seconds | Indexed `workflow_timers` table |
| **PR-004** | Workflow instantiation | < 200ms | Including validation |
| **PR-005** | Parallel gateway synchronization (10 tasks) | < 100ms | Token-based coordination |

### Security Requirements

| ID | Requirement | Scope |
|----|-------------|-------|
| **SR-001** | Prevent unauthorized task actions | Validate at engine level, not just API |
| **SR-002** | Sanitize condition expressions | Prevent code injection in conditions |
| **SR-003** | Tenant isolation | Auto-scope queries when `nexus-tenancy` detected |
| **SR-004** | Plugin sandboxing | Prevent malicious plugin code execution |
| **SR-005** | Audit all state changes | Immutable history log |
| **SR-006** | Role-based access control | Integration with permission systems |

### Reliability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **REL-001** | All state changes MUST be ACID-compliant | Wrapped in database transactions |
| **REL-002** | Failed plugin activities must not block transitions | Fire-and-forget or queue-based execution |
| **REL-003** | Concurrency control for task actions | Prevent duplicate approvals via database locking |
| **REL-004** | State corruption protection | Validate state machine integrity before transitions |
| **REL-005** | Automatic retry for transient failures | Configurable retry policy for queued activities |

### Scalability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **SCL-001** | Support async/queued execution of activities | Laravel Queue integration |
| **SCL-002** | Horizontal scaling of timer workers | Multiple workers can process timers concurrently |
| **SCL-003** | Efficient database queries | Proper indexes on state, assignee, trigger_at columns |
| **SCL-004** | Support for 100,000+ concurrent workflow instances | Optimized for large-scale ERP deployments |

### Maintainability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **MAINT-001** | Framework-agnostic core | Zero Laravel dependencies in `src/Core/` |
| **MAINT-002** | Laravel adapter pattern | All framework-specific code in `src/Adapters/Laravel/` |
| **MAINT-003** | Comprehensive test coverage | > 80% code coverage, > 90% for core engine |
| **MAINT-004** | Clear separation of concerns | State management, task management, timer management are independent |

---

## Business Rules

| ID | Rule | Level |
|----|------|-------|
| **BR-001** | A user cannot approve their own submission | Configurable per workflow (Level 2) |
| **BR-002** | All state changes MUST be ACID-compliant | All levels |
| **BR-003** | Escalations occur automatically when deadlines exceeded | Level 3 |
| **BR-004** | Compensation executes in reverse order of activities | Level 3 |
| **BR-005** | Delegation chains cannot exceed 3 levels | Level 3 |
| **BR-006** | Level 1 definitions MUST be 100% compatible with Level 2/3 | All levels |
| **BR-007** | Workflow instance tied to single model instance | All levels |
| **BR-008** | Parallel tasks must ALL complete before proceeding | Level 2 |
| **BR-009** | Task assignment checked against delegation rules | Level 3 |
| **BR-010** | Multi-approver tasks follow configured strategy | Level 2 |

---
