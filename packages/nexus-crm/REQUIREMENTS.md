# Nexus CRM Package Requirements

**Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Initial Draft - Progressive Disclosure Model  
**Package:** `nexus-crm`  
**Namespace:** `Nexus\Crm`

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Core Philosophy & Architectural Alignment](#core-philosophy--architectural-alignment)
3. [Personas & User Stories](#personas--user-stories)
4. [Functional Requirements](#functional-requirements)
5. [Non-Functional Requirements](#non-functional-requirements)
6. [Business Rules](#business-rules)
7. [Data Requirements](#data-requirements)
8. [JSON Schema Specification](#json-schema-specification)
9. [Package Structure](#package-structure)
10. [Integration with Nexus ERP Ecosystem](#integration-with-nexus-erp-ecosystem)
11. [Success Metrics](#success-metrics)
12. [Development Phases](#development-phases)
13. [Testing Requirements](#testing-requirements)
14. [Dependencies](#dependencies)
15. [Glossary](#glossary)

---

## Executive Summary

**nexus-crm** is a progressive, headless CRM engine for Laravel that scales from basic contact tracking to enterprise customer relationship management. Built on the Nexus ERP **Maximum Atomicity** principle, it provides a completely independent, testable, and reusable customer management foundation that integrates seamlessly with the Nexus ERP ecosystem.

### The Problem We Solve

CRM packages typically force organizations into difficult trade-offs:

- **Simple tools** (like basic contact managers) lack the depth required for growth and scaling
- **Enterprise systems** (Salesforce, HubSpot, Microsoft Dynamics) are complex, costly, and create vendor lock-in
- **Tight coupling** to specific frameworks makes CRM logic difficult to reuse and test

### Our Solution: Progressive Disclosure Architecture

We solve these challenges through a **progressive disclosure model** that scales with your needs:

| Level | Time to Setup | Capability | Database Required |
|-------|--------------|------------|------------------|
| **Level 1** | 5 minutes | Basic CRM - Add `HasCrm` trait, manage contacts in-app | No (model attributes) |
| **Level 2** | 1 hour | Sales Automation - Database-driven leads, opportunities, pipelines, campaigns | Yes (dedicated tables) |
| **Level 3** | Production-ready | Enterprise CRM - AI insights, automation, integrations, compliance (GDPR) | Yes (advanced features) |

---

## Core Philosophy & Architectural Alignment

The nexus-crm package embodies core Nexus ERP architectural principles:

| Principle | Implementation in nexus-crm |
|-----------|---------------------------|
| **Maximum Atomicity** | Complete CRM domain in single package; zero dependencies on other Nexus packages |
| **Independent Testability** | Entire package testable in isolation with only Laravel framework dependencies |
| **Progressive Disclosure** | Start simple (Level 1), grow into complexity as needed (Level 2/3) |
| **Backwards Compatibility** | Level 1 code continues working after upgrading to Level 2/3 |
| **Headless Backend** | Pure API/CLI interface; no blade views or frontend dependencies |
| **Framework Agnostic Core** | Core business logic (`src/Core/`) has zero Laravel dependencies |
| **Contract-Driven Design** | All integrations via contracts; can swap implementations (e.g., email providers) |
| **Event-Driven Architecture** | Emits domain events for cross-package communication (e.g., `LeadCreatedEvent`) |
| **Extensible by Design** | Plugin system for custom fields, integrations, automations, and conditions |

### Why This Approach Wins

**For Mass Market Users (80%):**
- Quick setup - get started in minutes
- No database overhead for basic usage
- Easy learning curve - progressive complexity
- Fits naturally with existing Laravel models

**For Enterprise Users (20%):**
- Lead scoring and qualification pipelines
- Rich integrations (email, webhooks, API)
- Advanced analytics and reporting
- GDPR compliance and data governance
- Scalable data handling for millions of records

---

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
| **MAINT-001** | Framework-agnostic core | No Laravel dependencies in `src/Core/` directory |
| **MAINT-002** | Laravel adapter pattern | Framework-specific code in `src/Adapters/Laravel/` |
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

## Data Requirements

### Core CRM Tables

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `crm_definitions` | JSON schemas for CRM workflows | `id`, `name`, `schema` (JSON), `active`, `version`, `created_at` |
| `crm_instances` | Running CRM processes | `id`, `subject_type`, `subject_id`, `definition_id`, `state`, `data` (JSON), `started_at`, `ended_at` |
| `crm_history` | Audit trail for all changes | `id`, `instance_id`, `event`, `before` (JSON), `after` (JSON), `actor_id`, `payload` (JSON), `created_at` |

### Entity Tables

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `crm_contacts` | Contacts and leads | `id`, `instance_id`, `name`, `email`, `phone`, `status`, `score`, `tenant_id` |
| `crm_opportunities` | Sales deals | `id`, `contact_id`, `stage`, `value`, `currency`, `close_date`, `probability`, `tenant_id` |
| `crm_campaigns` | Marketing campaigns | `id`, `name`, `type`, `start_date`, `end_date`, `metrics` (JSON), `tenant_id` |

### Automation Tables

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `crm_timers` | Scheduled events | `id`, `instance_id`, `type`, `trigger_at`, `payload` (JSON), `processed_at` |
| `crm_sla` | SLA tracking metrics | `id`, `instance_id`, `duration_minutes`, `started_at`, `breach_at`, `status` |
| `crm_escalations` | Escalation history | `id`, `entity_id`, `level`, `from_user_id`, `to_user_id`, `reason`, `escalated_at` |

**Database Portability:** All tables designed to work with MySQL 8+, PostgreSQL 12+, SQLite 3.35+, SQL Server (as per Nexus ERP standards).

---

## JSON Schema Specification

### Level 1: In-Model CRM

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Crm\Traits\HasCrm;

class User extends Model
{
    use HasCrm;
    
    /**
     * Define CRM configuration for this model
     *
     * @return array<string, mixed>
     */
    public function crm(): array
    {
        return [
            'entities' => [
                'contact' => [
                    'fields' => ['name', 'email', 'phone', 'company'],
                ],
            ],
        ];
    }
}

// Usage
$user = User::find(1);
$user->crm()->addContact([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '+1-555-0100',
    'company' => 'Acme Corp',
]);

$contacts = $user->crm()->getContacts();
$history = $user->crm()->history();
```

### Level 2: Database-Driven CRM with Entities

```json
{
  "id": "sales-pipeline",
  "label": "Sales Pipeline CRM",
  "version": "1.0.0",
  "dataSchema": {
    "lead_score": { 
      "type": "number",
      "min": 0,
      "max": 100
    },
    "lead_source": {
      "type": "string",
      "enum": ["website", "referral", "cold_call", "event"]
    }
  },
  "entities": {
    "lead": {
      "stages": ["new", "contacted", "qualified", "opportunity", "closed_won", "closed_lost"]
    },
    "opportunity": {
      "stages": ["proposal", "negotiation", "closed_won", "closed_lost"]
    }
  },
  "transitions": {
    "qualify_lead": {
      "from": "contacted",
      "to": "qualified",
      "condition": "data.lead_score > 50",
      "actions": ["send_qualification_email", "notify_sales_manager"]
    },
    "convert_to_opportunity": {
      "from": "qualified",
      "to": "opportunity",
      "condition": "data.budget_confirmed == true",
      "actions": ["create_opportunity_record"]
    }
  }
}
```

### Level 3: Enterprise CRM with Automation

```json
{
  "id": "enterprise-crm",
  "label": "Enterprise CRM with SLA",
  "version": "1.0.0",
  "sla": {
    "duration": "2 days",
    "breach_actions": ["escalate_to_manager", "send_sla_breach_notification"]
  },
  "entities": {
    "lead": {
      "stages": ["new", "contacted", "qualified"],
      "automation": {
        "escalation": [
          {
            "after": "24 hours",
            "condition": "stage == 'new'",
            "action": "reassign_to_next_available"
          },
          {
            "after": "48 hours",
            "condition": "stage == 'contacted'",
            "action": "escalate_to_senior_manager"
          }
        ],
        "reminders": [
          {
            "before": "4 hours",
            "target": "sla_breach",
            "action": "send_reminder_notification"
          }
        ]
      }
    }
  },
  "integrations": {
    "email": {
      "provider": "sendgrid",
      "templates": {
        "new_lead": "template_001",
        "qualification": "template_002"
      }
    },
    "webhook": {
      "on_lead_created": "https://api.example.com/webhooks/lead-created",
      "on_opportunity_closed": "https://api.example.com/webhooks/opportunity-closed"
    }
  }
}
```

**Built-in Condition Types:**
- `expression` - Simple comparison expressions (==, >, <, >=, <=, !=)
- `role_check` - Verify user has specific role
- `permission_check` - Verify user has specific permission
- `custom` - Custom condition via `ConditionContract` implementation

**Built-in Approval Strategies:**
- `unison` - All assigned users must approve
- `majority` - >50% of assigned users must approve
- `quorum` - Configurable threshold (e.g., 3 out of 5)
- `first` - First response determines outcome
- `custom` - Custom strategy via `StrategyContract` implementation

---

## Package Structure

```
packages/nexus-crm/
├── src/
│   ├── Core/                           # Framework-agnostic business logic
│   │   ├── Contracts/
│   │   │   ├── CrmEngineContract.php
│   │   │   ├── IntegrationContract.php
│   │   │   ├── ConditionEvaluatorContract.php
│   │   │   ├── ApprovalStrategyContract.php
│   │   │   ├── StorageContract.php
│   │   │   └── TimerContract.php
│   │   ├── Engine/
│   │   │   ├── CrmEngine.php           # Core CRM execution engine
│   │   │   ├── EntityManager.php       # Entity lifecycle management
│   │   │   ├── TransitionManager.php   # Stage transition logic
│   │   │   └── StateManager.php        # State persistence
│   │   ├── Services/
│   │   │   ├── LeadService.php         # Lead-specific business logic
│   │   │   ├── OpportunityService.php  # Opportunity management
│   │   │   ├── CampaignService.php     # Campaign execution
│   │   │   ├── EscalationService.php   # Escalation rules engine
│   │   │   └── SlaService.php          # SLA tracking and breach detection
│   │   ├── DTOs/
│   │   │   ├── CrmDefinition.php       # CRM definition value object
│   │   │   ├── CrmInstance.php         # CRM instance value object
│   │   │   ├── Contact.php             # Contact data transfer object
│   │   │   └── Opportunity.php         # Opportunity DTO
│   │   └── Exceptions/
│   │       ├── CrmException.php
│   │       ├── InvalidTransitionException.php
│   │       └── SlaBreachException.php
│   │
│   ├── Strategies/                     # Approval strategies
│   │   ├── UnisonStrategy.php          # All must approve
│   │   ├── MajorityStrategy.php        # >50% must approve
│   │   ├── QuorumStrategy.php          # Configurable threshold
│   │   └── FirstResponseStrategy.php   # First response wins
│   │
│   ├── Conditions/                     # Condition evaluators
│   │   ├── ExpressionCondition.php     # Simple expressions (>, ==, etc.)
│   │   ├── RoleCheckCondition.php      # Check user role
│   │   └── PermissionCheckCondition.php # Check user permission
│   │
│   ├── Plugins/                        # Integration plugins
│   │   ├── EmailIntegration.php        # Email sending integration
│   │   ├── WebhookIntegration.php      # Webhook HTTP integration
│   │   └── SlackIntegration.php        # Slack notification integration
│   │
│   ├── Timers/                         # Timer system
│   │   ├── TimerQueue.php              # Timer queue management
│   │   ├── TimerProcessor.php          # Process scheduled timers
│   │   └── TimerScheduler.php          # Schedule new timers
│   │
│   ├── Http/                           # API layer (Laravel-specific)
│   │   ├── Controllers/
│   │   │   ├── CrmController.php       # Main CRM API endpoints
│   │   │   ├── LeadController.php      # Lead management API
│   │   │   ├── OpportunityController.php # Opportunity API
│   │   │   └── DashboardController.php # Dashboard data API
│   │   ├── Resources/
│   │   │   ├── CrmInstanceResource.php
│   │   │   ├── ContactResource.php
│   │   │   └── OpportunityResource.php
│   │   └── Requests/
│   │       ├── CreateContactRequest.php
│   │       └── UpdateOpportunityRequest.php
│   │
│   ├── Adapters/                       # Framework adapters
│   │   └── Laravel/
│   │       ├── Traits/
│   │       │   └── HasCrm.php          # Eloquent model trait for Level 1
│   │       ├── Models/
│   │       │   ├── CrmDefinition.php   # Eloquent model for definitions
│   │       │   ├── CrmInstance.php     # Eloquent model for instances
│   │       │   ├── CrmContact.php      # Eloquent model for contacts
│   │       │   └── CrmOpportunity.php  # Eloquent model for opportunities
│   │       ├── Services/
│   │       │   ├── CrmDashboard.php    # Dashboard query service
│   │       │   └── EloquentStorage.php # Eloquent storage implementation
│   │       ├── Commands/
│   │       │   ├── ProcessTimersCommand.php  # Artisan command for timers
│   │       │   └── ProcessEscalationsCommand.php # Process escalations
│   │       └── CrmServiceProvider.php  # Laravel package registration
│   │
│   └── Events/                         # Domain events
│       ├── LeadCreatedEvent.php
│       ├── LeadQualifiedEvent.php
│       ├── OpportunityCreatedEvent.php
│       ├── OpportunityClosedEvent.php
│       ├── CampaignStartedEvent.php
│       ├── SlaBreachedEvent.php
│       └── EscalationTriggeredEvent.php
│
├── database/
│   ├── migrations/
│   │   ├── 2025_11_15_000001_create_crm_definitions_table.php
│   │   ├── 2025_11_15_000002_create_crm_instances_table.php
│   │   ├── 2025_11_15_000003_create_crm_history_table.php
│   │   ├── 2025_11_15_000004_create_crm_contacts_table.php
│   │   ├── 2025_11_15_000005_create_crm_opportunities_table.php
│   │   ├── 2025_11_15_000006_create_crm_campaigns_table.php
│   │   ├── 2025_11_15_000007_create_crm_timers_table.php
│   │   ├── 2025_11_15_000008_create_crm_sla_table.php
│   │   └── 2025_11_15_000009_create_crm_escalations_table.php
│   └── seeders/
│       └── CrmSeeder.php
│
├── tests/
│   ├── Unit/
│   │   ├── EntityTransitionTest.php
│   │   ├── ConditionEvaluatorTest.php
│   │   ├── ApprovalStrategyTest.php
│   │   ├── TimerSchedulerTest.php
│   │   └── SlaCalculatorTest.php
│   ├── Feature/
│   │   ├── Level1CrmTest.php           # Level 1 trait-based CRM
│   │   ├── Level2AutomationTest.php    # Level 2 DB-driven automation
│   │   ├── Level3EnterpriseTest.php    # Level 3 SLA and escalation
│   │   ├── LeadManagementTest.php
│   │   ├── OpportunityManagementTest.php
│   │   └── CampaignExecutionTest.php
│   └── Integration/
│       ├── TenancyIntegrationTest.php  # Test with nexus-tenancy
│       ├── AuditLogIntegrationTest.php # Test with nexus-audit-log
│       └── EmailIntegrationTest.php    # Test email plugin
│
├── config/
│   └── crm.php                          # Package configuration
│
├── composer.json                        # Package dependencies
├── README.md                            # Package documentation
└── CHANGELOG.md                         # Version history
```

---

## Integration with Nexus ERP Ecosystem

The nexus-crm package integrates seamlessly with other Nexus ERP packages while maintaining atomic independence:

### Integration with nexus-tenancy

**Purpose:** Multi-tenant data isolation for CRM entities

**Integration Points:**
- All CRM tables include `tenant_id` foreign key
- `BelongsToTenant` trait applied to all CRM models
- Global scope automatically filters by current tenant
- Tenant context set via `TenantManager` contract

```php
use Nexus\Tenancy\Contracts\TenantManagerContract;
use Nexus\Crm\Traits\HasCrm;

class Lead extends Model
{
    use HasCrm, BelongsToTenant;
    
    // Automatically scoped to current tenant
    // No manual tenant_id filtering needed
}
```

### Integration with nexus-audit-log

**Purpose:** Comprehensive audit trail for all CRM activities

**Integration Points:**
- `HasActivityLogging` trait on all CRM models
- Automatic logging of state changes, transitions, assignments
- Custom log names per entity type: `crm_lead`, `crm_opportunity`
- Audit data accessible via `$instance->getActivityLogs()`

```php
use Nexus\AuditLog\Traits\HasActivityLogging;

class CrmInstance extends Model
{
    use HasActivityLogging;
    
    protected function configureActivityLogging(): array
    {
        return [
            'log_name' => 'crm_instance',
            'log_attributes' => ['state', 'stage', 'assigned_to'],
            'log_only_dirty' => true,
        ];
    }
}
```

### Integration with nexus-settings

**Purpose:** Configurable CRM behavior per tenant/user

**Integration Points:**
- CRM configuration stored in settings system
- Per-tenant lead assignment rules
- Per-user notification preferences
- Global CRM feature toggles

```php
use Nexus\Settings\Facades\Settings;

// Get tenant-specific lead assignment rule
$assignmentRule = Settings::get('crm.lead_assignment_rule', $tenantId);

// Get user-specific notification preferences
$notifyOnNewLead = Settings::get('crm.notify_on_new_lead', $tenantId, $userId);
```

### Integration with nexus-sequencing

**Purpose:** Automatic numbering for leads, opportunities, quotes

**Integration Points:**
- Lead numbers: `LEAD-{YYYY}-{0000}`
- Opportunity numbers: `OPP-{YYYY}-{0000}`
- Quote numbers: `QUOTE-{YYYY}-{0000}`

```php
use Nexus\Sequencing\Actions\GenerateSerialNumberAction;

// Generate lead number
$leadNumber = GenerateSerialNumberAction::run('LEAD-{YYYY}-{0000}');
// Result: "LEAD-2025-0001"
```

### Integration with nexus-workflow

**Purpose:** Advanced workflow automation for complex CRM processes

**Integration Points:**
- CRM stages can trigger workflow instances
- Workflow approvals can advance CRM stages
- Shared workflow engine for state management
- Event synchronization between packages

**Note:** Both nexus-crm and nexus-workflow are independent but can interoperate via events and contracts.

### Atomic Package Principles Applied

**Zero Hard Dependencies:**
- nexus-crm does NOT require any other Nexus package to function
- Integrations are OPTIONAL and activated via contracts
- If nexus-tenancy is present, tenant scoping is enabled automatically
- If nexus-audit-log is present, activity logging is enabled automatically

**Contract-Based Communication:**
```php
// In CrmServiceProvider
public function register(): void
{
    // Optional integration with tenancy
    if (interface_exists(TenantManagerContract::class)) {
        $this->app->bind(
            TenantManagerContract::class,
            \Nexus\Tenancy\Services\TenantManager::class
        );
    }
    
    // Optional integration with audit logging
    if (interface_exists(ActivityLoggerContract::class)) {
        $this->enableActivityLogging();
    }
}
```

---

## Success Metrics

| Metric | Target | Time Period | Why It Matters |
|--------|--------|-------------|----------------|
| **Package Adoption** | >2,000 installations | 6 months | Validates mass market appeal |
| **Hello World Time** | <5 minutes | Ongoing | Measures developer experience quality |
| **Promotion Rate** | >10% upgrade to Level 2 | 6 months | Shows growth path effectiveness |
| **Enterprise Usage** | >5% using SLA features | 6 months | Validates niche value proposition |
| **Critical Bugs** | <5 P0 bugs | 6 months | Ensures production quality |
| **Test Coverage** | >85% overall, >90% core | Ongoing | Maintains engine reliability |
| **Documentation Quality** | <10 support questions/week | 3 months | Measures documentation clarity |
| **Performance SLA** | 95% of actions <100ms | Ongoing | Validates performance targets |

---

## Development Phases

### Phase 1: Level 1 - Basic CRM (Weeks 1-3)

**Goal:** Deliver fully functional trait-based CRM with zero database migrations

**Deliverables:**
- `HasCrm` trait implementation
- In-model CRM configuration parser
- Basic CRM engine for contact management
- Unit and feature tests for Level 1
- Documentation with "5-minute quickstart"

**Acceptance Criteria:**
- Developer can add `HasCrm` trait and define contacts in <5 minutes
- All contact CRUD operations work without migrations
- Full test coverage (>90%)

### Phase 2: Level 2 - Sales Automation (Weeks 4-8)

**Goal:** Database-driven CRM with leads, opportunities, and pipelines

**Deliverables:**
- Database migrations for CRM tables
- CRM definition JSON schema parser
- Lead and opportunity entity management
- Conditional pipeline engine
- Multi-user assignment strategies
- Dashboard API service
- Integration plugins (email, webhook)
- Feature tests for Level 2

**Acceptance Criteria:**
- Level 1 code continues working without changes
- Developers can define CRM workflows in JSON
- Conditional transitions work correctly
- Multi-user assignments resolve per strategy
- Dashboard provides real-time data

### Phase 3: Level 3 - Enterprise Features (Weeks 9-12)

**Goal:** Production-ready enterprise CRM with SLA, escalation, delegation

**Deliverables:**
- Timer system implementation
- SLA tracking and breach detection
- Escalation rules engine
- Delegation with date ranges
- Rollback/compensation logic
- Custom field configuration system
- Advanced reporting queries
- Enterprise feature tests

**Acceptance Criteria:**
- SLA breaches detected and actions triggered
- Escalations occur automatically per rules
- Delegations route correctly during absences
- Failed processes can rollback with compensation
- Custom fields validated and applied

### Phase 4: Extensibility & Polish (Weeks 13-14)

**Goal:** Enable ecosystem extensions and polish package

**Deliverables:**
- Custom condition evaluator API
- Custom integration plugin API
- Custom approval strategy API
- Performance optimization
- Security audit
- Code review and refactoring

**Acceptance Criteria:**
- Developers can create custom conditions
- Custom integrations register correctly
- Performance targets met (see NFRs)
- No critical security vulnerabilities

### Phase 5: Documentation & Launch (Weeks 15-16)

**Goal:** Comprehensive documentation and package release

**Deliverables:**
- Complete API documentation
- Tutorial series (Level 1, 2, 3)
- Video quickstart guides
- Migration guides from other CRMs
- Performance tuning guide
- Beta release and feedback collection
- Public v1.0.0 release

**Acceptance Criteria:**
- Documentation covers all use cases
- Tutorials enable <10-minute setup
- Beta users provide positive feedback
- All success metrics on track
- Package published to Packagist

---

## Testing Requirements

### Unit Tests

**Scope:** Individual classes and methods in isolation

**Coverage Targets:**
- Transition logic: 100%
- Approval strategies: 100%
- Condition evaluators: 100%
- Timer scheduling: 100%
- Delegation routing: 100%

**Example Tests:**
```php
test('unison strategy requires all approvals', function () {
    $strategy = new UnisonStrategy();
    $responses = [
        ['user_id' => 1, 'approved' => true],
        ['user_id' => 2, 'approved' => false],
    ];
    
    expect($strategy->canProceed($responses))->toBeFalse();
});

test('majority strategy requires >50% approval', function () {
    $strategy = new MajorityStrategy();
    $responses = [
        ['user_id' => 1, 'approved' => true],
        ['user_id' => 2, 'approved' => true],
        ['user_id' => 3, 'approved' => false],
    ];
    
    expect($strategy->canProceed($responses))->toBeTrue();
});
```

### Feature Tests

**Scope:** Package features working within Laravel context

**Test Cases:**
- Level 1 complete lifecycle (add, update, delete contacts)
- Level 2 entity management (leads, opportunities, campaigns)
- Level 3 escalation and SLA tracking
- Multi-user assignment and approval
- Custom condition and strategy registration

**Example Tests:**
```php
test('lead transitions from new to qualified based on score', function () {
    $definition = CrmDefinition::factory()->create([
        'schema' => [
            'transitions' => [
                'qualify' => [
                    'from' => 'new',
                    'to' => 'qualified',
                    'condition' => 'data.score > 50',
                ],
            ],
        ],
    ]);
    
    $lead = CrmContact::factory()->create([
        'stage' => 'new',
        'score' => 60,
    ]);
    
    $engine = app(CrmEngine::class);
    $engine->executeTransition($lead, 'qualify');
    
    expect($lead->refresh()->stage)->toBe('qualified');
});
```

### Integration Tests

**Scope:** Interaction with other Nexus packages and external systems

**Test Cases:**
- Tenancy integration (queries scoped to tenant)
- Audit logging integration (activities logged correctly)
- Settings integration (configuration applied per tenant/user)
- Email integration (emails queued and sent)
- Webhook integration (HTTP requests sent correctly)

### Acceptance Tests

**Scope:** User stories from requirements validated end-to-end

**Test Cases:**
- All user stories (US-001 through US-025)
- "Hello World" time <5 minutes verified
- Level 1 to Level 2 promotion without code changes
- All success metrics validated

---

## Dependencies

### Required Dependencies

| Dependency | Version | Purpose |
|------------|---------|---------|
| **PHP** | ≥8.3 | Modern language features (enums, readonly properties) |
| **Database** | MySQL 8+, PostgreSQL 12+, SQLite 3.35+, SQL Server | Data persistence |

### Optional Dependencies (Laravel Integration)

| Dependency | Version | Purpose |
|------------|---------|---------|
| **Laravel Framework** | ≥12.0 | Framework adapter features |
| **nexus-tenancy** | ≥1.0 | Multi-tenant data isolation |
| **nexus-audit-log** | ≥1.0 | Activity logging and audit trail |
| **nexus-settings** | ≥1.0 | Configuration management |
| **nexus-sequencing** | ≥1.0 | Automatic numbering for entities |
| **Redis** | ≥6.0 | Caching and queue backend (optional) |

### Package Installation

```bash
# Basic installation
composer require nexus/crm

# With optional integrations
composer require nexus/crm nexus/tenancy nexus/audit-log nexus/settings nexus/sequencing
```

---

## Glossary

| Term | Definition |
|------|------------|
| **Level 1** | Basic trait-based CRM for contact management without database migrations |
| **Level 2** | Database-driven CRM with leads, opportunities, pipelines, and automation |
| **Level 3** | Enterprise CRM with SLA tracking, escalation, delegation, and advanced features |
| **Lead** | Prospect entity representing a potential customer or sales opportunity |
| **Opportunity** | Sales deal stage representing a qualified lead with revenue potential |
| **Contact** | Person or entity with contact information (name, email, phone) |
| **Campaign** | Marketing or sales initiative targeting multiple leads simultaneously |
| **Stage** | Current state in a CRM entity lifecycle (e.g., "new", "qualified", "closed") |
| **Transition** | Movement from one stage to another based on conditions and rules |
| **Pipeline** | Series of stages that an entity progresses through (e.g., sales pipeline) |
| **Escalation** | Automatic reassignment of stale entities after timeout period |
| **SLA** | Service Level Agreement - time constraint for completing a stage |
| **Delegation** | Temporary routing of assignments to another user during absence |
| **Compensation** | Rollback activity executed when a process fails to restore previous state |
| **Gateway** | Decision point in a workflow where conditions determine next path |
| **Approval Strategy** | Method for resolving multi-user assignments (unison, majority, quorum) |
| **Integration** | External system connection (email, webhook, API) for automation |
| **Guard Condition** | Boolean check that determines if an action can proceed |
| **Hook** | Callback function executed before or after an action (before/after hooks) |

---

## Document Metadata

**Document Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Ready for Review  
**Author:** Nexus ERP Development Team  
**Related Documents:**
- [Nexus ERP System Architecture Document](../../docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md)
- [Nexus ERP Coding Guidelines](../../CODING_GUIDELINES.md)
- [nexus-tenancy Requirements](../nexus-tenancy/REQUIREMENTS.md)
- [nexus-audit-log Requirements](../nexus-audit-log/REQUIREMENTS.md)

---

**Next Steps:**
1. Review this requirements document with stakeholders
2. Confirm success metrics and targets
3. Validate technical feasibility of progressive disclosure model
4. Begin Phase 1 implementation (Level 1 - Basic CRM)
5. Establish CI/CD pipeline with automated testing
6. Create project board for task tracking
