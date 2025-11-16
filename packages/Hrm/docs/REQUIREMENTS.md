
## Functional Requirements

### 1. Employee Lifecycle Management

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-HRM-EMP-001** | Manage **employee master data** with personal information, emergency contacts, and dependents | High |
| **FR-HRM-EMP-002** | Track **employment contracts** with start date, probation period, position, and employment type | High |
| **FR-HRM-EMP-003** | Implement **employee lifecycle states** (prospect → active → probation → permanent → notice → terminated) | High |
| **FR-HRM-EMP-004** | Support **automatic org hierarchy** integration via OrganizationServiceContract (manager, subordinates, department queries) | High |
| **FR-HRM-EMP-005** | Track **employment history** with position changes, transfers, and promotions | Medium |
| **FR-HRM-EMP-006** | Manage **employee documents** with secure storage, version control, and expiry tracking | Medium |

### 2. Leave Management

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-HRM-LEAVE-001** | Define **leave types** (annual, sick, maternity, unpaid, custom) with entitlement rules | High |
| **FR-HRM-LEAVE-002** | Calculate **automatic leave entitlements** with pro-rata, carry-forward, and seniority-based rules | High |
| **FR-HRM-LEAVE-003** | Process **leave requests** with workflow integration for approval routing | High |
| **FR-HRM-LEAVE-004** | Track **leave balances** in real-time with YTD tracking and negative balance handling | High |
| **FR-HRM-LEAVE-005** | Support **leave adjustments** with audit trail and reason tracking | Medium |
| **FR-HRM-LEAVE-006** | Generate **leave reports** (balance summary, usage patterns, departmental analytics) | Medium |

### 3. Attendance & Time Tracking

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-HRM-ATT-001** | Record **clock-in/clock-out** events with timestamp and optional geolocation | High |
| **FR-HRM-ATT-002** | Track **break times** and **overtime hours** with automatic calculation | High |
| **FR-HRM-ATT-003** | Manage **shift schedules** with recurring patterns and shift swapping | High |
| **FR-HRM-ATT-004** | Handle **roster management** with team assignments and coverage tracking | Medium |
| **FR-HRM-ATT-005** | Support **flexible work arrangements** (remote work, flexible hours, compressed weeks) | Medium |
| **FR-HRM-ATT-006** | Generate **attendance reports** (monthly summary, absenteeism, tardiness analytics) | High |

### 4. Performance Management

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-HRM-PERF-001** | Define **review cycles** with scheduled periods (annual, bi-annual, quarterly) | High |
| **FR-HRM-PERF-002** | Support **customizable review templates** with weighted KPIs and competency frameworks | High |
| **FR-HRM-PERF-003** | Enable **360-degree feedback** with peer, manager, and self-assessment capabilities | High |
| **FR-HRM-PERF-004** | Track **goal setting and OKRs** with progress monitoring and milestone tracking | Medium |
| **FR-HRM-PERF-005** | Support **performance calibration** sessions with comparison across departments | Medium |
| **FR-HRM-PERF-006** | Generate **performance analytics** (top performers, improvement areas, distribution curves) | Medium |

### 5. Disciplinary & Grievance Management

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-HRM-DISC-001** | Track **disciplinary cases** with severity levels (verbal → written → suspension → termination) | High |
| **FR-HRM-DISC-002** | Integrate with **workflow engine** for escalation and approval routing | High |
| **FR-HRM-DISC-003** | Record **grievances** with investigation tracking and resolution status | Medium |
| **FR-HRM-DISC-004** | Generate **case reports** with timeline visualization and audit trail | Medium |
| **FR-HRM-DISC-005** | Support **document attachments** for evidence, witness statements, and resolutions | Medium |

### 6. Training & Development

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-HRM-TRN-001** | Maintain **training catalog** with course details, duration, and providers | High |
| **FR-HRM-TRN-002** | Track **training enrollments** and **completion records** with certification generation | High |
| **FR-HRM-TRN-003** | Manage **certification expiry** with automatic reminders and renewal workflows | High |
| **FR-HRM-TRN-004** | Calculate **training budgets** per employee with departmental allocation tracking | Medium |
| **FR-HRM-TRN-005** | Generate **skills matrix** showing competency levels across the organization | Medium |

### 7. Recruitment Integration (Phase 2)

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-HRM-REC-001** | Manage **job vacancies** with position requirements and approval workflow | Low |
| **FR-HRM-REC-002** | Track **candidate pipeline** with stages (screening → interview → offer → onboarding) | Low |
| **FR-HRM-REC-003** | Support **interview scheduling** with panel coordination and feedback collection | Low |
| **FR-HRM-REC-004** | Automate **offer letter generation** with template management | Low |

---

## Business Rules

| Rule ID | Description | Scope |
|---------|-------------|-------|
| **BR-HRM-001** | Employees MUST have **active contract** before leave accrual begins | Leave |
| **BR-HRM-002** | Leave requests CANNOT exceed **available balance** unless negative balance policy enabled | Leave |
| **BR-HRM-003** | **Probation completion** required before permanent leave entitlements activate | Employee, Leave |
| **BR-HRM-004** | Attendance records MUST NOT overlap for same employee (prevent duplicate clock-ins) | Attendance |
| **BR-HRM-005** | Performance reviews MUST be conducted by **employee's direct manager** or authorized delegate | Performance |
| **BR-HRM-006** | Disciplinary actions require **documented evidence** and approval workflow completion | Disciplinary |
| **BR-HRM-007** | Training certifications with expiry dates trigger **automatic reminders 30 days before expiry** | Training |
| **BR-HRM-008** | Employee termination MUST trigger **automatic leave balance calculation** and final settlement | Employee |

---

## Data Requirements

| Requirement ID | Description | Scope |
|----------------|-------------|-------|
| **DR-HRM-001** | Employee master table: personal data, emergency contacts, dependents (JSON), employment status | Employee |
| **DR-HRM-002** | Employment contracts table: contract type, start/end dates, position, probation period, work schedule | Employee |
| **DR-HRM-003** | Leave entitlements table: employee_id, leave_type_id, year, entitled_days, used_days, carried_forward | Leave |
| **DR-HRM-004** | Leave requests table: employee_id, leave_type_id, dates, status, approval_chain, workflow_instance_id | Leave |
| **DR-HRM-005** | Attendance records table: employee_id, clock_in, clock_out, break_duration, overtime, location | Attendance |
| **DR-HRM-006** | Performance reviews table: employee_id, review_cycle_id, reviewer_id, scores, comments, status | Performance |
| **DR-HRM-007** | Disciplinary cases table: employee_id, case_type, severity, status, resolution, case_handler_id | Disciplinary |
| **DR-HRM-008** | Training records table: employee_id, training_id, completion_date, certification_number, expiry_date | Training |

---

## Integration Requirements

### Internal Package Communication

| Component | Integration Method | Implementation |
|-----------|-------------------|----------------|
| **Nexus\Backoffice** | Event-driven & Contract | Listen to `EmployeeCreatedEvent` from backoffice; use `OrganizationServiceContract` for hierarchy queries |
| **Nexus\Workflow** | Service contract | Use `WorkflowServiceContract` for leave/disciplinary approvals, performance review routing |
| **Nexus\Tenancy** | Event-driven | Listen to `TenantCreatedEvent` for default leave policy setup |
| **Nexus\AuditLog** | Service contract | Use `ActivityLoggerContract` for all employee lifecycle changes |
| **Nexus\Settings** | Service contract | Use `SettingsServiceContract` for HR policies, leave rules, working hour configurations |


## Performance Requirements

| Requirement ID | Description | Target |
|----------------|-------------|--------|
| **PR-HRM-001** | Employee search across 100K records | < 500ms |
| **PR-HRM-002** | Leave balance calculation with complex rules | < 200ms |
| **PR-HRM-003** | Monthly attendance report generation (1000 employees) | < 5 seconds |
| **PR-HRM-004** | Performance review data aggregation (department-level) | < 2 seconds |
| **PR-HRM-005** | Real-time leave balance check during request submission | < 100ms |

---

## Security Requirements

| Requirement ID | Description |
|----------------|-------------|
| **SR-HRM-001** | Implement audit logging for all employee data changes using `ActivityLoggerContract` |
| **SR-HRM-002** | Enforce tenant isolation for all HR data via tenant scoping |
| **SR-HRM-003** | Support authorization policies through contract-based permission system |
| **SR-HRM-004** | Encrypt sensitive employee data (personal information, salary details) at rest |
| **SR-HRM-005** | Implement field-level access control (HR managers see salary, line managers don't) |

---