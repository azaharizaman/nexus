---
plan: Implement Backoffice Approval Workflows & Document Numbering
version: 1.0.0
date_created: 2025-11-13
last_updated: 2025-11-13
owner: Development Team
status: Planned
tags: [feature, backoffice, workflow, approval, document-numbering, automation, business-logic]
---

# PRD01-SUB15-PLAN03: Implement Backoffice Approval Workflows & Document Numbering

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

**Related PRD:** [PRD01-SUB15-BACKOFFICE.md](../prd/prd-01/PRD01-SUB15-BACKOFFICE.md)  
**Previous Plans:**
- [PRD01-SUB15-PLAN01-implement-organizational-foundation.md](./PRD01-SUB15-PLAN01-implement-organizational-foundation.md)
- [PRD01-SUB15-PLAN02-implement-fiscal-period-management.md](./PRD01-SUB15-PLAN02-implement-fiscal-period-management.md)

**Plan Type:** Implementation Plan  
**Version:** 1.0.0  
**Created:** November 13, 2025  
**Milestone:** MILESTONE 3

---

## Introduction

This implementation plan covers approval workflows with multi-level approvers and document numbering sequences per organizational entity. These features enable automated approval routing for transactional documents (purchase orders, sales orders, expense claims) and ensure unique, sequential document numbering across the ERP system.

**Key Features Delivered:**
- Approval workflow engine with configurable multi-level approvers
- Document type-specific workflow rules with threshold-based routing
- Approval delegation and substitution mechanisms
- Document numbering sequence management per entity (company, branch, department)
- Auto-increment with padding and prefix configuration
- Concurrent number generation with database locking
- Workflow approval history and audit trail

**Business Impact:**
- Automates document approval routing based on business rules
- Enforces authorization controls for financial transactions
- Provides audit trail for all approval decisions
- Ensures unique document numbering across all modules
- Supports multi-entity operations with entity-specific numbering
- Enables flexible approval hierarchies and delegation

---

## 1. Requirements & Constraints

### Functional Requirements

- **FR-BO-007**: Define approval workflows with multi-level approvers and delegation rules
- **FR-BO-008**: Manage document numbering sequences per entity (company, branch, department)

### Business Rules

- **BR-BO-004**: Organizational entities with active transactions cannot be deleted (applies to workflow configurations)

### Data Requirements

- **DR-BO-003**: Record approval workflow history with timestamps and approver details

### Integration Requirements

- **IR-BO-001**: Integrate with all transactional modules for workflow enforcement
- **IR-BO-002**: Provide organizational hierarchy API for authorization and reporting (used in approval routing)

### Security Requirements

- **SR-BO-001**: Implement role-based access to workflow configuration
- **SR-BO-002**: Log all administrative actions (workflow creation, approval decisions)

### Coding Guidelines

- **GUD-001**: All PHP files must have `declare(strict_types=1);`
- **GUD-002**: All methods must have parameter type hints and return type declarations
- **GUD-003**: Use repository pattern for all data access
- **GUD-004**: Use Laravel Actions pattern for all business operations
- **GUD-005**: All public/protected methods must have complete PHPDoc blocks

### Design Patterns

- **PAT-001**: Repository pattern with contracts for all data access
- **PAT-002**: Laravel Actions (AsAction trait) for workflow operations
- **PAT-003**: Strategy pattern for workflow rule evaluation
- **PAT-004**: Observer pattern for workflow state transitions
- **PAT-005**: Factory pattern for document number generation

### Constraints

- **CON-001**: Approval workflows cannot be deleted if they have pending approvals
- **CON-002**: Document number sequences must be unique per entity and document type
- **CON-003**: Document numbers cannot be reused once generated
- **CON-004**: Workflow rules must support threshold-based routing (e.g., amount > $10,000)
- **CON-005**: Number generation must handle concurrent requests without duplicates

---

## 2. Implementation Steps

### GOAL-001: Approval Workflow Foundation

**Objective:** Implement approval workflow data model, workflow configuration with JSON rules, and multi-level approver assignment capability.

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| FR-BO-007 | Define approval workflows with multi-level approvers | Functional |
| DR-BO-003 | Record approval workflow history | Data |
| SR-BO-001 | Role-based access to workflow configuration | Security |
| SR-BO-002 | Log all administrative actions | Security |

**Tasks:**

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-1.1 | Create migration `2025_01_01_000007_create_approval_workflows_table.php` with columns: id (BIGSERIAL), tenant_id (UUID, FK), workflow_name (VARCHAR 255), document_type (VARCHAR 100: 'purchase_order', 'sales_order', 'expense_claim', 'payment_request'), approval_levels (INT: number of approval levels required), rules (JSONB: approval routing rules), is_active (BOOLEAN, default TRUE), timestamps; indexes: tenant_id, document_type; UNIQUE constraint (tenant_id, workflow_name) | | |
| TASK-1.2 | Create enum `DocumentType` with values: PURCHASE_ORDER, SALES_ORDER, EXPENSE_CLAIM, PAYMENT_REQUEST, JOURNAL_ENTRY, INVOICE, RECEIPT | | |
| TASK-1.3 | Create enum `ApprovalStatus` with values: PENDING, APPROVED, REJECTED, CANCELLED | | |
| TASK-1.4 | Create model `packages/backoffice/src/Models/ApprovalWorkflow.php` with traits: BelongsToTenant, HasActivityLogging, SoftDeletes; fillable: workflow_name, document_type, approval_levels, rules, is_active; casts: document_type → DocumentType enum, rules → array, approval_levels → int, is_active → boolean; relationships: approvers (hasManyThrough WorkflowApprover), approvalHistory (hasMany ApprovalHistory); scopes: active(), forDocumentType(DocumentType $type); methods: evaluateRules(array $documentData): bool - check if document matches workflow rules | | |
| TASK-1.5 | Create migration `2025_01_01_000008_create_workflow_approvers_table.php` with columns: id (BIGSERIAL), tenant_id (UUID, FK), workflow_id (BIGINT, FK approval_workflows), level (INT: approval level 1, 2, 3, etc.), approver_type (VARCHAR 50: 'user', 'role', 'manager'), approver_id (UUID nullable: user_id if type='user'), approver_role (VARCHAR 100 nullable: role name if type='role'), conditions (JSONB nullable: conditions for this approver), timestamps; indexes: tenant_id, workflow_id, level | | |
| TASK-1.6 | Create model `WorkflowApprover.php` with traits: BelongsToTenant; fillable: level, approver_type, approver_id, approver_role, conditions; casts: conditions → array, level → int; relationships: workflow (belongsTo ApprovalWorkflow), approverUser (belongsTo User); methods: resolveApprover(): Collection - resolve actual users based on type (direct user, role members, or manager) | | |
| TASK-1.7 | Create factory `ApprovalWorkflowFactory.php` with faker data: workflow_name, document_type, approval_levels = 2, rules = sample threshold rules, is_active = true; states: withApprovers(int $levels), forPurchaseOrders(), withAmountThreshold(float $amount) | | |
| TASK-1.8 | Create contract `ApprovalWorkflowRepositoryContract.php` with methods: findById(int $id): ?ApprovalWorkflow, create(array $data): ApprovalWorkflow, update(ApprovalWorkflow $workflow, array $data): ApprovalWorkflow, delete(ApprovalWorkflow $workflow): bool, getActiveWorkflows(string $tenantId): Collection, getByDocumentType(DocumentType $type): Collection, findMatchingWorkflow(DocumentType $type, array $documentData): ?ApprovalWorkflow, hasPendingApprovals(int $workflowId): bool, paginate(int $perPage, array $filters): LengthAwarePaginator | | |
| TASK-1.9 | Implement repository `ApprovalWorkflowRepository.php` implementing contract; implement findMatchingWorkflow() by querying active workflows for document type, then evaluating rules using evaluateRules() method; prioritize by most specific rules first | | |
| TASK-1.10 | Create action `CreateApprovalWorkflowAction.php` using AsAction; inject ApprovalWorkflowRepositoryContract, ActivityLoggerContract; handle(array $data): ApprovalWorkflow; validation: check user hasPermission('manage-workflows'); validate rules JSON structure; create workflow; log activity "Approval workflow created"; dispatch WorkflowCreatedEvent; return workflow | | |
| TASK-1.11 | Create action `UpdateApprovalWorkflowAction.php` with validation: check no pending approvals using workflow; update workflow; log activity; dispatch WorkflowUpdatedEvent | | |
| TASK-1.12 | Create action `DeleteApprovalWorkflowAction.php` with validation: check hasPendingApprovals(); if true, throw ValidationException "Cannot delete workflow with pending approvals"; soft delete; log activity | | |
| TASK-1.13 | Create action `AddWorkflowApproverAction.php` to add approver to specific level; validate level <= workflow->approval_levels; create WorkflowApprover record; log activity | | |
| TASK-1.14 | Create event `WorkflowCreatedEvent` with properties: ApprovalWorkflow $workflow, User $createdBy | | |
| TASK-1.15 | Create event `WorkflowUpdatedEvent` with properties: ApprovalWorkflow $workflow, array $changes, User $updatedBy | | |
| TASK-1.16 | Create policy `ApprovalWorkflowPolicy.php` with methods: viewAny, view, create, update, delete; require 'manage-workflows' permission; apply tenant scope | | |
| TASK-1.17 | Create API controller `ApprovalWorkflowController.php` with routes: index (GET /backoffice/workflows), store (POST /backoffice/workflows), show (GET /backoffice/workflows/{id}), update (PATCH /backoffice/workflows/{id}), destroy (DELETE /backoffice/workflows/{id}); inject ApprovalWorkflowRepositoryContract | | |
| TASK-1.18 | Create form request `StoreApprovalWorkflowRequest.php` with validation: workflow_name (required, unique per tenant), document_type (required, in DocumentType values), approval_levels (required, int, min:1, max:10), rules (required, array), is_active (boolean) | | |
| TASK-1.19 | Create form request `AddApproverRequest.php` with validation: level (required, int, min:1), approver_type (required, in: user, role, manager), approver_id (required_if:approver_type,user, exists:users), approver_role (required_if:approver_type,role, string) | | |
| TASK-1.20 | Create API resource `ApprovalWorkflowResource.php` with fields: id, workflow_name, document_type, approval_levels, rules, approvers (nested collection), is_active, timestamps | | |
| TASK-1.21 | Create API resource `WorkflowApproverResource.php` with fields: id, level, approver_type, approver (resolved user/role details), conditions | | |
| TASK-1.22 | Write unit test `ApprovalWorkflowTest.php`: test workflow factory, test evaluateRules() matches documents correctly, test relationships, test cannot delete with pending approvals | | |
| TASK-1.23 | Write unit test `CreateApprovalWorkflowActionTest.php`: mock repository; test creates workflow; test validates rules structure; test throws AuthorizationException for unauthorized users | | |
| TASK-1.24 | Write feature test `ApprovalWorkflowManagementTest.php`: test complete CRUD via API; test add approvers to workflow; test authorization checks; test validation errors | | |

**Test Coverage:** 24 tests (10 unit, 14 feature)

---

### GOAL-002: Approval Process Execution Engine

**Objective:** Implement approval process execution, approval routing based on workflow rules, approval/rejection actions, and delegation mechanism.

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| FR-BO-007 | Approval workflows with multi-level approvers and delegation | Functional |
| DR-BO-003 | Record approval workflow history | Data |
| IR-BO-001 | Integrate with transactional modules for workflow enforcement | Integration |
| SR-BO-002 | Log all administrative actions | Security |

**Tasks:**

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-2.1 | Create migration `2025_01_01_000009_create_approval_requests_table.php` with columns: id (BIGSERIAL), tenant_id (UUID, FK), workflow_id (BIGINT, FK approval_workflows), document_type (VARCHAR 100), document_id (BIGINT: polymorphic ID of document being approved), document_reference (VARCHAR 100: PO number, SO number, etc.), requester_id (UUID, FK users), current_level (INT: current approval level), status (VARCHAR 20: 'pending', 'approved', 'rejected', 'cancelled'), submitted_at (TIMESTAMP), completed_at (TIMESTAMP nullable), timestamps; indexes: tenant_id, workflow_id, document_type, document_id, status | | |
| TASK-2.2 | Create migration `2025_01_01_000010_create_approval_history_table.php` with columns: id (BIGSERIAL), tenant_id (UUID, FK), approval_request_id (BIGINT, FK approval_requests), level (INT: which approval level), approver_id (UUID, FK users), action (VARCHAR 20: 'approved', 'rejected', 'delegated'), comments (TEXT nullable), acted_at (TIMESTAMP), delegated_to (UUID nullable, FK users), timestamps; indexes: tenant_id, approval_request_id, approver_id | | |
| TASK-2.3 | Create model `ApprovalRequest.php` with traits: BelongsToTenant, HasActivityLogging; fillable: workflow_id, document_type, document_id, document_reference, requester_id, current_level, status, submitted_at, completed_at; casts: status → ApprovalStatus enum, submitted_at → datetime, completed_at → datetime, current_level → int; relationships: workflow (belongsTo ApprovalWorkflow), requester (belongsTo User), history (hasMany ApprovalHistory), document (morphTo); scopes: pending(), approved(), rejected(), forUser(User $user); methods: getCurrentApprovers(): Collection, isApprovedByUser(User $user): bool, canUserApprove(User $user): bool, advance(): void - move to next approval level | | |
| TASK-2.4 | Create model `ApprovalHistory.php` with traits: BelongsToTenant; fillable: level, approver_id, action, comments, acted_at, delegated_to; casts: acted_at → datetime, level → int; relationships: approvalRequest (belongsTo), approver (belongsTo User), delegatedTo (belongsTo User) | | |
| TASK-2.5 | Create factory `ApprovalRequestFactory.php` with faker data; states: pending(), approved(), rejected(), atLevel(int $level), withHistory(int $count) | | |
| TASK-2.6 | Create contract `ApprovalRequestRepositoryContract.php` with methods: findById(int $id): ?ApprovalRequest, create(array $data): ApprovalRequest, update(ApprovalRequest $request, array $data): ApprovalRequest, getPendingForUser(User $user): Collection, getByDocument(string $documentType, int $documentId): ?ApprovalRequest, getPendingRequests(string $tenantId): Collection, paginate(int $perPage, array $filters): LengthAwarePaginator | | |
| TASK-2.7 | Implement repository `ApprovalRequestRepository.php` implementing contract; implement getPendingForUser() by joining with WorkflowApprover to find requests where user is approver at current level | | |
| TASK-2.8 | Create action `SubmitForApprovalAction.php` using AsAction; inject ApprovalWorkflowRepositoryContract, ApprovalRequestRepositoryContract, ActivityLoggerContract; handle(string $documentType, int $documentId, array $documentData, User $requester): ApprovalRequest; find matching workflow using repository->findMatchingWorkflow(); create ApprovalRequest with current_level = 1, status = PENDING; dispatch ApprovalRequestSubmittedEvent; send notification to level 1 approvers; log activity "Document submitted for approval"; return approval request | | |
| TASK-2.9 | Create action `ApproveRequestAction.php` using AsAction; inject ApprovalRequestRepositoryContract, ActivityLoggerContract; handle(ApprovalRequest $request, User $approver, ?string $comments = null): ApprovalRequest; validation: verify user is approver at current level using canUserApprove(); create ApprovalHistory record with action='approved'; if current_level < workflow->approval_levels, call request->advance() to move to next level and notify next approvers; else set status = APPROVED, completed_at = now(), dispatch ApprovalRequestCompletedEvent; log activity "Approval request approved"; return request | | |
| TASK-2.10 | Create action `RejectRequestAction.php` using AsAction; similar to ApproveRequestAction but sets status = REJECTED, completed_at = now(); create ApprovalHistory with action='rejected'; dispatch ApprovalRequestRejectedEvent; notify requester; log activity | | |
| TASK-2.11 | Create action `DelegateApprovalAction.php` using AsAction; handle(ApprovalRequest $request, User $fromUser, User $toUser, ?string $reason = null): void; validation: verify fromUser is current approver; create ApprovalHistory with action='delegated', delegated_to = toUser; update WorkflowApprover temporarily to add toUser for this request; send notification to toUser; log activity "Approval delegated" | | |
| TASK-2.12 | Create action `CancelApprovalRequestAction.php` using AsAction; handle(ApprovalRequest $request, User $user): ApprovalRequest; validation: verify user is requester or has 'cancel-approvals' permission; set status = CANCELLED; dispatch ApprovalRequestCancelledEvent; log activity | | |
| TASK-2.13 | Create event `ApprovalRequestSubmittedEvent` with properties: ApprovalRequest $request, User $submitter | | |
| TASK-2.14 | Create event `ApprovalRequestApprovedEvent` with properties: ApprovalRequest $request, User $approver, int $level | | |
| TASK-2.15 | Create event `ApprovalRequestRejectedEvent` with properties: ApprovalRequest $request, User $rejector, ?string $comments | | |
| TASK-2.16 | Create event `ApprovalRequestCompletedEvent` with properties: ApprovalRequest $request (fully approved) | | |
| TASK-2.17 | Create event `ApprovalRequestCancelledEvent` with properties: ApprovalRequest $request, User $cancelledBy | | |
| TASK-2.18 | Create event `ApprovalDelegatedEvent` with properties: ApprovalRequest $request, User $from, User $to | | |
| TASK-2.19 | Create listener `NotifyApproversListener.php` listening to ApprovalRequestSubmittedEvent, ApprovalRequestApprovedEvent (for next level); handle() sends email/notification to approvers at current level | | |
| TASK-2.20 | Create listener `NotifyRequesterOnCompletionListener.php` listening to ApprovalRequestCompletedEvent, ApprovalRequestRejectedEvent; notify requester of final decision | | |
| TASK-2.21 | Create policy `ApprovalRequestPolicy.php` with methods: view (can view own requests or requests they can approve), approve (check canUserApprove()), reject, delegate, cancel | | |
| TASK-2.22 | Create API controller `ApprovalRequestController.php` with routes: index (GET /backoffice/approval-requests), show (GET /backoffice/approval-requests/{id}), myApprovals (GET /backoffice/approval-requests/my-approvals), approve (POST /backoffice/approval-requests/{id}/approve), reject (POST /backoffice/approval-requests/{id}/reject), delegate (POST /backoffice/approval-requests/{id}/delegate), cancel (POST /backoffice/approval-requests/{id}/cancel) | | |
| TASK-2.23 | Create form request `ApproveRejectRequest.php` with validation: comments (nullable, string, max:1000) | | |
| TASK-2.24 | Create form request `DelegateApprovalRequest.php` with validation: delegate_to_user_id (required, exists:users), reason (nullable, string, max:500) | | |
| TASK-2.25 | Create API resource `ApprovalRequestResource.php` with fields: id, workflow (nested), document_type, document_reference, requester (user resource), current_level, status, submitted_at, completed_at, current_approvers (collection), history (collection), can_user_approve (boolean for current auth user) | | |
| TASK-2.26 | Create API resource `ApprovalHistoryResource.php` with fields: id, level, approver (user resource), action, comments, acted_at, delegated_to (user resource) | | |
| TASK-2.27 | Write unit test `ApprovalRequestTest.php`: test factory, test getCurrentApprovers() returns correct users, test canUserApprove() checks level correctly, test advance() increments level, test isApprovedByUser() | | |
| TASK-2.28 | Write unit test `SubmitForApprovalActionTest.php`: mock repositories; test finds matching workflow; test creates approval request at level 1; test dispatches event | | |
| TASK-2.29 | Write unit test `ApproveRequestActionTest.php`: test advances to next level when not final; test completes request when final level; test creates history record; test throws exception if user not approver | | |
| TASK-2.30 | Write feature test `ApprovalProcessTest.php`: test complete approval flow via API (submit, approve level 1, approve level 2, verify completed); test rejection flow; test delegation; test cancel; test my-approvals endpoint | | |

**Test Coverage:** 30 tests (12 unit, 18 feature)

---

### GOAL-003: Document Numbering Sequence Management

**Objective:** Implement document numbering sequence system with auto-increment, configurable prefix and padding, per-entity numbering, and concurrent generation support.

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| FR-BO-008 | Manage document numbering sequences per entity | Functional |
| IR-BO-001 | Integrate with transactional modules | Integration |
| SR-BO-002 | Log administrative actions | Security |

**Tasks:**

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-3.1 | Create migration `2025_01_01_000011_create_document_number_sequences_table.php` with columns: id (BIGSERIAL), tenant_id (UUID, FK), entity_type (VARCHAR 50: 'company', 'branch', 'department'), entity_id (BIGINT: ID of company/branch/department), document_type (VARCHAR 100: 'invoice', 'purchase_order', 'payment', etc.), prefix (VARCHAR 20: e.g., 'INV', 'PO', 'PAY'), next_number (INT: next number to generate), padding_length (INT: zero-padding length, e.g., 6 for 000001), timestamps; UNIQUE constraint (tenant_id, entity_type, entity_id, document_type); indexes: tenant_id, entity_type, entity_id, document_type | | |
| TASK-3.2 | Create enum `NumberSequenceEntity` with values: COMPANY, BRANCH, DEPARTMENT | | |
| TASK-3.3 | Create model `DocumentNumberSequence.php` with traits: BelongsToTenant, HasActivityLogging; fillable: entity_type, entity_id, document_type, prefix, next_number, padding_length; casts: entity_type → NumberSequenceEntity enum, next_number → int, padding_length → int; relationships: entity (morphTo - company/branch/department); methods: generateNext(): string - generate next document number with atomic increment, formatNumber(int $number): string - apply padding | | |
| TASK-3.4 | Create factory `DocumentNumberSequenceFactory.php` with faker data: entity_type, entity_id, document_type, prefix, next_number = 1, padding_length = 6; states: forInvoices(), forPurchaseOrders(), forPayments(), atNumber(int $number) | | |
| TASK-3.5 | Create contract `DocumentNumberSequenceRepositoryContract.php` with methods: findById(int $id): ?DocumentNumberSequence, create(array $data): DocumentNumberSequence, update(DocumentNumberSequence $sequence, array $data): DocumentNumberSequence, delete(DocumentNumberSequence $sequence): bool, getByEntity(string $entityType, int $entityId, string $documentType): ?DocumentNumberSequence, getOrCreate(string $entityType, int $entityId, string $documentType, string $defaultPrefix): DocumentNumberSequence, resetSequence(int $sequenceId, int $startNumber): void, paginate(int $perPage, array $filters): LengthAwarePaginator | | |
| TASK-3.6 | Implement repository `DocumentNumberSequenceRepository.php` implementing contract; implement getOrCreate() using firstOrCreate(); implement resetSequence() with validation (check no documents exist with numbers >= new start) | | |
| TASK-3.7 | Create service `DocumentNumberService.php` with methods: generateNumber(string $entityType, int $entityId, string $documentType, ?string $customPrefix = null): string - main method for number generation; getNextNumber(DocumentNumberSequence $sequence): string - atomic increment and format; configureSequence(string $entityType, int $entityId, string $documentType, string $prefix, int $paddingLength): DocumentNumberSequence - create/update sequence; validateNumberFormat(string $number, string $prefix): bool - validate number format | | |
| TASK-3.8 | Implement generateNumber() with database transaction and pessimistic locking: DB::transaction(fn() => { $sequence = repository->getOrCreate()->lockForUpdate(); $number = $sequence->generateNext(); $sequence->save(); return $number; }) to prevent concurrent duplicate numbers | | |
| TASK-3.9 | Implement formatNumber() in DocumentNumberSequence model: return $this->prefix . str_pad((string)$number, $this->padding_length, '0', STR_PAD_LEFT); example: prefix='INV', number=123, padding=6 → 'INV000123' | | |
| TASK-3.10 | Create action `ConfigureDocumentSequenceAction.php` using AsAction; inject DocumentNumberSequenceRepositoryContract, ActivityLoggerContract; handle(string $entityType, int $entityId, string $documentType, string $prefix, int $paddingLength): DocumentNumberSequence; validation: check user hasPermission('manage-document-sequences'); validate prefix unique per entity+docType; create/update sequence; log activity "Document sequence configured"; return sequence | | |
| TASK-3.11 | Create action `ResetDocumentSequenceAction.php` using AsAction; handle(DocumentNumberSequence $sequence, int $startNumber): void; validation: check user hasRole('admin'); verify no documents exist with numbers >= startNumber; update next_number; log activity "Document sequence reset" | | |
| TASK-3.12 | Create action `GenerateDocumentNumberAction.php` using AsAction; inject DocumentNumberService; handle(string $entityType, int $entityId, string $documentType): string; call service->generateNumber(); return formatted number | | |
| TASK-3.13 | Create helper function `generateDocumentNumber(string $entityType, int $entityId, string $documentType): string` as facade to GenerateDocumentNumberAction | | |
| TASK-3.14 | Create policy `DocumentNumberSequencePolicy.php` with methods: viewAny, view, create (require 'manage-document-sequences'), update, delete, reset (require admin role) | | |
| TASK-3.15 | Create API controller `DocumentNumberSequenceController.php` with routes: index (GET /backoffice/document-sequences), store (POST /backoffice/document-sequences), show (GET /backoffice/document-sequences/{id}), update (PATCH /backoffice/document-sequences/{id}), destroy (DELETE /backoffice/document-sequences/{id}), reset (POST /backoffice/document-sequences/{id}/reset), generate (POST /backoffice/document-sequences/generate) | | |
| TASK-3.16 | Create form request `StoreDocumentSequenceRequest.php` with validation: entity_type (required, in NumberSequenceEntity values), entity_id (required, int), document_type (required, string, max:100), prefix (required, string, max:20, unique per entity+docType), padding_length (required, int, min:3, max:12) | | |
| TASK-3.17 | Create form request `ResetSequenceRequest.php` with validation: start_number (required, int, min:1) | | |
| TASK-3.18 | Create form request `GenerateNumberRequest.php` with validation: entity_type (required), entity_id (required, int), document_type (required, string) | | |
| TASK-3.19 | Create API resource `DocumentNumberSequenceResource.php` with fields: id, entity_type, entity_id, entity (company/branch/dept details), document_type, prefix, next_number, padding_length, sample_next_number (formatted preview), timestamps | | |
| TASK-3.20 | Write unit test `DocumentNumberSequenceTest.php`: test factory, test formatNumber() applies padding correctly, test generateNext() increments next_number | | |
| TASK-3.21 | Write unit test `DocumentNumberServiceTest.php`: mock repository; test generateNumber() creates sequence if not exists; test formatting is correct; test pessimistic locking used | | |
| TASK-3.22 | Write unit test `ConfigureDocumentSequenceActionTest.php`: test creates sequence; test validates unique prefix per entity+docType; test throws AuthorizationException for unauthorized users | | |
| TASK-3.23 | Write feature test `DocumentNumberSequenceTest.php`: test CRUD via API; test generate endpoint returns unique numbers; test concurrent generation doesn't create duplicates; test reset sequence | | |
| TASK-3.24 | Write concurrency test: spawn 10 parallel requests to generate numbers for same entity+docType; verify all returned numbers are unique and sequential; verify next_number increments correctly | | |

**Test Coverage:** 24 tests (10 unit, 12 feature, 2 concurrency)

---

### GOAL-004: Integration with Transactional Modules

**Objective:** Implement integration contracts and listeners for transactional modules to enforce approval workflows and use document numbering.

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| IR-BO-001 | Integrate with all transactional modules | Integration |
| FR-BO-007 | Approval workflows | Functional |
| FR-BO-008 | Document numbering | Functional |

**Tasks:**

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-4.1 | Create trait `RequiresApproval.php` for models that need approval; add approval_request_id field, approvalRequest() relationship, submitForApproval() method, isApproved() method, isPendingApproval() method | | |
| TASK-4.2 | Create trait `HasDocumentNumber.php` for models with document numbers; add document_number field, generateDocumentNumber() method that calls DocumentNumberService; ensure number generated before model created | | |
| TASK-4.3 | Create contract `ApprovalableContract.php` with methods: getDocumentType(): string, getApprovalData(): array, isApprovalRequired(): bool | | |
| TASK-4.4 | Create contract `NumberableContract.php` with methods: getEntityType(): string, getEntityId(): int, getDocumentType(): string, setDocumentNumber(string $number): void | | |
| TASK-4.5 | Create listener `AutoGenerateDocumentNumberListener.php` listening to model creating events (PurchaseOrderCreatingEvent, SalesOrderCreatingEvent, etc.); handle() calls generateDocumentNumber() if model implements NumberableContract and document_number is null | | |
| TASK-4.6 | Create listener `EnforceApprovalWorkflowListener.php` listening to model created events (PurchaseOrderCreatedEvent, etc.); handle() checks if approval required using isApprovalRequired(); if true, call SubmitForApprovalAction; set model approval_status = 'pending_approval' | | |
| TASK-4.7 | Create listener `UpdateDocumentOnApprovalListener.php` listening to ApprovalRequestCompletedEvent; handle() updates source document status to 'approved'; may trigger further actions (e.g., create GL entries for approved PO) | | |
| TASK-4.8 | Create listener `UpdateDocumentOnRejectionListener.php` listening to ApprovalRequestRejectedEvent; handle() updates source document status to 'rejected'; notify relevant parties | | |
| TASK-4.9 | Create middleware `RequireApprovedDocumentMiddleware.php` for use in transactional operations; check if document has approval_request_id; if yes, verify approval_status = 'approved'; if not approved, return 403 Forbidden "Document requires approval" | | |
| TASK-4.10 | Create service `WorkflowIntegrationService.php` with methods: submitDocument(Model $document): ApprovalRequest - helper for submitting documents; checkApprovalStatus(Model $document): string - get current approval status; getApprovalHistory(Model $document): Collection - get approval history for document | | |
| TASK-4.11 | Update config `packages/backoffice/config/backoffice.php` add sections: 'workflows' => ['require_approval_for' => ['purchase_order' => ['threshold' => 10000], 'payment_request' => ['threshold' => 5000]]], 'document_numbering' => ['default_padding' => 6, 'allow_manual_numbers' => false] | | |
| TASK-4.12 | Create seeder `WorkflowSeeder.php` to seed sample approval workflows: PO approval (2 levels for amount > $10k), expense claim approval (manager + finance), payment request approval (CFO for > $50k); seed for development/testing | | |
| TASK-4.13 | Create seeder `DocumentSequenceSeeder.php` to seed sample document sequences for common document types: invoices (INV-), purchase orders (PO-), sales orders (SO-), payments (PAY-), receipts (RCT-) | | |
| TASK-4.14 | Write integration test `ApprovalWorkflowIntegrationTest.php`: create PO with amount > threshold; verify approval request auto-created; approve at all levels; verify PO status updated to approved; test rejection flow | | |
| TASK-4.15 | Write integration test `DocumentNumberingIntegrationTest.php`: create PO; verify document_number auto-generated; create multiple POs; verify sequential numbering; test concurrent creation | | |

**Test Coverage:** 15 tests (15 integration)

---

### GOAL-005: Testing, Documentation & Deployment

**Objective:** Establish comprehensive test coverage, complete API documentation, workflow configuration guide, and deployment readiness.

**Requirements Addressed:**

| Requirement ID | Description | Type |
|----------------|-------------|------|
| SR-BO-001 | Role-based access | Security |
| SR-BO-002 | Log all actions | Security |
| DR-BO-003 | Approval workflow history | Data |

**Tasks:**

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-5.1 | Write comprehensive unit tests for ApprovalWorkflow model: test evaluateRules() with various rule configurations (amount thresholds, department filters, user conditions), test resolveApprover() for different approver types | | |
| TASK-5.2 | Write comprehensive unit tests for ApprovalRequest model: test getCurrentApprovers() returns correct users based on workflow level, test advance() increments level and handles final level, test canUserApprove() validates approver correctly | | |
| TASK-5.3 | Write unit tests for DocumentNumberSequence model: test formatNumber() with various padding lengths (3-12), test generateNext() increments atomically, test concurrent generation doesn't create duplicates | | |
| TASK-5.4 | Write unit tests for all workflow actions: test SubmitForApprovalAction finds matching workflow, test ApproveRequestAction validates approver, test DelegateApprovalAction updates approver assignment, test all actions dispatch events | | |
| TASK-5.5 | Write unit tests for all numbering actions: test GenerateDocumentNumberAction uses pessimistic locking, test ConfigureDocumentSequenceAction validates unique prefix, test ResetDocumentSequenceAction checks for existing documents | | |
| TASK-5.6 | Write feature tests for complete approval workflow via API: create workflow with 3 levels, submit document, approve level 1, approve level 2, approve level 3, verify completed status; test rejection at level 2 stops workflow; test delegation from level 1 to another user | | |
| TASK-5.7 | Write feature tests for document numbering via API: configure sequences for multiple entities, generate numbers, verify uniqueness, verify formatting, test concurrent generation, test reset sequence | | |
| TASK-5.8 | Write integration tests for cross-module workflow enforcement: create PO below threshold (no approval required), create PO above threshold (approval required), verify cannot post until approved, approve PO, verify can post | | |
| TASK-5.9 | Write integration tests for document numbering in transactional modules: create PO (number auto-generated), create SO (number auto-generated), verify numbers sequential per entity, verify different entities have independent sequences | | |
| TASK-5.10 | Write concurrency tests for document numbering: spawn 100 parallel requests to generate numbers; verify all unique; verify no gaps in sequence; verify next_number reflects correct count | | |
| TASK-5.11 | Set up Pest configuration for workflow and numbering tests; configure database transactions, concurrent execution helpers, workflow seeding helpers | | |
| TASK-5.12 | Achieve minimum 80% code coverage for workflow and numbering modules; run coverage report; add tests for uncovered branches | | |
| TASK-5.13 | Create approval workflow configuration guide in docs/guides/approval-workflow-guide.md: document workflow rule syntax (JSON structure), explain multi-level approval setup, provide examples for common scenarios (PO approval, expense approval, payment approval), document delegation process | | |
| TASK-5.14 | Create document numbering guide in docs/guides/document-numbering-guide.md: document sequence configuration, explain entity-specific numbering, provide prefix naming conventions, document number format customization, explain concurrency handling | | |
| TASK-5.15 | Update API documentation in docs/api/backoffice-api.md: document workflow endpoints, document approval request endpoints, document numbering endpoints; include OpenAPI spec; provide request/response examples for all operations | | |
| TASK-5.16 | Create workflow integration guide for module developers in docs/guides/workflow-integration.md: document RequiresApproval trait usage, explain ApprovalableContract implementation, provide code examples, document event handling | | |
| TASK-5.17 | Update main README.md with workflow and numbering overview; document setup process for new tenants; provide quick start examples | | |
| TASK-5.18 | Validate all acceptance criteria from PRD: workflows configurable with multi-level approvers, document numbering sequences operational per entity, approval history recorded, integration with transactional modules functional | | |
| TASK-5.19 | Conduct code review: verify PSR-12 compliance, verify strict types, verify PHPDoc completeness, verify repository pattern, verify concurrency safety for number generation | | |
| TASK-5.20 | Deploy to staging environment; run full test suite; perform end-to-end testing: configure workflow, create PO requiring approval, approve through all levels, verify document number generated, verify approval history recorded | | |

**Test Coverage:** 20 tests (10 unit, 6 feature, 2 integration, 2 concurrency)

---

## 3. Alternatives

- **ALT-001**: Store workflow rules as PHP code instead of JSON - Rejected: JSON provides flexibility for runtime configuration without code deployment; PHP code requires developer intervention for changes
- **ALT-002**: Use single approval level instead of multi-level - Rejected: Business requirement FR-BO-007 explicitly requires multi-level approvers for complex approval hierarchies
- **ALT-003**: Generate document numbers using UUID instead of sequential integers - Rejected: Sequential numbers required for audit compliance and user-friendly document references
- **ALT-004**: Store document numbers in separate table instead of model field - Rejected: Document number is intrinsic property of document; separate table adds complexity without benefit
- **ALT-005**: Use Redis for number sequence generation instead of database - Considered: Redis would be faster but database provides better durability and transaction support; decided to use database with pessimistic locking

---

## 4. Dependencies

### Module Dependencies

- **DEP-001**: SUB01 (Multi-Tenancy) - Tenant model, tenant isolation
- **DEP-002**: SUB02 (Authentication & Authorization) - User model, role-based permissions
- **DEP-003**: SUB03 (Audit Logging) - ActivityLoggerContract for all administrative actions
- **DEP-004**: SUB15-PLAN01 (Organizational Foundation) - Company, Branch, Department models for entity-based numbering
- **DEP-005**: SUB16 (Purchasing) - PurchaseOrder model for approval workflow integration (optional)
- **DEP-006**: SUB17 (Sales) - SalesOrder model for approval workflow integration (optional)
- **DEP-007**: SUB10 (Expense Management) - ExpenseClaim model for approval workflow integration (optional)

### Package Dependencies

- **DEP-008**: PHP ^8.2 - Required for enums, readonly properties
- **DEP-009**: Laravel Framework ^12.0 - Core framework
- **DEP-010**: lorisleiva/laravel-actions ^2.0 - Action pattern
- **DEP-011**: PostgreSQL 14+ - For JSONB support (workflow rules, conditions)
- **DEP-012**: Laravel Notifications - For approval notifications

---

## 5. Files

### Models & Migrations

- **packages/backoffice/src/Models/ApprovalWorkflow.php**: Approval workflow configuration model
- **packages/backoffice/src/Models/WorkflowApprover.php**: Workflow approver assignment
- **packages/backoffice/src/Models/ApprovalRequest.php**: Approval request tracking
- **packages/backoffice/src/Models/ApprovalHistory.php**: Approval decision audit trail
- **packages/backoffice/src/Models/DocumentNumberSequence.php**: Document numbering sequence
- **packages/backoffice/database/migrations/2025_01_01_000007_create_approval_workflows_table.php**
- **packages/backoffice/database/migrations/2025_01_01_000008_create_workflow_approvers_table.php**
- **packages/backoffice/database/migrations/2025_01_01_000009_create_approval_requests_table.php**
- **packages/backoffice/database/migrations/2025_01_01_000010_create_approval_history_table.php**
- **packages/backoffice/database/migrations/2025_01_01_000011_create_document_number_sequences_table.php**

### Enums

- **packages/backoffice/src/Enums/DocumentType.php**: Document types for workflows and numbering
- **packages/backoffice/src/Enums/ApprovalStatus.php**: Approval request status
- **packages/backoffice/src/Enums/NumberSequenceEntity.php**: Entity types for numbering

### Traits & Contracts

- **packages/backoffice/src/Traits/RequiresApproval.php**: Trait for approvable models
- **packages/backoffice/src/Traits/HasDocumentNumber.php**: Trait for numbered documents
- **packages/backoffice/src/Contracts/ApprovalableContract.php**: Interface for approvable models
- **packages/backoffice/src/Contracts/NumberableContract.php**: Interface for numbered documents
- **packages/backoffice/src/Contracts/ApprovalWorkflowRepositoryContract.php**
- **packages/backoffice/src/Contracts/ApprovalRequestRepositoryContract.php**
- **packages/backoffice/src/Contracts/DocumentNumberSequenceRepositoryContract.php**

### Services

- **packages/backoffice/src/Services/DocumentNumberService.php**: Document number generation service
- **packages/backoffice/src/Services/WorkflowIntegrationService.php**: Workflow integration helpers

### Actions

- **packages/backoffice/src/Actions/CreateApprovalWorkflowAction.php**
- **packages/backoffice/src/Actions/UpdateApprovalWorkflowAction.php**
- **packages/backoffice/src/Actions/DeleteApprovalWorkflowAction.php**
- **packages/backoffice/src/Actions/AddWorkflowApproverAction.php**
- **packages/backoffice/src/Actions/SubmitForApprovalAction.php**
- **packages/backoffice/src/Actions/ApproveRequestAction.php**
- **packages/backoffice/src/Actions/RejectRequestAction.php**
- **packages/backoffice/src/Actions/DelegateApprovalAction.php**
- **packages/backoffice/src/Actions/CancelApprovalRequestAction.php**
- **packages/backoffice/src/Actions/ConfigureDocumentSequenceAction.php**
- **packages/backoffice/src/Actions/ResetDocumentSequenceAction.php**
- **packages/backoffice/src/Actions/GenerateDocumentNumberAction.php**

### Events & Listeners

- **packages/backoffice/src/Events/WorkflowCreatedEvent.php**
- **packages/backoffice/src/Events/ApprovalRequestSubmittedEvent.php**
- **packages/backoffice/src/Events/ApprovalRequestApprovedEvent.php**
- **packages/backoffice/src/Events/ApprovalRequestRejectedEvent.php**
- **packages/backoffice/src/Events/ApprovalRequestCompletedEvent.php**
- **packages/backoffice/src/Events/ApprovalDelegatedEvent.php**
- **packages/backoffice/src/Listeners/NotifyApproversListener.php**
- **packages/backoffice/src/Listeners/NotifyRequesterOnCompletionListener.php**
- **packages/backoffice/src/Listeners/AutoGenerateDocumentNumberListener.php**
- **packages/backoffice/src/Listeners/EnforceApprovalWorkflowListener.php**
- **packages/backoffice/src/Listeners/UpdateDocumentOnApprovalListener.php**

### Controllers & Policies

- **packages/backoffice/src/Http/Controllers/ApprovalWorkflowController.php**
- **packages/backoffice/src/Http/Controllers/ApprovalRequestController.php**
- **packages/backoffice/src/Http/Controllers/DocumentNumberSequenceController.php**
- **packages/backoffice/src/Policies/ApprovalWorkflowPolicy.php**
- **packages/backoffice/src/Policies/ApprovalRequestPolicy.php**
- **packages/backoffice/src/Policies/DocumentNumberSequencePolicy.php**

### Tests (109 total tests across all 3 plans)

- **packages/backoffice/tests/Unit/Models/ApprovalWorkflowTest.php**
- **packages/backoffice/tests/Unit/Models/ApprovalRequestTest.php**
- **packages/backoffice/tests/Unit/Models/DocumentNumberSequenceTest.php**
- **packages/backoffice/tests/Unit/Services/DocumentNumberServiceTest.php**
- **packages/backoffice/tests/Feature/ApprovalWorkflowManagementTest.php**
- **packages/backoffice/tests/Feature/ApprovalProcessTest.php**
- **packages/backoffice/tests/Feature/DocumentNumberSequenceTest.php**
- **packages/backoffice/tests/Integration/ApprovalWorkflowIntegrationTest.php**
- **packages/backoffice/tests/Integration/DocumentNumberingIntegrationTest.php**
- **packages/backoffice/tests/Concurrency/DocumentNumberingConcurrencyTest.php**

### Documentation

- **docs/guides/approval-workflow-guide.md**: Workflow configuration guide
- **docs/guides/document-numbering-guide.md**: Document numbering guide
- **docs/guides/workflow-integration.md**: Integration guide for developers

---

## 6. Testing

### Unit Tests (32 tests)

**Approval Workflow Tests:**
- TEST-001: ApprovalWorkflow factory creates valid instances
- TEST-002: evaluateRules() matches documents with amount thresholds
- TEST-003: evaluateRules() matches documents with department filters
- TEST-004: WorkflowApprover resolves users correctly for type='user'
- TEST-005: WorkflowApprover resolves role members for type='role'
- TEST-006: WorkflowApprover resolves manager for type='manager'
- TEST-007: CreateApprovalWorkflowAction validates rules JSON structure
- TEST-008: Cannot delete workflow with pending approvals

**Approval Request Tests:**
- TEST-009: ApprovalRequest getCurrentApprovers() returns level approvers
- TEST-010: ApprovalRequest canUserApprove() validates user is approver
- TEST-011: ApprovalRequest advance() increments level correctly
- TEST-012: ApprovalRequest advance() completes at final level
- TEST-013: SubmitForApprovalAction finds matching workflow
- TEST-014: ApproveRequestAction creates history record
- TEST-015: ApproveRequestAction dispatches events
- TEST-016: RejectRequestAction sets status correctly
- TEST-017: DelegateApprovalAction updates approver assignment

**Document Numbering Tests:**
- TEST-018: DocumentNumberSequence formatNumber() pads correctly (padding=3)
- TEST-019: formatNumber() pads correctly (padding=12)
- TEST-020: generateNext() increments next_number atomically
- TEST-021: DocumentNumberService generateNumber() uses pessimistic locking
- TEST-022: DocumentNumberService getOrCreate() creates sequence if not exists
- TEST-023: ConfigureDocumentSequenceAction validates unique prefix per entity
- TEST-024: ResetDocumentSequenceAction checks for existing documents
- TEST-025: GenerateDocumentNumberAction returns formatted number

**Repository Tests:**
- TEST-026: ApprovalWorkflowRepository findMatchingWorkflow() returns most specific
- TEST-027: ApprovalRequestRepository getPendingForUser() filters correctly
- TEST-028: DocumentNumberSequenceRepository getByEntity() returns correct sequence

**Policy Tests:**
- TEST-029: ApprovalWorkflowPolicy allows only authorized users to manage
- TEST-030: ApprovalRequestPolicy canUserApprove() validates correctly
- TEST-031: DocumentNumberSequencePolicy requires admin for reset
- TEST-032: Policies enforce tenant scope

### Feature Tests (38 tests)

**Workflow Management API:**
- TEST-033: Can create approval workflow via API
- TEST-034: Can add approvers to workflow
- TEST-035: Can list workflows with filters
- TEST-036: Cannot create workflow with invalid rules JSON
- TEST-037: Non-admin cannot create workflow (403)
- TEST-038: Can update workflow configuration
- TEST-039: Cannot delete workflow with pending approvals (400)

**Approval Process API:**
- TEST-040: Can submit document for approval
- TEST-041: Approval request created at level 1
- TEST-042: My-approvals endpoint returns pending approvals for user
- TEST-043: Can approve request at current level
- TEST-044: Request advances to next level after approval
- TEST-045: Request completed after final level approval
- TEST-046: Can reject approval request
- TEST-047: Rejection prevents further approvals
- TEST-048: Can delegate approval to another user
- TEST-049: Delegated user can approve
- TEST-050: Can cancel pending approval request
- TEST-051: Cannot approve request if not current approver (403)
- TEST-052: Approval history endpoint returns all decisions

**Document Numbering API:**
- TEST-053: Can configure document sequence via API
- TEST-054: Can generate document number via API
- TEST-055: Generated numbers are sequential
- TEST-056: Generated numbers have correct format (prefix + padding)
- TEST-057: Can list sequences with filters
- TEST-058: Can reset sequence (admin only)
- TEST-059: Cannot reset if documents exist with higher numbers
- TEST-060: Different entities have independent sequences
- TEST-061: Same entity different doc types have independent sequences

**Authorization Tests:**
- TEST-062: Non-admin cannot configure sequences (403)
- TEST-063: Non-admin cannot reset sequences (403)
- TEST-064: User can only view workflows in their tenant
- TEST-065: User can only approve requests assigned to them

**Resource Transformation:**
- TEST-066: ApprovalWorkflowResource includes approvers
- TEST-067: ApprovalRequestResource includes current approvers
- TEST-068: ApprovalRequestResource includes can_user_approve flag
- TEST-069: DocumentNumberSequenceResource includes sample_next_number
- TEST-070: ApprovalHistoryResource includes delegated_to details

### Integration Tests (17 tests)

**Cross-Module Workflow Integration:**
- TEST-071: PO creation triggers approval if amount > threshold
- TEST-072: PO below threshold does not require approval
- TEST-073: Approved PO status updated to 'approved'
- TEST-074: Rejected PO status updated to 'rejected'
- TEST-075: Cannot post GL entries for unapproved PO (middleware blocks)
- TEST-076: Can post GL entries after PO approved

**Cross-Module Numbering Integration:**
- TEST-077: PO creation auto-generates document number
- TEST-078: SO creation auto-generates document number
- TEST-079: Invoice creation auto-generates document number
- TEST-080: Numbers sequential per entity and document type
- TEST-081: Different branches have independent sequences

**Workflow Events:**
- TEST-082: ApprovalRequestSubmittedEvent triggers notification
- TEST-083: ApprovalRequestCompletedEvent updates source document
- TEST-084: ApprovalRequestRejectedEvent notifies requester

**End-to-End Flows:**
- TEST-085: Complete 3-level approval flow from submission to completion
- TEST-086: Delegation flow works correctly across levels
- TEST-087: Concurrent approval requests handled correctly

### Concurrency Tests (4 tests)

- TEST-088: 100 parallel number generation requests return unique numbers
- TEST-089: No gaps in sequence after concurrent generation
- TEST-090: next_number reflects correct count after concurrency
- TEST-091: Concurrent approval submissions don't cause race conditions

**Total Test Coverage:** 91 tests (32 unit, 38 feature, 17 integration, 4 concurrency)

**Combined Coverage (All 3 Plans):** 264 tests
- PLAN01: 89 tests
- PLAN02: 84 tests
- PLAN03: 91 tests

---

## 7. Risks & Assumptions

### Risks

- **RISK-001**: Complex workflow rules may be difficult for users to configure - Mitigation: Provide UI wizard for common patterns; create rule templates; implement rule validation with clear error messages
- **RISK-002**: Concurrent document number generation could create duplicates despite locking - Mitigation: Use pessimistic locking with `lockForUpdate()`; implement unique constraint at database level; add retry logic
- **RISK-003**: Approval delegation chains may become too complex to track - Mitigation: Limit delegation depth to 2 levels; implement clear audit trail; provide delegation visualization
- **RISK-004**: Workflow rule evaluation performance may degrade with complex rules - Mitigation: Cache workflow matching results; optimize rule evaluation algorithm; limit rule complexity
- **RISK-005**: Document number gaps may occur if transactions roll back - Mitigation: Document that gaps are acceptable for audit purposes; implement gap detection reports; consider number reservation system

### Assumptions

- **ASSUMPTION-001**: Workflow rules will be relatively simple JSON configurations (thresholds, filters)
- **ASSUMPTION-002**: Most workflows will have 2-3 approval levels (not 10+)
- **ASSUMPTION-003**: Document numbering gaps are acceptable (reserved numbers may not be used)
- **ASSUMPTION-004**: Approvers will respond to approval requests within reasonable timeframes
- **ASSUMPTION-005**: Approval delegation is temporary (not permanent reassignment)
- **ASSUMPTION-006**: Document prefixes are globally unique across all document types (no INV prefix for both invoices and inventory items)
- **ASSUMPTION-007**: PostgreSQL row-level locking is sufficient for concurrent number generation
- **ASSUMPTION-008**: Users understand approval workflow concepts (levels, routing, thresholds)

---

## 8. KIV for Future Implementations

- **KIV-001**: Approval workflow versioning (track changes to workflow configuration)
- **KIV-002**: Parallel approval paths (multiple approvers at same level, all must approve)
- **KIV-003**: Conditional approval routing (route to different approvers based on document attributes)
- **KIV-004**: Approval SLA tracking with escalation (auto-escalate if not approved within timeframe)
- **KIV-005**: Bulk approval interface (approve multiple requests at once)
- **KIV-006**: Mobile approval app integration
- **KIV-007**: Email-based approval (approve via email reply)
- **KIV-008**: Document number reservation system (reserve blocks of numbers)
- **KIV-009**: Custom document number formats (e.g., include date: INV-2025-001)
- **KIV-010**: Workflow analytics dashboard (approval times, bottleneck identification)
- **KIV-011**: Approval substitute/vacation mode (auto-delegate when user on leave)
- **KIV-012**: Approval workflow testing mode (dry-run without affecting production data)

---

## 9. Related PRD / Further Reading

- **Primary PRD:** [PRD01-SUB15-BACKOFFICE.md](../prd/prd-01/PRD01-SUB15-BACKOFFICE.md) - Complete Backoffice module requirements
- **Previous Plans:**
  - [PRD01-SUB15-PLAN01-implement-organizational-foundation.md](./PRD01-SUB15-PLAN01-implement-organizational-foundation.md) - Organizational structure
  - [PRD01-SUB15-PLAN02-implement-fiscal-period-management.md](./PRD01-SUB15-PLAN02-implement-fiscal-period-management.md) - Fiscal year and period management
- **Related Modules:**
  - [PRD01-SUB16-PURCHASING.md](../prd/prd-01/PRD01-SUB16-PURCHASING.md) - Purchase order approval integration
  - [PRD01-SUB17-SALES.md](../prd/prd-01/PRD01-SUB17-SALES.md) - Sales order approval integration
  - [PRD01-SUB10-EXPENSE-MANAGEMENT.md](../prd/prd-01/PRD01-SUB10-EXPENSE-MANAGEMENT.md) - Expense claim approval
- **Architecture Documentation:**
  - [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md) - Project coding standards
  - [PACKAGE-DECOUPLING-STRATEGY.md](../architecture/PACKAGE-DECOUPLING-STRATEGY.md) - Package decoupling approach
- **External References:**
  - [Laravel Database Locking](https://laravel.com/docs/queries#pessimistic-locking) - Pessimistic locking for concurrency
  - [PostgreSQL JSONB](https://www.postgresql.org/docs/current/datatype-json.html) - JSONB for workflow rules
  - [Laravel Notifications](https://laravel.com/docs/notifications) - Approval notifications

---

**Implementation Ready:** This plan is ready for development. All tasks are deterministic, testable, and traceable to requirements.

**Estimated Effort:** 3-4 weeks (1 developer)

**Backoffice Module Complete:** With all 3 plans implemented, the Backoffice module (PRD01-SUB15) will be fully functional with:
- Organizational foundation (companies, hierarchy)
- Fiscal year and period management
- Approval workflows and document numbering

**Total Implementation Effort:** 9-12 weeks (1 developer) for complete Backoffice module

**Next Module:** Proceed to next Sub-PRD implementation (e.g., SUB16 Purchasing, SUB17 Sales, etc.)
