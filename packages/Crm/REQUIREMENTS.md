

## Personas & User Stories

### Personas

| ID | Persona | Role | Primary Goal |
|----|---------|------|--------------|
| **P1** | Mass Market Developer | Full-stack developer at startup | "Add contact management to my User model in 5 minutes" |
| **P2** | In-House CRM Developer | Backend developer at mid-size firm | "Build lead-to-sale pipeline integrated with existing data" |
| **P3** | End-User (Sales Rep/Manager) | Business user | "Track leads, opportunities, interactions in one place" |
| **P4** | System Administrator | IT/DevOps | "Configure custom fields, integrations without code changes" |

### User Stories

#### Level 1: Basic CRM (Mass Appeal)

| ID | Persona | User Story | Priority |
|----|---------|-----------|----------|
| **US-001** | P1 | As a developer, I want to add the `HasCrm` trait to my model to manage contacts without migrations | High |
| **US-002** | P1 | As a developer, I want to define contact fields as an array in my model without external dependencies | High |
| **US-003** | P1 | As a developer, I want to call `$model->crm()->addContact($data)` to create a new contact | High |
| **US-004** | P1 | As a developer, I want to call `$model->crm()->can('edit')` to check permissions declaratively | High |
| **US-005** | P1 | As a developer, I want to call `$model->crm()->history()` to view audit logs | Medium |

#### Level 2: Sales Automation

| ID | Persona | User Story | Priority |
|----|---------|-----------|----------|
| **US-010** | P2 | As a developer, I want to promote to database-driven CRM without changing Level 1 code | High |
| **US-011** | P2 | As a developer, I want to define leads and opportunities with customizable stages | High |
| **US-012** | P2 | As a developer, I want to use conditional pipelines (e.g., if score > 50, promote to qualified) | High |
| **US-013** | P2 | As a developer, I want to run parallel campaigns (email + phone calls simultaneously) | High |
| **US-014** | P2 | As a developer, I want multi-user assignments with approval strategies (unison, majority, quorum) | High |
| **US-015** | P3 | As a sales manager, I want a unified dashboard showing all pending leads and opportunities | High |
| **US-016** | P3 | As a sales rep, I want to log interactions with notes and file attachments | High |

#### Level 3: Enterprise CRM

| ID | Persona | User Story | Priority |
|----|---------|-----------|----------|
| **US-020** | P2 | As a sales manager, I want stale leads to auto-escalate after a configured time period | High |
| **US-021** | P2 | As a sales manager, I want SLA tracking for lead response times with breach notifications | High |
| **US-022** | P3 | As a sales rep, I want to delegate my leads to a colleague during vacation with auto-routing | High |
| **US-023** | P2 | As a developer, I want to rollback failed campaigns with compensation logic | Medium |
| **US-024** | P4 | As a system admin, I want to configure custom fields through an admin interface | Medium |
| **US-025** | P2 | As a sales manager, I want conversion rate reports by stage, user, and time period | Medium |

---

## Functional Requirements

### FR-L1: Level 1 - Basic CRM (Mass Appeal)

| ID | Requirement | Priority | Acceptance Criteria |
|----|------------|----------|-------------------|
| **FR-L1-001** | HasCrm trait for models | High | Add trait to any model; define `crm()` method returning array config; no migrations required; works instantly |
| **FR-L1-002** | In-model contact definitions | High | Define fields as array; store in JSON model column; no external tables or dependencies |
| **FR-L1-003** | `crm()->addContact($data)` method | High | Create contact; emit `ContactCreatedEvent`; validate data; run in transaction |
| **FR-L1-004** | `crm()->can($action)` method | High | Return boolean permission check; guard conditions evaluated; no side effects |
| **FR-L1-005** | `crm()->history()` method | Medium | Return collection of changes; include timestamps, actors, before/after values |
| **FR-L1-006** | Guard conditions on actions | Medium | Accept callable; e.g., `fn($contact) => $contact->status == 'active'`; evaluated before action |
| **FR-L1-007** | Hooks (before/after) | Medium | Register callbacks; e.g., notify after contact added; chainable |

### FR-L2: Level 2 - Sales Automation

| ID | Requirement | Priority | Acceptance Criteria |
|----|------------|----------|-------------------|
| **FR-L2-001** | Database-driven CRM definitions (JSON) | High | Table `crm_definitions` for schemas; same API as Level 1; override in-model config; hot-reload without code changes |
| **FR-L2-002** | Lead/Opportunity stages | High | Define entity type: "lead", "opportunity"; assign to users/roles; pause until user action |
| **FR-L2-003** | Conditional pipelines | High | Support expressions: `==`, `>`, `<`, `AND`, `OR`; access to `data.score`, `data.status`, etc. |
| **FR-L2-004** | Parallel campaigns | High | Define array of actions; execute simultaneously; wait for all to complete before proceeding |
| **FR-L2-005** | Inclusive gateways | Medium | Multiple conditions can be true; execute all true paths; synchronize at join point |
| **FR-L2-006** | Multi-user assignment strategies | High | Built-in strategies: unison (all approve), majority (>50%), quorum (custom threshold); extensible via contract |
| **FR-L2-007** | Dashboard API/Service | High | `CrmDashboard::forUser($id)->pending()` returns pending items; support filter/sort; paginated |
| **FR-L2-008** | Actions (convert, close, etc.) | High | Validate transition; log activity; support comments/attachments; trigger next stage automatically |
| **FR-L2-009** | Data validation | Medium | Schema validation in JSON definition; types: string, number, date, boolean, array; required/optional |
| **FR-L2-010** | Plugin integrations | High | Asynchronous execution; built-in: email, webhook; extensible via `IntegrationContract` |

### FR-L3: Level 3 - Enterprise CRM

| ID | Requirement | Priority | Acceptance Criteria |
|----|------------|----------|-------------------|
| **FR-L3-001** | Escalation rules | High | Trigger after configurable time; notify/reassign; record escalation history; scheduled execution |
| **FR-L3-002** | SLA tracking | High | Track duration from start; define breach actions; status: on_track, at_risk, breached |
| **FR-L3-003** | Delegation with date ranges | High | Table: delegator, delegatee, start_date, end_date; auto-route during delegation; max depth: 3 levels |
| **FR-L3-004** | Rollback logic | Medium | Compensation activities on failure; execute in reverse order; restore previous state |
| **FR-L3-005** | Custom fields configuration | Medium | Define in database; validated on entity creation; optional admin UI via Nexus ERP Core |
| **FR-L3-006** | Timer system | High | Table `crm_timers`; indexed `trigger_at`; workers poll and process; NOT cron-based |

### FR-EXT: Extensibility

| ID | Requirement | Priority | Acceptance Criteria |
|----|------------|----------|-------------------|
| **FR-EXT-001** | Custom integrations | High | Implement `IntegrationContract`: `execute()`, `compensate()` methods |
| **FR-EXT-002** | Custom conditions | High | Implement `ConditionEvaluatorContract`: `evaluate($context)` method; return boolean |
| **FR-EXT-003** | Custom strategies | High | Implement `ApprovalStrategyContract`: `canProceed($responses)` method |
| **FR-EXT-004** | Custom triggers | Medium | Implement `TriggerContract`: webhook, event-based, schedule-based |
| **FR-EXT-005** | Custom storage | Low | Implement `StorageContract`: support Eloquent (default), Redis, custom backends |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Notes |
|----|------------|--------|-------|
| **PR-001** | Action execution time | < 100ms | Excluding async operations (emails, webhooks) |
| **PR-002** | Dashboard query (1,000 items) | < 500ms | With proper database indexing |
| **PR-003** | SLA check (10,000 active) | < 2s | Using timers table with indexed `trigger_at` |
| **PR-004** | CRM initialization | < 200ms | Including validation and schema loading |
| **PR-005** | Parallel gateway synchronization (10 branches) | < 100ms | Token-based coordination |

### Security Requirements

| ID | Requirement | Scope |
|----|------------|-------|
| **SR-001** | Unauthorized action prevention | Engine level - guard conditions evaluated before any state change |
| **SR-002** | Expression sanitization | Prevent code injection in conditional expressions |
| **SR-003** | Tenant isolation | Auto-scope all queries to current tenant (via `nexus-tenancy` integration) |
| **SR-004** | Plugin sandboxing | Prevent malicious plugin code execution; validate before registration |
| **SR-005** | Audit change tracking | Immutable audit log for all CRM entity changes |
| **SR-006** | RBAC integration | Permission checks via `nexus-identity-management` (if available) or Laravel gates |

### Reliability Requirements

| ID | Requirement | Notes |
|----|------------|-------|
| **REL-001** | ACID guarantees for state changes | All transitions wrapped in database transactions |
| **REL-002** | Failed integrations don't block progress | Queue async operations; retry with exponential backoff |
| **REL-003** | Concurrency control | Optimistic locking to prevent race conditions |
| **REL-004** | Data corruption protection | Schema validation before persistence |
| **REL-005** | Retry failed transient operations | Configurable retry policy with dead letter queue |

### Scalability Requirements

| ID | Requirement | Notes |
|----|------------|-------|
| **SCL-001** | Asynchronous integrations | Queue-based execution for email, webhooks, external API calls |
| **SCL-002** | Horizontal timer scaling | Multiple workers can process timers concurrently without conflicts |
| **SCL-003** | Efficient query performance | Proper indexes on `status`, `user_id`, `trigger_at`, `tenant_id` |
| **SCL-004** | Support 100,000+ active instances | Optimized queries and caching for large-scale deployments |

### Maintainability Requirements

| ID | Requirement | Notes |
|----|------------|-------|
| **MAINT-001** | Framework-agnostic core | No Laravel dependencies in `src/Core/` directory; Laravel dependencies permitted in `src/Adapters/Laravel/` and `src/Http/` as per architectural guidelines |
| **MAINT-002** | Laravel adapter pattern | Framework-specific code in `src/Adapters/Laravel/` |
| **MAINT-003** | Orchestration policy | Atomic packages MUST NOT depend on `lorisleiva/laravel-actions`. Orchestration (multi-entrypoint actions) belongs in `nexus/erp` where `laravel-actions` may be used; in-package service classes should remain framework-agnostic and testable. |
| **MAINT-003** | Test coverage | >80% overall, >90% for core business logic |
| **MAINT-004** | Domain separation | Lead, opportunity, campaign logic independent and separately testable |

---

## Business Rules

| ID | Rule | Level |
|----|------|-------|
| **BR-001** | Users cannot self-assign leads (configurable) | Level 2 |
| **BR-002** | All state changes must be ACID-compliant | All Levels |
| **BR-003** | Stale leads auto-escalate after configured timeout | Level 3 |
| **BR-004** | Compensation activities execute in reverse order | Level 3 |
| **BR-005** | Delegation chain maximum depth: 3 levels | Level 3 |
| **BR-006** | Level 1 code remains compatible after Level 2/3 upgrade | All Levels |
| **BR-007** | One CRM instance per subject model | All Levels |
| **BR-008** | Parallel branches must all complete before proceeding | Level 2 |
| **BR-009** | Assignment checks delegation chain first | Level 3 |
| **BR-010** | Multi-user tasks resolved per configured strategy | Level 2 |

---