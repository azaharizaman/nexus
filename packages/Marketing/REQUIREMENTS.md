
## Personas & User Stories

### Personas

| ID | Persona | Role | Primary Goal |
|----|---------|------|--------------|
| **P1** | Mass Market Developer | Full-stack dev at startup | "Add marketing campaigns to my Product model in 5 minutes" |
| **P2** | Marketing Automation Engineer | Backend dev at mid-size company | "Build automated drip campaigns with multi-channel delivery" |
| **P3** | Marketing Manager | Business user | "Launch campaigns, track leads, analyze performance without coding" |
| **P4** | System Administrator | IT/DevOps | "Configure channels, metrics, integrations without developer help" |
| **P5** | Compliance Officer | Legal/Privacy team | "Ensure GDPR/CAN-SPAM compliance and audit trail" |

### User Stories

#### Phase 1: Basic Campaign Tracking (Mass Appeal)

| ID | Persona | User Story | Priority | Acceptance Criteria |
|----|---------|------------|----------|---------------------|
| **US-001** | P1 | As a developer, I want to add `HasMarketing` trait to my model to enable campaign tracking | High | Trait added, `marketing()` method available, no migration required |
| **US-002** | P1 | As a developer, I want to define campaigns as an array in my model | High | Campaign definitions stored as JSON in model column, no external tables |
| **US-003** | P1 | As a developer, I want to call `$model->marketing()->launchCampaign($data)` to start a campaign | High | Campaign launched, events dispatched, data validated, transaction-safe |
| **US-004** | P1 | As a developer, I want to call `$model->marketing()->can($action)` to check permissions | High | Returns boolean, uses guards, no side effects |
| **US-005** | P1 | As a developer, I want to call `$model->marketing()->history()` to view campaign history | Medium | Returns collection of changes with timestamps and actors |
| **US-006** | P1 | As a developer, I want to define guard conditions on actions | Medium | Callable conditions, e.g., `fn($campaign) => $campaign->budget > 0` |
| **US-007** | P1 | As a developer, I want hooks (before/after) for campaign lifecycle events | Medium | Callbacks executed, e.g., notify team after campaign launch |

#### Phase 2: Marketing Automation

| ID | Persona | User Story | Priority | Acceptance Criteria |
|----|---------|------------|----------|---------------------|
| **US-010** | P2 | As a developer, I want to promote to database-driven campaigns without code changes | High | Run migration, same API works, campaigns stored in DB, hot-reloadable |
| **US-011** | P2 | As a marketer, I want to define campaign stages (draft → active → paused → completed) | High | State machine enforced, transitions validated, events dispatched |
| **US-012** | P2 | As a marketer, I want conditional audience targeting (e.g., age > 25, location = 'US') | High | Expression evaluator supports ==, >, <, AND, OR, NOT operators |
| **US-013** | P2 | As a marketer, I want parallel channel execution (email + SMS + social) | High | Channels execute concurrently, wait for all to complete, retry on failure |
| **US-014** | P2 | As a marketer, I want multi-team campaign approval workflows | High | Support approval strategies: unanimous, majority, quorum (configurable) |
| **US-015** | P3 | As a marketer, I want a unified dashboard to view active campaigns and metrics | High | API endpoint: `MarketingDashboard::forUser($id)->activeCampaigns()` with filters |
| **US-016** | P3 | As a marketer, I want to log engagements (opens, clicks, conversions) with notes | High | Engagement tracking with metadata, attachments, searchable |
| **US-017** | P2 | As a developer, I want to validate campaign data against JSON schema | Medium | Schema validation for required fields, types: string, number, date, enum |
| **US-018** | P2 | As a developer, I want plugin system for custom channels | High | Async channel execution, built-in: email, SMS, webhook; extensible via plugins |

#### Phase 3: Enterprise Marketing

| ID | Persona | User Story | Priority | Acceptance Criteria |
|----|---------|------------|----------|---------------------|
| **US-020** | P2 | As a marketer, I want automatic escalation for underperforming campaigns | High | After configurable time, notify/reassign, history logged, scheduled check |
| **US-021** | P2 | As a marketer, I want ROI tracking with budget constraints | High | Track spend vs. revenue, breach actions trigger, status: on_track, over_budget |
| **US-022** | P3 | As a marketer, I want to delegate campaigns during absences | High | Delegation table: delegator, delegatee, date ranges; auto-route, max depth: 3 |
| **US-023** | P2 | As a developer, I want rollback logic for failed campaigns | Medium | Compensation actions on failure, reverse order execution |
| **US-024** | P4 | As an admin, I want to configure custom metrics via admin panel | Medium | Database-driven rules, applied on initialization, no code deployment |
| **US-025** | P3 | As a marketer, I want reports on conversion rates and engagement | Medium | Reporting API with filters: date range, channel, segment, export formats |
| **US-026** | P5 | As a compliance officer, I want GDPR compliance features | High | Consent tracking, data export, deletion workflows, audit trail |
| **US-027** | P2 | As a developer, I want A/B testing for campaign variants | High | Split testing with statistical significance calculation, auto-winner selection |

---

## Functional Requirements

### FR-P1: Phase 1 - Basic Campaign Tracking (Mass Appeal)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-P1-001** | `HasMarketing` trait for models | High | Add trait; define `marketing()` array; no migration needed; works instantly |
| **FR-P1-002** | In-model campaign definitions | High | Array-based configuration; stored in model JSON column; no external tables |
| **FR-P1-003** | `marketing()->launchCampaign($data)` method | High | Launches campaign; dispatches events; validates data; transaction-safe |
| **FR-P1-004** | `marketing()->can($action)` permission check | High | Returns boolean; uses guard conditions; read-only operation |
| **FR-P1-005** | `marketing()->history()` audit trail | Medium | Returns collection of changes with timestamps, actors, and metadata |
| **FR-P1-006** | Guard conditions on actions | Medium | Supports callable guards: `fn($campaign) => $campaign->isActive()` |
| **FR-P1-007** | Lifecycle hooks (before/after) | Medium | Callbacks for events: `beforeLaunch`, `afterLaunch`, `beforePause`, etc. |
| **FR-P1-008** | Basic validation rules | High | Required fields, type checking, custom validators |

### FR-P2: Phase 2 - Marketing Automation

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-P2-001** | Database-driven campaign definitions (JSON) | High | Campaigns table stores schemas; same API; overrides in-model config; hot-reloadable |
| **FR-P2-002** | Campaign lifecycle stages | High | States: draft → active → paused → completed; transitions validated; events dispatched |
| **FR-P2-003** | Conditional audience targeting | High | Expression evaluator: ==, >, <, !=, AND, OR, NOT; access to lead/campaign data |
| **FR-P2-004** | Parallel channel execution | High | Array of channels; execute concurrently; wait for all; individual retry on failure |
| **FR-P2-005** | Multi-team approval workflows | High | Approval strategies: unanimous, majority, quorum (2/3, 3/4); extensible |
| **FR-P2-006** | Dashboard API service | High | `MarketingDashboard::forUser($id)->activeCampaigns()`; filter by status, date, channel |
| **FR-P2-007** | Engagement tracking | High | Log opens, clicks, conversions; metadata, comments, attachments; searchable |
| **FR-P2-008** | Data validation via JSON schema | Medium | Schema definition in JSON; types: string, number, date, boolean, enum |
| **FR-P2-009** | Plugin architecture for channels | High | Channel contract interface; async execution; built-in: email, SMS, webhook |
| **FR-P2-010** | Lead scoring system | Medium | Configurable scoring rules; automatic score updates; threshold triggers |
| **FR-P2-011** | Campaign templates | Medium | Reusable templates; variable substitution; versioning |
| **FR-P2-012** | Segment management | High | Dynamic segments based on conditions; cached for performance |

### FR-P3: Phase 3 - Enterprise Marketing

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-P3-001** | Automatic escalation rules | High | After configurable time; notify/reassign; history logged; scheduled workers |
| **FR-P3-002** | ROI tracking with budgets | High | Track spend and revenue; breach actions (pause, alert); status indicators |
| **FR-P3-003** | Campaign delegation system | High | Delegation table; date ranges; automatic routing; maximum delegation depth: 3 |
| **FR-P3-004** | Rollback and compensation logic | Medium | Compensation actions on failure; reverse order execution; idempotent |
| **FR-P3-005** | Custom metrics configuration | Medium | Database-driven metric rules; applied on initialization; no code deployment |
| **FR-P3-006** | Timer and scheduling system | High | Timers table; indexed `trigger_at` column; worker-based (not cron); retry logic |
| **FR-P3-007** | Advanced reporting | High | Conversion funnels, cohort analysis, attribution modeling; export to CSV, PDF |
| **FR-P3-008** | GDPR compliance features | High | Consent management, data export, deletion workflows, audit trail |
| **FR-P3-009** | A/B testing engine | High | Variant creation, traffic splitting, statistical analysis, auto-winner selection |
| **FR-P3-010** | Behavioral triggers | High | Event-based campaign triggers; conditional logic; cooldown periods |
| **FR-P3-011** | Drip campaign automation | High | Multi-step sequences; time-based delays; conditional branching |
| **FR-P3-012** | API rate limiting and throttling | Medium | Per-channel rate limits; backoff strategies; queue management |

### FR-EXT: Extensibility Features

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-EXT-001** | Custom channel plugins | High | `ChannelContract` interface: `execute()`, `validate()`, `compensate()` methods |
| **FR-EXT-002** | Custom condition evaluators | High | `ConditionEvaluatorContract`: `evaluate($context)` method |
| **FR-EXT-003** | Custom approval strategies | High | `ApprovalStrategyContract`: `canProceed($approvals)` method |
| **FR-EXT-004** | Custom trigger handlers | Medium | `TriggerContract`: webhook, event-based, scheduled triggers |
| **FR-EXT-005** | Custom storage backends | Low | `StorageContract`: Eloquent, Redis, MongoDB adapters |
| **FR-EXT-006** | Custom metric calculators | Medium | `MetricCalculatorContract`: define and compute custom KPIs |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Measurement Context |
|----|-------------|--------|---------------------|
| **PR-001** | Campaign launch time | < 100ms | Excluding async channel execution |
| **PR-002** | Dashboard query (1,000 active campaigns) | < 500ms | With proper database indexing |
| **PR-003** | ROI calculation (10,000 campaigns) | < 2s | Using timers table with indexed queries |
| **PR-004** | Campaign initialization | < 200ms | Including validation |
| **PR-005** | Parallel channel synchronization (10 channels) | < 100ms | Token-based coordination overhead |
| **PR-006** | Lead scoring update | < 50ms | Per lead, excluding external API calls |
| **PR-007** | Segment recalculation (100,000 leads) | < 10s | Background job, not blocking |

### Security Requirements

| ID | Requirement | Scope | Implementation |
|----|-------------|-------|----------------|
| **SR-001** | Prevent unauthorized campaign actions | Engine level | Guard conditions + authorization gates |
| **SR-002** | Sanitize user expressions | Condition evaluator | No code injection, whitelist operators |
| **SR-003** | Multi-tenant data isolation | Database queries | Automatic tenant_id scoping |
| **SR-004** | Sandbox plugin execution | Plugin system | Isolated execution context, resource limits |
| **SR-005** | Audit all campaign changes | Database | Immutable audit log table |
| **SR-006** | RBAC integration | Authorization | Laravel permissions/policies integration |
| **SR-007** | API authentication | REST/GraphQL APIs | Sanctum token-based auth |
| **SR-008** | Rate limiting per tenant | API layer | Configurable limits, Redis-backed |

### Reliability Requirements

| ID | Requirement | Implementation |
|----|-------------|----------------|
| **REL-001** | ACID transactions for state changes | Database transactions for all state modifications |
| **REL-002** | Failed channels don't block campaign | Queue-based execution with retry logic |
| **REL-003** | Concurrency control | Optimistic locking with version numbers |
| **REL-004** | Data corruption protection | Schema validation before persisting |
| **REL-005** | Retry transient failures | Exponential backoff with configurable max attempts |
| **REL-006** | Idempotent operations | Duplicate detection using unique keys |
| **REL-007** | Dead letter queue | Failed messages stored for manual review |

### Scalability Requirements

| ID | Requirement | Implementation |
|----|-------------|----------------|
| **SCL-001** | Horizontal scaling | Stateless services, Redis-backed queues |
| **SCL-002** | Handle 100,000+ active campaigns | Optimized queries, database indexing |
| **SCL-003** | Handle 1,000,000+ leads | Partitioned tables, efficient segmentation |
| **SCL-004** | Concurrent campaign processing | Queue workers with configurable concurrency |
| **SCL-005** | Efficient query performance | Database indexes on: tenant_id, status, created_at, trigger_at |
| **SCL-006** | Caching strategy | Redis cache for: segments, templates, metrics (TTL: 5-60 minutes) |

### Maintainability Requirements

| ID | Requirement | Implementation |
|----|-------------|----------------|
| **MAINT-001** | Framework-agnostic core | No Laravel dependencies in `src/Core/` |
| **MAINT-002** | Laravel adapter separation | All framework code in `src/Adapters/Laravel/` |
| **MAINT-003** | Test coverage | > 80% overall, > 90% for core engine |
| **MAINT-004** | Module independence | Campaign, channel, lead, analytics modules are independent |
| **MAINT-005** | Documentation | PHPDoc for all public methods, README with examples |
| **MAINT-006** | Code style | PSR-12, Laravel Pint formatting |

---

## Business Rules

| ID | Rule | Scope |
|----|------|-------|
| **BR-001** | Campaigns cannot target same lead more than once per day (configurable) | Phase 2+ |
| **BR-002** | All state changes must be ACID transactions | All phases |
| **BR-003** | Low ROI campaigns auto-escalate after configured threshold | Phase 3 |
| **BR-004** | Compensation actions execute in reverse order of original actions | Phase 3 |
| **BR-005** | Delegation chain maximum depth: 3 levels | Phase 3 |
| **BR-006** | Phase 1 configurations remain compatible with Phase 2/3 | All phases |
| **BR-007** | One marketing instance per model/entity | All phases |
| **BR-008** | Parallel channels must all complete before proceeding | Phase 2+ |
| **BR-009** | Campaign assignment checks delegation chain first | Phase 3 |
| **BR-010** | Multi-team approval uses configured strategy | Phase 2+ |
| **BR-011** | GDPR consent required for EU leads | Phase 3 |
| **BR-012** | Unsubscribe respected across all campaigns | Phase 2+ |
| **BR-013** | Lead scoring updates trigger segment recalculation | Phase 2+ |
| **BR-014** | A/B test traffic distribution must total 100% | Phase 3 |

---