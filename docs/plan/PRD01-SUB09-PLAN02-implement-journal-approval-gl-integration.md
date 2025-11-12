---
plan: Implement Journal Entry Approval Workflow and General Ledger Integration
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, accounting, journal-entries, approval-workflow, general-ledger, integration, business-logic]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan establishes the approval workflow for journal entries and integration with the General Ledger system. It implements multi-level authorization controls, submission and approval actions, automatic posting to GL after approval, reversal functionality, and batch operations. This plan depends on PLAN01 (Journal Entries Core) being completed first and assumes PRD01-SUB08 (General Ledger) is available for integration.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-JE-001**: Support **manual journal entry creation** with multi-line debit/credit entries and attachments (PLAN01)
- **FR-JE-003**: Implement **approval workflow** with configurable authorization rules before posting to GL

**Business Rules:**
- **BR-JE-001**: Only **authorized users** with proper permissions may post journals to the general ledger
- **BR-JE-002**: Journal entries MUST be **balanced (debit = credit)** before approval (validated in PLAN01)
- **BR-JE-003**: **Approved journals** cannot be edited, only reversed with offsetting entry
- **BR-JE-004**: Recurring journals **auto-generate on schedule** but require approval before posting

**Integration Requirements:**
- **IR-JE-001**: Integrate with **General Ledger** for automatic posting after approval

**Performance Requirements:**
- **PR-JE-001**: Approval and posting workflow must complete within **2 seconds** per entry
- **PR-JE-002**: Support **batch approval** of 100+ journal entries in under 5 seconds

**Security Requirements:**
- **SR-JE-001**: Enforce **role-based access** for journal entry creation, approval, and posting

**Architecture Requirements:**
- **ARCH-JE-001**: Use **database transactions** to ensure atomicity when posting multiple lines to GL

**Events:**
- **EV-JE-002**: Dispatch `JournalEntryApprovedEvent` when journal entry is approved by authorized user
- **EV-JE-003**: Dispatch `JournalEntryPostedEvent` when journal entry is posted to General Ledger

**Constraints:**
- **CON-001**: Cannot approve own journal entries (maker-checker principle)
- **CON-002**: Approval permissions must be explicit (cannot inherit from general accounting role)
- **CON-003**: Posting to GL is automatic after approval (no manual step)
- **CON-004**: Reversal entries must reference original entry for audit trail
- **CON-005**: Rejected entries return to draft status and can be edited
- **CON-006**: Cannot reverse already-reversed entries (prevent circular reversals)

**Guidelines:**
- **GUD-001**: Use PSR-12 coding standards, strict types, repository pattern, and Laravel Actions
- **GUD-002**: Log all approval and posting operations using Spatie Activity Log
- **GUD-003**: Use Laravel Policies for authorization checks (not inline if statements)
- **GUD-004**: Wrap GL posting in database transaction with proper rollback on failure
- **GUD-005**: Send notifications to relevant users on status changes

**Patterns:**
- **PAT-001**: Policy pattern for authorization (JournalEntryPolicy)
- **PAT-002**: Laravel Actions for approval operations (ApproveJournalEntryAction, PostToGLAction)
- **PAT-003**: Event-listener pattern for GL integration
- **PAT-004**: Batch processing pattern for mass approval

## 2. Implementation Steps

### GOAL-001: Create Authorization Policy for Journal Entries

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-JE-001, SR-JE-001, CON-001, CON-002 | Implement JournalEntryPolicy with explicit permission checks for create, update, approve, post, and delete operations | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create `app/Domains/Accounting/Policies/JournalEntryPolicy.php` with namespace. Add `declare(strict_types=1);`. Import `use App\Models\User; use App\Domains\Accounting\Models\JournalEntry; use Illuminate\Auth\Access\HandlesAuthorization;` | | |
| TASK-002 | Implement `viewAny(User $user): bool` method: `return $user->hasPermissionTo('view-journal-entries');`. This checks if user can list journal entries | | |
| TASK-003 | Implement `view(User $user, JournalEntry $entry): bool` method: `return $user->hasPermissionTo('view-journal-entries') && $user->tenant_id === $entry->tenant_id;`. Verify tenant scoping | | |
| TASK-004 | Implement `create(User $user): bool` method: `return $user->hasPermissionTo('create-journal-entries');`. Basic permission check for creation | | |
| TASK-005 | Implement `update(User $user, JournalEntry $entry): bool` method: `return $user->hasPermissionTo('update-journal-entries') && $entry->canEdit() && $user->tenant_id === $entry->tenant_id;`. Cannot edit if posted | | |
| TASK-006 | Implement `delete(User $user, JournalEntry $entry): bool` method: `return $user->hasPermissionTo('delete-journal-entries') && $entry->status === JournalEntryStatus::DRAFT && $user->tenant_id === $entry->tenant_id;`. Only delete drafts | | |
| TASK-007 | Implement `approve(User $user, JournalEntry $entry): bool` method: `return $user->hasPermissionTo('approve-journal-entries') && $entry->status === JournalEntryStatus::PENDING_APPROVAL && $user->id !== $entry->created_by && $user->tenant_id === $entry->tenant_id;`. Maker-checker: cannot approve own entries (CON-001) | | |
| TASK-008 | Implement `reject(User $user, JournalEntry $entry): bool` method: Same logic as approve() - requires explicit permission and cannot reject own entries | | |
| TASK-009 | Implement `post(User $user, JournalEntry $entry): bool` method: `return $user->hasPermissionTo('post-journal-entries') && $entry->status === JournalEntryStatus::APPROVED && $user->tenant_id === $entry->tenant_id;`. Only post approved entries | | |
| TASK-010 | Implement `reverse(User $user, JournalEntry $entry): bool` method: `return $user->hasPermissionTo('reverse-journal-entries') && $entry->status === JournalEntryStatus::POSTED && $entry->reversal_entry_id === null && $user->tenant_id === $entry->tenant_id;`. Cannot reverse if already reversed (CON-006) | | |
| TASK-011 | Register policy in `app/Providers/AuthServiceProvider.php` boot() method: `Gate::policy(JournalEntry::class, JournalEntryPolicy::class);` | | |

### GOAL-002: Implement Approval Workflow Actions

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-JE-003, BR-JE-001, BR-JE-003, EV-JE-002 | Create actions for submitting, approving, and rejecting journal entries with proper validation and authorization | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-012 | Create `app/Domains/Accounting/Actions/SubmitJournalEntryAction.php` with namespace. Add `declare(strict_types=1);`. Use AsAction trait. Constructor: `public function __construct(private readonly JournalEntryRepositoryContract $repository, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-013 | Implement `handle(JournalEntry $entry): JournalEntry` method. Step 1: Authorize: `Gate::authorize('update', $entry);` (user must have update permission to submit) | | |
| TASK-014 | Step 2: Validate entry is in correct status: `if ($entry->status !== JournalEntryStatus::DRAFT) { throw new InvalidStatusTransitionException('Only draft entries can be submitted'); }` | | |
| TASK-015 | Step 3: Validate entry is balanced: `if (!$entry->isBalanced()) { throw new UnbalancedEntryException('Entry must be balanced before submission'); }` | | |
| TASK-016 | Step 4: Validate entry has lines: `if ($entry->lines->isEmpty()) { throw new ValidationException::withMessages(['lines' => 'Entry must have at least 2 lines']); }` | | |
| TASK-017 | Step 5: Update status to pending approval: `DB::transaction(function() use ($entry) { $this->repository->update($entry, ['status' => JournalEntryStatus::PENDING_APPROVAL]); $this->activityLogger->log('Journal entry submitted for approval', $entry, auth()->user()); event(new JournalEntrySubmittedEvent($entry)); return $entry->fresh(); });` | | |
| TASK-018 | Create `app/Domains/Accounting/Actions/ApproveJournalEntryAction.php` with namespace. Add `declare(strict_types=1);`. Use AsAction trait. Constructor includes repository, activity logger | | |
| TASK-019 | Implement `handle(JournalEntry $entry, ?string $notes = null): JournalEntry` method. Step 1: Authorize: `Gate::authorize('approve', $entry);`. This checks maker-checker automatically | | |
| TASK-020 | Step 2: Validate status: `if ($entry->status !== JournalEntryStatus::PENDING_APPROVAL) { throw new InvalidStatusTransitionException('Only pending entries can be approved'); }` | | |
| TASK-021 | Step 3: Validate still balanced (defensive check): `if (!$entry->isBalanced()) { throw new UnbalancedEntryException('Entry is no longer balanced'); }` | | |
| TASK-022 | Step 4: Update status to approved: `DB::transaction(function() use ($entry, $notes) { $this->repository->update($entry, ['status' => JournalEntryStatus::APPROVED, 'approved_by' => auth()->id(), 'approved_at' => now()]); $this->activityLogger->log("Journal entry approved" . ($notes ? ": {$notes}" : ""), $entry, auth()->user()); event(new JournalEntryApprovedEvent($entry, $notes)); return $entry->fresh(); });` | | |
| TASK-023 | Create `app/Domains/Accounting/Actions/RejectJournalEntryAction.php` with namespace. Similar structure to approve action | | |
| TASK-024 | Implement `handle(JournalEntry $entry, string $reason): JournalEntry` method. Authorize with 'reject' gate, validate pending status, update to REJECTED status, clear approved_by/approved_at, log with rejection reason. Reason is REQUIRED for audit trail | | |
| TASK-025 | Create `app/Domains/Accounting/Events/JournalEntrySubmittedEvent.php` with namespace. Constructor: `public function __construct(public readonly JournalEntry $entry) {}` | | |
| TASK-026 | Create `app/Domains/Accounting/Events/JournalEntryApprovedEvent.php` with namespace. Constructor: `public function __construct(public readonly JournalEntry $entry, public readonly ?string $notes) {}` | | |
| TASK-027 | Create `app/Domains/Accounting/Events/JournalEntryRejectedEvent.php` with namespace. Constructor: `public function __construct(public readonly JournalEntry $entry, public readonly string $reason) {}` | | |

### GOAL-003: Implement General Ledger Posting Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-JE-001, FR-JE-003, ARCH-JE-001, PR-JE-001, EV-JE-003 | Create action to automatically post approved journal entries to General Ledger with ACID compliance | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-028 | Create `app/Domains/Accounting/Actions/PostJournalEntryToGLAction.php` with namespace. Add `declare(strict_types=1);`. Use AsAction trait. Constructor: `public function __construct(private readonly JournalEntryRepositoryContract $journalRepo, private readonly GLServiceContract $glService, private readonly ActivityLoggerContract $activityLogger) {}`. Note: GLServiceContract is from PRD01-SUB08 | | |
| TASK-029 | Implement `handle(JournalEntry $entry): JournalEntry` method. Step 1: Authorize: `Gate::authorize('post', $entry);`. Requires explicit post permission | | |
| TASK-030 | Step 2: Validate status: `if ($entry->status !== JournalEntryStatus::APPROVED) { throw new InvalidStatusTransitionException('Only approved entries can be posted'); }` | | |
| TASK-031 | Step 3: Validate not already posted: `if ($entry->posted_to_gl_at !== null) { throw new AlreadyPostedException('Entry already posted to GL'); }` | | |
| TASK-032 | Step 4: Prepare GL entry data structure: `$glData = ['posting_date' => $entry->entry_date, 'fiscal_year' => $entry->entry_date->year, 'fiscal_period' => $entry->entry_date->month, 'description' => "JE: {$entry->entry_number} - {$entry->description}", 'source_type' => JournalEntry::class, 'source_id' => $entry->id, 'lines' => $entry->lines->map(fn($line) => ['account_id' => $line->account_id, 'debit_amount' => $line->debit_amount, 'credit_amount' => $line->credit_amount, 'description' => $line->description, 'cost_center' => $line->cost_center, 'project_code' => $line->project_code])->toArray()];` | | |
| TASK-033 | Step 5: Post to GL in transaction (CRITICAL for ACID compliance): `DB::transaction(function() use ($entry, $glData) { $glEntry = $this->glService->createAndPostEntry($glData); $this->journalRepo->update($entry, ['status' => JournalEntryStatus::POSTED, 'posted_to_gl_at' => now(), 'gl_entry_id' => $glEntry->id]); $this->activityLogger->log("Journal entry posted to GL (GL Entry: {$glEntry->entry_number})", $entry, auth()->user()); event(new JournalEntryPostedEvent($entry, $glEntry)); return $entry->fresh(); });`. If GL posting fails, entire transaction rolls back | | |
| TASK-034 | Create `app/Domains/Accounting/Events/JournalEntryPostedEvent.php` with namespace. Constructor: `public function __construct(public readonly JournalEntry $entry, public readonly GLEntry $glEntry) {}`. Import GLEntry from SUB08 | | |
| TASK-035 | Create listener `app/Domains/Accounting/Listeners/AutoPostApprovedJournalListener.php` with namespace. Add `declare(strict_types=1);`. Use `use Illuminate\Events\Attribute\Listen;` | | |
| TASK-036 | Implement `#[Listen(JournalEntryApprovedEvent::class)] public function handle(JournalEntryApprovedEvent $event): void` method. Constructor: `public function __construct(private readonly PostJournalEntryToGLAction $postAction) {}`. Body: `try { $this->postAction->handle($event->entry); } catch (\Exception $e) { Log::error("Failed to auto-post journal entry {$event->entry->id}: {$e->getMessage()}"); // Do not throw - allows approval to succeed even if posting fails (can retry later) }` | | |
| TASK-037 | Register listener in `app/Providers/EventServiceProvider.php` $listen array: `JournalEntryApprovedEvent::class => [AutoPostApprovedJournalListener::class]`. This makes posting automatic after approval (CON-003) | | |

### GOAL-004: Implement Journal Entry Reversal

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-JE-003, FR-JE-002, CON-004, CON-006 | Create reversal functionality that creates offsetting entries with proper audit trail and prevents circular reversals | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-038 | Create `app/Domains/Accounting/Actions/ReverseJournalEntryAction.php` with namespace. Add `declare(strict_types=1);`. Use AsAction trait. Constructor: `public function __construct(private readonly JournalEntryRepositoryContract $repository, private readonly CreateJournalEntryAction $createAction, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-039 | Implement `handle(JournalEntry $entry, string $reason, ?Carbon $reversalDate = null): JournalEntry` method. Step 1: Authorize: `Gate::authorize('reverse', $entry);`. Checks if posted and not already reversed | | |
| TASK-040 | Step 2: Set reversal date: `$reversalDate = $reversalDate ?? now()->startOfMonth()->addMonth();`. Default to first day of next month | | |
| TASK-041 | Step 3: Validate reversal date is after original: `if ($reversalDate->lt($entry->entry_date)) { throw new InvalidReversalDateException('Reversal date must be after original entry date'); }` | | |
| TASK-042 | Step 4: Create offsetting entry data: `$reversalData = ['entry_date' => $reversalDate, 'description' => "REVERSAL of {$entry->entry_number}: {$entry->description} - Reason: {$reason}", 'lines' => $entry->lines->map(fn($line) => ['account_id' => $line->account_id, 'debit_amount' => $line->credit_amount, 'credit_amount' => $line->debit_amount, 'description' => "Reversal: {$line->description}", 'cost_center' => $line->cost_center, 'project_code' => $line->project_code])->toArray()];`. Note: Swap debits and credits | | |
| TASK-043 | Step 5: Create reversal entry in transaction: `DB::transaction(function() use ($entry, $reversalData, $reason) { $reversalEntry = $this->createAction->handle($reversalData); $this->repository->update($entry, ['reversal_entry_id' => $reversalEntry->id]); $reversalEntry->update(['description' => $reversalEntry->description . " [References: {$entry->entry_number}]"]); $this->activityLogger->log("Journal entry reversed: {$reason}", $entry, auth()->user()); event(new JournalEntryReversedEvent($entry, $reversalEntry, $reason)); return $reversalEntry; });` | | |
| TASK-044 | Create `app/Domains/Accounting/Events/JournalEntryReversedEvent.php` with namespace. Constructor: `public function __construct(public readonly JournalEntry $originalEntry, public readonly JournalEntry $reversalEntry, public readonly string $reason) {}` | | |
| TASK-045 | Update JournalEntry model to add relationship: `reversal()` belongsTo JournalEntry (reversal_entry_id). Also add `isReversed(): bool` helper: `return $this->reversal_entry_id !== null;` | | |
| TASK-046 | Create `app/Domains/Accounting/Exceptions/InvalidReversalDateException.php` with namespace. Extend Exception: `class InvalidReversalDateException extends \Exception {}` | | |
| TASK-047 | Create `app/Domains/Accounting/Exceptions/AlreadyPostedException.php` with namespace. Extend Exception: `class AlreadyPostedException extends \Exception {}` | | |

### GOAL-005: Implement Batch Approval Operations

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PR-JE-002, FR-JE-003 | Create batch approval/rejection actions for processing multiple journal entries efficiently | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-048 | Create `app/Domains/Accounting/Actions/BatchApproveJournalEntriesAction.php` with namespace. Add `declare(strict_types=1);`. Use AsAction trait. Constructor: `public function __construct(private readonly ApproveJournalEntryAction $approveAction, private readonly JournalEntryRepositoryContract $repository) {}` | | |
| TASK-049 | Implement `handle(array $entryIds, ?string $notes = null): array` method. Step 1: Load all entries with eager loading: `$entries = $this->repository->findByIds($entryIds);` (implement findByIds() in repository) | | |
| TASK-050 | Step 2: Process each entry, collecting results: `$results = ['approved' => [], 'failed' => []]; foreach ($entries as $entry) { try { Gate::authorize('approve', $entry); $approved = $this->approveAction->handle($entry, $notes); $results['approved'][] = $approved->id; } catch (\Exception $e) { $results['failed'][] = ['id' => $entry->id, 'entry_number' => $entry->entry_number, 'error' => $e->getMessage()]; } } return $results;`. Continue on individual failures | | |
| TASK-051 | Step 3: Log batch operation: `$this->activityLogger->log("Batch approval: {count($results['approved'])} approved, {count($results['failed'])} failed", null, auth()->user());` | | |
| TASK-052 | Create `app/Domains/Accounting/Actions/BatchRejectJournalEntriesAction.php` with namespace. Similar structure to batch approve | | |
| TASK-053 | Implement `handle(array $entryIds, string $reason): array` method. Use same pattern as batch approve but with reject action. Reason is REQUIRED for all rejections | | |
| TASK-054 | Add method to JournalEntryRepositoryContract: `findByIds(array $ids): Collection`. Implement in DatabaseJournalEntryRepository: `return JournalEntry::with(['lines.account'])->whereIn('id', $ids)->get();`. Use eager loading to prevent N+1 | | |
| TASK-055 | Create `app/Domains/Accounting/Exceptions/InvalidStatusTransitionException.php` with namespace. Extend Exception: `class InvalidStatusTransitionException extends \Exception {}` | | |

## 3. Alternatives

- **ALT-001**: Require manual GL posting after approval instead of automatic - **Rejected** because introduces extra step prone to errors, automatic posting ensures consistency (CON-003)
- **ALT-002**: Allow users to approve own journal entries - **Rejected** because violates maker-checker principle (segregation of duties), critical for financial controls
- **ALT-003**: Use single "accountant" permission instead of separate create/approve/post - **Rejected** because lacks granular control, cannot implement proper segregation of duties (CON-002)
- **ALT-004**: Store approval notes in separate table - **Rejected** because simple string field sufficient, notes are for reference not complex workflow
- **ALT-005**: Allow reversal to any date (past or future) - **Rejected** because reversing to past creates backdated entries which complicates period closing

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `laravel/framework` ^12.0
- **DEP-002**: `spatie/laravel-activitylog` ^4.0 (audit logging)
- **DEP-003**: `lorisleiva/laravel-actions` ^2.0 (action pattern)

**Internal Dependencies:**
- **DEP-004**: PRD01-SUB09-PLAN01 (Journal Entries Core) - MUST be completed first
- **DEP-005**: PRD01-SUB08 (General Ledger) - MUST be available for GL integration
- **DEP-006**: PRD01-SUB02 (RBAC/Permissions) - Required for permission checks
- **DEP-007**: PRD01-SUB03 (Audit Logging System)

**Infrastructure:**
- **DEP-008**: PostgreSQL 14+ OR MySQL 8.0+ with ACID transaction support
- **DEP-009**: Laravel Notifications (for approval alerts)

## 5. Files

**Policies:**
- `app/Domains/Accounting/Policies/JournalEntryPolicy.php` - Authorization rules

**Actions:**
- `app/Domains/Accounting/Actions/SubmitJournalEntryAction.php` - Submit for approval
- `app/Domains/Accounting/Actions/ApproveJournalEntryAction.php` - Approve entry
- `app/Domains/Accounting/Actions/RejectJournalEntryAction.php` - Reject entry
- `app/Domains/Accounting/Actions/PostJournalEntryToGLAction.php` - Post to GL
- `app/Domains/Accounting/Actions/ReverseJournalEntryAction.php` - Create reversal
- `app/Domains/Accounting/Actions/BatchApproveJournalEntriesAction.php` - Batch approve
- `app/Domains/Accounting/Actions/BatchRejectJournalEntriesAction.php` - Batch reject

**Events:**
- `app/Domains/Accounting/Events/JournalEntrySubmittedEvent.php` - Entry submitted
- `app/Domains/Accounting/Events/JournalEntryApprovedEvent.php` - Entry approved
- `app/Domains/Accounting/Events/JournalEntryRejectedEvent.php` - Entry rejected
- `app/Domains/Accounting/Events/JournalEntryPostedEvent.php` - Posted to GL
- `app/Domains/Accounting/Events/JournalEntryReversedEvent.php` - Entry reversed

**Listeners:**
- `app/Domains/Accounting/Listeners/AutoPostApprovedJournalListener.php` - Auto-post after approval

**Exceptions:**
- `app/Domains/Accounting/Exceptions/InvalidStatusTransitionException.php` - Invalid status change
- `app/Domains/Accounting/Exceptions/AlreadyPostedException.php` - Already posted to GL
- `app/Domains/Accounting/Exceptions/InvalidReversalDateException.php` - Invalid reversal date

**Service Providers (updated):**
- `app/Providers/AuthServiceProvider.php` - Register JournalEntryPolicy
- `app/Providers/EventServiceProvider.php` - Register auto-post listener

**Database Seeders:**
- `database/seeders/JournalEntryPermissionsSeeder.php` - Seed required permissions

## 6. Testing

**Unit Tests (10 tests):**
- **TEST-001**: `test_policy_authorize_create_for_permitted_user` - Test create permission
- **TEST-002**: `test_policy_deny_create_for_unpermitted_user` - Test authorization failure
- **TEST-003**: `test_policy_deny_approve_own_entry` - Test maker-checker (CON-001)
- **TEST-004**: `test_policy_allow_approve_other_users_entry` - Test valid approval
- **TEST-005**: `test_policy_deny_post_unapproved_entry` - Test status validation
- **TEST-006**: `test_policy_deny_reverse_already_reversed` - Test CON-006
- **TEST-007**: `test_submit_action_validates_balance` - Test balance check
- **TEST-008**: `test_submit_action_transitions_to_pending` - Test status change
- **TEST-009**: `test_approve_action_sets_approved_by` - Test approved_by field
- **TEST-010**: `test_reject_action_requires_reason` - Test required parameter

**Feature Tests (15 tests):**
- **TEST-011**: `test_submit_journal_entry_workflow` - Full submit flow
- **TEST-012**: `test_approve_journal_entry_workflow` - Full approve flow
- **TEST-013**: `test_reject_journal_entry_workflow` - Full reject flow
- **TEST-014**: `test_post_to_gl_creates_gl_entry` - Test GL integration
- **TEST-015**: `test_post_to_gl_rolls_back_on_failure` - Test transaction rollback
- **TEST-016**: `test_auto_post_listener_triggered_on_approval` - Test event listener
- **TEST-017**: `test_reverse_entry_swaps_debits_credits` - Test reversal logic
- **TEST-018**: `test_reverse_entry_creates_audit_trail` - Test reversal_entry_id
- **TEST-019**: `test_reversal_prevents_circular_reversals` - Test CON-006
- **TEST-020**: `test_batch_approve_processes_all_entries` - Test batch operation
- **TEST-021**: `test_batch_approve_continues_on_individual_failure` - Test error handling
- **TEST-022**: `test_unauthorized_user_cannot_approve` - Test authorization
- **TEST-023**: `test_cannot_approve_own_journal_entry` - Test maker-checker
- **TEST-024**: `test_activity_log_records_all_workflow_steps` - Test audit trail
- **TEST-025**: `test_tenant_scoping_in_policy` - Test tenant isolation

**Integration Tests (8 tests):**
- **TEST-026**: `test_full_workflow_draft_to_posted` - End-to-end test
- **TEST-027**: `test_gl_service_integration_works` - Test GLServiceContract
- **TEST-028**: `test_reversal_creates_valid_gl_entry` - Test reversed GL posting
- **TEST-029**: `test_policy_registered_in_gate` - Test Gate::policy() registration
- **TEST-030**: `test_events_dispatched_in_correct_order` - Test event flow
- **TEST-031**: `test_listener_registered_correctly` - Test event listener binding
- **TEST-032**: `test_permissions_seeder_creates_all_permissions` - Test seeder
- **TEST-033**: `test_notification_sent_on_approval` - Test notification (future)

**Performance Tests (3 tests):**
- **TEST-034**: `test_approve_and_post_completes_under_2s` - Test PR-JE-001
- **TEST-035**: `test_batch_approve_100_entries_under_5s` - Test PR-JE-002
- **TEST-036**: `test_reversal_action_completes_under_1s` - Test reversal performance

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: GL posting fails but approval succeeds - **Mitigation**: Auto-post listener catches exceptions, logs errors, allows manual retry via admin interface
- **RISK-002**: Race condition when approving same entry twice - **Mitigation**: Use database transactions with SELECT FOR UPDATE, status check prevents duplicate approval
- **RISK-003**: Batch approval timeout with large batches - **Mitigation**: Process in chunks, implement queue-based batch processing for >100 entries
- **RISK-004**: User loses approve permission after submitting entry - **Mitigation**: Policy checks permission at approval time, not submission time (correct behavior)
- **RISK-005**: Circular reversals if not properly prevented - **Mitigation**: Check reversal_entry_id in policy, prevent reversing reversed entries (CON-006)

**Assumptions:**
- **ASSUMPTION-001**: GLServiceContract from SUB08 provides createAndPostEntry() method with proper validation
- **ASSUMPTION-002**: Most approval batches contain <50 entries, rarely exceed 100
- **ASSUMPTION-003**: Users have stable permissions (not frequently revoked/granted during active sessions)
- **ASSUMPTION-004**: Reversal date defaults to next month are acceptable (can be overridden)
- **ASSUMPTION-005**: Automatic posting after approval is desired behavior (no manual posting step needed)

## 8. KIV for future implementations

- **KIV-001**: Implement multi-level approval workflow (e.g., >$10k requires CFO approval)
- **KIV-002**: Add email/Slack notifications for approval requests
- **KIV-003**: Implement approval delegation (approve on behalf of someone)
- **KIV-004**: Add bulk operations UI with selection checkboxes
- **KIV-005**: Implement approval history view (who approved what when)
- **KIV-006**: Add scheduled auto-approval for trusted users (with conditions)
- **KIV-007**: Implement approval comments/discussion thread
- **KIV-008**: Add webhook support for approval events (integrate with external systems)

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB09-JOURNAL-ENTRIES.md](../prd/prd-01/PRD01-SUB09-JOURNAL-ENTRIES.md)
- **Related Sub-PRDs:**
  - PRD01-SUB02 (RBAC/Permissions) - Permission checks
  - PRD01-SUB08 (General Ledger) - GL integration
- **Related Plans:**
  - PRD01-SUB09-PLAN01 (Journal Entries Core) - Prerequisite
  - PRD01-SUB08-PLAN01 (General Ledger Core) - GL integration dependency
- **External Documentation:**
  - Laravel Policies: https://laravel.com/docs/authorization#creating-policies
  - Maker-Checker Principle: https://en.wikipedia.org/wiki/Dual_control
  - Segregation of Duties: https://en.wikipedia.org/wiki/Separation_of_duties
  - ACID Transactions: https://en.wikipedia.org/wiki/ACID
