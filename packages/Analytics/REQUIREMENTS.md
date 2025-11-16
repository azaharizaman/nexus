

## User Personas

| ID | Persona | Role | Primary Goal |
|----|---------|------|--------------|
| **P1** | Mass Market Developer | Full-stack dev at startup | "Add quick metrics to my Order model in 5 minutes" |
| **P2** | In-House Analytics Developer | Backend dev at mid-size firm | "Build KPI dashboards integrated with ERP data" |
| **P3** | End-User (Analyst/Manager) | Business user | "View reports, forecasts, insights in one place" |
| **P4** | System Administrator | IT/DevOps | "Configure data sources, alerts without code" |

---

## Functional Requirements

### Level 1: Basic Reporting (Mass Appeal)

| Requirement ID | Description | Priority | Acceptance Criteria |
|----------------|-------------|----------|---------------------|
| **FR-L1-001** | Provide `HasAnalytics` trait for models | High | Add trait; define `analytics()` array; no migration; works instantly (Atomy app provides the Laravel adaptor trait: `Nexus\Atomy\Support\Traits\HasAnalytics`) |
| **FR-L1-002** | Support in-model query definitions | High | Array-based metric definitions stored in model attribute; no external config |
| **FR-L1-003** | Implement `analytics()->runQuery($name)` method | High | Execute query; emit events; validate permissions; use transactions |
| **FR-L1-004** | Implement `analytics()->can($action)` method | High | Boolean authorization check; guard-based; no side effects |
| **FR-L1-005** | Implement `analytics()->history()` method | Medium | Collection of query runs with timestamps, actors, results |
| **FR-L1-006** | Support guard conditions on queries | Medium | Callable guards (e.g., `fn($query) => $query->user_id == auth()->id()`) |
| **FR-L1-007** | Provide before/after hooks | Medium | Callbacks for pre/post query execution (e.g., cache warming) |

**User Stories (Level 1):**
- US-001: As a developer, add `HasAnalytics` trait to query data without setup
- US-002: As a developer, define queries as array in model, no DB tables
- US-003: As a developer, call `$model->analytics()->runQuery($name)` to execute
- US-004: As a developer, call `$model->analytics()->can('view')` for permissions
- US-005: As a developer, call `$model->analytics()->history()` for audit logs

### Level 2: Dashboard & Reports

| Requirement ID | Description | Priority | Acceptance Criteria |
|----------------|-------------|----------|---------------------|
| **FR-L2-001** | Support DB-driven analytics definitions (JSON) | High | Schemas in database table; same API; override in-model; hot-reload |
| **FR-L2-002** | Implement metric/report aggregations | High | Types: report, metric; groupBy, sum, avg; scheduled execution |
| **FR-L2-003** | Provide conditional filters | High | Expression evaluator (==, >, AND/OR); access to query parameters |
| **FR-L2-004** | Support parallel data aggregation | High | Array of data sources; simultaneous fetch; merge results |
| **FR-L2-005** | Implement inclusive filters | Medium | Multiple filters can be true; combine results at end |
| **FR-L2-006** | Support multi-role sharing | High | Unison and selective sharing; extensible permission strategies |
| **FR-L2-007** | Provide dashboard API/service | High | `AnalyticsDashboard::forUser($id)->metrics()` with filtering |
| **FR-L2-008** | Implement export actions (PDF, CSV) | High | Format validation; activity logging; attachment support; hooks |
| **FR-L2-009** | Provide data validation | Medium | Schema-based validation in JSON; types: number, date, string |
| **FR-L2-010** | Support pluggable data sources | High | Async fetching; built-in: DB, API; extensible architecture |

**User Stories (Level 2):**
- US-010: As a developer, promote to DB-driven analytics without code changes
- US-011: As a developer, define metrics, reports with aggregations
- US-012: As a developer, use conditional filters (e.g., `date > now-30d`)
- US-013: As a developer, fetch data from multiple sources in parallel
- US-014: As a developer, share reports with multiple user roles
- US-015: As an analyst, access unified dashboard for metrics/reports
- US-016: As an analyst, export reports in multiple formats with attachments

### Level 3: Enterprise BI

| Requirement ID | Description | Priority | Acceptance Criteria |
|----------------|-------------|----------|---------------------|
| **FR-L3-001** | Implement alert rules | High | Threshold-based alerts; notify/escalate; history tracking; scheduled |
| **FR-L3-002** | Provide predictive ML integration | High | Support ML models; forecast actions; status tracking (accurate, drift) |
| **FR-L3-003** | Support delegation with date ranges | High | Delegator/delegatee mapping; automatic access; max depth 3 levels |
| **FR-L3-004** | Implement query rollback | Medium | Compensation actions on failure; configurable retry order |
| **FR-L3-005** | Provide custom visualization config | Medium | DB-driven visualization rules; apply on render; admin UI optional |
| **FR-L3-006** | Implement timer system | High | Database table; index on `trigger_at`; worker-based; not cron |

**User Stories (Level 3):**
- US-020: As a developer, configure auto-alerts on KPI thresholds
- US-021: As a developer, integrate predictive modeling for forecasts
- US-022: As a manager, delegate report access during absences
- US-023: As a developer, implement cache/refresh for failed queries
- US-024: As an admin, configure custom visualizations via database
- US-025: As an analyst, generate reports on data trends

### Extensibility Requirements

| Requirement ID | Description | Priority | Acceptance Criteria |
|----------------|-------------|----------|---------------------|
| **FR-EXT-001** | Support custom data sources | High | `SourceContract` interface: `fetch()`, `transform()` methods |
| **FR-EXT-002** | Support custom filters | High | `FilterEvaluatorContract` interface: `apply()` method |
| **FR-EXT-003** | Support custom aggregations | High | `AggStrategyContract` interface: `compute()` method |
| **FR-EXT-004** | Support custom triggers | Medium | `TriggerContract` interface: `schedule()`, `event()` methods |
| **FR-EXT-005** | Support custom storage backends | Low | `StorageContract` interface: Eloquent, Redis, custom |

---

## Business Rules

| Rule ID | Description | Scope |
|---------|-------------|-------|
| **BR-ANA-001** | Users cannot view sensitive data about themselves | L2+ Config |
| **BR-ANA-002** | All query executions MUST use ACID transactions | All Levels |
| **BR-ANA-003** | Predictive model drift MUST trigger automatic alerts | L3 |
| **BR-ANA-004** | Failed queries MUST use compensation actions for reversal | L3 |
| **BR-ANA-005** | Delegation chains limited to maximum 3 levels depth | L3 |
| **BR-ANA-006** | Level 1 definitions MUST remain compatible after L2/3 upgrade | All Levels |
| **BR-ANA-007** | Each model instance has one analytics instance | All Levels |
| **BR-ANA-008** | Parallel data sources MUST complete all before returning results | L2 |
| **BR-ANA-009** | Delegated access MUST check delegation chain for permissions | L3 |
| **BR-ANA-010** | Multi-role sharing follows configured strategy (unison/selective) | L2 |

---

## Data Requirements

### Core Analytics Tables

| Table | Purpose | Key Fields | Level |
|-------|---------|------------|-------|
| `analytics_definitions` | JSON schema storage | `id`, `name`, `schema`, `active`, `version` | L2+ |
| `analytics_instances` | Running analytics state | `id`, `subject_type`, `subject_id`, `def_id`, `state`, `data`, `start_at`, `end_at` | L2+ |
| `analytics_history` | Audit trail | `id`, `instance_id`, `event`, `before`, `after`, `actor_id`, `payload`, `created_at` | L1+ |

### Entity Tables

| Table | Purpose | Key Fields | Level |
|-------|---------|------------|-------|
| `analytics_metrics` | KPI definitions | `id`, `instance_id`, `name`, `value`, `trend`, `timestamp` | L2+ |
| `analytics_reports` | Generated outputs | `id`, `metric_id`, `format`, `generated_at`, `expiry_at` | L2+ |
| `analytics_alerts` | Threshold notifications | `id`, `report_id`, `threshold`, `triggered_at`, `acknowledged_at` | L3 |

### Automation Tables

| Table | Purpose | Key Fields | Level |
|-------|---------|------------|-------|
| `analytics_timers` | Scheduled events | `id`, `instance_id`, `type`, `trigger_at`, `payload`, `status` | L3 |
| `analytics_predictions` | ML forecasts | `id`, `instance_id`, `model_name`, `input_data`, `output_data`, `confidence` | L3 |
| `analytics_delegations` | Temporary access | `id`, `delegator_id`, `delegatee_id`, `resource_type`, `resource_id`, `starts_at`, `ends_at` | L3 |

---

## Integration Requirements

### Internal Package Communication

| Component | Integration Method | Implementation |
|-----------|-------------------|----------------|
| **Nexus\Tenancy** | Event-driven | Listen to `TenantCreated` event for analytics setup |
| **Nexus\AuditLog** | Service contract | Use `ActivityLoggerContract` for change tracking |
| **External ML Service** | Service contract | Define `PredictionEngineContract` interface |
| **External Data Source** | Service contract | Define `DataSourceContract` interface |
