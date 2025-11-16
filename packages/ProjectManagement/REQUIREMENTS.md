

## Personas & User Stories

### Personas

| ID | Persona | Role | Primary Goal |
|-----|---------|------|--------------|
| **P1** | Project Manager | Project lead | "Plan and execute projects on time and within budget" |
| **P2** | Team Member | Developer/Designer/Consultant | "Know what tasks I'm assigned to and log my time accurately" |
| **P3** | Resource Manager | Operations manager | "Allocate team members efficiently across multiple projects" |
| **P4** | Finance Controller | Finance team | "Track project costs, revenue, and profitability in real-time" |
| **P5** | Client Stakeholder | External customer | "View project progress, approve milestones, and review invoices" |
| **P6** | Executive/PMO Director | Leadership | "Oversee portfolio of projects, identify risks, optimize resource utilization" |

### User Stories

#### Level 1: Basic Project Management (Simple Task Tracking)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-001** | P1 | As a project manager, I want to create a project with basic details (name, client, start/end dates, budget) | **High** |
| **US-002** | P1 | As a project manager, I want to create tasks within a project with descriptions, assignees, and due dates | **High** |
| **US-003** | P2 | As a team member, I want to view all tasks assigned to me across all projects in one place | **High** |
| **US-004** | P2 | As a team member, I want to log time against tasks (hours worked, date, description) | **High** |
| **US-005** | P1 | As a project manager, I want to view time logged by team members to track project progress | **High** |
| **US-006** | P1 | As a project manager, I want to mark tasks as complete and track project completion percentage | Medium |
| **US-007** | P2 | As a team member, I want to receive notifications when tasks are assigned to me or deadlines are approaching | Medium |

#### Level 2: Advanced Project Management (Resource Planning & Milestones)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-010** | P1 | As a project manager, I want to define project milestones with deliverables and approval workflows | **High** |
| **US-011** | P1 | As a project manager, I want to create task dependencies (Task B cannot start until Task A is complete) | **High** |
| **US-012** | P3 | As a resource manager, I want to view team member availability and allocation across all projects | **High** |
| **US-013** | P3 | As a resource manager, I want to allocate team members to projects based on skills and availability | **High** |
| **US-014** | P4 | As a finance controller, I want to track project budget vs actual costs (labor + expenses) in real-time | **High** |
| **US-015** | P1 | As a project manager, I want to create project invoices based on milestones or time & materials | **High** |
| **US-016** | P5 | As a client, I want to receive milestone deliverables and approve them before payment is authorized | Medium |
| **US-017** | P1 | As a project manager, I want to generate Gantt charts showing project timeline and dependencies | Medium |

#### Level 3: Enterprise Project Management (Portfolio & EVM)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-020** | P6 | As an executive, I want a portfolio dashboard showing health of all active projects (on track, at risk, overdue) | **High** |
| **US-021** | P6 | As an executive, I want to view resource utilization across the organization (% allocated, overallocated, underutilized) | **High** |
| **US-022** | P1 | As a project manager, I want to track earned value metrics (PV, EV, AC, SPI, CPI) for project performance analysis | Medium |
| **US-023** | P1 | As a project manager, I want to compare fixed-price vs time & materials project profitability | Medium |
| **US-024** | P6 | As an executive, I want to forecast project revenue and cash flow for next 6 months | Medium |
| **US-025** | P1 | As a project manager, I want to capture lessons learned during project closure for future reference | Medium |
| **US-026** | P3 | As a resource manager, I want to receive alerts when team members are overallocated (>100% capacity) | **High** |

---

## Functional Requirements

### FR-L1: Level 1 - Basic Project Management (Essential MVP)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L1-001** | Create project with basic details | **High** | • Project name, client/customer reference, description<br>• Start date, end date (planned)<br>• Project manager assignment<br>• Project status (draft, active, on hold, completed, cancelled)<br>• Budget estimate (optional for Level 1) |
| **FR-L1-002** | Create and manage tasks | **High** | • Task title, description, assignee<br>• Due date, priority (low, medium, high, critical)<br>• Task status (to do, in progress, blocked, completed, cancelled)<br>• Parent project linkage<br>• Supporting attachments (specs, designs) |
| **FR-L1-003** | Task assignment and notifications | **High** | • Assign task to single team member<br>• Email notification on assignment<br>• Email reminder 24 hours before due date<br>• Notification when task is marked complete |
| **FR-L1-004** | Time tracking and timesheet entry | **High** | • Log hours worked against a task<br>• Date of work, hours (decimal or HH:MM), work description<br>• Billable vs non-billable flag<br>• Timesheet approval workflow (optional)<br>• Edit/delete own timesheets (before approval) |
| **FR-L1-005** | My Tasks view | **High** | • Dashboard showing all tasks assigned to logged-in user<br>• Filter by status (pending, in progress, overdue)<br>• Sort by due date, priority, project<br>• Quick action: mark task complete |
| **FR-L1-006** | Project dashboard | **High** | • Overview: total tasks, completed tasks, completion %<br>• Timeline: project start/end dates, days remaining<br>• Team members assigned to project<br>• Recent activity log (tasks created, timesheets logged) |
| **FR-L1-007** | Time report by project | Medium | • Total hours logged per project<br>• Breakdown by team member<br>• Filter by date range<br>• Export to CSV |

### FR-L2: Level 2 - Advanced Project Management (Milestones & Resources)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L2-001** | Project milestones management | **High** | • Define milestones with name, due date, deliverables<br>• Link tasks to milestones<br>• Milestone completion triggers billing event (if milestone-based project)<br>• Client approval workflow for milestone deliverables<br>• Track milestone status (pending, in review, approved, rejected) |
| **FR-L2-002** | Task dependencies and Gantt charts | **High** | • Define predecessor tasks (finish-to-start, start-to-start)<br>• Calculate critical path automatically<br>• Visual Gantt chart with drag-to-reschedule<br>• Highlight overdue tasks in red<br>• Export Gantt chart as PDF/PNG |
| **FR-L2-003** | Resource allocation and capacity planning | **High** | • Assign team members to projects with % allocation (e.g., John 50% on Project A)<br>• View team member workload across all projects<br>• Flag overallocation (>100% capacity)<br>• Filter resources by skill, department, availability<br>• Suggest resources based on skills required |
| **FR-L2-004** | Budget tracking (planned vs actual) | **High** | • Set project budget (labor + expenses + contingency)<br>• Track actual labor costs (hours × hourly rate) + actual expenses<br>• Calculate budget variance (budget - actual)<br>• Alert when budget utilization >80%<br>• Forecast project cost at completion |
| **FR-L2-005** | Project invoicing (milestone & T&M) | **High** | • Milestone billing: invoice on milestone approval<br>• Time & materials billing: invoice based on logged hours + expenses<br>• Apply client billing rates (may differ from internal cost rates)<br>• Generate invoice draft linked to project<br>• Send invoice to nexus-accounting for posting |
| **FR-L2-006** | Expense tracking | Medium | • Log project expenses (travel, materials, subcontractor costs)<br>• Attach receipts<br>• Expense approval workflow<br>• Include in project cost calculations<br>• Billable vs non-billable expenses |
| **FR-L2-007** | Timesheet approval workflow | **High** | • Team members submit timesheets (daily/weekly)<br>• Project manager reviews and approves/rejects<br>• Rejected timesheets return to team member with comments<br>• Approved timesheets locked from editing<br>• Bulk approve/reject functionality |

### FR-L3: Level 3 - Enterprise Project Management (Portfolio & EVM)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L3-001** | Portfolio dashboard | **High** | • Grid view of all active projects<br>• Health indicators: on track (green), at risk (yellow), overdue (red)<br>• Filter by status, project manager, client<br>• Sortable by budget, deadline, completion %<br>• Drill-down to project details |
| **FR-L3-002** | Resource utilization dashboard | **High** | • View all team members with % allocation<br>• Identify overallocated resources (>100%)<br>• Identify underutilized resources (<80%)<br>• Timeline view showing resource allocation over time<br>• Forecast future resource needs based on project pipeline |
| **FR-L3-003** | Earned value management (EVM) | Medium | • Calculate Planned Value (PV) based on baseline schedule<br>• Calculate Earned Value (EV) based on work completed<br>• Calculate Actual Cost (AC) from timesheets + expenses<br>• Compute Schedule Performance Index (SPI = EV/PV)<br>• Compute Cost Performance Index (CPI = EV/AC)<br>• Estimate at Completion (EAC), Estimate to Complete (ETC) |
| **FR-L3-004** | Project profitability analysis | Medium | • Compare revenue (invoiced/invoiceable) vs costs (labor + expenses)<br>• Calculate gross margin % per project<br>• Breakdown by project type (fixed-price vs T&M)<br>• Identify most/least profitable project types<br>• Trend analysis (profitability over time) |
| **FR-L3-005** | Revenue forecasting | Medium | • Project revenue forecast based on project pipeline (won, in progress, proposed)<br>• Monthly revenue projection for next 6-12 months<br>• Confidence levels (high, medium, low) based on project status<br>• Compare forecast to actuals |
| **FR-L3-006** | Lessons learned repository | Medium | • Template for lessons learned (what went well, what didn't, recommendations)<br>• Capture during project closure<br>• Tag by project type, industry, client<br>• Searchable knowledge base<br>• Link to similar past projects |
| **FR-L3-007** | Advanced resource management | **High** | • Skills matrix: define required skills per task<br>• Match team members to tasks based on skills<br>• Training needs analysis (skill gaps)<br>• Resource availability calendar (vacations, public holidays)<br>• Capacity planning: "Can we take on this new project?" analysis |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Notes |
|----|-------------|--------|-------|
| **PR-001** | Project creation and save | < 1 second | Including auto-save functionality |
| **PR-002** | Task creation and assignment | < 1 second | Including notification trigger |
| **PR-003** | Timesheet entry and save | < 500ms | Frequent operation, must be fast |
| **PR-004** | Gantt chart rendering (100 tasks) | < 3 seconds | First load with caching |
| **PR-005** | Portfolio dashboard loading | < 5 seconds | For 100+ active projects |
| **PR-006** | Resource allocation view | < 3 seconds | For 50+ team members, 100+ projects |

### Security Requirements

| ID | Requirement | Scope |
|----|-------------|-------|
| **SR-001** | Tenant data isolation | All project data MUST be tenant-scoped (via nexus-tenancy) |
| **SR-002** | Role-based access control | Enforce permissions: create-project, approve-timesheet, approve-milestone, view-financials |
| **SR-003** | Client portal access | Client stakeholders can view only their own projects (not other clients' data) |
| **SR-004** | Timesheet integrity | Approved timesheets cannot be edited (immutable after approval) |
| **SR-005** | Financial data protection | Project budgets/costs visible only to authorized roles (PM, finance, executives) |
| **SR-006** | Audit trail completeness | ALL create/update/delete operations MUST be logged via nexus-audit-log |

### Reliability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **REL-001** | All financial calculations MUST be ACID-compliant | Wrapped in database transactions |
| **REL-002** | Timesheet approval MUST prevent double-billing | Lock mechanism on approval |
| **REL-003** | Resource allocation MUST prevent double-booking | Validation on project assignment |
| **REL-004** | Milestone approval workflow MUST be resumable after failure | Use nexus-workflow persistence |

### Compliance Requirements

| ID | Requirement | Jurisdiction |
|----|-------------|--------------|
| **COMP-001** | Labor law compliance | Overtime calculations, rest periods (jurisdiction-specific) |
| **COMP-002** | Client billing transparency | Detailed timesheet breakdown for client invoices (e.g., legal, consulting industries) |
| **COMP-003** | Revenue recognition rules | Fixed-price vs T&M revenue recognition per accounting standards (IFRS 15, ASC 606) |
| **COMP-004** | Data retention policies | Retain project records for 7 years (typical requirement) |

---
---

## Business Rules

| ID | Rule | Level |
|----|------|-------|
| **BR-001** | A project MUST have a project manager assigned | All levels |
| **BR-002** | A task MUST belong to a project | All levels |
| **BR-003** | Timesheet hours cannot be negative or exceed 24 hours per day per user | All levels |
| **BR-004** | Approved timesheets are immutable (cannot be edited or deleted) | All levels |
| **BR-005** | A task's actual hours MUST equal the sum of all approved timesheet hours for that task | All levels |
| **BR-006** | Milestone billing amount cannot exceed remaining project budget (for fixed-price projects) | Level 2 |
| **BR-007** | Resource allocation percentage cannot exceed 100% per user per day | Level 2 |
| **BR-008** | Task dependencies must not create circular references | Level 2 |
| **BR-009** | Project status cannot be "completed" if there are incomplete tasks | All levels |
| **BR-010** | Timesheet billing rate defaults to resource allocation rate for the project | Level 2 |
| **BR-011** | Client stakeholders can view only their own projects | All levels |
| **BR-012** | Revenue recognition for fixed-price projects based on % completion or milestone approval | Level 3 |
| **BR-013** | Earned value calculations require baseline (planned) values to be set | Level 3 |
| **BR-014** | Lessons learned can only be created after project status = completed or cancelled | Level 3 |
| **BR-015** | Timesheet approval requires user to have approve-timesheet permission for the project | All levels |

---

## Workflow State Machines

### Project Workflow

```
States:
  - draft (initial)
  - active (in execution)
  - on_hold (temporarily paused)
  - completed (successfully finished)
  - cancelled (terminated before completion)

Transitions:
  activate: draft → active
    - Validates: project has at least one task or milestone
    - Validates: project manager assigned
    - Triggers: notification to project team
    
  pause: active → on_hold
    - Guard: user has manage-project permission
    - Requires: reason for pause
    
  resume: on_hold → active
    - Guard: user has manage-project permission
    
  complete: active → completed
    - Validates: all tasks are completed or cancelled
    - Validates: all milestones approved
    - Triggers: project closure workflow, capture lessons learned
    
  cancel: [draft, active, on_hold] → cancelled
    - Guard: user has manage-project permission
    - Requires: cancellation reason
```

### Task Workflow

```
States:
  - todo (initial, not started)
  - in_progress (being worked on)
  - blocked (waiting on dependency or external factor)
  - completed (finished)
  - cancelled (no longer needed)

Transitions:
  start: todo → in_progress
    - Guard: assigned user or project manager
    - Optional: check task dependencies (predecessors completed)
    
  block: in_progress → blocked
    - Requires: reason for blockage
    - Triggers: notification to project manager
    
  unblock: blocked → in_progress
    - Requires: resolution description
    
  complete: in_progress → completed
    - Guard: assigned user or project manager
    - Optional: require actual hours logged
    
  reopen: completed → in_progress
    - Guard: project manager or admin
    - Audit: log reopen reason
    
  cancel: [todo, in_progress, blocked] → cancelled
    - Guard: project manager or admin
```

### Timesheet Approval Workflow

```
States:
  - draft (being edited by user)
  - submitted (awaiting approval)
  - approved (locked, ready for billing)
  - rejected (returned to user)
  - invoiced (included in an invoice)

Transitions:
  submit: draft → submitted
    - Validates: work_date not in future
    - Validates: hours > 0
    - Validates: task exists and project is active
    
  approve: submitted → approved
    - Guard: user has approve-timesheet permission
    - Guard: user is not the timesheet owner (separation of duties)
    - Action: lock timesheet from editing
    
  reject: submitted → rejected
    - Guard: user has approve-timesheet permission
    - Requires: rejection reason
    - Triggers: notification to timesheet owner
    
  resubmit: rejected → submitted
    - Guard: timesheet owner
    - Action: user edits and resubmits
    
  invoice: approved → invoiced
    - Automatic transition when included in project invoice
    - Timesheet now fully immutable
```

### Milestone Approval Workflow

```
States:
  - pending (not yet submitted)
  - in_review (submitted to client for approval)
  - approved (client accepted)
  - rejected (client rejected, rework needed)

Transitions:
  submit: pending → in_review
    - Validates: all tasks linked to milestone are completed
    - Action: upload deliverables, notify client
    
  approve: in_review → approved
    - Guard: client stakeholder or project manager with delegation
    - Action: trigger billing event (if milestone billing)
    - Triggers: notification to project team, finance team
    
  reject: in_review → rejected
    - Guard: client stakeholder
    - Requires: rejection reason and feedback
    - Triggers: notification to project manager
    
  resubmit: rejected → in_review
    - Guard: project manager
    - Action: address feedback, re-upload deliverables
```

---
