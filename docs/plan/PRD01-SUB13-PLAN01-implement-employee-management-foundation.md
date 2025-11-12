---
plan: Implement HCM Employee Management Foundation and Lifecycle Workflows
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, hcm, employee-management, organizational-hierarchy, lifecycle-workflows, employee-history, gdpr-compliance]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan delivers the foundation of the Human Capital Management (HCM) module with employee master data management, organizational hierarchy with reporting relationships, employee lifecycle workflows (hire, transfer, promotion, termination), and employment history tracking. It implements UUID-based employee records, AES-256 encryption for sensitive personal data (SSN, ID numbers, salary), soft deletes for audit compliance, and GDPR-compliant data management (right to erasure after retention period). This plan establishes the core employee management system that supports 100,000+ employees per tenant with efficient hierarchical queries completing under 100ms.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-HCM-001**: Maintain **employee master data** including personal, job, and payroll information
- **FR-HCM-002**: Support **organizational hierarchy** with reporting relationships and department structure
- **FR-HCM-003**: Manage **employee lifecycle** (hire, transfer, promotion, termination) with workflow
- **FR-HCM-004**: Track **employment history** including position changes, salary adjustments, and transfers
- **FR-HCM-005**: Support **multiple employment types** (full-time, part-time, contract, intern)

**Business Rules:**
- **BR-HCM-001**: Disallow **deletion of employee records** with existing payroll or leave history
- **BR-HCM-002**: Employee IDs must be **unique within tenant**
- **BR-HCM-003**: Terminated employees cannot have **active leave or payroll** processing

**Data Requirements:**
- **DR-HCM-001**: Store **sensitive personal data** (SSN, ID numbers) with AES-256 encryption
- **DR-HCM-002**: Maintain **complete audit trail** of all employee data changes

**Integration Requirements:**
- **IR-HCM-002**: Integrate with **SUB15 (Backoffice)** for department and position management
- **IR-HCM-003**: Integrate with **SUB02 (Authentication)** for employee user account management

**Security Requirements:**
- **SR-HCM-001**: Implement **role-based access** to employee personal information
- **SR-HCM-002**: **Encrypt sensitive fields** (salary, SSN, bank account) at rest using Laravel encryption
- **SR-HCM-003**: Log all **access to employee records** for compliance auditing

**Performance Requirements:**
- **PR-HCM-001**: Employee record retrieval must complete under **200ms**
- **PR-HCM-002**: Organizational hierarchy query must return in **< 100ms** for 10k employees

**Scalability Requirements:**
- **SCR-HCM-001**: Support **100,000+ employee records** per tenant with efficient indexing

**Compliance Requirements:**
- **CR-HCM-001**: Comply with **GDPR** for employee personal data protection (right to access, rectification, erasure)
- **CR-HCM-002**: Support **right to erasure** for terminated employees after retention period

**Architecture Requirements:**
- **ARCH-HCM-001**: Use **soft deletes** for employee records to maintain referential integrity

**Events:**
- **EV-HCM-001**: Dispatch `EmployeeHiredEvent` when new employee is onboarded
- **EV-HCM-002**: Dispatch `EmployeeTerminatedEvent` when employment ends
- **EV-HCM-003**: Dispatch `EmployeeTransferredEvent` when employee changes department/position

**Constraints:**
- **CON-001**: Employee numbers must be unique per tenant
- **CON-002**: Hire date cannot be in the future
- **CON-003**: Termination date must be >= hire date
- **CON-004**: Manager must be active employee, cannot be self-referential
- **CON-005**: Cannot change employment history retroactively older than 90 days
- **CON-006**: Encrypted fields (SSN, salary) cannot be queried directly

**Guidelines:**
- **GUD-001**: Use PSR-12 coding standards, strict types, repository pattern, and Laravel Actions
- **GUD-002**: Log all employee data access using Spatie Activity Log
- **GUD-003**: Use UUID for employee primary keys for better distribution across tenants
- **GUD-004**: Index all foreign keys and frequently queried fields
- **GUD-005**: Cache organizational hierarchy for 15 minutes to meet PR-HCM-002

**Patterns:**
- **PAT-001**: Repository pattern with EmployeeRepositoryContract
- **PAT-002**: Strategy pattern for different employment types (full-time, part-time, contract, intern)
- **PAT-003**: Laravel Actions for HireEmployeeAction, TerminateEmployeeAction, TransferEmployeeAction
- **PAT-004**: Observer pattern for automatic history tracking on employee status changes
- **PAT-005**: Builder pattern for complex organizational hierarchy queries

## 2. Implementation Steps

### GOAL-001: Create Employee and Employment History Database Schema

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-HCM-001, FR-HCM-002, FR-HCM-004, FR-HCM-005, DR-HCM-001, BR-HCM-002, SCR-HCM-001, ARCH-HCM-001 | Implement employees and employee_employment_history tables with UUID primary keys, encryption support, and efficient indexing | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `database/migrations/YYYY_MM_DD_HHMMSS_create_employees_table.php` with namespace. Add `declare(strict_types=1);`. Use anonymous migration class format: `return new class extends Migration` | | |
| TASK-002 | In up() method, create `employees` table with columns: `id` (UUID PRIMARY KEY DEFAULT gen_random_uuid()), `tenant_id` (UUID NOT NULL), `employee_number` (VARCHAR 50 NOT NULL), `first_name` (VARCHAR 100 NOT NULL), `last_name` (VARCHAR 100 NOT NULL), `middle_name` (VARCHAR 100 NULL), `email` (VARCHAR 255 NULL UNIQUE), `phone` (VARCHAR 50 NULL), `date_of_birth` (DATE NULL), `gender` (VARCHAR 20 NULL - 'male', 'female', 'other', 'prefer_not_to_say'), `ssn_encrypted` (TEXT NULL - AES-256 encrypted), `national_id_encrypted` (TEXT NULL - encrypted), `address_line1` (VARCHAR 255 NULL), `address_line2` (VARCHAR 255 NULL), `city` (VARCHAR 100 NULL), `state` (VARCHAR 100 NULL), `postal_code` (VARCHAR 20 NULL), `country` (VARCHAR 100 NULL), `hire_date` (DATE NOT NULL), `termination_date` (DATE NULL), `employment_type` (VARCHAR 50 NOT NULL - 'full-time', 'part-time', 'contract', 'intern'), `status` (VARCHAR 20 NOT NULL DEFAULT 'active' - 'active', 'terminated', 'suspended'), `department_id` (BIGINT NULL), `position_id` (BIGINT NULL), `manager_id` (UUID NULL - self-referencing), `user_id` (BIGINT NULL - link to users table), `emergency_contact_name` (VARCHAR 100 NULL), `emergency_contact_phone` (VARCHAR 50 NULL), `emergency_contact_relationship` (VARCHAR 50 NULL), timestamps, `deleted_at` (TIMESTAMP NULL for soft deletes) | | |
| TASK-003 | Add unique constraint on employees: `UNIQUE KEY uk_emp_tenant_number (tenant_id, employee_number)` to enforce BR-HCM-002 | | |
| TASK-004 | Add indexes on employees for query performance (PR-HCM-001, PR-HCM-002, SCR-HCM-001): `INDEX idx_emp_tenant (tenant_id)`, `INDEX idx_emp_status (status)`, `INDEX idx_emp_manager (manager_id)` for hierarchy queries, `INDEX idx_emp_department (department_id)` for org charts, `INDEX idx_emp_hire_date (hire_date)` for anniversary reports, `INDEX idx_emp_user (user_id)` for authentication lookup, `INDEX idx_emp_email (email)` for user search, `INDEX idx_emp_deleted (deleted_at)` for soft delete queries | | |
| TASK-005 | Add foreign keys on employees: `FOREIGN KEY fk_emp_tenant (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE`, `FOREIGN KEY fk_emp_manager (manager_id) REFERENCES employees(id) ON DELETE SET NULL`, `FOREIGN KEY fk_emp_department (department_id) REFERENCES departments(id) ON DELETE SET NULL`, `FOREIGN KEY fk_emp_position (position_id) REFERENCES positions(id) ON DELETE SET NULL`, `FOREIGN KEY fk_emp_user (user_id) REFERENCES users(id) ON DELETE SET NULL` | | |
| TASK-006 | Create `employee_employment_history` table with columns: `id` (BIGSERIAL PRIMARY KEY), `employee_id` (UUID NOT NULL), `effective_date` (DATE NOT NULL), `event_type` (VARCHAR 50 NOT NULL - 'hire', 'transfer', 'promotion', 'demotion', 'salary_adjustment', 'termination'), `from_department_id` (BIGINT NULL), `to_department_id` (BIGINT NULL), `from_position_id` (BIGINT NULL), `to_position_id` (BIGINT NULL), `from_salary_encrypted` (TEXT NULL - encrypted), `to_salary_encrypted` (TEXT NULL - encrypted), `reason` (TEXT NULL), `notes` (TEXT NULL), `created_by` (BIGINT NOT NULL - user who made change), `created_at` (TIMESTAMP NOT NULL) | | |
| TASK-007 | Add indexes on employee_employment_history: `INDEX idx_emp_hist_employee (employee_id)`, `INDEX idx_emp_hist_date (effective_date)`, `INDEX idx_emp_hist_type (event_type)`, `INDEX idx_emp_hist_created_by (created_by)` | | |
| TASK-008 | Add foreign keys on employee_employment_history: `FOREIGN KEY fk_emp_hist_employee (employee_id) REFERENCES employees(id) ON DELETE CASCADE`, `FOREIGN KEY fk_emp_hist_from_dept (from_department_id) REFERENCES departments(id) ON DELETE SET NULL`, `FOREIGN KEY fk_emp_hist_to_dept (to_department_id) REFERENCES departments(id) ON DELETE SET NULL`, `FOREIGN KEY fk_emp_hist_from_pos (from_position_id) REFERENCES positions(id) ON DELETE SET NULL`, `FOREIGN KEY fk_emp_hist_to_pos (to_position_id) REFERENCES positions(id) ON DELETE SET NULL`, `FOREIGN KEY fk_emp_hist_created_by (created_by) REFERENCES users(id)` | | |
| TASK-009 | In down() method, drop tables in reverse order: `Schema::dropIfExists('employee_employment_history')`, then employees | | |

### GOAL-002: Create Employee Model with Encryption and Status Management

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-HCM-001, FR-HCM-005, DR-HCM-001, SR-HCM-002, ARCH-HCM-001 | Implement Employee model with field encryption, status management, and soft deletes | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-010 | Create `app/Domains/Hcm/Models/Employee.php` with namespace. Add `declare(strict_types=1);`. Import traits: `use BelongsToTenant, HasFactory, HasUuids, SoftDeletes, LogsActivity;` | | |
| TASK-011 | Define $fillable array: `['tenant_id', 'employee_number', 'first_name', 'last_name', 'middle_name', 'email', 'phone', 'date_of_birth', 'gender', 'ssn_encrypted', 'national_id_encrypted', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country', 'hire_date', 'termination_date', 'employment_type', 'status', 'department_id', 'position_id', 'manager_id', 'user_id', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship']` | | |
| TASK-012 | Define $casts array: `['hire_date' => 'date', 'termination_date' => 'date', 'date_of_birth' => 'date', 'status' => EmployeeStatus::class, 'employment_type' => EmploymentType::class, 'deleted_at' => 'datetime']`. Use UUIDs by default (HasUuids trait) | | |
| TASK-013 | Define $hidden array to prevent accidental exposure: `['ssn_encrypted', 'national_id_encrypted', 'deleted_at']`. Encrypted fields should never be serialized in API responses | | |
| TASK-014 | Create `app/Domains/Hcm/Enums/EmployeeStatus.php` as string-backed enum with cases: `ACTIVE = 'active'`, `TERMINATED = 'terminated'`, `SUSPENDED = 'suspended'`. Implement `label(): string`, `canEdit(): bool` (ACTIVE or SUSPENDED only), `isTerminated(): bool`, `canProcessPayroll(): bool` (ACTIVE only - BR-HCM-003) | | |
| TASK-015 | Create `app/Domains/Hcm/Enums/EmploymentType.php` as string-backed enum with cases: `FULL_TIME = 'full-time'`, `PART_TIME = 'part-time'`, `CONTRACT = 'contract'`, `INTERN = 'intern'`. Implement `label(): string`, `isFullTime(): bool`, `isEligibleForBenefits(): bool` (FULL_TIME and PART_TIME only), `requiresContract(): bool` (CONTRACT and INTERN only) | | |
| TASK-016 | Create `app/Domains/Hcm/Enums/EmploymentEventType.php` as string-backed enum with cases: `HIRE = 'hire'`, `TRANSFER = 'transfer'`, `PROMOTION = 'promotion'`, `DEMOTION = 'demotion'`, `SALARY_ADJUSTMENT = 'salary_adjustment'`, `TERMINATION = 'termination'`. Implement `label(): string`, `requiresDepartmentChange(): bool`, `requiresPositionChange(): bool`, `requiresSalaryChange(): bool` | | |
| TASK-017 | Implement `getActivitylogOptions(): LogOptions` in Employee: `return LogOptions::defaults()->logOnly(['employee_number', 'first_name', 'last_name', 'status', 'employment_type', 'department_id', 'position_id', 'hire_date', 'termination_date'])->logOnlyDirty()->dontSubmitEmptyLogs();`. Exclude encrypted fields from activity log (SR-HCM-003) | | |
| TASK-018 | Add relationships in Employee: `manager()` belongsTo Employee (self-referencing), `subordinates()` hasMany Employee as 'manager_id' for direct reports, `department()` belongsTo Department with withDefault(), `position()` belongsTo Position with withDefault(), `user()` belongsTo User with withDefault() (IR-HCM-003), `employmentHistory()` hasMany EmployeeEmploymentHistory ordered by effective_date DESC | | |
| TASK-019 | Implement accessor/mutator for encrypted fields (DR-HCM-001, SR-HCM-002): `getSsnAttribute(): ?string { return $this->ssn_encrypted ? decrypt($this->ssn_encrypted) : null; } setSsnAttribute(?string $value): void { $this->attributes['ssn_encrypted'] = $value ? encrypt($value) : null; }`. Repeat for national_id | | |
| TASK-020 | Add computed attributes: `getFullNameAttribute(): string { return trim("{$this->first_name} {$this->middle_name} {$this->last_name}"); }`, `getTenureYearsAttribute(): float { $start = $this->hire_date; $end = $this->termination_date ?? now(); return $start->diffInYears($end); }`, `getIsActiveAttribute(): bool { return $this->status === EmployeeStatus::ACTIVE; }` | | |
| TASK-021 | Add scopes: `scopeActive(Builder $query): Builder` filtering status ACTIVE, `scopeTerminated(Builder $query): Builder`, `scopeByDepartment(Builder $query, int $departmentId): Builder`, `scopeByManager(Builder $query, string $managerId): Builder`, `scopeByEmploymentType(Builder $query, EmploymentType $type): Builder`, `scopeHiredBetween(Builder $query, Carbon $from, Carbon $to): Builder` | | |
| TASK-022 | Implement validation in static boot (CON-002, CON-003, CON-004): `static::saving(function ($employee) { if ($employee->hire_date && $employee->hire_date->isFuture()) { throw new InvalidHireDateException('Hire date cannot be in the future'); } if ($employee->termination_date && $employee->termination_date->lt($employee->hire_date)) { throw new InvalidTerminationDateException('Termination date must be after hire date'); } if ($employee->manager_id === $employee->id) { throw new SelfReferencingManagerException('Employee cannot be their own manager'); } });` | | |
| TASK-023 | Implement `canDelete(): bool` method for BR-HCM-001: `return !$this->employmentHistory()->exists() && !$this->leaveRequests()->exists() && !$this->payrollRecords()->exists();`. Check if employee has any history records | | |
| TASK-024 | Implement GDPR compliance method (CR-HCM-001, CR-HCM-002): `anonymize(): void { DB::transaction(function() { $this->update(['first_name' => 'ANONYMIZED', 'last_name' => 'ANONYMIZED', 'email' => null, 'phone' => null, 'ssn_encrypted' => null, 'national_id_encrypted' => null, 'address_line1' => null, 'date_of_birth' => null]); activity()->log('Employee data anonymized for GDPR compliance', $this); }); }`. Right to erasure after retention | | |

### GOAL-003: Implement Employee Repository with Hierarchy Queries

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PAT-001, PR-HCM-001, PR-HCM-002, SCR-HCM-001, GUD-005 | Create repository contract and implementation with efficient hierarchical queries and caching | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-025 | Create `app/Domains/Hcm/Contracts/EmployeeRepositoryContract.php` with namespace. Add `declare(strict_types=1);`. Define interface methods: `findById(string $id): ?Employee`, `findByNumber(string $number, ?string $tenantId = null): ?Employee`, `findByEmail(string $email): ?Employee`, `findByUserId(int $userId): ?Employee`, `getActive(?string $tenantId = null): Collection`, `getByDepartment(int $departmentId): Collection`, `getDirectReports(string $managerId): Collection`, `getAllSubordinates(string $managerId): Collection` for recursive hierarchy, `getOrganizationalHierarchy(?string $rootManagerId = null): array`, `paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator`, `create(array $data): Employee`, `update(Employee $employee, array $data): Employee` | | |
| TASK-026 | Create `app/Domains/Hcm/Repositories/DatabaseEmployeeRepository.php` implementing EmployeeRepositoryContract. Add `declare(strict_types=1);`. Constructor has no dependencies | | |
| TASK-027 | Implement `findByNumber()` with tenant isolation: `return Employee::where('tenant_id', $tenantId ?? tenant_id())->where('employee_number', $number)->first();`. Used for employee lookup by HR (PR-HCM-001) | | |
| TASK-028 | Implement `getDirectReports()` with eager loading: `return Employee::with(['position', 'department'])->where('manager_id', $managerId)->where('status', EmployeeStatus::ACTIVE)->orderBy('first_name')->get();`. For manager dashboards | | |
| TASK-029 | Implement `getAllSubordinates()` using recursive CTE for PostgreSQL (PR-HCM-002, GUD-005): `return Cache::remember("employee_subordinates_{$managerId}", 900, function() use ($managerId) { return DB::table('employees')->selectRaw('id, first_name, last_name, manager_id, department_id')->whereRaw("id IN (WITH RECURSIVE subordinates AS (SELECT id, manager_id FROM employees WHERE manager_id = ? UNION SELECT e.id, e.manager_id FROM employees e INNER JOIN subordinates s ON e.manager_id = s.id) SELECT id FROM subordinates)", [$managerId])->get(); });`. Cache for 15 minutes | | |
| TASK-030 | Implement `getOrganizationalHierarchy()` method building tree structure (PR-HCM-002): `return Cache::remember("org_hierarchy_{$rootManagerId}", 900, function() use ($rootManagerId) { $employees = Employee::with(['position', 'department'])->active()->when($rootManagerId, fn($q) => $q->where('manager_id', $rootManagerId), fn($q) => $q->whereNull('manager_id'))->get(); return $this->buildHierarchyTree($employees); });`. Returns nested array with children | | |
| TASK-031 | Implement `buildHierarchyTree(Collection $employees): array` private helper: `$tree = []; foreach ($employees as $employee) { $node = ['id' => $employee->id, 'name' => $employee->full_name, 'position' => $employee->position?->name, 'department' => $employee->department?->name, 'children' => $this->buildHierarchyTree($employee->subordinates)]; $tree[] = $node; } return $tree;`. Recursive tree builder | | |
| TASK-032 | Implement `paginate()` with filters: Support filters: `status` (string), `department_id` (int), `employment_type` (string), `manager_id` (UUID), `hire_date_from`, `hire_date_to`, `search` (name/email/employee_number). Build query with conditional filters, eager load relationships: `['department', 'position', 'manager']` | | |
| TASK-033 | Bind contract in AppServiceProvider: `$this->app->bind(EmployeeRepositoryContract::class, DatabaseEmployeeRepository::class);` | | |

### GOAL-004: Implement Employee Lifecycle Actions (Hire, Transfer, Terminate)

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-HCM-003, FR-HCM-004, EV-HCM-001, EV-HCM-002, EV-HCM-003, PAT-003, PAT-004 | Create lifecycle management actions with automatic history tracking and event dispatching | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-034 | Create `app/Domains/Hcm/Actions/HireEmployeeAction.php` with namespace. Add `declare(strict_types=1);`. Use AsAction trait. Constructor: `public function __construct(private readonly EmployeeRepositoryContract $employeeRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-035 | Implement `handle(array $data): Employee` in HireEmployeeAction. Step 1: Validate employee number unique: `if ($this->employeeRepo->findByNumber($data['employee_number'], tenant_id())) { throw new DuplicateEmployeeNumberException(); }` (BR-HCM-002, CON-001) | | |
| TASK-036 | Step 2: Validate hire date (CON-002): `$hireDate = Carbon::parse($data['hire_date']); if ($hireDate->isFuture()) { throw new InvalidHireDateException('Hire date cannot be in the future'); }` | | |
| TASK-037 | Step 3: Validate manager if provided (CON-004): `if (isset($data['manager_id'])) { $manager = $this->employeeRepo->findById($data['manager_id']); if (!$manager || !$manager->is_active) { throw new InvalidManagerException('Manager must be an active employee'); } }` | | |
| TASK-038 | Step 4: Create employee in transaction: `DB::transaction(function() use ($data) { $employeeData = array_merge($data, ['tenant_id' => tenant_id(), 'status' => EmployeeStatus::ACTIVE]); $employee = $this->employeeRepo->create($employeeData); $this->createHireHistory($employee); event(new EmployeeHiredEvent($employee, $employee->hire_date)); $this->activityLogger->log("Employee hired: {$employee->full_name} ({$employee->employee_number})", $employee, auth()->user()); return $employee; });` | | |
| TASK-039 | Implement `createHireHistory(Employee $employee): void` private method: `EmployeeEmploymentHistory::create(['employee_id' => $employee->id, 'effective_date' => $employee->hire_date, 'event_type' => EmploymentEventType::HIRE, 'to_department_id' => $employee->department_id, 'to_position_id' => $employee->position_id, 'reason' => 'Initial hire', 'created_by' => auth()->id()]);`. Auto-create history record (FR-HCM-004) | | |
| TASK-040 | Create `app/Domains/Hcm/Actions/TransferEmployeeAction.php` with AsAction trait. Constructor: `public function __construct(private readonly EmployeeRepositoryContract $employeeRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-041 | Implement `handle(Employee $employee, int $toDepartmentId, ?int $toPositionId = null, ?string $reason = null): Employee` in TransferEmployeeAction. Step 1: Validate employee can be transferred: `if (!$employee->status->canEdit()) { throw new CannotTransferException('Employee status does not allow transfers'); }` | | |
| TASK-042 | Step 2: Validate new department exists: `$newDepartment = Department::find($toDepartmentId); if (!$newDepartment) { throw new DepartmentNotFoundException(); }` | | |
| TASK-043 | Step 3: Process transfer in transaction: `DB::transaction(function() use ($employee, $toDepartmentId, $toPositionId, $reason) { $fromDepartmentId = $employee->department_id; $fromPositionId = $employee->position_id; $employee->update(['department_id' => $toDepartmentId, 'position_id' => $toPositionId ?? $employee->position_id]); $this->createTransferHistory($employee, $fromDepartmentId, $toDepartmentId, $fromPositionId, $toPositionId, $reason); event(new EmployeeTransferredEvent($employee, $fromDepartmentId, $toDepartmentId)); $this->activityLogger->log("Employee transferred: {$employee->full_name} to department {$toDepartmentId}", $employee, auth()->user()); Cache::forget("org_hierarchy_*"); return $employee->fresh(['department', 'position']); });`. Clear hierarchy cache | | |
| TASK-044 | Implement `createTransferHistory()` private method: `EmployeeEmploymentHistory::create(['employee_id' => $employee->id, 'effective_date' => now(), 'event_type' => EmploymentEventType::TRANSFER, 'from_department_id' => $fromDepartmentId, 'to_department_id' => $toDepartmentId, 'from_position_id' => $fromPositionId, 'to_position_id' => $toPositionId, 'reason' => $reason, 'created_by' => auth()->id()]);` | | |
| TASK-045 | Create `app/Domains/Hcm/Actions/TerminateEmployeeAction.php` with AsAction trait. Constructor: `public function __construct(private readonly EmployeeRepositoryContract $employeeRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-046 | Implement `handle(Employee $employee, Carbon $terminationDate, string $reason): Employee` in TerminateEmployeeAction. Step 1: Validate termination date (CON-003): `if ($terminationDate->lt($employee->hire_date)) { throw new InvalidTerminationDateException('Termination date cannot be before hire date'); }` | | |
| TASK-047 | Step 2: Check for active dependencies (BR-HCM-003): `if ($employee->leaveRequests()->where('status', 'approved')->exists()) { throw new HasActiveLeavesException('Employee has active approved leaves'); }` | | |
| TASK-048 | Step 3: Process termination in transaction: `DB::transaction(function() use ($employee, $terminationDate, $reason) { $employee->update(['status' => EmployeeStatus::TERMINATED, 'termination_date' => $terminationDate]); $this->createTerminationHistory($employee, $reason); event(new EmployeeTerminatedEvent($employee, $terminationDate, $reason)); $this->activityLogger->log("Employee terminated: {$employee->full_name} - {$reason}", $employee, auth()->user()); Cache::forget("org_hierarchy_*"); return $employee->fresh(); });` | | |
| TASK-049 | Implement `createTerminationHistory()` private method: `EmployeeEmploymentHistory::create(['employee_id' => $employee->id, 'effective_date' => $employee->termination_date, 'event_type' => EmploymentEventType::TERMINATION, 'from_department_id' => $employee->department_id, 'from_position_id' => $employee->position_id, 'reason' => $reason, 'created_by' => auth()->id()]);` | | |
| TASK-050 | Create events: `app/Domains/Hcm/Events/EmployeeHiredEvent.php` with properties: `public readonly Employee $employee, public readonly Carbon $hireDate`. Similarly create `EmployeeTerminatedEvent.php` with `public readonly Employee $employee, public readonly Carbon $terminationDate, public readonly string $reason`, and `EmployeeTransferredEvent.php` with `public readonly Employee $employee, public readonly int $fromDepartmentId, public readonly int $toDepartmentId` | | |
| TASK-051 | Create exceptions: `app/Domains/Hcm/Exceptions/DuplicateEmployeeNumberException.php`, `InvalidHireDateException.php`, `InvalidTerminationDateException.php`, `SelfReferencingManagerException.php`, `InvalidManagerException.php`, `CannotTransferException.php`, `DepartmentNotFoundException.php`, `HasActiveLeavesException.php` | | |

### GOAL-005: Implement Employee History Tracking Model and Observer

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-HCM-004, DR-HCM-002, PAT-004 | Create employment history model with encryption and automatic observer for tracking changes | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-052 | Create `app/Domains/Hcm/Models/EmployeeEmploymentHistory.php` with namespace. Add `declare(strict_types=1);`. Use `HasFactory` trait | | |
| TASK-053 | Define $fillable: `['employee_id', 'effective_date', 'event_type', 'from_department_id', 'to_department_id', 'from_position_id', 'to_position_id', 'from_salary_encrypted', 'to_salary_encrypted', 'reason', 'notes', 'created_by']`. Define $casts: `['effective_date' => 'date', 'event_type' => EmploymentEventType::class, 'created_at' => 'datetime']` | | |
| TASK-054 | Add relationships: `employee()` belongsTo Employee, `fromDepartment()` belongsTo Department with withDefault(), `toDepartment()` belongsTo Department with withDefault(), `fromPosition()` belongsTo Position with withDefault(), `toPosition()` belongsTo Position with withDefault(), `creator()` belongsTo User (created_by) with withDefault() | | |
| TASK-055 | Implement accessor/mutator for encrypted salary fields (DR-HCM-001, SR-HCM-002): `getFromSalaryAttribute(): ?float { return $this->from_salary_encrypted ? (float)decrypt($this->from_salary_encrypted) : null; } setFromSalaryAttribute(?float $value): void { $this->attributes['from_salary_encrypted'] = $value ? encrypt((string)$value) : null; }`. Repeat for to_salary | | |
| TASK-056 | Add scopes: `scopeByEmployee(Builder $query, string $employeeId): Builder`, `scopeByEventType(Builder $query, EmploymentEventType $type): Builder`, `scopeRecent(Builder $query, int $days = 30): Builder` filtering created_at within N days, `scopeBeforeDate(Builder $query, Carbon $date): Builder` | | |
| TASK-057 | Implement validation in static boot (CON-005): `static::creating(function ($history) { $oldestAllowed = now()->subDays(90); if ($history->effective_date->lt($oldestAllowed)) { throw new HistoryTooOldException("Cannot create history records older than 90 days"); } });`. Prevent backdating beyond 90 days | | |
| TASK-058 | Create `app/Domains/Hcm/Observers/EmployeeObserver.php` with namespace. Add `declare(strict_types=1);`. Implement `updated(Employee $employee): void` method | | |
| TASK-059 | In `updated()` method, detect significant changes and create history: `$significantFields = ['department_id', 'position_id', 'status']; $changed = array_intersect_key($employee->getChanges(), array_flip($significantFields)); if (!empty($changed)) { if (isset($changed['department_id'])) { EmployeeEmploymentHistory::create(['employee_id' => $employee->id, 'effective_date' => now(), 'event_type' => EmploymentEventType::TRANSFER, 'from_department_id' => $employee->getOriginal('department_id'), 'to_department_id' => $employee->department_id, 'created_by' => auth()->id()]); } }`. Auto-track department changes (PAT-004) | | |
| TASK-060 | Register observer in service provider: `Employee::observe(EmployeeObserver::class);` in `AppServiceProvider::boot()` | | |

## 3. Alternatives

- **ALT-001**: Use integer IDs instead of UUIDs - **Rejected** because UUIDs provide better distribution across tenants and avoid ID conflicts in mergers (SCR-HCM-001)
- **ALT-002**: Store salary in plain text - **Rejected** violates SR-HCM-002 and exposes sensitive data
- **ALT-003**: Use hard deletes for employees - **Rejected** violates ARCH-HCM-001, loses audit trail
- **ALT-004**: Cache organizational hierarchy indefinitely - **Rejected** because hierarchy changes require immediate updates, 15-minute TTL balances performance and freshness
- **ALT-005**: Use nested set model for hierarchy - **Deferred** to future optimization if recursive CTE performance inadequate, recursive approach simpler for MVP

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `laravel/framework` ^12.0
- **DEP-002**: `spatie/laravel-activitylog` ^4.0 (audit logging)
- **DEP-003**: `lorisleiva/laravel-actions` ^2.0 (action pattern)
- **DEP-004**: `ramsey/uuid` ^4.7 (UUID generation - included in Laravel)

**Internal Dependencies:**
- **DEP-022**: PRD01-SUB01 (Multi-Tenancy) - Tenant isolation for employee data
- **DEP-023**: PRD01-SUB02 (Authentication & Authorization) - User accounts for employees (IR-HCM-003)
- **DEP-024**: PRD01-SUB03 (Audit Logging) - Activity logging for employee changes (DR-HCM-002)
- **DEP-025**: PRD01-SUB15 (Backoffice) - Department and Position management (IR-HCM-002)

**Infrastructure:**
- **DEP-026**: PostgreSQL database with recursive CTE support for hierarchy queries
- **DEP-027**: Redis cache for organizational hierarchy caching (GUD-005)

## 5. Files

**Migrations:**
- `database/migrations/YYYY_MM_DD_HHMMSS_create_employees_table.php` - Employees master
- `database/migrations/YYYY_MM_DD_HHMMSS_create_employee_employment_history_table.php` - History

**Models:**
- `app/Domains/Hcm/Models/Employee.php` - Employee master
- `app/Domains/Hcm/Models/EmployeeEmploymentHistory.php` - History tracking

**Enums:**
- `app/Domains/Hcm/Enums/EmployeeStatus.php` - Employee lifecycle status
- `app/Domains/Hcm/Enums/EmploymentType.php` - Employment types
- `app/Domains/Hcm/Enums/EmploymentEventType.php` - History event types

**Contracts:**
- `app/Domains/Hcm/Contracts/EmployeeRepositoryContract.php` - Employee repository

**Repositories:**
- `app/Domains/Hcm/Repositories/DatabaseEmployeeRepository.php` - Employee repo

**Actions:**
- `app/Domains/Hcm/Actions/HireEmployeeAction.php` - Hire employee
- `app/Domains/Hcm/Actions/TransferEmployeeAction.php` - Transfer employee
- `app/Domains/Hcm/Actions/TerminateEmployeeAction.php` - Terminate employee

**Events:**
- `app/Domains/Hcm/Events/EmployeeHiredEvent.php` - Employee hired
- `app/Domains/Hcm/Events/EmployeeTerminatedEvent.php` - Employee terminated
- `app/Domains/Hcm/Events/EmployeeTransferredEvent.php` - Employee transferred

**Observers:**
- `app/Domains/Hcm/Observers/EmployeeObserver.php` - Auto history tracking

**Exceptions:**
- `app/Domains/Hcm/Exceptions/DuplicateEmployeeNumberException.php` - Duplicate employee #
- `app/Domains/Hcm/Exceptions/InvalidHireDateException.php` - Invalid hire date
- `app/Domains/Hcm/Exceptions/InvalidTerminationDateException.php` - Invalid termination date
- `app/Domains/Hcm/Exceptions/SelfReferencingManagerException.php` - Self-referencing manager
- `app/Domains/Hcm/Exceptions/InvalidManagerException.php` - Invalid manager
- `app/Domains/Hcm/Exceptions/CannotTransferException.php` - Cannot transfer
- `app/Domains/Hcm/Exceptions/DepartmentNotFoundException.php` - Department not found
- `app/Domains/Hcm/Exceptions/HasActiveLeavesException.php` - Has active leaves
- `app/Domains/Hcm/Exceptions/HistoryTooOldException.php` - History too old

**Service Provider (updated):**
- `app/Providers/AppServiceProvider.php` - Repository bindings, observer registration

## 6. Testing

**Unit Tests (20 tests):**
- **TEST-001**: `test_employee_status_enum_has_all_cases` - Verify 3 status cases
- **TEST-002**: `test_employment_type_enum_benefits_eligibility` - Test isEligibleForBenefits()
- **TEST-003**: `test_employee_full_name_computed` - Test getFullNameAttribute()
- **TEST-004**: `test_employee_tenure_calculated` - Test getTenureYearsAttribute()
- **TEST-005**: `test_ssn_encryption_decryption` - Test encrypted accessor/mutator
- **TEST-006**: `test_employee_number_unique_per_tenant` - Test BR-HCM-002
- **TEST-007**: `test_hire_date_cannot_be_future` - Test CON-002
- **TEST-008**: `test_termination_date_after_hire_date` - Test CON-003
- **TEST-009**: `test_cannot_set_self_as_manager` - Test CON-004
- **TEST-010**: `test_employee_can_delete_checks_history` - Test BR-HCM-001
- **TEST-011**: `test_employee_anonymize_gdpr` - Test CR-HCM-002
- **TEST-012**: `test_repository_finds_by_email` - Test findByEmail()
- **TEST-013**: `test_repository_gets_direct_reports` - Test getDirectReports()
- **TEST-014**: `test_recursive_subordinates_query` - Test getAllSubordinates()
- **TEST-015**: `test_organizational_hierarchy_tree` - Test getOrganizationalHierarchy()
- **TEST-016**: `test_employment_history_salary_encryption` - Test salary accessor/mutator
- **TEST-017**: `test_history_cannot_be_backdated_90_days` - Test CON-005
- **TEST-018**: `test_employee_factory_generates_valid_data` - Test factory
- **TEST-019**: `test_employee_scope_active_works` - Test scopeActive()
- **TEST-020**: `test_soft_delete_maintains_relationships` - Test ARCH-HCM-001

**Feature Tests (18 tests):**
- **TEST-021**: `test_hire_employee_action_validates_unique_number` - Test BR-HCM-002
- **TEST-022**: `test_hire_employee_creates_history_record` - Test FR-HCM-004
- **TEST-023**: `test_hire_employee_dispatches_event` - Test EV-HCM-001
- **TEST-024**: `test_transfer_employee_updates_department` - Test FR-HCM-003
- **TEST-025**: `test_transfer_employee_creates_history` - Test FR-HCM-004
- **TEST-026**: `test_transfer_employee_dispatches_event` - Test EV-HCM-003
- **TEST-027**: `test_terminate_employee_validates_date` - Test CON-003
- **TEST-028**: `test_terminate_employee_checks_active_leaves` - Test BR-HCM-003
- **TEST-029**: `test_terminate_employee_creates_history` - Test FR-HCM-004
- **TEST-030**: `test_terminate_employee_dispatches_event` - Test EV-HCM-002
- **TEST-031**: `test_employee_observer_tracks_department_change` - Test PAT-004
- **TEST-032**: `test_activity_log_records_employee_changes` - Test SR-HCM-003
- **TEST-033**: `test_encrypted_fields_not_in_api_response` - Test $hidden array
- **TEST-034**: `test_employment_types_all_supported` - Test FR-HCM-005
- **TEST-035**: `test_hierarchy_cache_cleared_on_transfer` - Test GUD-005
- **TEST-036**: `test_manager_cannot_be_inactive` - Test CON-004
- **TEST-037**: `test_gdpr_anonymization_workflow` - Test CR-HCM-001
- **TEST-038**: `test_employee_soft_delete_preserves_relationships` - Test ARCH-HCM-001

**Integration Tests (8 tests):**
- **TEST-039**: `test_hire_transfer_terminate_lifecycle` - Test complete workflow
- **TEST-040**: `test_employee_history_timeline` - Test chronological history
- **TEST-041**: `test_organizational_hierarchy_integration` - Test full hierarchy
- **TEST-042**: `test_repository_binding_resolves_correctly` - Test service container
- **TEST-043**: `test_eager_loading_prevents_n_plus_one` - Test query count
- **TEST-044**: `test_employee_department_integration` - Test IR-HCM-002
- **TEST-045**: `test_employee_user_account_integration` - Test IR-HCM-003
- **TEST-046**: `test_audit_log_integration` - Test DR-HCM-002

**Performance Tests (2 tests):**
- **TEST-047**: `test_employee_retrieval_under_200ms` - Test PR-HCM-001
- **TEST-048**: `test_hierarchy_query_under_100ms_for_10k` - Test PR-HCM-002

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Recursive hierarchy queries timeout on deeply nested structures - **Mitigation**: Limit hierarchy depth to 10 levels, cache results for 15 minutes, use database indexes
- **RISK-002**: Encrypted field queries cause performance degradation - **Mitigation**: Never query encrypted fields directly (CON-006), use unencrypted indexed fields for search
- **RISK-003**: Employee history grows unbounded - **Mitigation**: Archive history older than 7 years to separate table
- **RISK-004**: Cache invalidation misses cause stale hierarchy - **Mitigation**: Use cache tags for granular invalidation, fall back to direct query if cache empty
- **RISK-005**: GDPR right to erasure conflicts with audit requirements - **Mitigation**: Anonymize instead of delete, retain employee_number and audit logs

**Assumptions:**
- **ASSUMPTION-001**: Most organizations have hierarchy depth < 5 levels
- **ASSUMPTION-002**: Employee turnover rate < 20% annually, history growth manageable
- **ASSUMPTION-003**: SSN and national ID never change after hire (no update workflow needed)
- **ASSUMPTION-004**: Manager changes trigger automatic notification (implemented in separate listener)
- **ASSUMPTION-005**: Encrypted salary field only used for history tracking, payroll uses separate encrypted table

## 8. KIV for future implementations

- **KIV-001**: Implement bulk employee import from CSV/Excel
- **KIV-002**: Add employee photo management with facial recognition
- **KIV-003**: Implement employee skills and competency tracking
- **KIV-004**: Add emergency contact validation (phone number format, relationship types)
- **KIV-005**: Implement position-based salary ranges and validation
- **KIV-006**: Add employee rehire workflow (formerly terminated employees)
- **KIV-007**: Implement matrix reporting (multiple managers/dotted line reporting)
- **KIV-008**: Add employee anniversary and birthday notifications
- **KIV-009**: Implement employee document version control
- **KIV-010**: Add employee performance rating integration with history

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB13-HCM.md](../prd/prd-01/PRD01-SUB13-HCM.md)
- **Related Sub-PRDs:**
  - PRD01-SUB15 (Backoffice) - Department and position management
  - PRD01-SUB02 (Authentication) - User account management
  - PRD01-SUB03 (Audit Logging) - Activity logging for compliance
- **External Documentation:**
  - GDPR Compliance Guide: https://gdpr.eu/what-is-gdpr/
  - Laravel Encryption: https://laravel.com/docs/encryption
  - PostgreSQL Recursive Queries: https://www.postgresql.org/docs/current/queries-with.html
  - Organizational Hierarchy Patterns: https://martinfowler.com/eaaCatalog/hierarchyPattern.html
