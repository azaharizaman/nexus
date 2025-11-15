nexus-analytics Package Requirements
Version: 1.0.0 Last Updated: November 16, 2025 Status: Initial Draft - Progressive Disclosure Model

Executive Summary
nexus-analytics is a progressive analytics & reporting engine for PHP/Laravel that scales from basic data queries to enterprise BI and predictive insights.
The Problem We Solve
Analytics packages often force choices:
	•	Simple tools (basic query builders) lack depth for dashboards or predictions.
	•	Enterprise BI systems (Tableau, Power BI) are complex, costly, and external.
We solve both with progressive disclosure:
	1	Level 1: Basic Reporting (5 minutes) - Add HasAnalytics trait. Run queries on models. No extra tables.
	2	Level 2: Dashboard & Reports (1 hour) - Database-driven metrics, visualizations, scheduled reports.
	3	Level 3: Enterprise BI (Production-ready) - AI/ML predictions, real-time analytics, compliance.
Core Philosophy
	1	Progressive Disclosure - Learn as needed.
	2	Backwards Compatible - Level 1 works post-upgrade.
	3	Headless Backend - API-only, no UI.
	4	Framework Agnostic Core - No Laravel in core.
	5	Extensible - Plugins for data sources, visuals, algorithms.
Why This Approach Wins
For Mass Market (80%):
	•	Quick setup.
	•	No DB for basics.
	•	Easy learning.
	•	Fits existing models.
For Enterprise (20%):
	•	Predictive models, drill-downs.
	•	Integrations (DB, API).
	•	KPI tracking, GDPR compliance.
	•	Scalable querying.

Personas & User Stories
Personas
ID
Persona
Role
Primary Goal
P1
Mass Market Developer
Full-stack dev at startup
“Add quick metrics to my Order model in 5 minutes”
P2
In-House Analytics Developer
Backend dev at mid-size firm
“Build KPI dashboards integrated with ERP data”
P3
End-User (Analyst/Manager)
Business user
“View reports, forecasts, insights in one place”
P4
System Administrator
IT/DevOps
“Configure data sources, alerts without code”
User Stories
Level 1: Basic Reporting (Mass Appeal)
ID
Persona
Story
Priority
US-001
P1
As a developer, add HasAnalytics trait to query data
High
US-002
P1
Define queries as array in model, no DB tables
High
US-003
P1
Call $model->analytics()->runQuery($name) to execute
High
US-004
P1
Call $model->analytics()->can('view') for permissions
High
US-005
P1
Call $model->analytics()->history() for logs
Medium
Level 2: Dashboard & Reports
ID
Persona
Story
Priority
US-010
P2
Promote to DB-driven analytics without code changes
High
US-011
P2
Define metrics, reports with aggregations
High
US-012
P2
Use conditional filters (e.g., date > now-30d)
High
US-013
P2
Parallel data pulls (multiple sources)
High
US-014
P2
Multi-user sharing (roles)
High
US-015
P3
Unified dashboard for metrics/reports
High
US-016
P3
Export reports with formats/attachments
High
Level 3: Enterprise BI
ID
Persona
Story
Priority
US-020
P2
Auto-alert on KPI thresholds
High
US-021
P2
Predictive modeling for forecasts
High
US-022
P3
Delegate report access during absences
High
US-023
P2
Cache/refresh failed queries
Medium
US-024
P4
Configure custom visuals via admin
Medium
US-025
P2
Report on data trends
Medium

Functional Requirements
FR-L1: Level 1 - Basic Reporting (Mass Appeal)
ID
Requirement
Priority
Acceptance Criteria
FR-L1-001
HasAnalytics trait for models
High
Add trait; define analytics() array; no migrate; works instantly
FR-L1-002
In-model query definitions
High
Array for metrics; store in model column; no external
FR-L1-003
analytics()->runQuery($name) method
High
Execute query; events; validate; transaction
FR-L1-004
analytics()->can($action) method
High
Boolean check; guards; no effects
FR-L1-005
analytics()->history() method
Medium
Collection of runs; timestamps, actors
FR-L1-006
Guard conditions on queries
Medium
Callable; e.g., fn($query) => $query->user_id == auth()
FR-L1-007
Hooks (before/after)
Medium
Callbacks; e.g., cache after run
FR-L2: Level 2 - Dashboard & Reports
ID
Requirement
Priority
Acceptance Criteria
FR-L2-001
DB-driven analytics definitions (JSON)
High
Table for schemas; same API; override in-model; hot-reload
FR-L2-002
Metric/report aggregations
High
Type: “report”; group by, sum, avg; schedule runs
FR-L2-003
Conditional filters
High
Expressions: ==, >, AND; access params
FR-L2-004
Parallel data aggregation
High
Array; simultaneous; merge results
FR-L2-005
Inclusive filters
Medium
Multiple true; combine at end
FR-L2-006
Multi-role sharing
High
Unison, selective; extensible
FR-L2-007
Dashboard API/service
High
AnalyticsDashboard::forUser($id)->metrics(); filter/sort
FR-L2-008
Export actions (PDF, CSV)
High
Validate; log; attachments; trigger hooks
FR-L2-009
Data validation
Medium
Schema in JSON; types: number, date, string
FR-L2-010
Plugin data sources
High
Async; built-in: DB, API; extensible
FR-L3: Level 3 - Enterprise BI
ID
Requirement
Priority
Acceptance Criteria
FR-L3-001
Alert rules
High
On threshold; notify/escalate; history; scheduled
FR-L3-002
Predictive ML integration
High
Models; forecast actions; status: accurate, drift
FR-L3-003
Delegation with ranges
High
Table: delegator, delegatee, dates; access auto; depth 3
FR-L3-004
Query rollback
Medium
Compensation on failure; retry order
FR-L3-005
Custom visuals config
Medium
DB rules; apply on render; admin optional
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
Custom sources
High
SourceContract: fetch, transform
FR-EXT-002
Custom filters
High
FilterEvaluatorContract: apply
FR-EXT-003
Custom aggregations
High
AggStrategyContract: compute
FR-EXT-004
Custom triggers
Medium
TriggerContract: schedule, event
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
Query execution
< 100ms
Excl async
PR-002
Dashboard load (1,000 metrics)
< 500ms
Indexed
PR-003
ML predict (10,000)
< 2s
Timers table
PR-004
Init
< 200ms
Validation incl
PR-005
Parallel merge (10)
< 100ms
Data coord
Security Requirements
ID
Requirement
Scope
SR-001
Unauthorized queries prevent
Engine level
SR-002
Sanitize filters
No injection
SR-003
Tenant isolation
Auto-scope
SR-004
Plugin sandbox
No malicious
SR-005
Audit runs
Immutable log
SR-006
RBAC integration
Permissions
Reliability Requirements
ID
Requirement
Notes
REL-001
ACID queries
Transactions
REL-002
Failed sources no block
Queue
REL-003
Concurrency control
Locking
REL-004
Data corruption protection
Validate
REL-005
Retry transients
Policy config
Scalability Requirements
ID
Requirement
Notes
SCL-001
Async aggregations
Queue
SCL-002
Horizontal timers
Concurrent workers
SCL-003
Efficient queries
Indexes
SCL-004
100,000+ reports
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
Metric, report, predict indep

Business Rules
ID
Rule
Level
BR-001
No self-view sensitive data
Config (L2)
BR-002
ACID all runs
All
BR-003
Auto alert drifts
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
Access check delegation
L3
BR-010
Multi-role per strategy
L2

Data Requirements
Core Analytics Tables
Table
Purpose
Key Fields
analytics_definitions
JSON schemas
id, name, schema, active, version
analytics_instances
Running analytics
id, subject_type, subject_id, def_id, state, data, start, end
analytics_history
Audit
id, instance_id, event, before, after, actor, payload
Entity Tables
Table
Purpose
Key Fields
analytics_metrics
KPIs
id, instance_id, name, value, trend, timestamp
analytics_reports
Outputs
id, metric_id, format, generated_at, expiry
analytics_alerts
Notifications
id, report_id, threshold, triggered_at
Automation Tables
Table
Purpose
Key Fields
analytics_timers
Events
id, instance_id, type, trigger_at, payload
analytics_predictions
Forecasts
id, instance_id, model, input, output
analytics_escalations
History
id, entity_id, level, from, to, reason

JSON Schema Specification
Level 1: In-Model Analytics
use Nexus\Analytics\Traits\HasAnalytics;

class Order extends Model
{
    use HasAnalytics;
    
    public function analytics(): array
    {
        return [
            'queries' => [
                'total_sales' => ['select' => 'sum(amount)'],
            ],
        ];
    }
}

// Usage
$order->analytics()->runQuery('total_sales');
Level 2: DB Analytics with Metrics
{
  "id": "sales-report",
  "label": "Sales Report",
  "version": "1.0.0",
  "dataSchema": {
    "date_range": { "type": "date" }
  },
  "metrics": {
    "revenue": {
      "agg": ["sum", "avg"]
    }
  },
  "filters": {
    "date_filter": {
      "from": "draft",
      "expression": "data.date > now-30d"
    }
  }
}
Level 3: Automation
{
  "id": "bi-analytics",
  "alerts": {
    "threshold": "drop > 10%"
  },
  "metrics": {
    "forecast": {
      "automation": {
        "ml_model": "time_series"
      }
    }
  }
}
Built-in Filters: expression, date_range, etc.
Aggregations: sum, avg, count, etc.

Package Structure
packages/nexus-analytics/
├── src/
│   ├── Core/
│   │   ├── Contracts/
│   │   │   ├── AnalyticsEngineContract.php
│   │   │   ├── SourceContract.php
│   │   │   ├── FilterContract.php
│   │   │   ├── AggContract.php
│   │   ├── Engine/
│   │   │   ├── AnalyticsEngine.php
│   │   │   ├── MetricManager.php
│   │   ├── Services/
│   │   │   ├── ReportService.php
│   │   │   ├── PredictService.php
│   │   │   ├── AlertService.php
│   │   ├── DTOs/
│   │       ├── AnalyticsDefinition.php
│   │       ├── AnalyticsInstance.php
│   ├── Aggregations/
│   │   ├── SumAgg.php
│   │   ├── AvgAgg.php
│   ├── Filters/
│   │   ├── ExpressionFilter.php
│   ├── Plugins/
│   │   ├── DbSource.php
│   │   ├── ApiSource.php
│   ├── Timers/
│   │   ├── TimerQueue.php
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AnalyticsController.php
│   ├── Adapters/
│   │   └── Laravel/
│   │       ├── Traits/
│   │       │   └── HasAnalytics.php
│   │       ├── Models/
│   │       │   ├── AnalyticsDefinition.php
│   │       │   ├── AnalyticsInstance.php
│   │       ├── Services/
│   │       │   ├── AnalyticsDashboard.php
│   │       ├── Commands/
│   │       │   ├── ProcessTimersCommand.php
│   │       └── AnalyticsServiceProvider.php
│   └── Events/
│       ├── ReportGenerated.php
│       ├── AlertTriggered.php
├── database/
│   └── migrations/
│       ├── 2025_11_16_000001_create_analytics_definitions_table.php
│       ├── 2025_11_16_000002_create_analytics_instances_table.php
└── tests/
    ├── Unit/
    │   ├── QueryExecutionTest.php
    └── Feature/
        ├── Level1AnalyticsTest.php
        ├── Level2ReportTest.php

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
>5% predictions
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
	•	Metrics
	•	Filters
	•	Aggregations
	•	Tests
Phase 3: Level 3 (Weeks 9-12)
	•	Timers
	•	Predictions
	•	Alerts
	•	Tests
Phase 4: Extensibility (Weeks 13-14)
	•	Custom filters
	•	Sources
	•	Docs
Phase 5: Launch (Weeks 15-16)
	•	Docs
	•	Tutorials
	•	Optimization
	•	Audit
	•	Beta

Testing Requirements
Unit Tests
	•	Query logic
	•	Aggregations
	•	Filters
	•	Timers
	•	Alerts
Feature Tests
	•	L1 runs
	•	L2 reports
	•	L3 predictions
	•	Multi-role
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
	•	Level 1: Basic trait for queries
	•	Level 2: DB metrics, reports
	•	Level 3: BI, predictions
	•	Metric: KPI entity
	•	Report: Aggregated output
	•	Alert: Threshold notify
	•	Prediction: ML forecast
	•	Delegation: Temp access
	•	Compensation: Failure retry
	•	Gateway: Filter point

Document Version: 1.0.0 Last Updated: November 16, 2025 Status: Draft
