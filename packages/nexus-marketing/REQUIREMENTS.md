nexus-sales-marketing Package Requirements
Version: 1.0.0 Last Updated: November 15, 2025 Status: Initial Draft - Progressive Disclosure Model

Executive Summary
nexus-sales-marketing is a progressive sales & marketing engine for PHP/Laravel that scales from basic campaign tracking to enterprise promotion systems.
The Problem We Solve
Sales & marketing packages often force choices:
	•	Simple tools (basic trackers) lack scale for complex campaigns.
	•	Enterprise systems (Marketo, Salesforce Marketing) are complex, costly, vendor-locked.
We solve both with progressive disclosure:
	1	Level 1: Basic S&M (5 minutes) - Add HasSalesMarketing trait. Manage promotions in-app. No extra tables.
	2	Level 2: Campaign Automation (1 hour) - Database-driven leads, pricing, analytics.
	3	Level 3: Enterprise S&M (Production-ready) - AI targeting, multi-channel, compliance.
Core Philosophy
	1	Progressive Disclosure - Learn as needed.
	2	Backwards Compatible - Level 1 works post-upgrade.
	3	Headless Backend - API-only, no UI.
	4	Framework Agnostic Core - No Laravel in core.
	5	Extensible - Plugins for channels, metrics, strategies.
Why This Approach Wins
For Mass Market (80%):
	•	Quick setup.
	•	No DB for basics.
	•	Easy learning.
	•	Fits existing models.
For Enterprise (20%):
	•	Campaign orchestration, A/B testing.
	•	Integrations (email, social).
	•	ROI analytics, GDPR compliance.
	•	Scalable targeting.

Personas & User Stories
Personas
ID
Persona
Role
Primary Goal
P1
Mass Market Developer
Full-stack dev at startup
“Add promotion tracking to my Product model in 5 minutes”
P2
In-House S&M Developer
Backend dev at mid-size firm
“Build campaign-to-sale funnel integrated with existing data”
P3
End-User (Marketer/Sales)
Business user
“Run campaigns, track leads, analyze performance”
P4
System Administrator
IT/DevOps
“Configure channels, metrics without code”
User Stories
Level 1: Basic S&M (Mass Appeal)
ID
Persona
Story
Priority
US-001
P1
As a developer, add HasSalesMarketing trait to manage promotions
High
US-002
P1
Define promotions as array in model, no DB tables
High
US-003
P1
Call $model->sm()->launchCampaign($data) to start
High
US-004
P1
Call $model->sm()->can('edit') for permissions
High
US-005
P1
Call $model->sm()->history() for logs
Medium
Level 2: Campaign Automation
ID
Persona
Story
Priority
US-010
P2
Promote to DB-driven S&M without code changes
High
US-011
P2
Define campaigns, pricing with stages
High
US-012
P2
Use conditional targeting (e.g., segment > 100)
High
US-013
P2
Parallel channels (email + ads)
High
US-014
P2
Multi-team assignments
High
US-015
P3
Unified dashboard for campaigns/metrics
High
US-016
P3
Log engagements with notes/attachments
High
Level 3: Enterprise S&M
ID
Persona
Story
Priority
US-020
P2
Auto-escalate underperforming campaigns
High
US-021
P2
ROI tracking for budgets
High
US-022
P3
Delegate campaigns during absences
High
US-023
P2
Rollback failed promotions
Medium
US-024
P4
Configure custom metrics via admin
Medium
US-025
P2
Report on conversion rates
Medium

Functional Requirements
FR-L1: Level 1 - Basic S&M (Mass Appeal)
ID
Requirement
Priority
Acceptance Criteria
FR-L1-001
HasSalesMarketing trait for models
High
Add trait; define sm() array; no migrate; works instantly
FR-L1-002
In-model promotion definitions
High
Array for fields; store in model column; no external
FR-L1-003
sm()->launchCampaign($data) method
High
Start campaign; events; validate; transaction
FR-L1-004
sm()->can($action) method
High
Boolean check; guards; no effects
FR-L1-005
sm()->history() method
Medium
Collection of changes; timestamps, actors
FR-L1-006
Guard conditions on actions
Medium
Callable; e.g., fn($campaign) => $campaign->budget > 0
FR-L1-007
Hooks (before/after)
Medium
Callbacks; e.g., notify after launch
FR-L2: Level 2 - Campaign Automation
ID
Requirement
Priority
Acceptance Criteria
FR-L2-001
DB-driven S&M definitions (JSON)
High
Table for schemas; same API; override in-model; hot-reload
FR-L2-002
Campaign/pricing stages
High
Type: “campaign”; assign users/roles; pause until action
FR-L2-003
Conditional targeting
High
Expressions: ==, >, AND; access data
FR-L2-004
Parallel channels
High
Array; simultaneous; wait for all
FR-L2-005
Inclusive gateways
Medium
Multiple true paths; sync at join
FR-L2-006
Multi-team strategies
High
Unison, majority, quorum; extensible
FR-L2-007
Dashboard API/service
High
SmDashboard::forUser($id)->active(); filter/sort
FR-L2-008
Actions (optimize, close)
High
Validate; log; comments/attachments; trigger next
FR-L2-009
Data validation
Medium
Schema in JSON; types: string, number, date
FR-L2-010
Plugin channels
High
Async; built-in: email, ad; extensible
FR-L3: Level 3 - Enterprise S&M
ID
Requirement
Priority
Acceptance Criteria
FR-L3-001
Escalation rules
High
After time; notify/reassign; history; scheduled
FR-L3-002
ROI tracking
High
Budget; breach actions; status: on_track, underperform
FR-L3-003
Delegation with ranges
High
Table: delegator, delegatee, dates; route auto; depth 3
FR-L3-004
Rollback logic
Medium
Compensation on failure; reverse order
FR-L3-005
Custom metrics config
Medium
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
Custom channels
High
ChannelContract: execute, compensate
FR-EXT-002
Custom conditions
High
ConditionEvaluatorContract: evaluate
FR-EXT-003
Custom strategies
High
TeamStrategyContract: canProceed
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
ROI check (10,000)
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
Failed channels no block
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
Async channels
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
Campaign, pricing, channel indep

Business Rules
ID
Rule
Level
BR-001
No self-target campaigns
Config (L2)
BR-002
ACID all changes
All
BR-003
Auto escalate low ROI
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
Multi-team per strategy
L2

Data Requirements
Core S&M Tables
Table
Purpose
Key Fields
sm_definitions
JSON schemas
id, name, schema, active, version
sm_instances
Running S&M
id, subject_type, subject_id, def_id, state, data, start, end
sm_history
Audit
id, instance_id, event, before, after, actor, payload
Entity Tables
Table
Purpose
Key Fields
sm_campaigns
Promotions
id, instance_id, name, channel, status, budget
sm_pricings
Strategies
id, campaign_id, type, value, discount
sm_engagements
Interactions
id, campaign_id, lead_id, metric, date
Automation Tables
Table
Purpose
Key Fields
sm_timers
Events
id, instance_id, type, trigger_at, payload
sm_roi
Metrics
id, instance_id, budget, start, breach_at
sm_escalations
History
id, entity_id, level, from, to, reason

JSON Schema Specification
Level 1: In-Model S&M
use Nexus\SalesMarketing\Traits\HasSalesMarketing;

class Product extends Model
{
    use HasSalesMarketing;
    
    public function sm(): array
    {
        return [
            'entities' => [
                'campaign' => ['fields' => ['name', 'budget']],
            ],
        ];
    }
}

// Usage
$product->sm()->launchCampaign(['name' => 'Summer Sale', 'budget' => 1000]);
Level 2: DB S&M with Entities
{
  "id": "promo-funnel",
  "label": "Promotion Funnel",
  "version": "1.0.0",
  "dataSchema": {
    "target_segment": { "type": "number" }
  },
  "entities": {
    "campaign": {
      "stages": ["plan", "active", "closed"]
    }
  },
  "transitions": {
    "activate": {
      "from": "plan",
      "to": "active",
      "condition": "data.segment > 100"
    }
  }
}
Level 3: Automation
{
  "id": "enterprise-sm",
  "roi": {
    "threshold": "2x"
  },
  "entities": {
    "campaign": {
      "automation": {
        "escalation": [
          {"after": "24h", "action": "optimize"}
        ]
      }
    }
  }
}
Built-in Conditions: expression, segment_check, etc.
Strategies: unison, majority, etc.

Package Structure
packages/nexus-sales-marketing/
├── src/
│   ├── Core/
│   │   ├── Contracts/
│   │   │   ├── SmEngineContract.php
│   │   │   ├── ChannelContract.php
│   │   │   ├── ConditionContract.php
│   │   │   ├── StrategyContract.php
│   │   ├── Engine/
│   │   │   ├── SmEngine.php
│   │   │   ├── CampaignManager.php
│   │   ├── Services/
│   │   │   ├── CampaignService.php
│   │   │   ├── PricingService.php
│   │   │   ├── EscalationService.php
│   │   ├── DTOs/
│   │       ├── SmDefinition.php
│   │       ├── SmInstance.php
│   ├── Strategies/
│   │   ├── UnisonStrategy.php
│   │   ├── MajorityStrategy.php
│   ├── Conditions/
│   │   ├── ExpressionCondition.php
│   ├── Plugins/
│   │   ├── EmailChannel.php
│   │   ├── AdChannel.php
│   ├── Timers/
│   │   ├── TimerQueue.php
│   ├── Http/
│   │   └── Controllers/
│   │       ├── SmController.php
│   ├── Adapters/
│   │   └── Laravel/
│   │       ├── Traits/
│   │       │   └── HasSalesMarketing.php
│   │       ├── Models/
│   │       │   ├── SmDefinition.php
│   │       │   ├── SmInstance.php
│   │       ├── Services/
│   │       │   ├── SmDashboard.php
│   │       ├── Commands/
│   │       │   ├── ProcessTimersCommand.php
│   │       └── SmServiceProvider.php
│   └── Events/
│       ├── CampaignLaunched.php
│       ├── EngagementTracked.php
├── database/
│   └── migrations/
│       ├── 2025_11_15_000001_create_sm_definitions_table.php
│       ├── 2025_11_15_000002_create_sm_instances_table.php
└── tests/
    ├── Unit/
    │   ├── CampaignTransitionTest.php
    └── Feature/
        ├── Level1SmTest.php
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
>5% ROI
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
	•	Channels
	•	Strategies
	•	Tests
Phase 3: Level 3 (Weeks 9-12)
	•	Timers
	•	ROI
	•	Delegation
	•	Tests
Phase 4: Extensibility (Weeks 13-14)
	•	Custom conditions
	•	Channels
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
	•	L2 campaigns
	•	L3 escalation
	•	Multi-team
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
	•	Level 1: Basic trait for promotions
	•	Level 2: DB campaigns, targeting
	•	Level 3: Automation, ROI
	•	Campaign: Promotion entity
	•	Pricing: Strategy stage
	•	Escalation: Auto optimize
	•	ROI: Return constraint
	•	Delegation: Temp routing
	•	Compensation: Failure rollback
	•	Gateway: Decision point

Document Version: 1.0.0 Last Updated: November 15, 2025 Status: Ready for Review
