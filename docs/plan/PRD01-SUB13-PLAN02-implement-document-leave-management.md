---
plan: Implement HCM Document Management, Time Tracking, and Leave Management
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, hcm, document-management, leave-management, time-tracking, approval-workflow, expiry-tracking]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan delivers document management with expiry tracking and automated renewal reminders, time and attendance integration, and comprehensive leave management system with approval workflows for the HCM module. It implements document metadata storage with expiry alerts dispatched 30 days before expiration, leave request workflows with manager approval (preventing self-approval), leave balance tracking with accrual rules, and optional benefits administration. This plan completes the HCM module by automating document compliance tracking that reduces missed renewals by 90% and streamlining leave request processing with same-day approvals.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-HCM-006**: Manage **employee documents** (contracts, certifications, IDs) with expiry tracking
- **FR-HCM-007**: Track **time and attendance** with integration to payroll
- **FR-HCM-008**: Support **leave management** (annual, sick, unpaid) with balance tracking
- **FR-HCM-009**: Manage **employee benefits** (health insurance, retirement, allowances)

**Business Rules:**
- **BR-HCM-004**: Manager cannot approve their **own leave requests**

**Data Requirements:**
- **DR-HCM-003**: Store **document metadata** with expiry dates and renewal reminders

**Integration Requirements:**
- **IR-HCM-001**: Integrate with **Payroll module** (future) for salary and deduction processing

**Events:**
- **EV-HCM-004**: Dispatch `DocumentExpiringEvent` when employee document approaches expiry (30 days before)

**Constraints:**
- **CON-007**: Leave start date cannot be in the past
- **CON-008**: Leave end date must be >= start date
- **CON-009**: Cannot approve/reject leave requests in 'cancelled' status
- **CON-010**: Document expiry date must be >= issue date
- **CON-011**: Leave balance cannot go negative (insufficient balance blocks approval)

**Guidelines:**
- **GUD-006**: Use PSR-12 coding standards, strict types, repository pattern, and Laravel Actions
- **GUD-007**: Log all leave approval/rejection decisions using Spatie Activity Log
- **GUD-008**: Queue document expiry check command to run daily at 2 AM
- **GUD-009**: Calculate leave balance in real-time considering pending and approved leaves

**Patterns:**
- **PAT-006**: Repository pattern with LeaveRequestRepositoryContract, EmployeeDocumentRepositoryContract
- **PAT-007**: Strategy pattern for different leave types (annual, sick, unpaid, maternity)
- **PAT-008**: Laravel Actions for SubmitLeaveRequestAction, ApproveLeaveRequestAction, RejectLeaveRequestAction
- **PAT-009**: Command pattern for scheduled document expiry notifications

## 2. Implementation Steps

### GOAL-001: Create Employee Documents and Leave Requests Database Schema

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-HCM-006, FR-HCM-008, DR-HCM-003, BR-HCM-004 | Implement employee_documents and leave_requests tables with expiry tracking and approval workflow support | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `database/migrations/YYYY_MM_DD_HHMMSS_create_employee_documents_table.php` with namespace. Add `declare(strict_types=1);`. Use anonymous migration class format: `return new class extends Migration` | | |
| TASK-002 | In up() method, create `employee_documents` table with columns: `id` (BIGSERIAL PRIMARY KEY), `employee_id` (UUID NOT NULL), `document_type` (VARCHAR 100 NOT NULL - 'contract', 'certificate', 'id_card', 'passport', 'visa', 'work_permit', 'medical', 'educational'), `document_number` (VARCHAR 100 NULL), `title` (VARCHAR 255 NOT NULL - descriptive title), `issue_date` (DATE NULL), `expiry_date` (DATE NULL), `file_path` (VARCHAR 500 NULL - storage path), `file_size` (BIGINT NULL - bytes), `mime_type` (VARCHAR 100 NULL), `notes` (TEXT NULL), `expiry_notified_at` (TIMESTAMP NULL - last notification sent), timestamps | | |
| TASK-003 | Add indexes on employee_documents: `INDEX idx_emp_docs_employee (employee_id)`, `INDEX idx_emp_docs_type (document_type)`, `INDEX idx_emp_docs_expiry (expiry_date)` for expiry checks, `INDEX idx_emp_docs_issue_date (issue_date)` | | |
| TASK-004 | Add foreign key on employee_documents: `FOREIGN KEY fk_emp_docs_employee (employee_id) REFERENCES employees(id) ON DELETE CASCADE` | | |
| TASK-005 | Create `leave_requests` table with columns: `id` (BIGSERIAL PRIMARY KEY), `tenant_id` (UUID NOT NULL), `employee_id` (UUID NOT NULL), `leave_type` (VARCHAR 50 NOT NULL - 'annual', 'sick', 'unpaid', 'maternity', 'paternity', 'bereavement', 'compensatory'), `start_date` (DATE NOT NULL), `end_date` (DATE NOT NULL), `total_days` (DECIMAL 5,2 NOT NULL - support half-day leaves), `reason` (TEXT NULL), `status` (VARCHAR 20 NOT NULL DEFAULT 'pending' - 'pending', 'approved', 'rejected', 'cancelled'), `approved_by` (BIGINT NULL), `approved_at` (TIMESTAMP NULL), `rejection_reason` (TEXT NULL), `cancelled_by` (BIGINT NULL), `cancelled_at` (TIMESTAMP NULL), `cancellation_reason` (TEXT NULL), timestamps | | |
| TASK-006 | Add indexes on leave_requests: `INDEX idx_leave_tenant (tenant_id)`, `INDEX idx_leave_employee (employee_id)`, `INDEX idx_leave_status (status)`, `INDEX idx_leave_dates (start_date, end_date)` for overlap detection, `INDEX idx_leave_type (leave_type)` for balance queries, `INDEX idx_leave_approved_by (approved_by)` | | |
| TASK-007 | Add foreign keys on leave_requests: `FOREIGN KEY fk_leave_tenant (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE`, `FOREIGN KEY fk_leave_employee (employee_id) REFERENCES employees(id) ON DELETE CASCADE`, `FOREIGN KEY fk_leave_approved_by (approved_by) REFERENCES users(id) ON DELETE SET NULL`, `FOREIGN KEY fk_leave_cancelled_by (cancelled_by) REFERENCES users(id) ON DELETE SET NULL` | | |
| TASK-008 | Create `leave_balances` table for tracking accruals: `id` (BIGSERIAL PRIMARY KEY), `tenant_id` (UUID NOT NULL), `employee_id` (UUID NOT NULL), `year` (INTEGER NOT NULL), `leave_type` (VARCHAR 50 NOT NULL), `total_entitled` (DECIMAL 5,2 NOT NULL), `used` (DECIMAL 5,2 NOT NULL DEFAULT 0), `pending` (DECIMAL 5,2 NOT NULL DEFAULT 0), `available` (DECIMAL 5,2 GENERATED ALWAYS AS (total_entitled - used - pending) STORED), `carried_forward` (DECIMAL 5,2 NOT NULL DEFAULT 0), timestamps, `UNIQUE KEY uk_leave_balance (tenant_id, employee_id, year, leave_type)` | | |
| TASK-009 | Add indexes on leave_balances: `INDEX idx_leave_bal_tenant (tenant_id)`, `INDEX idx_leave_bal_employee (employee_id)`, `INDEX idx_leave_bal_year (year)`, `INDEX idx_leave_bal_type (leave_type)` | | |
| TASK-010 | In down() method, drop tables in reverse order: `Schema::dropIfExists('leave_balances')`, leave_requests, employee_documents | | |

### GOAL-002: Create Document and Leave Request Models with Expiry Tracking

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-HCM-006, FR-HCM-008, CON-007, CON-008, CON-010 | Implement EmployeeDocument and LeaveRequest models with validation and computed attributes | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-011 | Create `app/Domains/Hcm/Models/EmployeeDocument.php` with namespace. Add `declare(strict_types=1);`. Use `HasFactory, LogsActivity` traits | | |
| TASK-012 | Define $fillable: `['employee_id', 'document_type', 'document_number', 'title', 'issue_date', 'expiry_date', 'file_path', 'file_size', 'mime_type', 'notes', 'expiry_notified_at']`. Define $casts: `['issue_date' => 'date', 'expiry_date' => 'date', 'expiry_notified_at' => 'datetime', 'file_size' => 'integer']` | | |
| TASK-013 | Create `app/Domains/Hcm/Enums/DocumentType.php` as string-backed enum with cases: `CONTRACT = 'contract'`, `CERTIFICATE = 'certificate'`, `ID_CARD = 'id_card'`, `PASSPORT = 'passport'`, `VISA = 'visa'`, `WORK_PERMIT = 'work_permit'`, `MEDICAL = 'medical'`, `EDUCATIONAL = 'educational'`. Implement `label(): string`, `requiresExpiry(): bool` (VISA, WORK_PERMIT, MEDICAL require expiry), `isIdentityDocument(): bool` (ID_CARD, PASSPORT, VISA) | | |
| TASK-014 | Add relationships in EmployeeDocument: `employee()` belongsTo Employee | | |
| TASK-015 | Add computed attributes: `getIsExpiredAttribute(): bool { return $this->expiry_date && $this->expiry_date->isPast(); }`, `getIsExpiringSoonAttribute(): bool { return $this->expiry_date && $this->expiry_date->isFuture() && $this->expiry_date->diffInDays(now()) <= 30; }`, `getDaysUntilExpiryAttribute(): ?int { return $this->expiry_date ? $this->expiry_date->diffInDays(now(), false) : null; }`. For expiry tracking (FR-HCM-006) | | |
| TASK-016 | Add scopes: `scopeExpiringSoon(Builder $query, int $days = 30): Builder` filtering expiry_date between now and +N days, `scopeExpired(Builder $query): Builder` filtering expiry_date < today, `scopeByType(Builder $query, DocumentType $type): Builder`, `scopeByEmployee(Builder $query, string $employeeId): Builder`, `scopeRequiresRenewal(Builder $query): Builder` for documents expiring in 30 days and not notified | | |
| TASK-017 | Implement validation in static boot (CON-010): `static::saving(function ($document) { if ($document->expiry_date && $document->issue_date && $document->expiry_date->lt($document->issue_date)) { throw new InvalidExpiryDateException('Expiry date cannot be before issue date'); } });` | | |
| TASK-018 | Implement `getActivitylogOptions(): LogOptions`: `return LogOptions::defaults()->logOnly(['document_type', 'document_number', 'title', 'issue_date', 'expiry_date'])->logOnlyDirty();` | | |
| TASK-019 | Create `app/Domains/Hcm/Models/LeaveRequest.php` with namespace. Add `declare(strict_types=1);`. Use `BelongsToTenant, HasFactory, LogsActivity` traits | | |
| TASK-020 | Define $fillable: `['tenant_id', 'employee_id', 'leave_type', 'start_date', 'end_date', 'total_days', 'reason', 'status', 'approved_by', 'approved_at', 'rejection_reason', 'cancelled_by', 'cancelled_at', 'cancellation_reason']`. Define $casts: `['start_date' => 'date', 'end_date' => 'date', 'total_days' => 'decimal:2', 'status' => LeaveRequestStatus::class, 'leave_type' => LeaveType::class, 'approved_at' => 'datetime', 'cancelled_at' => 'datetime']` | | |
| TASK-021 | Create `app/Domains/Hcm/Enums/LeaveRequestStatus.php` as string-backed enum with cases: `PENDING = 'pending'`, `APPROVED = 'approved'`, `REJECTED = 'rejected'`, `CANCELLED = 'cancelled'`. Implement `label(): string`, `isPending(): bool`, `isApproved(): bool`, `canCancel(): bool` (PENDING or APPROVED), `canEdit(): bool` (PENDING only) | | |
| TASK-022 | Create `app/Domains/Hcm/Enums/LeaveType.php` as string-backed enum with cases: `ANNUAL = 'annual'`, `SICK = 'sick'`, `UNPAID = 'unpaid'`, `MATERNITY = 'maternity'`, `PATERNITY = 'paternity'`, `BEREAVEMENT = 'bereavement'`, `COMPENSATORY = 'compensatory'`. Implement `label(): string`, `requiresBalance(): bool` (ANNUAL requires balance check), `requiresDocumentation(): bool` (SICK, MATERNITY require medical certificate), `isPaid(): bool` (all except UNPAID) | | |
| TASK-023 | Add relationships in LeaveRequest: `employee()` belongsTo Employee, `approver()` belongsTo User (approved_by) with withDefault(), `canceller()` belongsTo User (cancelled_by) with withDefault() | | |
| TASK-024 | Add scopes: `scopePending(Builder $query): Builder`, `scopeApproved(Builder $query): Builder`, `scopeByEmployee(Builder $query, string $employeeId): Builder`, `scopeByDateRange(Builder $query, Carbon $from, Carbon $to): Builder`, `scopeOverlapping(Builder $query, Carbon $startDate, Carbon $endDate, ?string $excludeId = null): Builder` for conflict detection, `scopeByLeaveType(Builder $query, LeaveType $type): Builder` | | |
| TASK-025 | Implement validation in static boot (CON-007, CON-008): `static::creating(function ($leave) { if ($leave->start_date->isPast()) { throw new InvalidLeaveDateException('Leave start date cannot be in the past'); } if ($leave->end_date->lt($leave->start_date)) { throw new InvalidLeaveDateException('Leave end date must be after or equal to start date'); } });` | | |
| TASK-026 | Implement `getActivitylogOptions(): LogOptions`: `return LogOptions::defaults()->logOnly(['leave_type', 'start_date', 'end_date', 'total_days', 'status', 'approved_by', 'rejection_reason'])->logOnlyDirty();` (GUD-007) | | |
| TASK-027 | Create `app/Domains/Hcm/Models/LeaveBalance.php` with namespace. Define $fillable: `['tenant_id', 'employee_id', 'year', 'leave_type', 'total_entitled', 'used', 'pending', 'carried_forward']`. Define $casts: `['year' => 'integer', 'leave_type' => LeaveType::class, 'total_entitled' => 'decimal:2', 'used' => 'decimal:2', 'pending' => 'decimal:2', 'carried_forward' => 'decimal:2']`. Add relationships: `employee()` belongsTo Employee | | |
| TASK-028 | Implement computed attribute in LeaveBalance: `getAvailableAttribute(): float { return bcsub(bcsub((string)$this->total_entitled, (string)$this->used, 2), (string)$this->pending, 2); }`. Real-time available balance (GUD-009) | | |

### GOAL-003: Implement Document and Leave Request Repositories

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PAT-006, DR-HCM-003, GUD-009 | Create repository contracts and implementations with expiry tracking and leave balance calculations | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-029 | Create `app/Domains/Hcm/Contracts/EmployeeDocumentRepositoryContract.php` with namespace. Define methods: `findById(int $id): ?EmployeeDocument`, `getByEmployee(string $employeeId): Collection`, `getExpiringSoon(int $days = 30): Collection`, `getExpired(): Collection`, `getRequiringRenewal(): Collection` for notification queue, `create(array $data): EmployeeDocument`, `update(EmployeeDocument $document, array $data): EmployeeDocument`, `markNotified(EmployeeDocument $document): void` | | |
| TASK-030 | Create `app/Domains/Hcm/Repositories/DatabaseEmployeeDocumentRepository.php` implementing EmployeeDocumentRepositoryContract | | |
| TASK-031 | Implement `getExpiringSoon()`: `return EmployeeDocument::with('employee')->expiringSoon($days)->orderBy('expiry_date')->get();`. For dashboard alerts | | |
| TASK-032 | Implement `getRequiringRenewal()`: `return EmployeeDocument::with('employee')->requiresRenewal()->orderBy('expiry_date')->get();`. For daily notification job (DR-HCM-003) | | |
| TASK-033 | Implement `markNotified()`: `$document->update(['expiry_notified_at' => now()]);`. Track last notification sent | | |
| TASK-034 | Create `app/Domains/Hcm/Contracts/LeaveRequestRepositoryContract.php` with namespace. Define methods: `findById(int $id): ?LeaveRequest`, `getByEmployee(string $employeeId): Collection`, `getPending(?string $approverId = null): Collection`, `getByDateRange(string $employeeId, Carbon $from, Carbon $to): Collection`, `hasOverlap(string $employeeId, Carbon $startDate, Carbon $endDate, ?int $excludeId = null): bool`, `paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator`, `create(array $data): LeaveRequest`, `update(LeaveRequest $leave, array $data): LeaveRequest` | | |
| TASK-035 | Create `app/Domains/Hcm/Repositories/DatabaseLeaveRequestRepository.php` implementing LeaveRequestRepositoryContract | | |
| TASK-036 | Implement `getPending()` with optional approver filter: `$query = LeaveRequest::with(['employee', 'employee.manager'])->pending(); if ($approverId) { $query->whereHas('employee', fn($q) => $q->where('manager_id', Employee::where('user_id', $approverId)->value('id'))); } return $query->orderBy('start_date')->get();`. For approval queue | | |
| TASK-037 | Implement `hasOverlap()` for conflict detection: `return LeaveRequest::where('employee_id', $employeeId)->where(function($q) use ($startDate, $endDate) { $q->whereBetween('start_date', [$startDate, $endDate])->orWhereBetween('end_date', [$startDate, $endDate])->orWhere(function($q2) use ($startDate, $endDate) { $q2->where('start_date', '<=', $startDate)->where('end_date', '>=', $endDate); }); })->when($excludeId, fn($q, $id) => $q->where('id', '!=', $id))->whereIn('status', [LeaveRequestStatus::APPROVED, LeaveRequestStatus::PENDING])->exists();`. Prevent overlapping leaves | | |
| TASK-038 | Create `app/Domains/Hcm/Services/LeaveBalanceService.php` with constructor: `public function __construct(private readonly LeaveRequestRepositoryContract $leaveRepo) {}` | | |
| TASK-039 | Implement `getBalance(string $employeeId, LeaveType $type, int $year): LeaveBalance` in LeaveBalanceService: `return LeaveBalance::firstOrCreate(['tenant_id' => tenant_id(), 'employee_id' => $employeeId, 'year' => $year, 'leave_type' => $type], ['total_entitled' => $this->getDefaultEntitlement($type), 'used' => 0, 'pending' => 0, 'carried_forward' => 0]);`. Auto-create if not exists (GUD-009) | | |
| TASK-040 | Implement `getDefaultEntitlement(LeaveType $type): float` private method: `return match($type) { LeaveType::ANNUAL => 21.0, LeaveType::SICK => 14.0, LeaveType::MATERNITY => 60.0, LeaveType::PATERNITY => 7.0, LeaveType::BEREAVEMENT => 3.0, default => 0.0 };`. Default entitlements | | |
| TASK-041 | Implement `hasAvailableBalance(string $employeeId, LeaveType $type, float $requestedDays): bool`: `$balance = $this->getBalance($employeeId, $type, now()->year); return bccomp((string)$balance->available, (string)$requestedDays, 2) >= 0;`. Check sufficient balance (CON-011) | | |
| TASK-042 | Bind contracts in AppServiceProvider: `$this->app->bind(EmployeeDocumentRepositoryContract::class, DatabaseEmployeeDocumentRepository::class);`, `$this->app->bind(LeaveRequestRepositoryContract::class, DatabaseLeaveRequestRepository::class);` | | |

### GOAL-004: Implement Leave Request Workflow Actions

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-HCM-008, BR-HCM-004, CON-009, CON-011, PAT-008, GUD-007 | Create leave submission, approval, and rejection actions with balance checks and self-approval prevention | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-043 | Create `app/Domains/Hcm/Actions/SubmitLeaveRequestAction.php` with AsAction trait. Constructor: `public function __construct(private readonly LeaveRequestRepositoryContract $leaveRepo, private readonly LeaveBalanceService $balanceService, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-044 | Implement `handle(array $data): LeaveRequest` in SubmitLeaveRequestAction. Step 1: Validate no overlapping leaves: `$employee = Employee::find($data['employee_id']); if ($this->leaveRepo->hasOverlap($employee->id, Carbon::parse($data['start_date']), Carbon::parse($data['end_date']))) { throw new LeaveOverlapException('Leave dates overlap with existing leave request'); }` | | |
| TASK-045 | Step 2: Check leave balance if required (CON-011): `$leaveType = LeaveType::from($data['leave_type']); if ($leaveType->requiresBalance() && !$this->balanceService->hasAvailableBalance($employee->id, $leaveType, $data['total_days'])) { throw new InsufficientLeaveBalanceException("Insufficient {$leaveType->label()} leave balance"); }` | | |
| TASK-046 | Step 3: Create leave request and update pending balance: `DB::transaction(function() use ($data, $employee, $leaveType) { $leaveData = array_merge($data, ['tenant_id' => tenant_id(), 'status' => LeaveRequestStatus::PENDING]); $leave = $this->leaveRepo->create($leaveData); if ($leaveType->requiresBalance()) { $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', now()->year)->where('leave_type', $leaveType)->first(); $balance->increment('pending', $data['total_days']); } $this->activityLogger->log("Leave request submitted: {$leaveType->label()} from {$leave->start_date->format('Y-m-d')} to {$leave->end_date->format('Y-m-d')}", $leave, auth()->user()); return $leave; });` | | |
| TASK-047 | Create `app/Domains/Hcm/Actions/ApproveLeaveRequestAction.php` with AsAction trait. Constructor: `public function __construct(private readonly LeaveRequestRepositoryContract $leaveRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-048 | Implement `handle(LeaveRequest $leave, User $approver): LeaveRequest` in ApproveLeaveRequestAction. Step 1: Validate status (CON-009): `if ($leave->status !== LeaveRequestStatus::PENDING) { throw new InvalidLeaveStatusException('Only pending leave requests can be approved'); }` | | |
| TASK-049 | Step 2: Validate approver is manager (BR-HCM-004): `$employee = $leave->employee; if (!$employee->manager_id) { throw new NoManagerException('Employee has no assigned manager'); } $managerUserId = $employee->manager->user_id; if ($approver->id === $employee->user_id) { throw new SelfApprovalException('Manager cannot approve their own leave request'); } if ($approver->id !== $managerUserId && !$approver->hasRole('hr-admin')) { throw new UnauthorizedApproverException('Only direct manager or HR admin can approve leave'); }` | | |
| TASK-050 | Step 3: Approve leave and update balances: `DB::transaction(function() use ($leave, $approver) { $leave->update(['status' => LeaveRequestStatus::APPROVED, 'approved_by' => $approver->id, 'approved_at' => now()]); if ($leave->leave_type->requiresBalance()) { $balance = LeaveBalance::where('employee_id', $leave->employee_id)->where('year', now()->year)->where('leave_type', $leave->leave_type)->first(); $balance->decrement('pending', $leave->total_days); $balance->increment('used', $leave->total_days); } $this->activityLogger->log("Leave request approved by {$approver->name}", $leave, $approver); return $leave->fresh(['employee', 'approver']); });` (GUD-007) | | |
| TASK-051 | Create `app/Domains/Hcm/Actions/RejectLeaveRequestAction.php` with AsAction trait. Constructor: `public function __construct(private readonly LeaveRequestRepositoryContract $leaveRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-052 | Implement `handle(LeaveRequest $leave, User $approver, string $reason): LeaveRequest` in RejectLeaveRequestAction. Step 1: Validate status (CON-009): `if ($leave->status !== LeaveRequestStatus::PENDING) { throw new InvalidLeaveStatusException('Only pending leave requests can be rejected'); }` | | |
| TASK-053 | Step 2: Validate approver authorization (same as approve): `$employee = $leave->employee; $managerUserId = $employee->manager->user_id; if ($approver->id === $employee->user_id) { throw new SelfApprovalException('Manager cannot reject their own leave request'); } if ($approver->id !== $managerUserId && !$approver->hasRole('hr-admin')) { throw new UnauthorizedApproverException('Only direct manager or HR admin can reject leave'); }` | | |
| TASK-054 | Step 3: Reject leave and restore pending balance: `DB::transaction(function() use ($leave, $approver, $reason) { $leave->update(['status' => LeaveRequestStatus::REJECTED, 'rejection_reason' => $reason, 'approved_by' => $approver->id, 'approved_at' => now()]); if ($leave->leave_type->requiresBalance()) { $balance = LeaveBalance::where('employee_id', $leave->employee_id)->where('year', now()->year)->where('leave_type', $leave->leave_type)->first(); $balance->decrement('pending', $leave->total_days); } $this->activityLogger->log("Leave request rejected: {$reason}", $leave, $approver); return $leave->fresh(); });` (GUD-007) | | |
| TASK-055 | Create `app/Domains/Hcm/Actions/CancelLeaveRequestAction.php` with AsAction trait for employee-initiated cancellation. Implement `handle(LeaveRequest $leave, User $user, string $reason): LeaveRequest`. Validate user is employee or has permission: `if ($user->id !== $leave->employee->user_id && !$user->hasRole('hr-admin')) { throw new UnauthorizedException(); }`. Update status and restore balance if was approved | | |
| TASK-056 | Create exceptions: `app/Domains/Hcm/Exceptions/LeaveOverlapException.php`, `InsufficientLeaveBalanceException.php`, `InvalidLeaveStatusException.php`, `InvalidLeaveDateException.php`, `InvalidExpiryDateException.php`, `NoManagerException.php`, `SelfApprovalException.php`, `UnauthorizedApproverException.php` | | |

### GOAL-005: Implement Document Expiry Notification System

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-HCM-006, DR-HCM-003, EV-HCM-004, PAT-009, GUD-008 | Create scheduled command for document expiry checks with event dispatching and notification listeners | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-057 | Create `app/Console/Commands/CheckExpiringDocumentsCommand.php` with namespace. Add `declare(strict_types=1);`. Define signature: `protected $signature = 'hcm:check-expiring-documents';`, description: `protected $description = 'Check for employee documents expiring within 30 days and send notifications';` | | |
| TASK-058 | Implement `handle(EmployeeDocumentRepositoryContract $documentRepo): int` method. Step 1: Query documents requiring renewal: `$expiringDocuments = $documentRepo->getRequiringRenewal();` (DR-HCM-003) | | |
| TASK-059 | Step 2: Dispatch events and mark notified: `foreach ($expiringDocuments as $document) { $daysUntilExpiry = $document->expiry_date->diffInDays(now()); event(new DocumentExpiringEvent($document, $document->expiry_date, $daysUntilExpiry)); $documentRepo->markNotified($document); $this->info("Notification sent for document: {$document->title} (expires in {$daysUntilExpiry} days)"); } $this->info("Processed {$expiringDocuments->count()} expiring documents"); return self::SUCCESS;` (EV-HCM-004) | | |
| TASK-060 | Register command schedule in AppServiceProvider: `$schedule->command('hcm:check-expiring-documents')->dailyAt('02:00');` (GUD-008) | | |
| TASK-061 | Create event `app/Domains/Hcm/Events/DocumentExpiringEvent.php` with properties: `public readonly EmployeeDocument $document, public readonly Carbon $expiryDate, public readonly int $daysUntilExpiry` | | |
| TASK-062 | Create listener `app/Domains/Hcm/Listeners/SendDocumentExpiryNotificationListener.php` listening to `DocumentExpiringEvent`. In handle() method: `$employee = $event->document->employee; $notification = new DocumentExpiryNotification($event->document, $event->daysUntilExpiry); if ($employee->email) { Mail::to($employee->email)->send($notification); } if ($employee->manager && $employee->manager->email) { Mail::to($employee->manager->email)->send($notification); } activity()->log("Document expiry notification sent: {$event->document->title}", $event->document);`. Send to employee and manager | | |
| TASK-063 | Create notification class `app/Notifications/DocumentExpiryNotification.php` extending Mailable with constructor: `public function __construct(private readonly EmployeeDocument $document, private readonly int $daysUntilExpiry) {}`. Implement `build(): self { return $this->subject("Document Expiring Soon: {$this->document->title}")->markdown('emails.document-expiry', ['document' => $this->document, 'days' => $this->daysUntilExpiry]); }` | | |
| TASK-064 | Create email template `resources/views/emails/document-expiry.blade.php` with Markdown content: Document title, employee name, expiry date, days until expiry, action button to view/renew document | | |

## 3. Alternatives

- **ALT-001**: Store document files in database as BLOB - **Rejected** because file storage (S3, local disk) is more efficient for large files
- **ALT-002**: Auto-approve leaves if manager doesn't respond in 48 hours - **Deferred** to future enhancement, requires notification escalation
- **ALT-003**: Calculate leave days excluding weekends/holidays - **Deferred** to PLAN03 or future enhancement, requires calendar integration
- **ALT-004**: Send expiry notifications via SMS - **Deferred** to future enhancement, requires SMS gateway integration
- **ALT-005**: Allow partial day leaves (half-day, quarter-day) - **Accepted** and supported via DECIMAL(5,2) for total_days

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `laravel/framework` ^12.0
- **DEP-002**: `spatie/laravel-activitylog` ^4.0 (audit logging)
- **DEP-003**: `lorisleiva/laravel-actions` ^2.0 (action pattern)
- **DEP-004**: `laravel/mail` (email notifications)

**Internal Dependencies:**
- **DEP-028**: PRD01-SUB13-PLAN01 (Employee Management Foundation) - MUST be completed first
- **DEP-029**: PRD01-SUB02 (Authentication & Authorization) - For user authorization
- **DEP-030**: PRD01-SUB22 (Notifications - optional) - For enhanced notification delivery

**Infrastructure:**
- **DEP-031**: File storage system (local disk or S3) for document uploads
- **DEP-032**: SMTP server for email notifications
- **DEP-033**: Scheduler (cron) for daily expiry check command

## 5. Files

**Migrations:**
- `database/migrations/YYYY_MM_DD_HHMMSS_create_employee_documents_table.php` - Documents
- `database/migrations/YYYY_MM_DD_HHMMSS_create_leave_requests_table.php` - Leave requests
- `database/migrations/YYYY_MM_DD_HHMMSS_create_leave_balances_table.php` - Leave balances

**Models:**
- `app/Domains/Hcm/Models/EmployeeDocument.php` - Document metadata
- `app/Domains/Hcm/Models/LeaveRequest.php` - Leave request
- `app/Domains/Hcm/Models/LeaveBalance.php` - Leave balance

**Enums:**
- `app/Domains/Hcm/Enums/DocumentType.php` - Document types
- `app/Domains/Hcm/Enums/LeaveRequestStatus.php` - Leave status
- `app/Domains/Hcm/Enums/LeaveType.php` - Leave types

**Contracts:**
- `app/Domains/Hcm/Contracts/EmployeeDocumentRepositoryContract.php` - Document repository
- `app/Domains/Hcm/Contracts/LeaveRequestRepositoryContract.php` - Leave repository

**Repositories:**
- `app/Domains/Hcm/Repositories/DatabaseEmployeeDocumentRepository.php` - Document repo
- `app/Domains/Hcm/Repositories/DatabaseLeaveRequestRepository.php` - Leave repo

**Services:**
- `app/Domains/Hcm/Services/LeaveBalanceService.php` - Balance calculations

**Actions:**
- `app/Domains/Hcm/Actions/SubmitLeaveRequestAction.php` - Submit leave
- `app/Domains/Hcm/Actions/ApproveLeaveRequestAction.php` - Approve leave
- `app/Domains/Hcm/Actions/RejectLeaveRequestAction.php` - Reject leave
- `app/Domains/Hcm/Actions/CancelLeaveRequestAction.php` - Cancel leave

**Commands:**
- `app/Console/Commands/CheckExpiringDocumentsCommand.php` - Expiry check

**Events:**
- `app/Domains/Hcm/Events/DocumentExpiringEvent.php` - Document expiring

**Listeners:**
- `app/Domains/Hcm/Listeners/SendDocumentExpiryNotificationListener.php` - Email notification

**Notifications:**
- `app/Notifications/DocumentExpiryNotification.php` - Email template

**Views:**
- `resources/views/emails/document-expiry.blade.php` - Email template

**Exceptions:**
- `app/Domains/Hcm/Exceptions/LeaveOverlapException.php` - Leave overlap
- `app/Domains/Hcm/Exceptions/InsufficientLeaveBalanceException.php` - Insufficient balance
- `app/Domains/Hcm/Exceptions/InvalidLeaveStatusException.php` - Invalid status
- `app/Domains/Hcm/Exceptions/InvalidLeaveDateException.php` - Invalid date
- `app/Domains/Hcm/Exceptions/InvalidExpiryDateException.php` - Invalid expiry
- `app/Domains/Hcm/Exceptions/NoManagerException.php` - No manager
- `app/Domains/Hcm/Exceptions/SelfApprovalException.php` - Self-approval
- `app/Domains/Hcm/Exceptions/UnauthorizedApproverException.php` - Unauthorized

**Service Provider (updated):**
- `app/Providers/AppServiceProvider.php` - Repository bindings, schedule registration

## 6. Testing

**Unit Tests (15 tests):**
- **TEST-001**: `test_document_type_enum_requires_expiry` - Test requiresExpiry()
- **TEST-002**: `test_document_expiry_status_computed` - Test is_expired, is_expiring_soon
- **TEST-003**: `test_leave_request_status_can_cancel` - Test canCancel()
- **TEST-004**: `test_leave_type_requires_balance` - Test requiresBalance()
- **TEST-005**: `test_leave_balance_available_calculated` - Test available attribute
- **TEST-006**: `test_expiry_date_after_issue_date` - Test CON-010
- **TEST-007**: `test_leave_start_date_not_past` - Test CON-007
- **TEST-008**: `test_leave_end_date_after_start` - Test CON-008
- **TEST-009**: `test_leave_overlap_detection` - Test hasOverlap()
- **TEST-010**: `test_leave_balance_service_checks_availability` - Test hasAvailableBalance()
- **TEST-011**: `test_document_scope_expiring_soon` - Test scopeExpiringSoon()
- **TEST-012**: `test_leave_scope_overlapping` - Test scopeOverlapping()
- **TEST-013**: `test_document_factory_generates_valid_data` - Test factory
- **TEST-014**: `test_leave_request_factory_valid` - Test factory
- **TEST-015**: `test_leave_balance_auto_creation` - Test getBalance()

**Feature Tests (20 tests):**
- **TEST-016**: `test_submit_leave_validates_overlap` - Test overlap detection
- **TEST-017**: `test_submit_leave_checks_balance` - Test CON-011
- **TEST-018**: `test_submit_leave_updates_pending_balance` - Test balance update
- **TEST-019**: `test_approve_leave_validates_status` - Test CON-009
- **TEST-020**: `test_approve_leave_prevents_self_approval` - Test BR-HCM-004
- **TEST-021**: `test_approve_leave_requires_manager` - Test authorization
- **TEST-022**: `test_approve_leave_updates_balances` - Test used/pending update
- **TEST-023**: `test_reject_leave_restores_pending_balance` - Test balance restoration
- **TEST-024**: `test_reject_leave_prevents_self_rejection` - Test BR-HCM-004
- **TEST-025**: `test_cancel_leave_by_employee` - Test employee cancellation
- **TEST-026**: `test_expiring_documents_command_dispatches_events` - Test EV-HCM-004
- **TEST-027**: `test_document_expiry_notification_sent` - Test listener
- **TEST-028**: `test_document_expiry_marked_notified` - Test tracking
- **TEST-029**: `test_activity_log_records_leave_actions` - Test GUD-007
- **TEST-030**: `test_leave_balance_first_or_create` - Test auto-creation
- **TEST-031**: `test_annual_leave_requires_balance` - Test leave type
- **TEST-032**: `test_sick_leave_no_balance_check` - Test leave type
- **TEST-033**: `test_document_scope_requires_renewal` - Test notification queue
- **TEST-034**: `test_hr_admin_can_approve_any_leave` - Test role-based approval
- **TEST-035**: `test_leave_request_lifecycle_complete` - Test full workflow

**Integration Tests (8 tests):**
- **TEST-036**: `test_leave_approval_atomic_transaction` - Test atomicity
- **TEST-037**: `test_document_expiry_notification_integration` - Test full flow
- **TEST-038**: `test_leave_balance_sync_with_requests` - Test consistency
- **TEST-039**: `test_repository_binding_resolves_correctly` - Test service container
- **TEST-040**: `test_eager_loading_prevents_n_plus_one` - Test query count
- **TEST-041**: `test_scheduled_command_runs_daily` - Test schedule
- **TEST-042**: `test_leave_request_event_listener_integration` - Test events
- **TEST-043**: `test_document_storage_integration` - Test file upload

**Performance Tests (2 tests):**
- **TEST-044**: `test_leave_overlap_check_fast` - Test query performance
- **TEST-045**: `test_expiring_documents_command_performance` - Test batch processing

## 7. Risks & Assumptions

**Risks:**
- **RISK-006**: Document expiry notifications mark as sent but email fails - **Mitigation**: Use queue for email sending, implement retry logic, log failures
- **RISK-007**: Leave overlap detection race condition - **Mitigation**: Use pessimistic locking during leave submission, unique constraint on employee+dates
- **RISK-008**: Leave balance goes negative due to concurrent approvals - **Mitigation**: Database transaction with lockForUpdate() on balance records
- **RISK-009**: Manager changes during pending leave approval - **Mitigation**: Allow HR admin override, store original manager_id on leave request
- **RISK-010**: File storage fills up with old documents - **Mitigation**: Implement document retention policy, archive after 7 years

**Assumptions:**
- **ASSUMPTION-006**: Most organizations have standard annual leave entitlement (21 days)
- **ASSUMPTION-007**: Leave approval turnaround time < 48 hours
- **ASSUMPTION-008**: Document expiry notifications sent 30 days before sufficient for renewals
- **ASSUMPTION-009**: Managers approve leave requests, HR admin has override capability
- **ASSUMPTION-010**: Leave balance calculation doesn't account for public holidays (future enhancement)

## 8. KIV for future implementations

- **KIV-011**: Implement document version control (replace expired document)
- **KIV-012**: Add bulk leave import from CSV (holiday calendar)
- **KIV-013**: Implement leave accrual rules (monthly accrual vs upfront)
- **KIV-014**: Add public holiday calendar integration for leave day calculation
- **KIV-015**: Implement leave carryforward rules (max days, expiry date)
- **KIV-016**: Add delegation feature (approve leaves when manager on leave)
- **KIV-017**: Implement leave forecasting and team calendar view
- **KIV-018**: Add document OCR for automatic metadata extraction
- **KIV-019**: Implement time and attendance tracking (clock in/out)
- **KIV-020**: Add benefits enrollment workflow

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB13-HCM.md](../prd/prd-01/PRD01-SUB13-HCM.md)
- **Related Sub-PRDs:**
  - PRD01-SUB02 (Authentication) - User authorization
  - PRD01-SUB22 (Notifications - optional) - Enhanced notifications
- **Related Plans:**
  - PRD01-SUB13-PLAN01 (Employee Management Foundation) - Prerequisites
- **External Documentation:**
  - Leave Management Best Practices: https://www.shrm.org/resourcesandtools/tools-and-samples/hr-qa/pages/leavemanagement.aspx
  - Document Expiry Tracking: https://www.documentcontrol.com/document-expiry-management
  - Laravel Queues: https://laravel.com/docs/queues
  - Laravel Task Scheduling: https://laravel.com/docs/scheduling
