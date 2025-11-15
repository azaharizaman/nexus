nexus-crm Package Requirements
Version: 1.0.0 Last Updated: November 15, 2025 Status: Initial Draft - Progressive Disclosure Model

Executive Summary
nexus-crm is a progressive CRM engine for PHP/Laravel that scales from basic contact tracking to enterprise customer management.
The Problem We Solve
CRM packages often force choices:
	•	Simple tools (like basic contact managers) lack depth for growth.
	•	Enterprise systems (Salesforce, HubSpot) are complex, costly, and vendor-locked.
We solve both with progressive disclosure:
	1	Level 1: Basic CRM (5 minutes) - Add HasCrm trait to models. Manage contacts in-app. No extra tables.
	2	Level 2: Sales Automation (1 hour) - Database-driven leads, opportunities, campaigns.
	3	Level 3: Enterprise CRM (Production-ready) - AI insights, integrations, compliance.
Core Philosophy
	1	Progressive Disclosure - Learn as needed.
	2	Backwards Compatible - Level 1 works post-upgrade.
	3	Headless Backend - API-only, no UI.
	4	Framework Agnostic Core - No Laravel in core.
	5	Extensible - Plugins for fields, integrations, automations.
Why This Approach Wins
For Mass Market (80%):
	•	Quick setup.
	•	No DB for basics.
	•	Easy learning.
	•	Fits existing models.
For Enterprise (20%):
	•	Lead scoring, pipelines.
	•	Integrations (email, API).
	•	Analytics, GDPR compliance.
	•	Scalable data handling.

Personas & User Stories
Personas
ID
Persona
Role
Primary Goal
P1
Mass Market Developer
Full-stack dev at startup
“Add contact management to my User model in 5 minutes”
P2
In-House CRM Developer
Backend dev at mid-size firm
“Build lead-to-sale pipeline integrated with existing data”
P3
End-User (Sales Rep/Manager)
Business user
“Track leads, opportunities, interactions in one place”
P4
System Administrator
IT/DevOps
“Configure custom fields, integrations without code”
User Stories
Level 1: Basic CRM (Mass Appeal)
ID
Persona
Story
Priority
US-001
P1
As a developer, add HasCrm trait to manage contacts
High
US-002
P1
Define contacts as array in model, no DB tables
High
US-003
P1
Call $model->crm()->addContact($data) to create
High
US-004
P1
Call $model->crm()->can('edit') for permissions
High
US-005
P1
Call $model->crm()->history() for logs
Medium
Level 2: Sales Automation
ID
Persona
Story
Priority
US-010
P2
Promote to DB-driven CRM without code changes
High
US-011
P2
Define leads, opportunities with stages
High
US-012
P2
Use conditional pipelines (e.g., score > 50)
High
US-013
P2
Parallel campaigns (email + calls)
High
US-014
P2
Multi-user assignments (teams)
High
US-015
P3
Unified dashboard for leads/opportunities
High
US-016
P3
Log interactions with notes/attachments
High
Level 3: Enterprise CRM
ID
Persona
Story
Priority
US-020
P2
Auto-escalate stale leads
High
US-021
P2
SLA tracking for response times
High
US-022
P3
Delegate leads during absences
High
US-023
P2
Rollback failed campaigns
Medium
US-024
P4
Configure custom fields via admin
Medium
US-025
P2
Report on conversion rates
Medium

Functional Requirements
FR-L1: Level 1 - Basic CRM (Mass Appeal)
ID
Requirement
Priority
Acceptance Criteria
FR-L1-001
HasCrm trait for models
High
Add trait; define crm() array; no migrate; works instantly
FR-L1-002
In-model contact definitions
High
Array for fields; store in model column; no external
FR-L1-003
crm()->addContact($data) method
High
Create contact; events; validate; transaction
FR-L1-004
crm()->can($action) method
High
Boolean check; guards; no effects
FR-L1-005
crm()->history() method
Medium
Collection of changes; timestamps, actors
FR-L1-006
Guard conditions on actions
Medium
Callable; e.g., fn($contact) => $contact->status == ‘active’
FR-L1-007
Hooks (before/after)
Medium
Callbacks; e.g., notify after add
FR-L2: Level 2 - Sales Automation
ID
Requirement
Priority
Acceptance Criteria
FR-L2-001
DB-driven CRM definitions (JSON)
High
Table for schemas; same API; override in-model; hot-reload
FR-L2-002
Lead/opportunity stages
High
Type: “lead”; assign users/roles; pause until action
FR-L2-003
Conditional pipelines
High
Expressions: ==, >, AND; access data
FR-L2-004
Parallel campaigns
High
Array; simultaneous; wait for all
FR-L2-005
Inclusive gateways
Medium
Multiple true paths; sync at join
FR-L2-006
Multi-user strategies
High
Unison, majority, quorum; extensible
FR-L2-007
Dashboard API/service
High
CrmDashboard::forUser($id)->pending(); filter/sort
FR-L2-008
Actions (convert, close)
High
Validate; log; comments/attachments; trigger next
FR-L2-009
Data validation
Medium
Schema in JSON; types: string, number, date
FR-L2-010
Plugin integrations
High
Async; built-in: email, webhook; extensible
FR-L3: Level 3 - Enterprise CRM
ID
Requirement
Priority
Acceptance Criteria
FR-L3-001
Escalation rules
High
After time; notify/reassign; history; scheduled
FR-L3-002
SLA tracking
High
Duration; breach actions; status: on_track, breached
FR-L3-003
Delegation with ranges
High
Table: delegator, delegatee, dates; route auto; depth 3
FR-L3-004
Rollback logic
Medium
Compensation on failure; reverse order
FR-L3-005
Custom fields config
Medium**
DB rules; apply on init; admin optional
FR-L3-006
Timer system
High
Table; index trigger_at; workers; not cron
FR-EXT: Extensibility
ID
Requirement
Priority
Acceptance Criteria
FR-EXT-001
Custom integrations
High
ActivityContract: execute, compensate
FR-EXT-002
Custom conditions
High
ConditionEvaluatorContract: evaluate
FR-EXT-003
Custom strategies
High
ApprovalStrategyContract: canProceed
FR-EXT-004
Custom triggers
Medium
TriggerContract: webhook, event
FR-EXT-005
Custom storage
Low
StorageContract: Eloquent, Redis

Non-Functional Requirements
Performance Requirements
ID
Requirement
Target
Notes
PR-001
Action execution
< 100ms
Excl async
PR-002
Dashboard query (1,000 items)
< 500ms
Indexed
PR-003
SLA check (10,000)
< 2s
Timers table
PR-004
Init
< 200ms
Validation incl
PR-005
Parallel sync (10)
< 100ms
Token coord
Security Requirements
ID
Requirement
Scope
SR-001
Unauthorized actions prevent
Engine level
SR-002
Sanitize expressions
No injection
SR-003
Tenant isolation
Auto-scope
SR-004
Plugin sandbox
No malicious
SR-005
Audit changes
Immutable log
SR-006
RBAC integration
Permissions
Reliability Requirements
ID
Requirement
Notes
REL-001
ACID changes
Transactions
REL-002
Failed integrations no block
Queue
REL-003
Concurrency control
Locking
REL-004
Corruption protection
Validate
REL-005
Retry transients
Policy config
Scalability Requirements
ID
Requirement
Notes
SCL-001
Async integrations
Queue
SCL-002
Horizontal timers
Concurrent workers
SCL-003
Efficient queries
Indexes
SCL-004
100,000+ instances
Optimized
Maintainability Requirements
ID
Requirement
Notes
MAINT-001
Agnostic core
No deps in src/Core
MAINT-002
Laravel adapter
In Adapters/Laravel
MAINT-003
Test coverage
>80%, >90% core
MAINT-004
Separation
Lead, opp, campaign indep

Business Rules
ID
Rule
Level
BR-001
No self-assign leads
Config (L2)
BR-002
ACID all changes
All
BR-003
Auto escalate stale
L3
BR-004
Compensation reverse
L3
BR-005
Delegation max 3
L3
BR-006
L1 compat with L2/3
All
BR-007
Instance per model
All
BR-008
Parallel complete all
L2
BR-009
Assign check delegation
L3
BR-010
Multi-user per strategy
L2

Data Requirements
Core CRM Tables
Table
Purpose
Key Fields
crm_definitions
JSON schemas
id, name, schema, active, version
crm_instances
Running CRM
id, subject_type, subject_id, def_id, state, data, start, end
crm_history
Audit
id, instance_id, event, before, after, actor, payload
Entity Tables
Table
Purpose
Key Fields
crm_contacts
Contacts/leads
id, instance_id, name, email, status, score
crm_opportunities
Deals
id, contact_id, stage, value, close_date
crm_campaigns
Marketing
id, name, type, start, end, metrics
Automation Tables
Table
Purpose
Key Fields
crm_timers
Events
id, instance_id, type, trigger_at, payload
crm_sla
Metrics
id, instance_id, duration, start, breach_at
crm_escalations
History
id, entity_id, level, from, to, reason

JSON Schema Specification
Level 1: In-Model CRM
use Nexus\Crm\Traits\HasCrm;

class User extends Model
{
    use HasCrm;
    
    public function crm(): array
    {
        return [
            'entities' => [
                'contact' => ['fields' => ['name', 'email']],
            ],
        ];
    }
}

// Usage
$user->crm()->addContact(['name' => 'John', 'email' => 'j@example.com']);
Level 2: DB CRM with Entities
{
  "id": "sales-pipeline",
  "label": "Sales Pipeline",
  "version": "1.0.0",
  "dataSchema": {
    "lead_score": { "type": "number" }
  },
  "entities": {
    "lead": {
      "stages": ["new", "qualified", "opportunity"]
    }
  },
  "transitions": {
    "qualify": {
      "from": "new",
      "to": "qualified",
      "condition": "data.score > 50"
    }
  }
}
Level 3: Automation
{
  "id": "enterprise-crm",
  "sla": {
    "duration": "2 days"
  },
  "entities": {
    "lead": {
      "automation": {
        "escalation": [
          {"after": "24h", "action": "reassign"}
        ]
      }
    }
  }
}
Built-in Conditions: expression, role_check, etc.
Strategies: unison, majority, etc.

Package Structure
packages/nexus-crm/
├── src/
│   ├── Core/
│   │   ├── Contracts/
│   │   │   ├── CrmEngineContract.php
│   │   │   ├── IntegrationContract.php
│   │   │   ├── ConditionContract.php
│   │   │   ├── StrategyContract.php
│   │   ├── Engine/
│   │   │   ├── CrmEngine.php
│   │   │   ├── EntityManager.php
│   │   ├── Services/
│   │   │   ├── LeadService.php
│   │   │   ├── OpportunityService.php
│   │   │   ├── EscalationService.php
│   │   ├── DTOs/
│   │       ├── CrmDefinition.php
│   │       ├── CrmInstance.php
│   ├── Strategies/
│   │   ├── UnisonStrategy.php
│   │   ├── MajorityStrategy.php
│   ├── Conditions/
│   │   ├── ExpressionCondition.php
│   ├── Plugins/
│   │   ├── EmailIntegration.php
│   │   ├── WebhookIntegration.php
│   ├── Timers/
│   │   ├── TimerQueue.php
│   ├── Http/
│   │   └── Controllers/
│   │       ├── CrmController.php
│   ├── Adapters/
│   │   └── Laravel/
│   │       ├── Traits/
│   │       │   └── HasCrm.php
│   │       ├── Models/
│   │       │   ├── CrmDefinition.php
│   │       │   ├── CrmInstance.php
│   │       ├── Services/
│   │       │   ├── CrmDashboard.php
│   │       ├── Commands/
│   │       │   ├── ProcessTimersCommand.php
│   │       └── CrmServiceProvider.php
│   └── Events/
│       ├── LeadCreated.php
│       ├── OpportunityClosed.php
├── database/
│   └── migrations/
│       ├── 2025_11_15_000001_create_crm_definitions_table.php
│       ├── 2025_11_15_000002_create_crm_instances_table.php
└── tests/
    ├── Unit/
    │   ├── EntityTransitionTest.php
    └── Feature/
        ├── Level1CrmTest.php
        ├── Level2AutomationTest.php

Success Metrics
Metric
Target
Period
Why
Adoption
>2,000 installs
6m
Mass appeal
Hello World Time
<5min
Ongoing
DX
Promotion Rate
>10% to L2
6m
Growth
Enterprise Use
>5% SLA
6m
Niche
Bugs
<5 P0
6m
Quality
Coverage
>85%
Ongoing
Engine
Docs Quality
<10 questions/wk
3m
Clarity

Development Phases
Phase 1: Level 1 (Weeks 1-3)
	•	Trait impl
	•	In-model parser
	•	Basic engine
	•	Tests
Phase 2: Level 2 (Weeks 4-8)
	•	DB defs
	•	Entities
	•	Pipelines
	•	Strategies
	•	Tests
Phase 3: Level 3 (Weeks 9-12)
	•	Timers
	•	SLA
	•	Delegation
	•	Tests
Phase 4: Extensibility (Weeks 13-14)
	•	Custom conditions
	•	Integrations
	•	Docs
Phase 5: Launch (Weeks 15-16)
	•	Docs
	•	Tutorials
	•	Optimization
	•	Audit
	•	Beta

Testing Requirements
Unit Tests
	•	Transition logic
	•	Strategies
	•	Conditions
	•	Timers
	•	Delegation
Feature Tests
	•	L1 lifecycle
	•	L2 entities
	•	L3 escalation
	•	Multi-user
	•	Custom
Integration Tests
	•	Laravel (Eloquent, Queue)
	•	Tenancy
	•	Audit
	•	Load
Acceptance Tests
	•	All US
	•	<5min hello
	•	Promotion no changes

Dependencies
Required
	•	PHP ≥8.2
	•	DB: MySQL 8+, PG 12+, SQLite, SQL Server
Optional
	•	Laravel ≥12
	•	nexus-tenancy
	•	nexus-audit-log
	•	Redis

Glossary
	•	Level 1: Basic trait for contacts
	•	Level 2: DB entities, pipelines
	•	Level 3: Automation, SLA
	•	Lead: Prospect entity
	•	Opportunity: Deal stage
	•	Escalation: Auto reassign
	•	SLA: Time constraint
	•	Delegation: Temp routing
	•	Compensation: Failure rollback
	•	Gateway: Decision point

Document Version: 1.0.0 Last Updated: November 15, 2025 Status: Ready for Review
