---
plan: Implement Notification Infrastructure and Templates
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Laravel ERP Development Team
status: Planned
tags: [feature, notifications, events, infrastructure, templates, messaging, communication]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan establishes the foundational infrastructure for the Notifications & Events module, including database schema, template management system with variable substitution, in-app notification system, and system template seeding. This plan provides the core notification infrastructure that all subsequent notification delivery and streaming features will build upon.

## 1. Requirements & Constraints

**Requirements Addressed:**
- **FR-NE-002**: Provide notification templates with variable substitution
- **FR-NE-007**: Provide notification history with read/unread status tracking
- **DR-NE-001**: Store notification logs for audit and troubleshooting
- **DR-NE-002**: Maintain user preferences for notification channels and frequency
- **BR-NE-001**: Users must opt-in for non-critical notifications

**Security Requirements:**
- **SEC-001**: All tenant data must be isolated (tenant_id on all tables)
- **SEC-002**: Template access controlled via policies
- **SEC-003**: System templates immutable by tenants

**Architectural Constraints:**
- **ARCH-001**: Use repository pattern with contracts for all data access
- **ARCH-002**: All models must use BelongsToTenant trait
- **ARCH-003**: All models must use Searchable trait (Laravel Scout)
- **ARCH-004**: All timestamps use timezone-aware datetime

**Performance Constraints:**
- **PERF-001**: Template rendering must complete in < 100ms
- **PERF-002**: In-app notification queries must return in < 200ms for 100 items

**Guidelines:**
- **GUD-001**: Follow Laravel 12 conventions
- **GUD-002**: Use PHP 8.2+ features (readonly, enums, constructor promotion)
- **GUD-003**: All code must pass Pint formatting
- **GUD-004**: Minimum 80% test coverage

**Patterns:**
- **PAT-001**: Contract-driven development (define interfaces first)
- **PAT-002**: Repository pattern for data access
- **PAT-003**: Action pattern using lorisleiva/laravel-actions
- **PAT-004**: Event-driven architecture for cross-module communication

## 2. Implementation Steps

### GOAL-001: Create Package Structure and Database Schema

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-NE-002, FR-NE-007 | Establishes database foundation for notification templates and logs | | |
| DR-NE-001, DR-NE-002 | Creates tables for storing notification data and user preferences | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create package directory structure at `packages/notifications/src/` with subdirectories: Actions/, Contracts/, Events/, Listeners/, Models/, Observers/, Policies/, Repositories/, Services/, Broadcasting/, Http/{Controllers,Requests,Resources}/ | | |
| TASK-002 | Create migration `2025_01_01_000001_create_notification_templates_table.php` with columns: id, tenant_id (nullable, UUID), template_code (varchar 100), template_name (varchar 255), template_type (varchar 50), subject (text nullable), body_text (text), body_html (text nullable), variables (jsonb), is_system (boolean default false), is_active (boolean default true), timestamps, unique constraint on (tenant_id, template_code), indexes on tenant_id and template_type, foreign key on tenant_id cascading delete | | |
| TASK-003 | Create migration `2025_01_01_000002_create_event_subscriptions_table.php` with columns: id, tenant_id (UUID), user_id (bigint), event_type (varchar 255), notification_channels (jsonb array), is_active (boolean default true), timestamps, unique constraint on (tenant_id, user_id, event_type), indexes on tenant_id, user_id, and event_type, foreign keys on tenant_id and user_id cascading delete | | |
| TASK-004 | Create migration `2025_01_01_000003_create_user_notification_preferences_table.php` with columns: id, tenant_id (UUID), user_id (bigint), channel_type (varchar 20), is_enabled (boolean default true), digest_mode (boolean default false), digest_frequency (varchar 20 nullable), quiet_hours_start (time nullable), quiet_hours_end (time nullable), timestamps, unique constraint on (tenant_id, user_id, channel_type), indexes on tenant_id and user_id, foreign keys cascading delete | | |
| TASK-005 | Create migration `2025_01_01_000004_create_notification_log_table.php` with columns: id, tenant_id (UUID), user_id (bigint nullable), notification_type (varchar 100), channel_type (varchar 20), recipient (text), subject (text nullable), body (text), priority (varchar 20 default 'normal'), status (varchar 20 default 'pending'), delivery_attempts (int default 0), error_message (text nullable), external_id (varchar 255 nullable), scheduled_at, sent_at, delivered_at, read_at (all timestamp nullable), created_at, indexes on tenant_id, user_id, status, channel_type, scheduled_at, created_at, foreign keys cascading delete | | |
| TASK-006 | Create migration `2025_01_01_000005_create_in_app_notifications_table.php` with columns: id, tenant_id (UUID), user_id (bigint), notification_type (varchar 100), title (varchar 255), message (text), action_url (text nullable), action_label (varchar 100 nullable), icon (varchar 50 nullable), priority (varchar 20 default 'normal'), is_read (boolean default false), read_at (timestamp nullable), expires_at (timestamp nullable), created_at, indexes on tenant_id, user_id, is_read, created_at, foreign keys cascading delete | | |
| TASK-007 | Create NotificationsServiceProvider at `packages/notifications/src/NotificationsServiceProvider.php` with register() method binding contracts to implementations, boot() method publishing migrations and config | | |
| TASK-008 | Create composer.json at `packages/notifications/composer.json` with package name "azaharizaman/erp-notifications", namespace "Nexus\\Erp\\Notifications", require PHP ^8.2, laravel/framework ^12.0, azaharizaman/erp-core ^1.0, lorisleiva/laravel-actions ^2.0, autoload PSR-4, service provider registration | | |

### GOAL-002: Create Template Models and Repositories with Contracts

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-NE-002 | Implements template management with variable substitution | | |
| ARCH-001, PAT-001, PAT-002 | Establishes contract-driven repository pattern | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-009 | Create NotificationTemplateRepositoryContract interface at `packages/notifications/src/Contracts/NotificationTemplateRepositoryContract.php` with methods: findById(int $id): ?NotificationTemplate, findByCode(string $code, ?string $tenantId): ?NotificationTemplate, create(array $data): NotificationTemplate, update(NotificationTemplate $template, array $data): NotificationTemplate, delete(NotificationTemplate $template): bool, getSystemTemplates(): Collection, getTenantTemplates(string $tenantId): Collection | | |
| TASK-010 | Create NotificationTemplate model at `packages/notifications/src/Models/NotificationTemplate.php` with use BelongsToTenant, Searchable, LogsActivity traits, fillable fields: template_code, template_name, template_type, subject, body_text, body_html, variables, is_system, is_active, casts: variables as array, is_system as boolean, is_active as boolean, timestamps as datetime, searchableAs() returning 'notification_templates', toSearchableArray() including id, template_code, template_name, template_type, tenant_id | | |
| TASK-011 | Create EventSubscription model at `packages/notifications/src/Models/EventSubscription.php` with use BelongsToTenant, Searchable traits, fillable: event_type, notification_channels, is_active, casts: notification_channels as array, is_active as boolean, belongsTo user relationship | | |
| TASK-012 | Create UserNotificationPreference model at `packages/notifications/src/Models/UserNotificationPreference.php` with use BelongsToTenant, Searchable traits, fillable: channel_type, is_enabled, digest_mode, digest_frequency, quiet_hours_start, quiet_hours_end, casts: is_enabled, digest_mode as boolean, quiet_hours_start, quiet_hours_end as datetime (H:i format), belongsTo user relationship | | |
| TASK-013 | Create NotificationLog model at `packages/notifications/src/Models/NotificationLog.php` with use BelongsToTenant, Searchable traits, fillable: notification_type, channel_type, recipient, subject, body, priority, status, delivery_attempts, error_message, external_id, scheduled_at, sent_at, delivered_at, read_at, casts for all timestamps as datetime, delivery_attempts as integer, belongsTo user relationship (nullable) | | |
| TASK-014 | Create InAppNotification model at `packages/notifications/src/Models/InAppNotification.php` with use BelongsToTenant, Searchable traits, fillable: notification_type, title, message, action_url, action_label, icon, priority, is_read, read_at, expires_at, casts: is_read as boolean, read_at, expires_at as datetime, belongsTo user relationship, scopeUnread(), scopeActive() filtering expired | | |
| TASK-015 | Create NotificationTemplateRepository at `packages/notifications/src/Repositories/NotificationTemplateRepository.php` implementing NotificationTemplateRepositoryContract with all interface methods using Eloquent queries, findByCode() method caching results for 1 hour using Laravel cache | | |
| TASK-016 | Bind NotificationTemplateRepositoryContract to NotificationTemplateRepository in NotificationsServiceProvider register() method using $this->app->bind() | | |

### GOAL-003: Implement Template Rendering Service with Variable Substitution

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-NE-002 | Variable substitution in notification templates | | |
| PERF-001 | Template rendering < 100ms | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-017 | Create TemplateRenderServiceContract interface at `packages/notifications/src/Contracts/TemplateRenderServiceContract.php` with methods: render(NotificationTemplate $template, array $variables): string, renderSubject(NotificationTemplate $template, array $variables): ?string, validateVariables(NotificationTemplate $template, array $variables): array (returns missing variables) | | |
| TASK-018 | Create TemplateRenderService at `packages/notifications/src/Services/TemplateRenderService.php` implementing TemplateRenderServiceContract, render() method using Blade::render() for body_text with $variables array, renderSubject() for email subjects, validateVariables() checking required variables against provided variables, throw exception if critical variables missing | | |
| TASK-019 | Add escapeHtml() protected method to TemplateRenderService for sanitizing variables before rendering to prevent XSS attacks using htmlspecialchars() with ENT_QUOTES and UTF-8 encoding | | |
| TASK-020 | Add caching layer in render() method: cache key format "notification:template:{template_id}:{hash_of_variables}", cache TTL 15 minutes, store rendered output | | |
| TASK-021 | Create RenderNotificationTemplateAction at `packages/notifications/src/Actions/RenderNotificationTemplateAction.php` using AsAction trait, handle(NotificationTemplate $template, array $variables): string method injecting TemplateRenderServiceContract, calling render() and returning result | | |
| TASK-022 | Bind TemplateRenderServiceContract to TemplateRenderService in NotificationsServiceProvider register() method | | |

### GOAL-004: Implement In-App Notification System with Read/Unread Tracking

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-NE-007 | Notification history with read/unread status | | |
| PERF-002 | In-app notification queries < 200ms for 100 items | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-023 | Create InAppNotificationRepositoryContract interface at `packages/notifications/src/Contracts/InAppNotificationRepositoryContract.php` with methods: create(array $data): InAppNotification, markAsRead(InAppNotification $notification): bool, markAllAsRead(int $userId, string $tenantId): int, getUnreadCount(int $userId, string $tenantId): int, getUserNotifications(int $userId, string $tenantId, int $limit = 100): Collection, deleteExpired(): int | | |
| TASK-024 | Create InAppNotificationRepository at `packages/notifications/src/Repositories/InAppNotificationRepository.php` implementing InAppNotificationRepositoryContract, getUserNotifications() with eager loading user relationship, query optimization using indexes, pagination support | | |
| TASK-025 | Add getUnreadCount() method using DB query with where('is_read', false) and count(), cache result for 5 minutes with cache key "notifications:unread:{tenant_id}:{user_id}" | | |
| TASK-026 | Add deleteExpired() method as scheduled command running daily, deletes notifications where expires_at < now() and is_read = true, log number of deleted records | | |
| TASK-027 | Create CreateInAppNotificationAction at `packages/notifications/src/Actions/CreateInAppNotificationAction.php` using AsAction trait, handle(array $data): InAppNotification method injecting InAppNotificationRepositoryContract, validating data, creating notification, invalidating unread count cache | | |
| TASK-028 | Create MarkNotificationAsReadAction at `packages/notifications/src/Actions/MarkNotificationAsReadAction.php` using AsAction trait, handle(InAppNotification $notification): bool method setting is_read = true, read_at = now(), invalidating unread count cache | | |
| TASK-029 | Create InAppNotificationResource at `packages/notifications/src/Http/Resources/InAppNotificationResource.php` returning: id, type (notification_type), title, message, action (url + label), icon, priority, is_read, read_at (formatted ISO 8601), created_at (formatted ISO 8601), links (self URL) | | |
| TASK-030 | Bind InAppNotificationRepositoryContract to InAppNotificationRepository in NotificationsServiceProvider | | |

### GOAL-005: Seed System Templates and Create Testing Infrastructure

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-NE-002 | System templates available for common notifications | | |
| GUD-004 | Testing infrastructure with 80% coverage | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-031 | Create NotificationTemplateSeeder at `packages/notifications/database/seeders/NotificationTemplateSeeder.php` creating 20+ system templates: workflow_approved_email, workflow_rejected_email, workflow_pending_approval_email, invoice_overdue_email, payment_received_email, purchase_order_approved_email, password_reset_email, account_locked_email, low_stock_alert_email, budget_exceeded_email, each with tenant_id = null, is_system = true, template variables defined in variables column, subject and body_text with Blade syntax | | |
| TASK-032 | Add template examples: workflow_approved_email with variables: [submitter_name, workflow_name, approver_name, approved_at, comments], subject: "Workflow Approved: {{ $workflow_name }}", body with professional formatting | | |
| TASK-033 | Create NotificationTemplateFactory at `packages/notifications/database/factories/NotificationTemplateFactory.php` with definition() returning: template_code => fake()->unique()->word, template_name => fake()->sentence(3), template_type => fake()->randomElement(['email', 'sms', 'push', 'in_app']), subject => fake()->sentence(), body_text => fake()->paragraphs(3, true), variables => [], is_system => false, is_active => true | | |
| TASK-034 | Create InAppNotificationFactory with definition() returning: notification_type => fake()->word, title => fake()->sentence(), message => fake()->paragraph(), action_url => fake()->url(), priority => fake()->randomElement(['critical', 'high', 'normal', 'low']), is_read => false | | |
| TASK-035 | Create Feature test at `packages/notifications/tests/Feature/NotificationTemplateTest.php` testing: can list templates via API, can create custom template, can render template with variables, cannot modify system templates, tenant isolation working | | |
| TASK-036 | Create Feature test at `packages/notifications/tests/Feature/InAppNotificationTest.php` testing: can create in-app notification, can mark as read, can get unread count, can list user notifications with pagination, expired notifications auto-deleted | | |
| TASK-037 | Create Unit test at `packages/notifications/tests/Unit/TemplateRenderTest.php` testing: variable substitution working, missing variables throw exception, HTML escaping prevents XSS, caching working, render performance < 100ms | | |
| TASK-038 | Create Unit test at `packages/notifications/tests/Unit/InAppNotificationRepositoryTest.php` testing: repository methods working, unread count accurate, expired deletion working, query performance < 200ms for 100 items | | |
| TASK-039 | Run migrations with `php artisan migrate` and verify all tables created with correct schema | | |
| TASK-040 | Run seeder with `php artisan db:seed --class=NotificationTemplateSeeder` and verify 20+ system templates created | | |
| TASK-041 | Run `./vendor/bin/pest` to execute all tests and verify passing, run `./vendor/bin/pint` to format code | | |

## 3. Alternatives

- **ALT-001**: Use external template rendering service (e.g., Handlebars.js) instead of Blade - Rejected because Blade is native to Laravel and provides better integration
- **ALT-002**: Store rendered templates in database instead of caching - Rejected due to storage overhead and slower retrieval
- **ALT-003**: Use polling instead of caching for unread count - Rejected due to higher database load

## 4. Dependencies

- **DEP-001**: `azaharizaman/erp-core` package for BelongsToTenant trait and base models
- **DEP-002**: `lorisleiva/laravel-actions` for Action pattern
- **DEP-003**: `laravel/scout` for search functionality on models
- **DEP-004**: `spatie/laravel-activitylog` for audit logging on template changes
- **DEP-005**: Redis configured for caching (template rendering and unread counts)
- **DEP-006**: PostgreSQL 14+ for JSONB column support

## 5. Files

**Models:**
- `packages/notifications/src/Models/NotificationTemplate.php` - Template model with tenant isolation
- `packages/notifications/src/Models/EventSubscription.php` - Event subscription model
- `packages/notifications/src/Models/UserNotificationPreference.php` - User preferences model
- `packages/notifications/src/Models/NotificationLog.php` - Notification delivery log model
- `packages/notifications/src/Models/InAppNotification.php` - In-app notification model

**Contracts:**
- `packages/notifications/src/Contracts/NotificationTemplateRepositoryContract.php` - Template repository interface
- `packages/notifications/src/Contracts/TemplateRenderServiceContract.php` - Template rendering interface
- `packages/notifications/src/Contracts/InAppNotificationRepositoryContract.php` - In-app notification repository interface

**Repositories:**
- `packages/notifications/src/Repositories/NotificationTemplateRepository.php` - Template data access
- `packages/notifications/src/Repositories/InAppNotificationRepository.php` - In-app notification data access

**Services:**
- `packages/notifications/src/Services/TemplateRenderService.php` - Template rendering with variable substitution

**Actions:**
- `packages/notifications/src/Actions/RenderNotificationTemplateAction.php` - Render template action
- `packages/notifications/src/Actions/CreateInAppNotificationAction.php` - Create in-app notification
- `packages/notifications/src/Actions/MarkNotificationAsReadAction.php` - Mark notification as read

**Migrations:**
- `packages/notifications/database/migrations/2025_01_01_000001_create_notification_templates_table.php`
- `packages/notifications/database/migrations/2025_01_01_000002_create_event_subscriptions_table.php`
- `packages/notifications/database/migrations/2025_01_01_000003_create_user_notification_preferences_table.php`
- `packages/notifications/database/migrations/2025_01_01_000004_create_notification_log_table.php`
- `packages/notifications/database/migrations/2025_01_01_000005_create_in_app_notifications_table.php`

**Tests:**
- `packages/notifications/tests/Feature/NotificationTemplateTest.php` - Template API tests
- `packages/notifications/tests/Feature/InAppNotificationTest.php` - In-app notification tests
- `packages/notifications/tests/Unit/TemplateRenderTest.php` - Template rendering unit tests
- `packages/notifications/tests/Unit/InAppNotificationRepositoryTest.php` - Repository unit tests

## 6. Testing

- **TEST-001**: Verify all 5 migrations create tables with correct schema including indexes and foreign keys
- **TEST-002**: Verify NotificationTemplate model uses BelongsToTenant, Searchable, LogsActivity traits
- **TEST-003**: Verify template rendering with variables substitutes correctly using Blade syntax
- **TEST-004**: Verify missing required variables throw exception during rendering
- **TEST-005**: Verify HTML escaping prevents XSS attacks in rendered templates
- **TEST-006**: Verify template rendering completes in < 100ms (PERF-001)
- **TEST-007**: Verify template caching reduces database queries on repeated renders
- **TEST-008**: Verify in-app notifications can be created, marked as read, and deleted
- **TEST-009**: Verify unread count query returns accurate count and completes in < 50ms
- **TEST-010**: Verify getUserNotifications query returns correct results in < 200ms for 100 items (PERF-002)
- **TEST-011**: Verify expired notifications auto-delete daily via scheduled command
- **TEST-012**: Verify tenant isolation: users can only see notifications for their tenant
- **TEST-013**: Verify system templates cannot be modified by tenant users (is_system = true)
- **TEST-014**: Verify NotificationTemplateSeeder creates 20+ system templates
- **TEST-015**: Verify all tests pass with `./vendor/bin/pest` and achieve 80% coverage

## 7. Risks & Assumptions

- **RISK-001**: Template rendering performance may degrade with complex Blade syntax - Mitigation: Cache rendered templates
- **RISK-002**: Unread count queries may become slow with large notification volumes - Mitigation: Redis caching and database indexes
- **RISK-003**: System template modifications by mistake - Mitigation: Policy enforcement preventing updates to is_system = true templates
- **ASSUMPTION-001**: Blade templating engine provides sufficient flexibility for all notification types
- **ASSUMPTION-002**: Redis is available and configured for caching
- **ASSUMPTION-003**: PostgreSQL JSONB support is sufficient for storing template variables and notification channels
- **ASSUMPTION-004**: Users understand Blade syntax for custom templates

## 8. KIV for Future Implementations

- **KIV-001**: Visual template editor with drag-and-drop components for non-technical users
- **KIV-002**: Template versioning and rollback capability
- **KIV-003**: A/B testing for notification templates to optimize engagement
- **KIV-004**: Template analytics (open rates, click rates, conversion rates)
- **KIV-005**: Multi-language template support for localization
- **KIV-006**: Template preview before sending notifications

## 9. Related PRD / Further Reading

- [PRD01-SUB22: Notifications & Events](../prd/prd-01/PRD01-SUB22-NOTIFICATIONS-EVENTS.md) - Complete Sub-PRD requirements
- [Master PRD](../prd/PRD01-MVP.md) - Master PRD Section F.2.3 (Notifications & Events module)
- [PRD01-SUB01: Multi-Tenancy](../prd/prd-01/PRD01-SUB01-MULTITENANCY.md) - BelongsToTenant trait documentation
- [PRD01-SUB02: Authentication](../prd/prd-01/PRD01-SUB02-AUTHENTICATION.md) - User model and authentication
- [PRD01-SUB03: Audit Logging](../prd/prd-01/PRD01-SUB03-AUDIT-LOGGING.md) - LogsActivity trait usage
- [Laravel Blade Documentation](https://laravel.com/docs/blade) - Template syntax reference
- [Laravel Scout Documentation](https://laravel.com/docs/scout) - Search functionality
