# nexus-project-management Package Requirements

**Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Initial Requirements - Cohesive Bounded Context Model

---

## Executive Summary

**nexus-project-management** (namespace: `Nexus\Project`) is a **cohesive project management system** for PHP/Laravel that manages the complete project lifecycle—from project initiation through planning, execution, resource allocation, time tracking, billing, to project closure and lessons learned.

### The Domain Complexity We Embrace

Unlike atomic core packages (tenancy, settings, workflow), project management is a **vertical business domain** where **atomicity must yield to cohesion**:

1. **Statutory Coupling:** Labor laws, overtime regulations, and billing rules are jurisdiction-specific and industry-specific (e.g., construction vs IT consulting have different time tracking requirements).
2. **Workflow Specificity:** Project approval flows (proposal → SOW → kickoff → milestones → invoicing → closure) are domain-specific state machines that belong within the bounded context.
3. **Data Ownership:** Projects, tasks, timesheets, milestones, project budgets, and resource allocations form a cohesive aggregate where splitting across packages would create integration hell.

### Core Philosophy

1. **Bounded Context Coherence** - All project-related entities and rules live together in one package
2. **Domain-Driven Design** - Rich domain models with encapsulated business logic
3. **Progressive Complexity** - Start simple (basic task tracking), grow to advanced (resource planning, earned value management)
4. **Multi-Industry Support** - Flexible enough for professional services, construction, software development, consulting
5. **Extensible Time Tracking** - Leverage core workflow engine while maintaining project-specific states

### Why This Approach Works

**For Small Teams (60%):**
- Simple task lists with assignees and due dates
- Basic time tracking (hours per task)
- Lightweight project status reporting
- No complex resource planning

**For Mid-Market (30%):**
- Multi-project resource allocation
- Milestone-based billing and invoicing
- Budget vs actual tracking
- Gantt charts and dependency management

**For Enterprise (10%):**
- Program management (portfolio of projects)
- Complex resource capacity planning
- Earned value management (EVM)
- Time & materials vs fixed-price project types
- Integration with accounting for project costing

---

## Architectural Position in Nexus ERP

### Relationship to Core Packages

| Core Package | Relationship | Usage Pattern |
|-------------|--------------|---------------|
| **nexus-tenancy** | Depends On | All project data is tenant-scoped via `BelongsToTenant` trait |
| **nexus-workflow** | Leverages | Uses workflow engine for project approvals but defines project-specific states |
| **nexus-sequencing** | Depends On | Uses sequence generation for project numbers, task numbers, timesheet numbers |
| **nexus-settings** | Depends On | Retrieves project settings (default hourly rates, billing rules, labor cost rates) |
| **nexus-audit-log** | Depends On | Comprehensive audit trail for all project actions |
| **nexus-backoffice** | Depends On | Links to departments/teams for resource allocation and cost center tracking |
| **nexus-accounting** | Consumes From | Posts project costs, revenue recognition, and invoicing to GL |
| **nexus-hrm** | Integrates With | Retrieves employee rates, availability, skills for resource planning |

### Why This Is NOT an Atomic Package

**Project Management violates the independence criterion:**
- Cannot be meaningfully subdivided (splitting tasks from timesheets would break coherence)
- Labor laws and billing rules are project-management-specific and change frequently
- Workflow states are domain-specific (not generic state machine patterns)
- Testing requires project context (cannot test time tracking without project/task context)

**This is a COHESIVE VERTICAL** where domain logic density justifies consolidation.

---

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

## Domain Model

### Core Entities

```
Project
├── id (UUID)
├── tenant_id (UUID) - BelongsToTenant
├── project_number (string) - via nexus-sequencing
├── name (string)
├── description (text)
├── client_id (UUID) - from nexus-crm or nexus-party
├── project_manager_id (UUID) - User
├── status (enum: draft, active, on_hold, completed, cancelled)
├── start_date (date)
├── end_date_planned (date)
├── end_date_actual (date, nullable)
├── project_type (enum: fixed_price, time_materials, retainer)
├── budget_total (decimal, nullable)
├── budget_labor (decimal)
├── budget_expenses (decimal)
├── billing_rate_default (decimal) - $/hour for T&M projects
└── Tasks (hasMany Task)
└── Milestones (hasMany Milestone)
└── Timesheets (hasManyThrough Timesheet via Task)
└── ResourceAllocations (hasMany ResourceAllocation)

Task
├── id (UUID)
├── project_id (UUID)
├── task_number (string) - sequential within project
├── title (string)
├── description (text)
├── assigned_to (UUID, nullable) - User
├── status (enum: todo, in_progress, blocked, completed, cancelled)
├── priority (enum: low, medium, high, critical)
├── due_date (date, nullable)
├── milestone_id (UUID, nullable)
├── estimated_hours (decimal, nullable)
├── actual_hours (decimal) - computed from timesheets
├── parent_task_id (UUID, nullable) - for subtasks
├── sort_order (int)
└── Dependencies (manyToMany Task via task_dependencies pivot)
└── Timesheets (hasMany Timesheet)

Milestone
├── id (UUID)
├── project_id (UUID)
├── milestone_number (string)
├── name (string)
├── description (text)
├── due_date (date)
├── deliverables (text) - what client should receive
├── billing_amount (decimal, nullable) - for milestone billing
├── status (enum: pending, in_review, approved, rejected)
├── submitted_at (datetime, nullable)
├── approved_by (UUID, nullable)
├── approved_at (datetime, nullable)
└── Tasks (hasMany Task where milestone_id = this)

Timesheet
├── id (UUID)
├── tenant_id (UUID)
├── user_id (UUID) - who logged the time
├── project_id (UUID)
├── task_id (UUID)
├── work_date (date)
├── hours (decimal) - supports decimals (e.g., 1.5 hours) or HH:MM
├── description (text) - what was done
├── billable (boolean)
├── billing_rate (decimal) - rate at time of entry (may differ from current rate)
├── cost_rate (decimal) - internal cost (employee's hourly cost)
├── status (enum: draft, submitted, approved, rejected, invoiced)
├── submitted_at (datetime, nullable)
├── approved_by (UUID, nullable)
├── approved_at (datetime, nullable)
└── invoice_id (UUID, nullable) - linked when invoiced

ProjectExpense
├── id (UUID)
├── tenant_id (UUID)
├── project_id (UUID)
├── user_id (UUID) - who incurred expense
├── expense_date (date)
├── category (enum: travel, materials, subcontractor, equipment, other)
├── description (text)
├── amount (decimal)
├── billable (boolean)
├── receipt_attachment (string) - file path
├── status (enum: draft, submitted, approved, rejected, reimbursed)
└── invoice_id (UUID, nullable)

ResourceAllocation
├── id (UUID)
├── tenant_id (UUID)
├── project_id (UUID)
├── user_id (UUID) - team member
├── role (string) - e.g., "Developer", "Designer", "PM"
├── allocation_percentage (decimal) - 0-100% (50 = 50% of time)
├── start_date (date)
├── end_date (date, nullable)
├── hourly_rate (decimal) - billing rate for this person on this project
└── notes (text, nullable)

ProjectInvoice
├── id (UUID)
├── tenant_id (UUID)
├── project_id (UUID)
├── invoice_number (string)
├── invoice_date (date)
├── due_date (date)
├── milestone_id (UUID, nullable) - for milestone billing
├── billing_period_start (date, nullable) - for T&M billing
├── billing_period_end (date, nullable)
├── subtotal_labor (decimal)
├── subtotal_expenses (decimal)
├── tax_amount (decimal)
├── total_amount (decimal)
├── status (enum: draft, sent, paid, cancelled)
└── Line Items (computed from timesheets + expenses)
└── accounting_invoice_id (UUID, nullable) - link to nexus-accounting

ProjectBudgetSnapshot
├── id (UUID)
├── project_id (UUID)
├── snapshot_date (date)
├── budget_total (decimal)
├── actual_cost (decimal) - labor + expenses to date
├── forecast_cost (decimal) - estimated cost at completion
├── variance (decimal) - budget - actual
├── completion_percentage (decimal)
└── notes (text)

LessonsLearned
├── id (UUID)
├── project_id (UUID)
├── created_by (UUID)
├── created_at (datetime)
├── what_went_well (text)
├── what_went_wrong (text)
├── recommendations (text)
├── tags (json) - searchable tags (e.g., ["client communication", "scope creep"])
└── visibility (enum: private, team, organization) - who can see this
```

### Aggregate Relationships

```
Project (Aggregate Root)
  └─> Task (Entity)
      └─> Timesheet (Entity)
  └─> Milestone (Entity)
  └─> ProjectExpense (Entity)
  └─> ResourceAllocation (Entity)
  └─> ProjectInvoice (Entity)
  └─> ProjectBudgetSnapshot (Value Object)
  └─> LessonsLearned (Entity)
```

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

## Integration Points

### With Core Packages

| Core Package | Integration Type | Usage |
|-------------|------------------|-------|
| **nexus-tenancy** | Direct Dependency | All models use `BelongsToTenant` trait for data isolation |
| **nexus-sequencing** | Service Call | Generate project numbers, task numbers, timesheet numbers, invoice numbers |
| **nexus-settings** | Service Call | Retrieve default hourly rates, overtime rules, billing settings |
| **nexus-audit-log** | Event Listener | Log all create/update/delete operations on project entities |
| **nexus-workflow** | Engine Usage | Leverage workflow engine for project/task/timesheet/milestone approvals |
| **nexus-backoffice** | Data Reference | Link projects to departments/teams for cost allocation and reporting |

### With Business Domain Packages

| Package | Integration Type | Data Flow |
|---------|------------------|-----------|
| **nexus-accounting** | Event-Driven | Post project costs (labor + expenses) and revenue (invoices) to GL |
| **nexus-hrm** | Service Call | Retrieve employee hourly cost rates, skills, availability for resource planning |
| **nexus-crm** | Data Reference | Link projects to customers/clients for relationship tracking |
| **nexus-inventory** | Optional | Consume materials/inventory for projects (e.g., construction, manufacturing projects) |

### External Integrations (Optional)

| System | Integration Method | Purpose |
|--------|-------------------|---------|
| **Time Tracking Apps** | REST API | Import time entries from Toggl, Harvest, Clockify |
| **Gantt Chart Tools** | Export/Import | Export to MS Project, import from Primavera |
| **Calendar Systems** | CalDAV/iCal | Sync project tasks to Google Calendar, Outlook |
| **Collaboration Tools** | Webhooks | Sync tasks to Jira, Asana, Monday.com |

---

## Testing Requirements

### Unit Tests
- Timesheet hours calculation and validation
- Budget variance calculation
- Resource allocation overlap detection
- Task dependency cycle detection
- Earned value metrics calculation

### Feature Tests
- Complete project creation → task assignment → time logging → approval flow
- Milestone submission → client approval → invoice generation
- Resource allocation → overallocation detection
- Gantt chart generation with task dependencies
- Portfolio dashboard aggregation

### Integration Tests
- nexus-accounting integration: verify project cost and revenue posting
- nexus-hrm integration: verify employee rate retrieval and skill matching
- nexus-workflow integration: verify approval state transitions
- nexus-sequencing integration: verify unique number generation

### Performance Tests
- Load test: 100 concurrent timesheet submissions
- Stress test: Gantt chart rendering for project with 1000 tasks
- Dashboard performance: portfolio view with 500 active projects

---

## Package Structure

```
packages/nexus-project-management/
├── src/
│   ├── Models/                      # Domain entities
│   │   ├── Project.php
│   │   ├── Task.php
│   │   ├── Milestone.php
│   │   ├── Timesheet.php
│   │   ├── ProjectExpense.php
│   │   ├── ResourceAllocation.php
│   │   ├── ProjectInvoice.php
│   │   ├── ProjectBudgetSnapshot.php
│   │   └── LessonsLearned.php
│   │
│   ├── Repositories/                # Data access layer
│   │   ├── ProjectRepository.php
│   │   ├── TaskRepository.php
│   │   ├── TimesheetRepository.php
│   │   └── ResourceAllocationRepository.php
│   │
│   ├── Services/                    # Business logic services
│   │   ├── ProjectPlanningService.php
│   │   ├── TaskManagementService.php
│   │   ├── TimeTrackingService.php
│   │   ├── ResourcePlanningService.php
│   │   ├── BudgetTrackingService.php
│   │   ├── InvoicingService.php
│   │   ├── EarnedValueService.php
│   │   └── GanttChartService.php
│   │
│   ├── Contracts/                   # Interfaces for external consumption
│   │   ├── ProjectRepositoryContract.php
│   │   ├── ProjectServiceContract.php
│   │   ├── TaskServiceContract.php
│   │   └── TimeTrackingServiceContract.php
│   │
│   ├── Enums/                       # Domain enums
│   │   ├── ProjectStatus.php
│   │   ├── ProjectType.php
│   │   ├── TaskStatus.php
│   │   ├── TaskPriority.php
│   │   └── TimesheetStatus.php
│   │
│   ├── Events/                      # Domain events
│   │   ├── ProjectCreated.php
│   │   ├── TaskAssigned.php
│   │   ├── TimesheetSubmitted.php
│   │   ├── TimesheetApproved.php
│   │   ├── MilestoneApproved.php
│   │   └── ProjectCompleted.php
│   │
│   ├── Workflows/                   # Workflow definitions
│   │   ├── ProjectWorkflow.php
│   │   ├── TaskWorkflow.php
│   │   ├── TimesheetApprovalWorkflow.php
│   │   └── MilestoneApprovalWorkflow.php
│   │
│   ├── Rules/                       # Validation rules
│   │   ├── TimesheetValidationRules.php
│   │   ├── ResourceAllocationRules.php
│   │   └── DependencyCycleDetection.php
│   │
│   └── ProjectServiceProvider.php
│
├── database/
│   └── migrations/
│       ├── 2025_11_15_000001_create_projects_table.php
│       ├── 2025_11_15_000002_create_tasks_table.php
│       ├── 2025_11_15_000003_create_task_dependencies_table.php
│       ├── 2025_11_15_000004_create_milestones_table.php
│       ├── 2025_11_15_000005_create_timesheets_table.php
│       ├── 2025_11_15_000006_create_project_expenses_table.php
│       ├── 2025_11_15_000007_create_resource_allocations_table.php
│       ├── 2025_11_15_000008_create_project_invoices_table.php
│       └── 2025_11_15_000009_create_lessons_learned_table.php
│
├── config/
│   └── project.php                  # Package configuration
│
├── tests/
│   ├── Unit/
│   │   ├── Services/
│   │   │   ├── TimeTrackingServiceTest.php
│   │   │   ├── BudgetTrackingServiceTest.php
│   │   │   └── EarnedValueServiceTest.php
│   │   └── Rules/
│   │       └── DependencyCycleDetectionTest.php
│   │
│   └── Feature/
│       ├── ProjectCreationTest.php
│       ├── TaskManagementTest.php
│       ├── TimesheetApprovalTest.php
│       ├── MilestoneApprovalTest.php
│       └── ResourceAllocationTest.php
│
└── REQUIREMENTS.md                  # This document

Note: Controllers, Actions, Listeners, Routes, and HTTP concerns belong in Nexus\\Erp orchestration layer.
See src/Actions/Project/ and src/Http/Controllers/Api/V1/Project/ in the main ERP package.
```

---

## Configuration

### project.php

```php
return [
    // Default project settings
    'defaults' => [
        'project_type' => 'time_materials',
        'billing_rate' => 150.00,  // $/hour default
        'overtime_multiplier' => 1.5,  // 1.5x for overtime hours
        'max_hours_per_day' => 24,
    ],
    
    // Timesheet settings
    'timesheet' => [
        'require_approval' => true,
        'allow_future_dates' => false,
        'allow_edit_after_submission' => false,
        'auto_approve_after_days' => 7,  // Auto-approve if no action in 7 days
    ],
    
    // Budget tracking
    'budget' => [
        'alert_threshold' => 0.8,  // Alert when 80% of budget used
        'forecast_enabled' => true,
    ],
    
    // Resource allocation
    'resources' => [
        'allow_overallocation' => false,
        'max_allocation_percentage' => 100,
        'alert_on_overallocation' => true,
    ],
    
    // Milestone billing
    'milestones' => [
        'require_client_approval' => true,
        'auto_invoice_on_approval' => true,
    ],
    
    // Earned value management
    'earned_value' => [
        'enabled' => false,  // Enable for enterprise
        'baseline_required' => true,
    ],
];
```

---

## Success Metrics

| Metric | Target | Measurement Period | Why It Matters |
|--------|--------|-------------------|----------------|
| **Adoption Rate** | > 300 installations | 12 months | Validates project management solution viability |
| **Time Entry Rate** | > 90% of hours logged | Weekly | Measures user adoption of time tracking |
| **Project On-Time Delivery** | > 75% of projects delivered on/before planned end date | Ongoing | Measures project management effectiveness |
| **Budget Adherence** | > 70% of projects within 10% of budget | Ongoing | Measures financial planning accuracy |
| **Resource Utilization** | 75-85% average utilization | Monthly | Optimal range (not under/overutilized) |
| **Client Satisfaction** | > 4.2/5 rating | Per project | Milestone approval feedback |
| **User Satisfaction** | > 4.0/5 rating | Quarterly survey | Overall usability and effectiveness |

---

## Development Phases

### Phase 1: Core Project Management (Weeks 1-8)
- Implement project creation and management
- Implement task management (create, assign, track status)
- Implement basic time tracking (timesheet entry, my tasks view)
- Implement project dashboard
- Unit and feature tests for core flows

### Phase 2: Advanced Features (Weeks 9-14)
- Implement milestone management and approval workflow
- Implement task dependencies and Gantt chart
- Implement resource allocation
- Implement budget tracking (planned vs actual)
- Implement timesheet approval workflow
- Feature tests for advanced scenarios

### Phase 3: Financial Integration (Weeks 15-20)
- Implement project invoicing (milestone and T&M)
- Implement expense tracking
- Integration with nexus-accounting (cost/revenue posting)
- Integration with nexus-hrm (employee rates)
- Profitability analysis reports

### Phase 4: Enterprise Features (Weeks 21-24)
- Implement portfolio dashboard
- Implement resource utilization dashboard
- Implement earned value management (EVM)
- Implement lessons learned repository
- Advanced analytics and forecasting

### Phase 5: Optimization & Launch (Weeks 25-28)
- Performance tuning (database indexing, query optimization)
- Client portal for milestone approval
- Comprehensive documentation
- Video tutorials and user guides
- Beta testing with 5-10 organizations
- Production deployment

---

## Dependencies

### Required
- PHP ≥ 8.3
- Laravel ≥ 12.x
- nexus-tenancy (multi-tenant isolation)
- nexus-sequencing (document numbering)
- nexus-settings (configuration management)
- nexus-audit-log (activity tracking)
- nexus-workflow (approval processes)
- nexus-backoffice (organizational structure)

### Optional (for full functionality)
- nexus-accounting (cost/revenue posting, invoicing)
- nexus-hrm (employee rates, skills, availability)
- nexus-crm (customer relationship tracking)
- nexus-inventory (material consumption for projects)

---

## Glossary

- **Project:** Temporary endeavor with defined start and end dates to achieve specific objectives
- **Task:** Discrete unit of work within a project, assignable to a team member
- **Milestone:** Significant checkpoint or deliverable in a project, often tied to billing
- **Timesheet:** Record of hours worked by a team member on specific tasks
- **Resource Allocation:** Assignment of team members to projects with defined capacity (% of time)
- **Time & Materials (T&M):** Billing model where client pays for actual hours worked + expenses
- **Fixed-Price:** Billing model where client pays a predetermined amount regardless of hours
- **Earned Value Management (EVM):** Project performance measurement technique comparing planned vs actual progress and costs
- **Gantt Chart:** Visual timeline showing tasks, durations, and dependencies
- **Critical Path:** Sequence of dependent tasks that determines the minimum project duration

---

**Document Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Ready for Review and Implementation Planning

---

## Notes on Bounded Context Coherence

This package intentionally violates the **Maximum Atomicity** principle because:

1. **Project management is a cohesive business process** - splitting tasks, timesheets, milestones, and resource allocation into separate packages would create excessive orchestration overhead and violate domain cohesion.

2. **Labor laws and billing rules are project-management-specific** - overtime calculations, billable vs non-billable logic, and revenue recognition rules are domain concepts that change frequently and belong in the bounded context.

3. **Workflow states are project-specific** - while we leverage nexus-workflow engine, the actual states (in_progress, blocked, approved) are domain concepts that belong in the bounded context.

4. **Data ownership is clear** - projects, tasks, timesheets, and resource allocations form a cohesive aggregate where entities reference each other tightly (timesheet references task, task references project, resource allocation references project).

**This is intentional and aligns with Domain-Driven Design principles** where bounded contexts should be cohesive even if it means sacrificing some atomicity. The package remains **independently deployable and testable**, but it is **not atomically subdivided** into smaller packages.
