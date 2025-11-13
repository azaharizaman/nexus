---
plan: Implement Audit Logging System
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, infrastructure, audit, logging, compliance, security, monitoring, core-infrastructure]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan covers the comprehensive Audit Logging System for the Laravel ERP, providing activity logging for all CRUD operations with event-based recording, searchable audit trails, and compliance-ready immutable logs. This system tracks all critical operations with full context including actor, timestamp, before/after states, and tenant isolation to meet regulatory requirements (SOX, GDPR, HIPAA).

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-AL-001**: Capture Activity Logs for all CRUD operations with actor, timestamp, IP address, user agent, and request context
- **REQ-FR-AL-002**: Provide Search and Filter capabilities on logs by user, date range, event type, and entity
- **REQ-FR-AL-003**: Implement Log Retention Policy with automatic archival or deletion after configurable period (default: 7 years)
- **REQ-FR-AL-004**: Attach Data Context (before/after states) for high-value transactional records using JSON diff format
- **REQ-FR-AL-005**: Provide Audit Export capability in multiple formats (CSV, JSON, PDF) with filtering and date ranges
- **REQ-BR-AL-001**: All logs MUST be immutable once created - no updates or deletes allowed
- **REQ-BR-AL-002**: Log entries MUST include minimum fields: id, tenant_id, user_id, event, subject_type, subject_id, properties, created_at
- **REQ-BR-AL-003**: High-value transactions (invoices, payments, inventory) MUST log before/after state
- **REQ-BR-AL-004**: System-generated logs (cron jobs, queue workers) MUST identify as "system" actor
- **REQ-BR-AL-005**: Logs older than retention period (default: 7 years) can be archived/deleted
- **REQ-DR-AL-001**: Activity log storage MUST support flexible schema for properties (JSON/JSONB)
- **REQ-DR-AL-002**: Logs MUST include tenant_id for tenant isolation
- **REQ-DR-AL-003**: Logs MUST store before/after snapshots as JSON for audit trail
- **REQ-DR-AL-004**: IP address, user agent, and request ID MUST be captured for API requests
- **REQ-IR-AL-001**: Receive events from all modules (SUB02-SUB25) for centralized logging
- **REQ-IR-AL-002**: Integrate with SUB01 (Multi-Tenancy) for automatic tenant_id injection
- **REQ-PR-AL-001**: Logging operations should not add more than 10% overhead to request processing time
- **REQ-PR-AL-002**: Log writes MUST be asynchronous using queue system to avoid blocking requests
- **REQ-PR-AL-003**: Log queries MUST return results in < 500ms for 90th percentile
- **REQ-SR-AL-001**: Enforce Tenant Isolation on all log queries - users can only view their tenant's logs
- **REQ-SR-AL-002**: Optionally support Log Immutability through append-only storage with hash chain verification
- **REQ-SR-AL-003**: Sensitive fields (passwords, tokens, credit cards) MUST be masked in logs
- **REQ-SR-AL-004**: Log exports MUST require admin permission and be audit logged themselves
- **REQ-SCR-AL-001**: Support 1 million+ log entries per tenant per month with efficient partitioning
- **REQ-CR-AL-001**: Maintain 7-year audit trail for financial transactions (SOX, GAAP compliance)
- **REQ-ARCH-AL-001**: Use document store (MongoDB) or JSONB for flexible, append-only log schema
- **REQ-ARCH-AL-002**: Implement queue-based asynchronous logging to prevent performance impact
- **REQ-ARCH-AL-003**: Support pluggable storage drivers (MongoDB, PostgreSQL, Elasticsearch)
- **REQ-EV-AL-001**: Dispatch ActivityLoggedEvent when any system activity is logged
- **REQ-EV-AL-002**: Dispatch LogRetentionExpiredEvent when logs exceed retention period

### Security Constraints

- **SEC-001**: All log writes must be append-only - no UPDATE or DELETE operations allowed on activity_log table
- **SEC-002**: Log queries must include automatic tenant_id filtering to prevent cross-tenant log access
- **SEC-003**: Sensitive field masking must use consistent patterns (e.g., password: "***", token: "[REDACTED]")
- **SEC-004**: Log export functionality must verify admin permission via Gate or Policy

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: Use Spatie Laravel Activitylog package as foundation, then extend for ERP-specific needs
- **GUD-003**: Use Laravel 12+ conventions (anonymous migrations, modern factory syntax)
- **GUD-004**: Follow PSR-12 coding standards, enforced by Laravel Pint
- **GUD-005**: All logging must be asynchronous - dispatch to queues, never block requests

### Patterns to Follow

- **PAT-001**: Use Observer pattern for automatic model event logging (created, updated, deleted)
- **PAT-002**: Use Repository pattern for log storage abstraction (support multiple backends)
- **PAT-003**: Use Service pattern for log formatting, masking, and export logic
- **PAT-004**: Use Queue Jobs for asynchronous log writing to avoid request blocking
- **PAT-005**: Use Events for cross-module integration (emit ActivityLoggedEvent for other systems)

### Constraints

- **CON-001**: Must support PostgreSQL 14+ with JSONB or MongoDB 6+ for log storage
- **CON-002**: Queue system (Redis, Database, SQS) is mandatory for async logging
- **CON-003**: Package must be installable independently via Composer
- **CON-004**: Log retention period must be configurable per tenant (default: 7 years, minimum: 1 year)
- **CON-005**: Log search must support pagination with max 1000 results per page

## 2. Implementation Steps

### GOAL-001: Package Setup and Core Infrastructure

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| ARCH-AL-001, ARCH-AL-003 | Set up audit-logging package structure with Composer, create database schema with support for PostgreSQL JSONB and optional MongoDB driver, configure service providers and bindings. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create package directory structure: `packages/audit-logging/` with subdirectories `src/`, `database/migrations/`, `database/seeders/`, `config/`, `tests/`. Initialize `composer.json` with package name `azaharizaman/erp-audit-logging`, namespace `Nexus\Erp\AuditLogging`, require Laravel 12+, PHP 8.2+, Spatie Laravel Activitylog as base dependency. | | |
| TASK-002 | Create migration `database/migrations/create_activity_log_table.php` (anonymous class format): Define `activity_log` table with columns: id (BIGSERIAL), tenant_id (UUID/BIGINT, indexed, nullable for system logs), log_name (VARCHAR(255)), description (TEXT), subject_type (VARCHAR(255)), subject_id (BIGINT), causer_type (VARCHAR(255), nullable), causer_id (BIGINT, nullable), properties (JSONB for PostgreSQL, TEXT for MySQL), event (VARCHAR(255), indexed), ip_address (VARCHAR(45), nullable), user_agent (TEXT, nullable), request_id (VARCHAR(255), nullable, indexed), created_at (TIMESTAMP, indexed). Add composite indexes on (tenant_id, created_at), (tenant_id, event), (tenant_id, subject_type, subject_id). | | |
| TASK-003 | Create `config/audit-logging.php` configuration file with settings: enabled (bool, default true), queue_connection (string, default 'redis'), queue_name (string, default 'audit-logs'), storage_driver (string, default 'database', options: 'database', 'mongodb'), retention_days (int, default 2555 = 7 years), mask_sensitive_fields (array, default ['password', 'token', 'secret', 'api_key']), log_system_events (bool, default true), enable_before_after (bool, default true for high-value models). | | |
| TASK-004 | Create `src/AuditLoggingServiceProvider.php`: Register config, migrations, service bindings. Bind `AuditLogRepositoryContract` to `DatabaseAuditLogRepository` or `MongoAuditLogRepository` based on config. Register queue worker for `LogActivityJob`. Publish config and migrations. | | |
| TASK-005 | Create contracts in `src/Contracts/`: `AuditLogRepositoryContract.php` (methods: create, find, search, export, purgeExpired), `LogFormatterContract.php` (methods: format, maskSensitiveFields), `LogExporterContract.php` (methods: exportToCsv, exportToJson, exportToPdf). All contracts must have full PHPDoc with parameter types and return types. | | |

### GOAL-002: Activity Logging Engine with Model Observers

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AL-001, FR-AL-004, BR-AL-002, BR-AL-003, DR-AL-001, DR-AL-003, PR-AL-002 | Implement core activity logging engine with automatic model event tracking, before/after state capture, sensitive field masking, and asynchronous queue processing. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-006 | Create `src/Traits/Auditable.php` trait: Provides `bootAuditable()` method to register model observers. Implements methods: `auditLogName(): string` (returns table name by default), `auditableEvents(): array` (returns ['created', 'updated', 'deleted'] by default), `auditShouldLogBeforeAfter(): bool` (returns true for high-value models), `auditExcludeAttributes(): array` (returns attributes to exclude from logging). Use trait on models requiring audit logging. | | |
| TASK-007 | Create `src/Observers/AuditObserver.php`: Implement methods `created()`, `updated()`, `deleted()` to listen to model events. Extract before/after state: For created, capture new state; for updated, capture both old and new attributes using `$model->getOriginal()` and `$model->getAttributes()`; for deleted, capture final state. Dispatch `LogActivityJob` with event data. | | |
| TASK-008 | Create `src/Jobs/LogActivityJob.php` queue job (implements ShouldQueue): Accept parameters: tenant_id, log_name, description, subject_type, subject_id, causer_type, causer_id, event, properties (array with 'attributes', 'old' for before state), ip_address, user_agent, request_id. In handle() method, use `AuditLogRepositoryContract` to persist log entry. Apply sensitive field masking before save using `LogFormatterContract::maskSensitiveFields()`. | | |
| TASK-009 | Create `src/Services/LogFormatterService.php` implementing `LogFormatterContract`: Implement `maskSensitiveFields(array $data): array` to recursively traverse array/object, check keys against config `mask_sensitive_fields`, replace values with '[REDACTED]'. Implement `format(Model $model, string $event): array` to extract loggable data: actor (auth()->user() or 'system'), IP address from request(), user agent, tenant ID from model or context, before/after states if enabled. | | |
| TASK-010 | Create `src/Repositories/DatabaseAuditLogRepository.php` implementing `AuditLogRepositoryContract`: Use Eloquent model `AuditLog` for database operations. Implement `create(array $data): void` to insert log entry with all required fields. Ensure tenant_id is always included for tenant-scoped logs. Implement append-only constraint (no update/delete methods exposed). | | |

### GOAL-003: Search, Filter, and Query Capabilities

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AL-002, SR-AL-001, PR-AL-003 | Build comprehensive search and filter functionality for audit logs with tenant isolation, pagination, and performance optimization through indexing. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-011 | Extend `AuditLogRepositoryContract` with search method: `search(array $filters, int $perPage = 50): LengthAwarePaginator`. Filters: tenant_id (required, automatic), user_id (optional), causer_id (optional), event (optional, e.g., 'created', 'updated'), subject_type (optional, e.g., 'App\Models\Invoice'), date_from (optional, Carbon), date_to (optional, Carbon), log_name (optional), search_query (optional, full-text search in description/properties). | | |
| TASK-012 | Implement `search()` in `DatabaseAuditLogRepository`: Build Eloquent query with where clauses for each filter. Always apply tenant_id filter (never allow cross-tenant queries). For date range, use `whereBetween('created_at', [$from, $to])`. For search_query, use `where('description', 'like', "%{$query}%")` or PostgreSQL full-text search on JSONB properties. Apply pagination with `paginate($perPage)`. Enforce max 1000 results per page. | | |
| TASK-013 | Create `src/Http/Controllers/AuditLogController.php`: Implement `index(Request $request): JsonResponse` to handle GET /api/v1/audit-logs. Extract filters from request (user_id, event, date_from, date_to, etc.), validate inputs, inject tenant_id from authenticated user context automatically. Call `AuditLogRepositoryContract::search()`. Return `AuditLogResource::collection()` with pagination metadata. | | |
| TASK-014 | Create `src/Http/Resources/AuditLogResource.php`: Transform AuditLog model to JSON:API format with fields: id, tenant_id (only if super-admin), log_name, description, event, subject (type and id), causer (type, id, name), properties (formatted JSON), ip_address, user_agent, created_at (ISO 8601 format). Conditionally hide sensitive fields based on user permissions. | | |
| TASK-015 | Add API routes in service provider: `Route::middleware(['auth:sanctum', 'tenant'])->group(function() { Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('can:view-audit-logs'); Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])->middleware('can:view-audit-logs'); })`. Register routes in `AuditLoggingServiceProvider::boot()`. | | |

### GOAL-004: Log Retention, Archival, and Export

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AL-003, FR-AL-005, BR-AL-005, CR-AL-001, SR-AL-004 | Implement automated log retention policies, archival to cold storage, and export functionality in multiple formats (CSV, JSON, PDF) with admin authorization. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-016 | Create `src/Commands/PurgeExpiredLogsCommand.php` Artisan command: Signature `audit:purge-expired`. In handle(), calculate cutoff date: `now()->subDays(config('audit-logging.retention_days'))`. Query `AuditLog::where('created_at', '<', $cutoffDate)`. For each batch (chunk 1000), check if tenant has custom retention policy (via settings module), if not use default. Soft delete or archive logs (move to archive table or S3), dispatch `LogRetentionExpiredEvent`. Schedule command daily via Task Scheduler. | | |
| TASK-017 | Create `src/Services/LogExporterService.php` implementing `LogExporterContract`: Implement `exportToCsv(Collection $logs, string $filename): string` to generate CSV with columns: ID, Tenant, Event, Actor, Subject, Description, IP, Created At. Implement `exportToJson(Collection $logs): string` for JSON export with full properties. Implement `exportToPdf(Collection $logs, string $filename): string` using DomPDF or similar for formatted PDF report. Return file path or stream. | | |
| TASK-018 | Add export endpoint in `AuditLogController`: Implement `export(Request $request): Response` to handle POST /api/v1/audit-logs/export. Validate inputs: format (csv, json, pdf), filters (same as search), max_records (limit 100,000 for CSV, 10,000 for PDF). Check authorization: `Gate::authorize('export-audit-logs')`. Query logs using filters, call `LogExporterContract::exportTo{Format}()`, return download response. Log the export action itself to audit log. | | |
| TASK-019 | Create `src/Events/LogRetentionExpiredEvent.php`: Event class with properties: tenant_id, purged_count, cutoff_date. Dispatched when logs are purged. Optionally notify admins via SUB22 (Notifications) that old logs were archived/deleted. | | |
| TASK-020 | Create Policy `src/Policies/AuditLogPolicy.php`: Implement methods: `viewAny(User $user): bool` checks 'view-audit-logs' permission, `view(User $user, AuditLog $log): bool` checks same tenant or super-admin, `export(User $user): bool` checks 'export-audit-logs' permission (admin only). Register policy in `AuditLoggingServiceProvider::boot()` via `Gate::policy()`. | | |

### GOAL-005: Integration with Multi-Tenancy and Event System

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-AL-001, IR-AL-002, EV-AL-001, EV-AL-002, BR-AL-004 | Integrate audit logging with multi-tenancy system for automatic tenant context resolution, and implement event-driven architecture for cross-module communication. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-021 | Integrate with SUB01 Multi-Tenancy: Create middleware `src/Http/Middleware/InjectTenantToLogs.php` to automatically inject tenant_id from current tenant context into log data. Register middleware in service provider. Ensure all log writes include tenant_id from `TenantManager::current()` or auth()->user()->tenant_id. For system logs (cron, queue workers), set tenant_id to null and causer_type to 'system'. | | |
| TASK-022 | Create `src/Events/ActivityLoggedEvent.php`: Event class with properties: log_id, tenant_id, event, subject_type, subject_id, causer_type, causer_id, logged_at. Dispatched after log is successfully written. Allows other modules (SUB02, SUB22, SUB03) to react to logged activities for real-time monitoring, notifications, or analytics. | | |
| TASK-023 | Create listener `src/Listeners/NotifyHighValueActivityListener.php`: Listen to `ActivityLoggedEvent`. If event involves high-value entities (Invoice, Payment, InventoryAdjustment), dispatch notification via SUB22 (Notifications) to notify admins of critical activity. Check config `audit-logging.notify_high_value_events` to enable/disable. | | |
| TASK-024 | Register event listeners in `AuditLoggingServiceProvider::boot()`: `Event::listen(ActivityLoggedEvent::class, [NotifyHighValueActivityListener::class, 'handle'])`. Optionally listen to other module events (e.g., InvoiceCreatedEvent from SUB10) to ensure audit logging coverage across all modules. | | |
| TASK-025 | Create trait `src/Traits/LogsSystemActivity.php` for system processes: Provides helper methods `logSystemActivity(string $description, array $properties = []): void` to log activities from cron jobs, queue workers, or CLI commands. Automatically sets causer_type to 'system', causer_id to null, captures process ID and command name in properties. Use this trait in all system-level actions (e.g., data import, scheduled tasks). | | |

## 3. Alternatives

- **ALT-001**: Use Elasticsearch for log storage instead of PostgreSQL JSONB
  - *Pros*: Better full-text search, horizontal scalability, built-in aggregations
  - *Cons*: Additional infrastructure cost, increased complexity, eventual consistency model
  - *Decision*: Not chosen for MVP - PostgreSQL JSONB sufficient for initial scale; Elasticsearch can be added as pluggable driver later via ARCH-AL-003

- **ALT-002**: Implement blockchain-based immutable logs using hash chains
  - *Pros*: Cryptographic proof of immutability, tamper-evident, high compliance confidence
  - *Cons*: Performance overhead, increased storage, complex implementation
  - *Decision*: Not chosen for MVP - append-only database with audit controls sufficient for SOX/GDPR; blockchain can be future enhancement

- **ALT-003**: Use separate database for audit logs (read replica or dedicated instance)
  - *Pros*: Isolates log storage from transactional database, better performance isolation
  - *Cons*: Increased infrastructure complexity, cross-database queries challenging
  - *Decision*: Not chosen for MVP - single database sufficient initially; can migrate to separate instance as scale grows

- **ALT-004**: Implement real-time log streaming to external SIEM (Splunk, Datadog)
  - *Pros*: Enterprise-grade monitoring, advanced analytics, centralized security monitoring
  - *Cons*: Requires enterprise licensing, complex integration, vendor lock-in
  - *Decision*: Not chosen for MVP - internal audit system sufficient; SIEM integration via event hooks can be added later

## 4. Dependencies

**Package Dependencies:**
- **spatie/laravel-activitylog** ^4.0 - Base activity logging functionality
- **league/csv** ^9.0 - CSV export generation
- **barryvdh/laravel-dompdf** ^2.0 - PDF export generation (optional)
- **mongodb/laravel-mongodb** ^4.0 - MongoDB driver support (optional)

**Internal Dependencies:**
- **azaharizaman/erp-multitenancy** (PRD01-SUB01) - Required for tenant context resolution
- **azaharizaman/erp-authentication** (PRD01-SUB02) - Required for user/causer identification
- **azaharizaman/erp-settings-management** (PRD01-SUB05) - Optional for per-tenant retention policies

**Infrastructure Dependencies:**
- PostgreSQL 14+ with JSONB support OR MongoDB 6+
- Redis 6+ for queue system (asynchronous logging)
- Storage for exports (local filesystem or S3 for large exports)

## 5. Files

**Configuration:**
- `packages/audit-logging/config/audit-logging.php` - Package configuration

**Database:**
- `packages/audit-logging/database/migrations/create_activity_log_table.php` - Activity log schema
- `packages/audit-logging/database/migrations/create_activity_log_indexes.php` - Performance indexes

**Models:**
- `packages/audit-logging/src/Models/AuditLog.php` - Activity log Eloquent model

**Contracts:**
- `packages/audit-logging/src/Contracts/AuditLogRepositoryContract.php` - Repository interface
- `packages/audit-logging/src/Contracts/LogFormatterContract.php` - Formatter interface
- `packages/audit-logging/src/Contracts/LogExporterContract.php` - Exporter interface

**Repositories:**
- `packages/audit-logging/src/Repositories/DatabaseAuditLogRepository.php` - PostgreSQL implementation
- `packages/audit-logging/src/Repositories/MongoAuditLogRepository.php` - MongoDB implementation (optional)

**Services:**
- `packages/audit-logging/src/Services/LogFormatterService.php` - Log formatting and masking
- `packages/audit-logging/src/Services/LogExporterService.php` - Export to CSV/JSON/PDF

**Traits:**
- `packages/audit-logging/src/Traits/Auditable.php` - Model trait for automatic logging
- `packages/audit-logging/src/Traits/LogsSystemActivity.php` - System process logging

**Observers:**
- `packages/audit-logging/src/Observers/AuditObserver.php` - Model event observer

**Jobs:**
- `packages/audit-logging/src/Jobs/LogActivityJob.php` - Async log writing

**Controllers:**
- `packages/audit-logging/src/Http/Controllers/AuditLogController.php` - API endpoints

**Resources:**
- `packages/audit-logging/src/Http/Resources/AuditLogResource.php` - JSON:API transformation

**Middleware:**
- `packages/audit-logging/src/Http/Middleware/InjectTenantToLogs.php` - Tenant context injection

**Policies:**
- `packages/audit-logging/src/Policies/AuditLogPolicy.php` - Authorization policies

**Commands:**
- `packages/audit-logging/src/Commands/PurgeExpiredLogsCommand.php` - Log retention cleanup

**Events:**
- `packages/audit-logging/src/Events/ActivityLoggedEvent.php` - Activity logged notification
- `packages/audit-logging/src/Events/LogRetentionExpiredEvent.php` - Logs purged notification

**Listeners:**
- `packages/audit-logging/src/Listeners/NotifyHighValueActivityListener.php` - Critical activity alerts

**Service Provider:**
- `packages/audit-logging/src/AuditLoggingServiceProvider.php` - Package registration

## 6. Testing

**Unit Tests (15):**
- **TEST-001**: LogFormatterService::maskSensitiveFields() correctly masks password, token, api_key fields recursively
- **TEST-002**: LogFormatterService::format() extracts actor, IP, user agent, tenant ID correctly
- **TEST-003**: Auditable trait bootAuditable() registers model observers for created, updated, deleted events
- **TEST-004**: AuditObserver captures before/after state correctly for updated events using getOriginal()
- **TEST-005**: LogActivityJob writes log entry with all required fields (tenant_id, event, subject_type, etc.)
- **TEST-006**: DatabaseAuditLogRepository::create() inserts log entry without update capability (append-only)
- **TEST-007**: DatabaseAuditLogRepository::search() applies tenant_id filter automatically
- **TEST-008**: DatabaseAuditLogRepository::search() filters by date range correctly using whereBetween
- **TEST-009**: LogExporterService::exportToCsv() generates valid CSV with correct columns
- **TEST-010**: LogExporterService::exportToJson() exports full properties as valid JSON
- **TEST-011**: PurgeExpiredLogsCommand respects retention_days config (default 2555 days)
- **TEST-012**: AuditLogPolicy::viewAny() checks 'view-audit-logs' permission
- **TEST-013**: AuditLogPolicy::view() prevents cross-tenant log access
- **TEST-014**: LogsSystemActivity trait sets causer_type to 'system' and causer_id to null
- **TEST-015**: Sensitive fields (password, secret, token) are masked as '[REDACTED]' in logs

**Feature Tests (12):**
- **TEST-016**: Creating a model (Invoice) dispatches LogActivityJob with 'created' event
- **TEST-017**: Updating a model (Invoice) captures before/after state in properties JSON
- **TEST-018**: Deleting a model (Invoice) logs 'deleted' event with final state
- **TEST-019**: GET /api/v1/audit-logs returns paginated logs for authenticated user's tenant only
- **TEST-020**: GET /api/v1/audit-logs filters by user_id correctly
- **TEST-021**: GET /api/v1/audit-logs filters by event type (created, updated, deleted)
- **TEST-022**: GET /api/v1/audit-logs filters by date range (date_from, date_to)
- **TEST-023**: GET /api/v1/audit-logs/{id} returns 404 for cross-tenant log access
- **TEST-024**: POST /api/v1/audit-logs/export generates CSV file with filtered logs
- **TEST-025**: POST /api/v1/audit-logs/export requires 'export-audit-logs' permission (403 without)
- **TEST-026**: POST /api/v1/audit-logs/export logs the export action itself to audit log
- **TEST-027**: Audit logs for Tenant A are not accessible by users from Tenant B

**Integration Tests (8):**
- **TEST-028**: ActivityLoggedEvent is dispatched when log is written successfully
- **TEST-029**: NotifyHighValueActivityListener triggers notification for Invoice creation
- **TEST-030**: Multi-tenancy middleware injects tenant_id automatically into log context
- **TEST-031**: System-level cron job logs with causer_type='system' and null causer_id
- **TEST-032**: Log retention command purges logs older than configured retention period
- **TEST-033**: LogRetentionExpiredEvent is dispatched after purging expired logs
- **TEST-034**: Audit log search query performance < 500ms with 100k+ log entries (indexed queries)
- **TEST-035**: Concurrent log writes (100 requests) complete without data corruption (queue handles async)

**Performance Tests (5):**
- **TEST-036**: Log write operation adds < 10% overhead to request processing (async queue)
- **TEST-037**: Log search query returns results in < 500ms for 90th percentile (with indexes)
- **TEST-038**: Exporting 10,000 logs to CSV completes in < 30 seconds
- **TEST-039**: Querying logs by tenant_id uses index (EXPLAIN query plan verification)
- **TEST-040**: Bulk log insertion (1000 entries) via queue completes in < 60 seconds

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: High-volume logging could overwhelm queue system, causing backlogs
  - *Mitigation*: Use Redis queue with high throughput, implement queue monitoring, add queue workers during peak usage
  
- **RISK-002**: JSONB storage size could grow rapidly for large before/after states
  - *Mitigation*: Only log before/after for high-value models (configurable), compress properties JSON, implement aggressive retention policy
  
- **RISK-003**: Cross-tenant log leakage if tenant_id filtering not enforced correctly
  - *Mitigation*: Global scope on AuditLog model, comprehensive integration tests, code review focus on tenant isolation
  
- **RISK-004**: Export functionality could be abused to extract large amounts of data
  - *Mitigation*: Require admin permission, rate limit exports, log all export actions, set max export limits
  
- **RISK-005**: Log immutability could be bypassed via direct database access
  - *Mitigation*: Database-level triggers to prevent UPDATE/DELETE, strict IAM policies, audit database access logs

**Assumptions:**
- **ASSUMPTION-001**: PostgreSQL JSONB is sufficient for log storage (no immediate need for Elasticsearch)
- **ASSUMPTION-002**: 7-year retention is adequate for SOX/GDPR compliance (configurable per tenant if needed)
- **ASSUMPTION-003**: Queue system can handle average 1000 log writes/minute per tenant
- **ASSUMPTION-004**: Most tenants will have < 10 million logs per year (manageable with partitioning)
- **ASSUMPTION-005**: Export functionality will be used infrequently (< 10 exports/day per tenant)
- **ASSUMPTION-006**: Sensitive field masking patterns are sufficient for GDPR compliance
- **ASSUMPTION-007**: Append-only storage with audit controls meets immutability requirements (no blockchain needed)

## 8. Related PRD / Further Reading

**Primary PRD:**
- [PRD01-SUB03: Audit Logging System](../prd/prd-01/PRD01-SUB03-AUDIT-LOGGING.md) - Complete feature requirements

**Related Sub-PRDs:**
- [PRD01-SUB01: Multi-Tenancy System](../prd/prd-01/PRD01-SUB01-MULTITENANCY.md) - Tenant context resolution
- [PRD01-SUB02: Authentication System](../prd/prd-01/PRD01-SUB02-AUTHENTICATION.md) - User identification
- [PRD01-SUB05: Settings Management](../prd/prd-01/PRD01-SUB05-SETTINGS-MANAGEMENT.md) - Per-tenant retention policies

**Master PRD:**
- [PRD01-MVP: Laravel ERP MVP](../prd/PRD01-MVP.md) - Overall system architecture

**External Documentation:**
- [Spatie Laravel Activitylog](https://spatie.be/docs/laravel-activitylog/v4/introduction) - Base package documentation
- [PostgreSQL JSONB](https://www.postgresql.org/docs/current/datatype-json.html) - JSON storage and indexing
- [Laravel Queue System](https://laravel.com/docs/12.x/queues) - Asynchronous job processing
- [SOX Compliance Requirements](https://www.soxlaw.com/) - Sarbanes-Oxley audit trail requirements
- [GDPR Article 30](https://gdpr-info.eu/art-30-gdpr/) - Records of processing activities
