---
plan: Implement Workflow Engine Foundation
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, workflow-engine, approval-routing, state-machine, foundation, business-process]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan establishes the foundation for the Workflow Engine module, including package structure, database schema for workflow definitions and instances, state machine pattern implementation, basic approval routing logic, and integration with the authentication module. This foundation enables multi-level approval workflows across all ERP transactional modules with sequential and parallel routing capabilities.

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-WF-001**: Provide visual workflow designer for creating approval chains
- **REQ-FR-WF-002**: Support multi-level approval routing with parallel and sequential flows
- **REQ-FR-WF-003**: Support conditional routing based on transaction amount, type, or custom rules
- **REQ-FR-WF-006**: Provide workflow status tracking with real-time progress visualization
- **REQ-BR-WF-001**: Approvals must be executed in sequential order unless parallel routing is enabled
- **REQ-BR-WF-002**: Approvers cannot approve their own submissions
- **REQ-BR-WF-003**: Escalations occur automatically when approval deadlines are exceeded
- **REQ-DR-WF-001**: Store workflow definitions with routing rules and conditions
- **REQ-DR-WF-002**: Maintain workflow instance state tracking current step and history
- **REQ-DR-WF-003**: Track approval actions with timestamps, comments, and attachments
- **REQ-PR-WF-001**: Workflow routing decision must complete in < 100 milliseconds
- **REQ-ARCH-WF-001**: Use SQL for workflow definitions with JSON configuration
- **REQ-ARCH-WF-002**: Use Redis Queue for asynchronous workflow execution
- **REQ-ARCH-WF-003**: Implement state machine pattern for workflow instance management

### Security Constraints

- **SEC-001**: Workflow definitions must enforce tenant isolation
- **SEC-002**: Approval authority must be validated against user roles and limits
- **SEC-003**: Self-approval must be blocked at system level
- **SEC-004**: Workflow state transitions must be atomic and audited

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: Use Laravel 12+ conventions (anonymous migrations, modern factory syntax)
- **GUD-003**: Follow PSR-12 coding standards, enforced by Laravel Pint
- **GUD-004**: Use state machine pattern for workflow instance state management
- **GUD-005**: All workflow actions must be logged for audit trail

### Patterns to Follow

- **PAT-001**: Use State Machine pattern for workflow instance lifecycle management
- **PAT-002**: Use Strategy pattern for different routing types (sequential, parallel, conditional)
- **PAT-003**: Use Repository pattern for workflow definition and instance data access
- **PAT-004**: Use Action pattern for workflow operations (start, approve, reject, escalate)
- **PAT-005**: Use Observer pattern for workflow state change notifications

### Constraints

- **CON-001**: Workflow definitions limited to 50 steps maximum per workflow
- **CON-002**: Parallel approval steps limited to 10 approvers maximum
- **CON-003**: Workflow routing decision must complete within 100ms
- **CON-004**: Must support PostgreSQL 14+ and MySQL 8.0+ for workflow storage
- **CON-005**: Workflow instances retained for 24 months minimum

## 2. Implementation Steps

### GOAL-001: Package Setup and Database Schema

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-WF-001, DR-WF-002, DR-WF-003, ARCH-WF-001 | Set up workflow-engine package structure with Composer, create database schema for workflow definitions, instances, steps, and approval actions. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create package directory structure: `packages/workflow-engine/` with subdirectories `src/`, `database/migrations/`, `database/factories/`, `database/seeders/`, `config/`, `tests/Feature/`, `tests/Unit/`, `routes/`. Initialize `composer.json` with package name `azaharizaman/erp-workflow-engine`, namespace `Nexus\Erp\WorkflowEngine`, require Laravel 12+, PHP 8.2+, `azaharizaman/erp-multitenancy`, `azaharizaman/erp-authentication`. | | |
| TASK-002 | Create migration `database/migrations/create_workflow_definitions_table.php` (anonymous class): Define `workflow_definitions` table with columns: id (BIGSERIAL), tenant_id (UUID/BIGINT, indexed, NOT NULL), code (VARCHAR(50), unique per tenant), name (VARCHAR(255)), description (TEXT), entity_type (VARCHAR(100), e.g., 'purchase_order', 'expense_claim'), routing_type (ENUM: 'sequential', 'parallel', 'conditional'), steps_config (JSONB, array of step definitions with order, approver_role_id, approver_user_id, conditions), escalation_rules (JSONB, array of rules with deadline_hours, escalate_to_role_id), is_active (BOOLEAN, default true), created_by (BIGINT), created_at, updated_at. Add unique constraint on (tenant_id, code). Add index on (tenant_id, entity_type, is_active). | | |
| TASK-003 | Create migration `database/migrations/create_workflow_instances_table.php` (anonymous class): Define `workflow_instances` table with columns: id (BIGSERIAL), tenant_id (UUID/BIGINT, indexed, NOT NULL), workflow_definition_id (BIGINT, foreign key to workflow_definitions.id), entity_type (VARCHAR(100)), entity_id (BIGINT, the record being approved, e.g., purchase_order.id), entity_data (JSONB, snapshot of entity at workflow start), current_step (INTEGER, current step index in workflow), status (ENUM: 'pending', 'in_progress', 'approved', 'rejected', 'cancelled'), initiated_by (BIGINT, foreign key to users.id), initiated_at (TIMESTAMP), completed_at (TIMESTAMP, nullable), created_at, updated_at. Add index on (tenant_id, status, created_at). Add index on (entity_type, entity_id) for lookup. | | |
| TASK-004 | Create migration `database/migrations/create_workflow_steps_table.php` (anonymous class): Define `workflow_steps` table with columns: id (BIGSERIAL), workflow_instance_id (BIGINT, foreign key, indexed), step_number (INTEGER, order in workflow), step_name (VARCHAR(255)), approver_role_id (BIGINT, nullable, foreign key to roles.id), approver_user_id (BIGINT, nullable, foreign key to users.id), status (ENUM: 'pending', 'approved', 'rejected', 'skipped', 'escalated'), assigned_at (TIMESTAMP), due_at (TIMESTAMP, nullable), completed_at (TIMESTAMP, nullable), created_at, updated_at. Add index on (workflow_instance_id, step_number). Add index on (approver_user_id, status) for inbox queries. | | |
| TASK-005 | Create migration `database/migrations/create_approval_actions_table.php` (anonymous class): Define `approval_actions` table with columns: id (BIGSERIAL), workflow_step_id (BIGINT, foreign key, indexed), workflow_instance_id (BIGINT, foreign key, indexed), action (ENUM: 'approve', 'reject', 'delegate', 'comment'), performed_by (BIGINT, foreign key to users.id), comments (TEXT, nullable), attachments (JSONB, array of file paths), performed_at (TIMESTAMP, indexed), created_at, updated_at. Add index on (workflow_instance_id, performed_at) for history queries. Add index on (performed_by, performed_at) for user activity tracking. | | |
| TASK-006 | Create `config/workflow-engine.php` configuration file with settings: enabled (bool, default true), default_deadline_hours (int, default 48), max_steps_per_workflow (int, default 50, per CON-001), max_parallel_approvers (int, default 10, per CON-002), routing_timeout_ms (int, default 100, per CON-003), enable_self_approval_check (bool, default true), enable_escalation (bool, default true), enable_delegation (bool, default true), retention_months (int, default 24), queue_connection (string, default 'redis'), notification_channels (array: ['mail', 'database']). | | |
| TASK-007 | Create `src/WorkflowEngineServiceProvider.php`: Register config, migrations, service bindings. Bind `WorkflowDefinitionRepositoryContract` to `WorkflowDefinitionRepository`. Bind `WorkflowInstanceRepositoryContract` to `WorkflowInstanceRepository`. Bind `WorkflowExecutorServiceContract` to `WorkflowExecutorService`. Bind `ApprovalRoutingServiceContract` to `ApprovalRoutingService`. Register API routes from `routes/api.php`. Publish config and migrations. Register event listeners for workflow state changes. Register commands for workflow processing. | | |

### GOAL-002: Workflow Definition Models and Repositories

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-WF-001, ARCH-WF-003 | Create Eloquent models for workflow definitions and instances, implement repository pattern with contracts for data access. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-008 | Create `src/Models/WorkflowDefinition.php` Eloquent model: Include `declare(strict_types=1);`. Use traits: `BelongsToTenant`, `Searchable`, `LogsActivity`. Define fillable: tenant_id, code, name, description, entity_type, routing_type, steps_config, escalation_rules, is_active, created_by. Define casts: steps_config => 'array', escalation_rules => 'array', is_active => 'boolean'. Add relationships: belongsTo(Tenant), belongsTo(User, 'created_by'), hasMany(WorkflowInstance). Add scopes: scopeActive(), scopeByEntityType($type). Add methods: `getStepCount(): int`, `validateStepsConfig(): bool`, `canApprove(User $user, int $stepNumber): bool`. Implement searchableAs() => 'workflow_definitions', toSearchableArray() with tenant_id, code, name, entity_type. | | |
| TASK-009 | Create `src/Models/WorkflowInstance.php` Eloquent model: Include `declare(strict_types=1);`. Use traits: `BelongsToTenant`, `LogsActivity`. Define fillable: tenant_id, workflow_definition_id, entity_type, entity_id, entity_data, current_step, status, initiated_by, initiated_at, completed_at. Define casts: entity_data => 'array', current_step => 'integer', initiated_at => 'datetime', completed_at => 'datetime'. Add relationships: belongsTo(Tenant), belongsTo(WorkflowDefinition), belongsTo(User, 'initiated_by'), hasMany(WorkflowStep), hasMany(ApprovalAction). Add scopes: scopeByStatus($status), scopePending(), scopeInProgress(), scopeCompleted(). Add methods: `getCurrentStep(): ?WorkflowStep`, `canTransitionTo(string $newStatus): bool`, `isApproved(): bool`, `isRejected(): bool`. | | |
| TASK-010 | Create `src/Models/WorkflowStep.php` Eloquent model: Include `declare(strict_types=1);`. Use traits: `BelongsToTenant` (via workflow_instance). Define fillable: workflow_instance_id, step_number, step_name, approver_role_id, approver_user_id, status, assigned_at, due_at, completed_at. Define casts: step_number => 'integer', assigned_at => 'datetime', due_at => 'datetime', completed_at => 'datetime'. Add relationships: belongsTo(WorkflowInstance), belongsTo(Role, 'approver_role_id'), belongsTo(User, 'approver_user_id'), hasMany(ApprovalAction). Add scopes: scopePending(), scopeOverdue(), scopeByApprover($userId). Add methods: `isOverdue(): bool`, `canBeApprovedBy(User $user): bool`, `isPending(): bool`, `isCompleted(): bool`. | | |
| TASK-011 | Create `src/Models/ApprovalAction.php` Eloquent model: Include `declare(strict_types=1);`. Define fillable: workflow_step_id, workflow_instance_id, action, performed_by, comments, attachments, performed_at. Define casts: attachments => 'array', performed_at => 'datetime'. Add relationships: belongsTo(WorkflowStep), belongsTo(WorkflowInstance), belongsTo(User, 'performed_by'). Add scopes: scopeByAction($action), scopeByUser($userId). Add methods: `isApproval(): bool`, `isRejection(): bool`, `hasDelegation(): bool`. | | |
| TASK-012 | Create `src/Contracts/WorkflowDefinitionRepositoryContract.php` interface: Define methods: `find(int $id): ?WorkflowDefinition`, `findByCode(string $code): ?WorkflowDefinition`, `findByEntityType(string $entityType): Collection`, `findAll(array $filters = []): Collection`, `create(array $data): WorkflowDefinition`, `update(int $id, array $data): WorkflowDefinition`, `delete(int $id): bool`, `getActiveDefinitions(): Collection`. All methods must have PHPDoc with @param and @return types. | | |
| TASK-013 | Create `src/Repositories/WorkflowDefinitionRepository.php` implementing `WorkflowDefinitionRepositoryContract`: Use Eloquent model `WorkflowDefinition`. Inject `TenantManager` in constructor for automatic tenant_id filtering. Implement `find()` with tenant_id constraint. Implement `findByCode()` with tenant_id and code lookup. Implement `findByEntityType()` filtering by entity_type and is_active=true. Implement `findAll()` with support for filters (entity_type, routing_type, is_active), sorting, and pagination. Implement `create()` with automatic tenant_id injection and validation of steps_config (max 50 steps per CON-001). Implement `update()` with tenant_id verification. Implement `delete()` with soft delete. Implement `getActiveDefinitions()` returning is_active=true definitions. | | |
| TASK-014 | Create `src/Contracts/WorkflowInstanceRepositoryContract.php` interface: Define methods: `find(int $id): ?WorkflowInstance`, `findByEntity(string $entityType, int $entityId): Collection`, `findPendingForUser(int $userId): Collection`, `create(array $data): WorkflowInstance`, `update(int $id, array $data): WorkflowInstance`, `updateStatus(int $id, string $status): bool`, `getInstancesForDashboard(int $userId, array $filters = []): Collection`. All methods with PHPDoc. | | |
| TASK-015 | Create `src/Repositories/WorkflowInstanceRepository.php` implementing `WorkflowInstanceRepositoryContract`: Use Eloquent model `WorkflowInstance`. Implement `find()` with tenant_id constraint. Implement `findByEntity()` with entity_type and entity_id lookup. Implement `findPendingForUser()` querying workflow_steps where approver_user_id=$userId and status='pending', eager load workflow_instance. Implement `create()` with automatic tenant_id and initiated_at timestamp. Implement `updateStatus()` with status validation and completed_at update. Implement `getInstancesForDashboard()` with filters for status, date range, entity_type, ordering by initiated_at DESC. | | |

### GOAL-003: State Machine Implementation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| ARCH-WF-003, BR-WF-001, PR-WF-001 | Implement state machine pattern for workflow instance lifecycle management with atomic state transitions and validation. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-016 | Create `src/StateMachine/WorkflowState.php` enum: Define states as backed enum: PENDING ('pending'), IN_PROGRESS ('in_progress'), APPROVED ('approved'), REJECTED ('rejected'), CANCELLED ('cancelled'). Add methods: `canTransitionTo(WorkflowState $newState): bool` checking allowed transitions: PENDING -> IN_PROGRESS, IN_PROGRESS -> APPROVED|REJECTED|CANCELLED, APPROVED/REJECTED/CANCELLED are terminal states (no further transitions). Add method `isTerminal(): bool` returns true for APPROVED, REJECTED, CANCELLED. Use PHP 8.2 enum features. | | |
| TASK-017 | Create `src/StateMachine/WorkflowStateMachine.php` implementing `WorkflowStateMachineContract`: Inject `WorkflowInstanceRepositoryContract`, `EventDispatcher`. Implement methods: `transition(WorkflowInstance $instance, WorkflowState $newState, ?string $reason = null): bool` (executes state transition), `canTransition(WorkflowInstance $instance, WorkflowState $newState): bool` (validates transition), `getAvailableTransitions(WorkflowInstance $instance): array` (returns possible next states), `getCurrentState(WorkflowInstance $instance): WorkflowState` (returns current state as enum). Use database transactions for atomic updates. Dispatch `WorkflowStateChangedEvent` after successful transition. Validate transitions via `WorkflowState::canTransitionTo()`. Measure execution time, ensure < 100ms (PR-WF-001). | | |
| TASK-018 | Implement `transition()` in `WorkflowStateMachine`: Accept workflow_instance, new_state, optional reason. Start database transaction. Validate transition allowed via `canTransition()`. Update workflow_instance.status to new_state value. If new_state is terminal (APPROVED, REJECTED, CANCELLED), set completed_at to now(). Create audit log entry in workflow_instance activity log with old_state, new_state, reason, performed_by (from context), timestamp. Commit transaction. Dispatch `WorkflowStateChangedEvent` with instance, old_state, new_state. Return true on success. On validation failure, rollback transaction, throw `InvalidStateTransitionException`. On database error, rollback, throw `WorkflowStateMachineException`. | | |
| TASK-019 | Create `src/Contracts/WorkflowStateMachineContract.php` interface: Define methods: `transition(WorkflowInstance $instance, WorkflowState $newState, ?string $reason = null): bool`, `canTransition(WorkflowInstance $instance, WorkflowState $newState): bool`, `getAvailableTransitions(WorkflowInstance $instance): array`, `getCurrentState(WorkflowInstance $instance): WorkflowState`. All methods with full PHPDoc including @param, @return, @throws tags. Interface enables mocking for testing and future alternative implementations. | | |
| TASK-020 | Create `src/Events/WorkflowStateChangedEvent.php` implementing ShouldQueue: Include `declare(strict_types=1);`. Properties: workflow_instance_id (int), old_state (string), new_state (string), changed_at (Carbon), changed_by (int nullable). Implement ShouldQueue for asynchronous processing. Constructor accepts WorkflowInstance, old_state, new_state. Used by listeners to trigger notifications, update related entities, log changes. Queue on 'workflows' queue for isolation. | | |

### GOAL-004: Basic Approval Routing Logic

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-002, BR-WF-001, BR-WF-002, PR-WF-001 | Implement basic approval routing service supporting sequential and parallel flows with self-approval validation and performance optimization. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-021 | Create `src/Contracts/ApprovalRoutingServiceContract.php` interface: Define methods: `route(WorkflowInstance $instance): array` (determines next approvers), `canUserApprove(User $user, WorkflowInstance $instance): bool` (checks if user can approve current step), `getNextStep(WorkflowInstance $instance): ?array` (returns next step configuration), `getAllApproversForStep(int $stepNumber, array $stepConfig): array` (resolves role to users), `validateSelfApproval(User $user, WorkflowInstance $instance): bool` (checks BR-WF-002). All methods with PHPDoc. | | |
| TASK-022 | Create `src/Services/ApprovalRoutingService.php` implementing `ApprovalRoutingServiceContract`: Inject `WorkflowDefinitionRepositoryContract`, `RoleRepositoryContract`, `UserRepositoryContract`. Implement `route()`: 1) Get workflow_definition for instance, 2) Get steps_config, 3) Determine current_step, 4) Get next step based on routing_type (sequential: current_step+1, parallel: all steps at same level), 5) Resolve approvers (role_id to users, or direct user_id), 6) Create WorkflowStep records for next approvers, 7) Set assigned_at, calculate due_at (now + deadline_hours from config), 8) Return array of created steps. Measure execution time, ensure < 100ms (PR-WF-001). Cache role-to-users mapping for 5 minutes. | | |
| TASK-023 | Implement `canUserApprove()` in `ApprovalRoutingService`: Accept user and workflow_instance. Get current step from workflow_instance. Check if current step's approver_user_id matches user.id OR user has approver_role_id. If match found, call `validateSelfApproval()` to check BR-WF-002. Return true if user can approve and self-approval validation passes. Return false otherwise. Include permission check: user must have 'approve-workflows' permission. Check tenant isolation: ensure user.tenant_id matches instance.tenant_id. | | |
| TASK-024 | Implement `validateSelfApproval()` in `ApprovalRoutingService`: Accept user and workflow_instance. Check config 'enable_self_approval_check'. If disabled, return true (no validation). If enabled, check if instance.initiated_by equals user.id. If match, return false (self-approval blocked per BR-WF-002). Otherwise return true. Log validation result for audit. Consider edge case: user could be in approver_role but not initiator; in this case, allow approval. Only block if user is exact initiator. | | |
| TASK-025 | Implement `getAllApproversForStep()` in `ApprovalRoutingService`: Accept step_number and step_config from workflow definition. Extract approver_role_id or approver_user_id from step_config. If approver_user_id provided, return array with single user. If approver_role_id provided, query users with that role, filter by is_active=true, return array of users. Apply tenant_id filter. For parallel steps, return all users. For sequential, return single user or first from role. Limit parallel approvers to max_parallel_approvers from config (default 10, per CON-002). Cache role-to-users resolution for performance. | | |

### GOAL-005: Workflow Execution Service and Testing

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-002, DR-WF-002, DR-WF-003, ARCH-WF-002 | Implement workflow executor service for starting workflows, processing approvals, and create comprehensive tests. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-026 | Create `src/Contracts/WorkflowExecutorServiceContract.php` interface: Define methods: `startWorkflow(string $entityType, int $entityId, int $workflowDefinitionId, int $initiatedBy): WorkflowInstance` (initiates workflow), `approveStep(WorkflowStep $step, User $user, ?string $comments = null): bool` (approves current step), `rejectStep(WorkflowStep $step, User $user, string $reason): bool` (rejects and terminates workflow), `cancelWorkflow(WorkflowInstance $instance, User $user, string $reason): bool` (cancels in-progress workflow), `processNextStep(WorkflowInstance $instance): bool` (advances workflow after approval). All methods with PHPDoc. | | |
| TASK-027 | Create `src/Services/WorkflowExecutorService.php` implementing `WorkflowExecutorServiceContract`: Inject `WorkflowInstanceRepositoryContract`, `ApprovalRoutingServiceContract`, `WorkflowStateMachineContract`, `EventDispatcher`. Implement `startWorkflow()`: 1) Validate workflow_definition is active, 2) Create workflow_instance with status=PENDING, 3) Transition to IN_PROGRESS via state machine, 4) Call `ApprovalRoutingService::route()` to create first steps, 5) Dispatch `WorkflowStartedEvent`, 6) Return workflow_instance. Wrap in database transaction for atomicity. Validate entity exists and user has permission to initiate. | | |
| TASK-028 | Implement `approveStep()` in `WorkflowExecutorService`: Accept workflow_step, user, optional comments. Validate user can approve via `ApprovalRoutingService::canUserApprove()`. Create ApprovalAction record with action='approve', performed_by=user.id, comments, performed_at=now(). Update workflow_step.status='approved', completed_at=now(). Check if all steps at current level approved (for parallel workflows). If yes, call `processNextStep()`. If workflow complete (all steps approved), transition workflow_instance to APPROVED via state machine. Dispatch `StepApprovedEvent`. Return true on success. Use database transaction. Queue notification to initiator. | | |
| TASK-029 | Implement `rejectStep()` in `WorkflowExecutorService`: Accept workflow_step, user, rejection reason (required). Validate user can approve. Create ApprovalAction record with action='reject', comments=reason. Update workflow_step.status='rejected', completed_at=now(). Transition workflow_instance to REJECTED via state machine (terminal state). Dispatch `StepRejectedEvent`, `WorkflowCompletedEvent` with outcome='rejected'. Return true. Use transaction. Queue notification to initiator with rejection reason. No further steps processed after rejection. | | |
| TASK-030 | Create Feature test `tests/Feature/WorkflowExecutionTest.php`: Use Pest syntax. Test scenarios: 1) Start workflow for purchase order (expect workflow_instance created, first step assigned), 2) Approve step by authorized user (expect step approved, next step created), 3) Reject step (expect workflow rejected, no further steps), 4) Attempt self-approval by initiator (expect validation error per BR-WF-002), 5) Sequential workflow with 3 steps (expect steps approved in order), 6) Cancel in-progress workflow (expect status=cancelled), 7) Workflow routing completes < 100ms (PR-WF-001), 8) Unauthorized approval attempt (expect 403). Use factories for test data: tenants, users, roles, workflow definitions. Assert database state after each action. | | |
| TASK-031 | Create Unit test `tests/Unit/WorkflowStateMachineTest.php`: Test `canTransition()` method with valid and invalid transitions (PENDING->IN_PROGRESS valid, IN_PROGRESS->PENDING invalid, APPROVED->REJECTED invalid), Test `transition()` executes atomically with rollback on error, Test `getAvailableTransitions()` returns correct next states, Test state transition to terminal state sets completed_at, Test concurrent transition attempts (race condition), Test transition with reason logs correctly. Mock repositories and event dispatcher. Assert state changes and event dispatching. | | |
| TASK-032 | Create Factory `database/factories/WorkflowDefinitionFactory.php`: Define factory for WorkflowDefinition model with random data: code (unique), name, entity_type ('purchase_order' default), routing_type ('sequential' default), steps_config (array with 3 sample steps: [{step_number: 1, step_name: 'Manager Approval', approver_role_id: 2}, {step_number: 2, step_name: 'Director Approval', approver_role_id: 3}, {step_number: 3, step_name: 'CFO Approval', approver_user_id: 5}]), escalation_rules (array with sample rule), is_active (true). State methods: inactive() sets is_active=false, parallelRouting() sets routing_type='parallel', withSteps(int $count) generates specified number of steps. | | |
| TASK-033 | Create Factory `database/factories/WorkflowInstanceFactory.php`: Define factory for WorkflowInstance model: workflow_definition_id (from WorkflowDefinition factory), entity_type ('purchase_order'), entity_id (random integer), entity_data (sample PO data), current_step (0), status ('pending'), initiated_by (from User factory), initiated_at (now()). State methods: inProgress() sets status='in_progress' and current_step=1, approved() sets status='approved' and completed_at=now(), rejected() sets status='rejected' and completed_at=now(), withSteps(int $count) creates related WorkflowStep records. | | |

## 3. Alternatives

- **ALT-001**: Use dedicated workflow engine library (Camunda, Temporal) instead of custom implementation
  - *Pros*: Feature-rich, battle-tested, BPMN 2.0 support, visual designer
  - *Cons*: Additional infrastructure, Java-based (Camunda), steeper learning curve, overkill for simple approvals
  - *Decision*: Not chosen for MVP - Custom implementation sufficient for ERP approval workflows; can migrate later

- **ALT-002**: Use event sourcing pattern for workflow history instead of status updates
  - *Pros*: Complete audit trail, time-travel debugging, replay capability
  - *Cons*: Increased complexity, storage overhead, harder to query current state
  - *Decision*: Deferred - Traditional status tracking sufficient for MVP; event sourcing can be added later

- **ALT-003**: Store workflow steps as separate workflow_definition_steps table instead of JSON
  - *Pros*: Normalized schema, easier to query individual steps, better referential integrity
  - *Cons*: More complex queries, harder to version workflow definitions
  - *Decision*: Not chosen - JSONB in PostgreSQL provides good balance of flexibility and performance

- **ALT-004**: Use message queue (RabbitMQ) instead of Laravel Queue for workflow execution
  - *Pros*: Better reliability, at-least-once delivery, more scalable
  - *Cons*: Additional infrastructure, increased complexity
  - *Decision*: Deferred - Laravel Queue with Redis driver sufficient for MVP; can migrate later if needed

## 4. Dependencies

**Package Dependencies:**
- `azaharizaman/erp-multitenancy` (PRD01-SUB01) - Required for tenant context
- `azaharizaman/erp-authentication` (PRD01-SUB02) - Required for user roles and permissions
- `azaharizaman/erp-audit-logging` (PRD01-SUB03) - Optional for enhanced audit trail
- `azaharizaman/erp-notifications` (PRD01-SUB22) - Required for workflow notifications (to be created)

**Internal Dependencies:**
- Authentication module for user and role data
- Tenant Manager for tenant context resolution
- Permission system for approval authority validation

**Infrastructure Dependencies:**
- PostgreSQL 14+ OR MySQL 8.0+ for workflow storage
- Redis for queue processing (Laravel Queue with Redis driver recommended)
- Queue worker process to handle asynchronous workflow execution

## 5. Files

**Configuration:**
- `packages/workflow-engine/config/workflow-engine.php` - Package configuration

**Migrations:**
- `packages/workflow-engine/database/migrations/create_workflow_definitions_table.php` - Workflow definitions schema
- `packages/workflow-engine/database/migrations/create_workflow_instances_table.php` - Workflow instances schema
- `packages/workflow-engine/database/migrations/create_workflow_steps_table.php` - Workflow steps schema
- `packages/workflow-engine/database/migrations/create_approval_actions_table.php` - Approval actions log schema

**Models:**
- `packages/workflow-engine/src/Models/WorkflowDefinition.php` - Workflow definition model
- `packages/workflow-engine/src/Models/WorkflowInstance.php` - Workflow instance model
- `packages/workflow-engine/src/Models/WorkflowStep.php` - Workflow step model
- `packages/workflow-engine/src/Models/ApprovalAction.php` - Approval action model

**Enums:**
- `packages/workflow-engine/src/StateMachine/WorkflowState.php` - Workflow state enum

**Contracts:**
- `packages/workflow-engine/src/Contracts/WorkflowDefinitionRepositoryContract.php` - Definition repository interface
- `packages/workflow-engine/src/Contracts/WorkflowInstanceRepositoryContract.php` - Instance repository interface
- `packages/workflow-engine/src/Contracts/WorkflowStateMachineContract.php` - State machine interface
- `packages/workflow-engine/src/Contracts/ApprovalRoutingServiceContract.php` - Routing service interface
- `packages/workflow-engine/src/Contracts/WorkflowExecutorServiceContract.php` - Executor service interface

**Repositories:**
- `packages/workflow-engine/src/Repositories/WorkflowDefinitionRepository.php` - Definition repository
- `packages/workflow-engine/src/Repositories/WorkflowInstanceRepository.php` - Instance repository

**Services:**
- `packages/workflow-engine/src/StateMachine/WorkflowStateMachine.php` - State machine implementation
- `packages/workflow-engine/src/Services/ApprovalRoutingService.php` - Routing logic
- `packages/workflow-engine/src/Services/WorkflowExecutorService.php` - Workflow execution

**Events:**
- `packages/workflow-engine/src/Events/WorkflowStateChangedEvent.php` - State change event
- `packages/workflow-engine/src/Events/WorkflowStartedEvent.php` - Workflow started event (to be created in PLAN02)
- `packages/workflow-engine/src/Events/StepApprovedEvent.php` - Step approved event (to be created in PLAN02)
- `packages/workflow-engine/src/Events/StepRejectedEvent.php` - Step rejected event (to be created in PLAN02)

**Factories:**
- `packages/workflow-engine/database/factories/WorkflowDefinitionFactory.php` - Definition factory
- `packages/workflow-engine/database/factories/WorkflowInstanceFactory.php` - Instance factory

**Tests:**
- `packages/workflow-engine/tests/Feature/WorkflowExecutionTest.php` - Workflow execution tests
- `packages/workflow-engine/tests/Unit/WorkflowStateMachineTest.php` - State machine unit tests

**Service Provider:**
- `packages/workflow-engine/src/WorkflowEngineServiceProvider.php` - Package service provider

## 6. Testing

- **TEST-001**: Start workflow for purchase order, verify workflow_instance created with status=IN_PROGRESS, first step assigned
- **TEST-002**: Approve step by authorized user, verify step status updated, approval_action created, next step assigned
- **TEST-003**: Reject step, verify workflow_instance status=REJECTED, workflow terminated, no further steps
- **TEST-004**: Attempt self-approval (initiator approving own request), expect validation error per BR-WF-002
- **TEST-005**: Sequential workflow with 3 steps, approve each in order, verify final status=APPROVED
- **TEST-006**: Cancel in-progress workflow, verify status=CANCELLED, remaining steps cancelled
- **TEST-007**: Performance test: Workflow routing decision completes < 100ms (PR-WF-001)
- **TEST-008**: Unauthorized approval attempt (user not in approver role), expect 403 Forbidden
- **TEST-009**: State machine: Valid transition PENDING->IN_PROGRESS succeeds
- **TEST-010**: State machine: Invalid transition IN_PROGRESS->PENDING fails with exception
- **TEST-011**: State machine: Transition to terminal state (APPROVED) sets completed_at
- **TEST-012**: Concurrent approval attempts handled gracefully with database locking

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Workflow routing decisions could exceed 100ms with complex conditional logic
  - *Mitigation*: Cache role-to-users mapping, optimize queries with indexes, limit complexity, measure performance in tests
- **RISK-002**: Race conditions in concurrent approval attempts could cause inconsistent state
  - *Mitigation*: Use database transactions with row-level locking, implement optimistic locking with version field
- **RISK-003**: Large number of workflow instances could impact database performance
  - *Mitigation*: Implement data retention policy (archive completed workflows after 24 months), add database indexes, partition tables
- **RISK-004**: Self-approval validation could be bypassed through role changes during workflow
  - *Mitigation*: Check initiator at approval time (not just workflow start), log all role changes, re-validate on each approval

**Assumptions:**
- **ASSUMPTION-001**: Users assigned to approval roles are active and have necessary permissions
- **ASSUMPTION-002**: Workflow definitions are configured correctly before workflows are started
- **ASSUMPTION-003**: Redis or equivalent queue backend is available for asynchronous processing
- **ASSUMPTION-004**: Workflow steps complete within reasonable timeframe (not months or years)
- **ASSUMPTION-005**: Role-to-users mappings change infrequently enough to be cached safely

## 8. KIV for future implementations

- **KIV-001**: Implement visual workflow designer UI for creating workflow definitions (currently JSON configuration)
- **KIV-002**: Add conditional routing based on transaction amount, type, or custom rules (addressed in PLAN02)
- **KIV-003**: Implement escalation rules with automatic deadline enforcement (addressed in PLAN03)
- **KIV-004**: Add delegation capability for approvers to delegate authority (addressed in PLAN02)
- **KIV-005**: Implement workflow templates for common approval patterns (pre-defined workflows)
- **KIV-006**: Add workflow versioning to handle definition changes for in-progress workflows
- **KIV-007**: Implement workflow analytics dashboard (approval time metrics, bottlenecks)
- **KIV-008**: Add BPMN 2.0 support for compatibility with external workflow tools

## 9. Related PRD / Further Reading

- Master PRD: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- Sub-PRD: [../prd/prd-01/PRD01-SUB21-WORKFLOW-ENGINE.md](../prd/prd-01/PRD01-SUB21-WORKFLOW-ENGINE.md)
- Related Sub-PRD: [../prd/prd-01/PRD01-SUB02-AUTHENTICATION.md](../prd/prd-01/PRD01-SUB02-AUTHENTICATION.md) - User roles and permissions
- Related Sub-PRD: [../prd/prd-01/PRD01-SUB22-NOTIFICATIONS-EVENTS.md](../prd/prd-01/PRD01-SUB22-NOTIFICATIONS-EVENTS.md) - Workflow notifications
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- GitHub Copilot Instructions: [../../.github/copilot-instructions.md](../../.github/copilot-instructions.md)
- State Machine Pattern: https://refactoring.guru/design-patterns/state
