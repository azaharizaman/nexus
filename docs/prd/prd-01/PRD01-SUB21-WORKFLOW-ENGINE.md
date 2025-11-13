# PRD01-SUB21: Workflow Engine

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Optional Feature Modules - Process Automation  
**Related Sub-PRDs:** SUB02 (Authentication), SUB22 (Notifications), All transactional modules  
**Composer Package:** `azaharizaman/erp-workflow-engine`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Workflow Engine module provides visual workflow designer, multi-level approval routing, conditional logic, escalation rules, and delegation capabilities for automating business process approvals across all ERP modules.

### Purpose

This module solves the challenge of implementing complex, multi-level approval workflows that adapt to business rules, enforce authorization policies, and ensure timely decision-making through escalation and delegation mechanisms.

### Scope

**Included:**
- Visual workflow designer for creating approval chains
- Multi-level approval routing with parallel and sequential flows
- Conditional routing based on transaction amount, type, or custom rules
- Escalation rules for overdue approvals with deadline enforcement
- Delegation of approval authority with time-bound assignments
- Workflow status tracking with real-time progress visualization
- Workflow templates for common approval patterns
- Workflow inbox for pending approvals with filtering and sorting

**Excluded:**
- Transaction processing (handled by transactional modules)
- Document management and storage (future module)
- Advanced BPM features (BPMN 2.0, process mining)

### Dependencies

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for workflows
- **SUB02 (Authentication & Authorization)** - User approval rights
- **SUB03 (Audit Logging)** - Track approval actions
- **SUB22 (Notifications & Events)** - Notify approvers of pending tasks

**Optional Dependencies:**
- All transactional modules (PO, SO, JE, etc.) can use workflows
- **SUB15 (Backoffice)** - Department and role-based routing

### Composer Package Information

- **Package Name:** `azaharizaman/erp-workflow-engine`
- **Namespace:** `Nexus\Erp\WorkflowEngine`
- **Monorepo Location:** `/packages/workflow-engine/`
- **Installation:** `composer require azaharizaman/erp-workflow-engine` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB21 (Workflow Engine). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-WF-001** | Provide **visual workflow designer** for creating approval chains | High | Planned |
| **FR-WF-002** | Support **multi-level approval routing** with parallel and sequential flows | High | Planned |
| **FR-WF-003** | Support **conditional routing** based on transaction amount, type, or custom rules | High | Planned |
| **FR-WF-004** | Implement **escalation rules** for overdue approvals with deadline enforcement | High | Planned |
| **FR-WF-005** | Support **delegation of approval authority** with time-bound assignments | Medium | Planned |
| **FR-WF-006** | Provide **workflow status tracking** with real-time progress visualization | High | Planned |
| **FR-WF-007** | Support **workflow templates** for common approval patterns (PO, expense, invoice) | Medium | Planned |
| **FR-WF-008** | Provide **workflow inbox** for pending approvals with filtering and sorting | High | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-WF-001** | Approvals must be executed in **sequential order** unless parallel routing is enabled | Planned |
| **BR-WF-002** | Approvers cannot approve their **own submissions** | Planned |
| **BR-WF-003** | Escalations occur **automatically** when approval deadlines are exceeded | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-WF-001** | Store **workflow definitions** with routing rules and conditions | Planned |
| **DR-WF-002** | Maintain **workflow instance state** tracking current step and history | Planned |
| **DR-WF-003** | Track **approval actions** with timestamps, comments, and attachments | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-WF-001** | Integrate with **all transactional modules** for approval workflows | Planned |
| **IR-WF-002** | Integrate with **Notification system** for approval notifications | Planned |
| **IR-WF-003** | Support **external workflow triggers** via API for third-party systems | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-WF-001** | Implement **role-based approval authority** with amount limits | Planned |
| **SR-WF-002** | **Audit log** all approval actions with full traceability | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-WF-001** | Workflow routing decision must complete in **< 100 milliseconds** | Planned |
| **PR-WF-002** | Support **1,000+ concurrent workflow instances** | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-WF-001** | Support **100+ active workflow definitions** per tenant | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-WF-001** | Use **SQL for workflow definitions** with JSON configuration | Planned |
| **ARCH-WF-002** | Use **Redis Queue** for asynchronous workflow execution | Planned |
| **ARCH-WF-003** | Implement **state machine pattern** for workflow instance management | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-WF-001** | `WorkflowStartedEvent` | When workflow instance is created | Planned |
| **EV-WF-002** | `WorkflowApprovedEvent` | When approval step is completed | Planned |
| **EV-WF-003** | `WorkflowRejectedEvent` | When approval is rejected | Planned |
| **EV-WF-004** | `WorkflowEscalatedEvent` | When approval is escalated due to timeout | Planned |

---

## Technical Specifications

### Database Schema

**Workflow Definitions Table:**

```sql
CREATE TABLE workflow_definitions (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    workflow_code VARCHAR(100) NOT NULL,
    workflow_name VARCHAR(255) NOT NULL,
    workflow_type VARCHAR(50) NOT NULL,  -- 'purchase_order', 'sales_order', 'journal_entry', 'expense_report', 'custom'
    entity_type VARCHAR(255) NOT NULL,  -- Fully qualified class name of target entity
    routing_config JSONB NOT NULL,  -- Approval steps configuration
    conditions JSONB NULL,  -- Conditional routing rules
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE (tenant_id, workflow_code),
    INDEX idx_workflow_defs_tenant (tenant_id),
    INDEX idx_workflow_defs_type (workflow_type),
    INDEX idx_workflow_defs_entity (entity_type),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Workflow Instances Table:**

```sql
CREATE TABLE workflow_instances (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    workflow_definition_id BIGINT NOT NULL REFERENCES workflow_definitions(id),
    entity_id BIGINT NOT NULL,  -- ID of the entity being approved
    entity_type VARCHAR(255) NOT NULL,
    workflow_status VARCHAR(20) NOT NULL DEFAULT 'pending',  -- 'pending', 'approved', 'rejected', 'cancelled', 'escalated'
    current_step INT NOT NULL DEFAULT 1,
    total_steps INT NOT NULL,
    started_by BIGINT NOT NULL REFERENCES users(id),
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_workflow_instances_tenant (tenant_id),
    INDEX idx_workflow_instances_definition (workflow_definition_id),
    INDEX idx_workflow_instances_entity (entity_type, entity_id),
    INDEX idx_workflow_instances_status (workflow_status),
    INDEX idx_workflow_instances_started_by (started_by),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Workflow Steps Table:**

```sql
CREATE TABLE workflow_steps (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    workflow_instance_id BIGINT NOT NULL REFERENCES workflow_instances(id) ON DELETE CASCADE,
    step_number INT NOT NULL,
    step_type VARCHAR(20) NOT NULL DEFAULT 'approval',  -- 'approval', 'notification', 'action'
    step_config JSONB NOT NULL,  -- Approver(s), routing type (sequential, parallel)
    step_status VARCHAR(20) NOT NULL DEFAULT 'pending',  -- 'pending', 'approved', 'rejected', 'skipped', 'escalated'
    assigned_to BIGINT NULL REFERENCES users(id),  -- Current approver
    approved_by BIGINT NULL REFERENCES users(id),
    approval_comments TEXT NULL,
    escalation_deadline TIMESTAMP NULL,
    escalated_to BIGINT NULL REFERENCES users(id),
    escalated_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_workflow_steps_tenant (tenant_id),
    INDEX idx_workflow_steps_instance (workflow_instance_id),
    INDEX idx_workflow_steps_assigned (assigned_to),
    INDEX idx_workflow_steps_status (step_status),
    INDEX idx_workflow_steps_deadline (escalation_deadline),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Approval Actions Table:**

```sql
CREATE TABLE approval_actions (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    workflow_instance_id BIGINT NOT NULL REFERENCES workflow_instances(id),
    workflow_step_id BIGINT NOT NULL REFERENCES workflow_steps(id),
    action_type VARCHAR(20) NOT NULL,  -- 'approve', 'reject', 'delegate', 'recall', 'cancel'
    action_comments TEXT NULL,
    attachments JSONB NULL,
    delegated_from BIGINT NULL REFERENCES users(id),
    delegated_to BIGINT NULL REFERENCES users(id),
    delegation_expires_at TIMESTAMP NULL,
    performed_by BIGINT NOT NULL REFERENCES users(id),
    performed_at TIMESTAMP NOT NULL,
    
    INDEX idx_approval_actions_tenant (tenant_id),
    INDEX idx_approval_actions_instance (workflow_instance_id),
    INDEX idx_approval_actions_step (workflow_step_id),
    INDEX idx_approval_actions_user (performed_by),
    INDEX idx_approval_actions_date (performed_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Escalation Rules Table:**

```sql
CREATE TABLE escalation_rules (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    workflow_definition_id BIGINT NOT NULL REFERENCES workflow_definitions(id),
    step_number INT NOT NULL,
    escalation_hours INT NOT NULL,  -- Hours before escalation
    escalate_to BIGINT NOT NULL REFERENCES users(id),  -- User or role to escalate to
    notification_template_id BIGINT NULL,  -- Reference to notification template
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_escalation_rules_tenant (tenant_id),
    INDEX idx_escalation_rules_definition (workflow_definition_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Delegation Rules Table:**

```sql
CREATE TABLE delegation_rules (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    delegator_id BIGINT NOT NULL REFERENCES users(id),  -- User delegating authority
    delegate_id BIGINT NOT NULL REFERENCES users(id),  -- User receiving authority
    workflow_type VARCHAR(50) NULL,  -- Specific workflow type or NULL for all
    delegation_reason TEXT NULL,
    valid_from TIMESTAMP NOT NULL,
    valid_until TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_delegation_rules_tenant (tenant_id),
    INDEX idx_delegation_rules_delegator (delegator_id),
    INDEX idx_delegation_rules_delegate (delegate_id),
    INDEX idx_delegation_rules_dates (valid_from, valid_until),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Workflow Templates Table:**

```sql
CREATE TABLE workflow_templates (
    id BIGSERIAL PRIMARY KEY,
    template_code VARCHAR(100) NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    template_type VARCHAR(50) NOT NULL,
    template_config JSONB NOT NULL,
    is_system BOOLEAN DEFAULT FALSE,  -- System templates cannot be deleted
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (template_code),
    INDEX idx_workflow_templates_type (template_type)
);
```

### API Endpoints

All endpoints follow the RESTful pattern under `/api/v1/workflows/`:

**Workflow Definitions:**
- `GET /api/v1/workflows/definitions` - List workflow definitions
- `POST /api/v1/workflows/definitions` - Create workflow definition
- `GET /api/v1/workflows/definitions/{id}` - Get definition details
- `PATCH /api/v1/workflows/definitions/{id}` - Update definition
- `DELETE /api/v1/workflows/definitions/{id}` - Delete definition

**Workflow Templates:**
- `GET /api/v1/workflows/templates` - List available templates
- `POST /api/v1/workflows/templates` - Create custom template
- `GET /api/v1/workflows/templates/{id}` - Get template details

**Workflow Instances:**
- `POST /api/v1/workflows/instances` - Start workflow instance
- `GET /api/v1/workflows/instances/{id}` - Get instance details
- `GET /api/v1/workflows/instances/{id}/status` - Get current status
- `POST /api/v1/workflows/instances/{id}/cancel` - Cancel workflow

**Approvals (Workflow Inbox):**
- `GET /api/v1/workflows/inbox` - Get pending approvals for current user
- `GET /api/v1/workflows/inbox/count` - Get count of pending approvals
- `POST /api/v1/workflows/approve/{stepId}` - Approve step
- `POST /api/v1/workflows/reject/{stepId}` - Reject step
- `POST /api/v1/workflows/delegate/{stepId}` - Delegate approval

**Escalations:**
- `GET /api/v1/workflows/escalation-rules` - List escalation rules
- `POST /api/v1/workflows/escalation-rules` - Create escalation rule
- `POST /api/v1/workflows/escalate/{stepId}` - Manual escalation

**Delegations:**
- `GET /api/v1/workflows/delegations` - List delegation rules
- `POST /api/v1/workflows/delegations` - Create delegation rule
- `DELETE /api/v1/workflows/delegations/{id}` - Remove delegation

**History & Reporting:**
- `GET /api/v1/workflows/history` - Get workflow history
- `GET /api/v1/workflows/instances/{id}/timeline` - Get instance timeline
- `GET /api/v1/workflows/reports/approval-times` - Approval time analytics

### Events

**Domain Events Emitted:**

```php
namespace Nexus\Erp\WorkflowEngine\Events;

class WorkflowStartedEvent
{
    public function __construct(
        public readonly WorkflowInstance $instance,
        public readonly Model $entity,
        public readonly User $startedBy
    ) {}
}

class WorkflowApprovedEvent
{
    public function __construct(
        public readonly WorkflowInstance $instance,
        public readonly WorkflowStep $step,
        public readonly User $approvedBy,
        public readonly ?string $comments
    ) {}
}

class WorkflowRejectedEvent
{
    public function __construct(
        public readonly WorkflowInstance $instance,
        public readonly WorkflowStep $step,
        public readonly User $rejectedBy,
        public readonly string $reason
    ) {}
}

class WorkflowEscalatedEvent
{
    public function __construct(
        public readonly WorkflowInstance $instance,
        public readonly WorkflowStep $step,
        public readonly User $escalatedTo,
        public readonly string $reason
    ) {}
}
```

### Event Listeners

**Events from Other Modules:**

This module listens to:
- `PurchaseOrderCreatedEvent` (SUB16) - Start PO approval workflow
- `SalesOrderCreatedEvent` (SUB17) - Start SO approval workflow
- `JournalEntryCreatedEvent` (SUB08) - Start JE approval workflow
- Any transactional entity creation events that require approval

---

## Implementation Plans

**Note:** Implementation plans follow the naming convention `PLAN{number}-implement-{component}.md`

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN21-implement-workflow-engine.md | FR-WF-001 to FR-WF-008, BR-WF-001 to BR-WF-003 | MILESTONE 10 | Not Started |

**Implementation plan will be created separately using:** `.github/prompts/create-implementation-plan.prompt.md`

---

## Acceptance Criteria

### Functional Acceptance

- [ ] Visual workflow designer functional for creating approval chains
- [ ] Multi-level approval routing working (parallel and sequential flows)
- [ ] Conditional routing based on transaction amount, type, or rules operational
- [ ] Escalation rules for overdue approvals functional
- [ ] Delegation of approval authority working
- [ ] Workflow status tracking with real-time visualization operational
- [ ] Workflow templates available for common patterns
- [ ] Workflow inbox for pending approvals functional

### Technical Acceptance

- [ ] All API endpoints return correct responses per OpenAPI spec
- [ ] Workflow routing decision completes in < 100ms (PR-WF-001)
- [ ] System supports 1,000+ concurrent workflow instances (PR-WF-002)
- [ ] System supports 100+ active workflow definitions per tenant (SCR-WF-001)
- [ ] SQL with JSON configuration functional (ARCH-WF-001)
- [ ] Redis Queue for async execution operational (ARCH-WF-002)
- [ ] State machine pattern implemented (ARCH-WF-003)

### Security Acceptance

- [ ] Role-based approval authority with amount limits enforced (SR-WF-001)
- [ ] All approval actions audit logged with full traceability (SR-WF-002)

### Integration Acceptance

- [ ] Integration with all transactional modules functional (IR-WF-001)
- [ ] Integration with Notification system working (IR-WF-002)
- [ ] External workflow triggers via API operational (IR-WF-003)

---

## Testing Strategy

### Unit Tests

**Test Coverage Requirements:** Minimum 80% code coverage

**Key Test Areas:**
- Workflow routing logic (sequential, parallel, conditional)
- Escalation calculation and execution
- Delegation authority validation
- State machine transitions
- Business rule enforcement (sequential order, no self-approval)

**Example Tests:**
```php
test('sequential approval enforces order', function () {
    $workflow = WorkflowDefinition::factory()->sequential()->create();
    $instance = WorkflowInstance::factory()->create([
        'workflow_definition_id' => $workflow->id,
        'current_step' => 1,
    ]);
    
    // Try to approve step 2 before step 1
    $step2 = WorkflowStep::factory()->create([
        'workflow_instance_id' => $instance->id,
        'step_number' => 2,
        'step_status' => 'pending',
    ]);
    
    $result = ApproveStepAction::run($step2, auth()->user(), 'Approved');
    
    expect($result)->toBeFalse();
    expect($step2->fresh()->step_status)->toBe('pending');
});

test('approver cannot approve own submission', function () {
    $user = User::factory()->create();
    $instance = WorkflowInstance::factory()->create(['started_by' => $user->id]);
    $step = WorkflowStep::factory()->create([
        'workflow_instance_id' => $instance->id,
        'assigned_to' => $user->id,
    ]);
    
    expect(fn () => ApproveStepAction::run($step, $user, 'Approved'))
        ->toThrow(BusinessRuleException::class, 'Cannot approve own submission');
});
```

### Feature Tests

**API Integration Tests:**
- Start workflow instance via API
- Approve/reject steps via API
- Delegate approval via API
- Check workflow inbox via API

**Example Tests:**
```php
test('can approve workflow step via API', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $instance = WorkflowInstance::factory()->create(['tenant_id' => $tenant->id]);
    $step = WorkflowStep::factory()->pending()->create([
        'workflow_instance_id' => $instance->id,
        'assigned_to' => $user->id,
    ]);
    
    $response = $this->actingAs($user)
        ->postJson("/api/v1/workflows/approve/{$step->id}", [
            'comments' => 'Approved by manager',
        ]);
    
    $response->assertOk();
    expect($step->fresh()->step_status)->toBe('approved');
    expect($step->fresh()->approved_by)->toBe($user->id);
});
```

### Integration Tests

**Cross-Module Integration:**
- PO creation triggers workflow
- Workflow approval posts GL entry
- Workflow rejection sends notification
- Escalation sends email notification

### Performance Tests

**Load Testing Scenarios:**
- Workflow routing decision: < 100ms (PR-WF-001)
- 1,000+ concurrent workflow instances (PR-WF-002)
- 100+ active workflow definitions per tenant
- Multiple users accessing inbox simultaneously

---

## Dependencies

### Feature Module Dependencies

**From Master PRD Section D.2.1:**

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for workflows
- **SUB02 (Authentication & Authorization)** - User approval rights
- **SUB03 (Audit Logging)** - Track approval actions
- **SUB22 (Notifications & Events)** - Notify approvers of pending tasks

**Optional Dependencies:**
- All transactional modules (SUB08, SUB11, SUB12, SUB16, SUB17, etc.) can use workflows
- **SUB15 (Backoffice)** - Department and role-based routing

### External Package Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "azaharizaman/erp-core": "^1.0",
    "lorisleiva/laravel-actions": "^2.0"
  },
  "require-dev": {
    "pestphp/pest": "^4.0"
  }
}
```

### Infrastructure Dependencies

- **Database:** PostgreSQL 14+ (for JSONB workflow configuration)
- **Cache:** Redis 6+ (for workflow state caching)
- **Queue:** Redis or database queue driver (for async workflow execution)

---

## Feature Module Structure

### Directory Structure (in Monorepo)

```
packages/workflow-engine/
├── src/
│   ├── Actions/
│   │   ├── StartWorkflowAction.php
│   │   ├── ApproveStepAction.php
│   │   ├── RejectStepAction.php
│   │   ├── DelegateApprovalAction.php
│   │   └── EscalateStepAction.php
│   ├── Contracts/
│   │   ├── WorkflowEngineServiceContract.php
│   │   └── RoutingServiceContract.php
│   ├── Events/
│   │   ├── WorkflowStartedEvent.php
│   │   ├── WorkflowApprovedEvent.php
│   │   ├── WorkflowRejectedEvent.php
│   │   └── WorkflowEscalatedEvent.php
│   ├── Listeners/
│   │   ├── StartWorkflowOnEntityCreatedListener.php
│   │   ├── SendApprovalNotificationListener.php
│   │   └── ProcessEscalationListener.php
│   ├── Models/
│   │   ├── WorkflowDefinition.php
│   │   ├── WorkflowInstance.php
│   │   ├── WorkflowStep.php
│   │   ├── ApprovalAction.php
│   │   ├── EscalationRule.php
│   │   ├── DelegationRule.php
│   │   └── WorkflowTemplate.php
│   ├── Observers/
│   │   └── WorkflowInstanceObserver.php
│   ├── Policies/
│   │   └── WorkflowDefinitionPolicy.php
│   ├── Repositories/
│   │   └── WorkflowDefinitionRepository.php
│   ├── Services/
│   │   ├── WorkflowEngineService.php
│   │   ├── RoutingService.php
│   │   ├── EscalationService.php
│   │   └── DelegationService.php
│   ├── StateMachines/
│   │   └── WorkflowStateMachine.php
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Resources/
│   └── WorkflowEngineServiceProvider.php
├── tests/
│   ├── Feature/
│   │   ├── WorkflowDefinitionTest.php
│   │   ├── ApprovalFlowTest.php
│   │   └── EscalationTest.php
│   └── Unit/
│       ├── RoutingLogicTest.php
│       └── StateMachineTest.php
├── database/
│   ├── migrations/
│   │   ├── 2025_01_01_000001_create_workflow_definitions_table.php
│   │   ├── 2025_01_01_000002_create_workflow_instances_table.php
│   │   ├── 2025_01_01_000003_create_workflow_steps_table.php
│   │   ├── 2025_01_01_000004_create_approval_actions_table.php
│   │   ├── 2025_01_01_000005_create_escalation_rules_table.php
│   │   ├── 2025_01_01_000006_create_delegation_rules_table.php
│   │   └── 2025_01_01_000007_create_workflow_templates_table.php
│   └── factories/
│       ├── WorkflowDefinitionFactory.php
│       └── WorkflowInstanceFactory.php
├── routes/
│   └── api.php
├── config/
│   └── workflow-engine.php
├── composer.json
└── README.md
```

---

## Migration Path

This is a new module with no existing functionality to migrate from.

**Initial Setup:**
1. Install package via Composer
2. Publish migrations and run `php artisan migrate`
3. Seed workflow templates for common approval patterns
4. Configure workflow definitions for each transaction type
5. Set up escalation and delegation rules
6. Train users on workflow inbox and approval process

---

## Success Metrics

From Master PRD Section B.3:

**Adoption Metrics:**
- Workflow automation coverage > 90% of approval processes
- User adoption of workflow inbox > 80%
- Workflow template usage > 60% of definitions

**Performance Metrics:**
- Workflow routing decision time < 100ms (PR-WF-001)
- Support 1,000+ concurrent instances (PR-WF-002)

**Efficiency Metrics:**
- Approval cycle time reduction > 50%
- Escalation rate < 10% of workflows
- On-time approval rate > 90%

**Operational Metrics:**
- Average approval time reduction > 60%
- Workflow completion rate > 95%

---

## Assumptions & Constraints

### Assumptions

1. Users have defined approval authority and hierarchy
2. Notification system operational for approval alerts
3. Transactional modules support workflow integration
4. Users trained on workflow designer and approval process
5. Business rules for approval routing clearly defined

### Constraints

1. Approvals must be executed in sequential order unless parallel routing enabled
2. Approvers cannot approve their own submissions
3. Escalations occur automatically when approval deadlines exceeded
4. System supports 100+ active workflow definitions per tenant
5. Workflow routing decision must complete in < 100ms

---

## Monorepo Integration

### Development

- Lives in `/packages/workflow-engine/` during development
- Main app uses Composer path repository to require locally:
  ```json
  {
    "repositories": [
      {
        "type": "path",
        "url": "./packages/workflow-engine"
      }
    ],
    "require": {
      "azaharizaman/erp-workflow-engine": "@dev"
    }
  }
  ```
- All changes committed to monorepo

### Release (v1.0)

- Tagged with monorepo version (e.g., v1.0.0)
- Published to Packagist as `azaharizaman/erp-workflow-engine`
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
2. Create implementation plan: `PLAN21-implement-workflow-engine.md` in `/docs/plan/`
3. Break down into GitHub issues
4. Assign to MILESTONE 10 from Master PRD Section F.2.4
5. Set up feature module structure in `/packages/workflow-engine/`
