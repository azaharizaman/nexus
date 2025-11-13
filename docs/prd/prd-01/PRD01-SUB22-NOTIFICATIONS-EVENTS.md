# PRD01-SUB22: Notifications & Events

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Optional Feature Modules - Communication  
**Related Sub-PRDs:** SUB02 (Authentication), SUB21 (Workflow Engine), All transactional modules  
**Composer Package:** `azaharizaman/erp-notifications`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Notifications & Events module provides multi-channel notification delivery, event subscriptions, notification templates, real-time event streaming, and comprehensive delivery tracking for keeping users informed of critical system events.

### Purpose

This module solves the challenge of reliably delivering time-sensitive notifications across multiple channels (email, SMS, push, in-app, webhooks) while respecting user preferences, handling failures gracefully, and providing real-time updates via WebSockets.

### Scope

**Included:**
- Multi-channel notifications (email, SMS, push, in-app, webhook)
- Notification templates with variable substitution
- Event subscriptions with user-configurable preferences
- Notification scheduling with delivery time optimization
- Delivery status tracking (sent, delivered, failed, bounced)
- Notification grouping and digest mode to reduce noise
- Notification history with read/unread status
- Real-time event streaming via WebSockets for live updates

**Excluded:**
- Chat and instant messaging (future module)
- Video conferencing integration (future module)
- Social media notifications (future integration)

### Dependencies

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for notifications
- **SUB02 (Authentication & Authorization)** - User notification preferences
- **SUB03 (Audit Logging)** - Track notification delivery

**Optional Dependencies:**
- **SUB21 (Workflow Engine)** - Approval notifications
- All transactional modules - Business event notifications

### Composer Package Information

- **Package Name:** `azaharizaman/erp-notifications`
- **Namespace:** `Nexus\Erp\Notifications`
- **Monorepo Location:** `/packages/notifications/`
- **Installation:** `composer require azaharizaman/erp-notifications` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB22 (Notifications & Events). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-NE-001** | Support **multi-channel notifications** (email, SMS, push, in-app, webhook) | High | Planned |
| **FR-NE-002** | Provide **notification templates** with variable substitution | High | Planned |
| **FR-NE-003** | Support **event subscriptions** with user-configurable preferences | High | Planned |
| **FR-NE-004** | Implement **notification scheduling** with delivery time optimization | Medium | Planned |
| **FR-NE-005** | Track **notification delivery status** (sent, delivered, failed, bounced) | High | Planned |
| **FR-NE-006** | Support **notification grouping** and **digest mode** to reduce noise | Medium | Planned |
| **FR-NE-007** | Provide **notification history** with read/unread status tracking | High | Planned |
| **FR-NE-008** | Support **real-time event streaming** via WebSockets for live updates | High | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-NE-001** | Users must **opt-in** for non-critical notifications | Planned |
| **BR-NE-002** | **Critical alerts** (security, compliance) cannot be disabled by users | Planned |
| **BR-NE-003** | Failed notifications must **retry** with exponential backoff up to 3 attempts | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-NE-001** | Store **notification logs** for audit and troubleshooting | Planned |
| **DR-NE-002** | Maintain **user preferences** for notification channels and frequency | Planned |
| **DR-NE-003** | Record **webhook delivery attempts** with response codes | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-NE-001** | Integrate with **all modules** via event-driven architecture | Planned |
| **IR-NE-002** | Support **external notification services** (SendGrid, Twilio, Firebase) | Planned |
| **IR-NE-003** | Provide **webhook endpoints** for third-party system notifications | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-NE-001** | Implement **rate limiting** to prevent notification spam | Planned |
| **SR-NE-002** | **Validate webhook signatures** to ensure authenticity | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-NE-001** | Notifications must be queued and delivered within **3 seconds** of triggering event | Planned |
| **PR-NE-002** | Support **10,000+ notifications per minute** during peak load | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-NE-001** | Scale to **1 million+ notifications per day** per tenant | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-NE-001** | Use **Redis with Laravel Queue/Horizon** for asynchronous message processing | Planned |
| **ARCH-NE-002** | Implement **pub/sub pattern** for real-time event broadcasting | Planned |
| **ARCH-NE-003** | Use **Laravel Echo** with WebSockets for real-time notifications | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-NE-001** | `NotificationSentEvent` | When notification is dispatched | Planned |
| **EV-NE-002** | `NotificationFailedEvent` | When delivery fails after retries | Planned |
| **EV-NE-003** | `WebhookDeliveredEvent` | When webhook successfully delivers | Planned |

---

## Technical Specifications

### Database Schema

**Notification Templates Table:**

```sql
CREATE TABLE notification_templates (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NULL,  -- NULL for system templates
    template_code VARCHAR(100) NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    template_type VARCHAR(50) NOT NULL,  -- 'email', 'sms', 'push', 'in_app', 'webhook'
    subject TEXT NULL,  -- For email
    body_text TEXT NOT NULL,
    body_html TEXT NULL,  -- For email
    variables JSONB NULL,  -- Available template variables
    is_system BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, template_code),
    INDEX idx_notification_templates_tenant (tenant_id),
    INDEX idx_notification_templates_type (template_type),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Event Subscriptions Table:**

```sql
CREATE TABLE event_subscriptions (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    user_id BIGINT NOT NULL REFERENCES users(id),
    event_type VARCHAR(255) NOT NULL,  -- Fully qualified event class name
    notification_channels JSONB NOT NULL,  -- Array of channels: ['email', 'in_app', 'push']
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, user_id, event_type),
    INDEX idx_event_subscriptions_tenant (tenant_id),
    INDEX idx_event_subscriptions_user (user_id),
    INDEX idx_event_subscriptions_event (event_type),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**User Notification Preferences Table:**

```sql
CREATE TABLE user_notification_preferences (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    user_id BIGINT NOT NULL REFERENCES users(id),
    channel_type VARCHAR(20) NOT NULL,  -- 'email', 'sms', 'push', 'in_app'
    is_enabled BOOLEAN DEFAULT TRUE,
    digest_mode BOOLEAN DEFAULT FALSE,
    digest_frequency VARCHAR(20) NULL,  -- 'hourly', 'daily', 'weekly'
    quiet_hours_start TIME NULL,
    quiet_hours_end TIME NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, user_id, channel_type),
    INDEX idx_user_prefs_tenant (tenant_id),
    INDEX idx_user_prefs_user (user_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Notification Log Table:**

```sql
CREATE TABLE notification_log (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    user_id BIGINT NULL REFERENCES users(id),
    notification_type VARCHAR(100) NOT NULL,
    channel_type VARCHAR(20) NOT NULL,
    recipient TEXT NOT NULL,  -- Email address, phone number, device token, webhook URL
    subject TEXT NULL,
    body TEXT NOT NULL,
    priority VARCHAR(20) DEFAULT 'normal',  -- 'critical', 'high', 'normal', 'low'
    status VARCHAR(20) NOT NULL DEFAULT 'pending',  -- 'pending', 'sent', 'delivered', 'failed', 'bounced'
    delivery_attempts INT DEFAULT 0,
    error_message TEXT NULL,
    external_id VARCHAR(255) NULL,  -- ID from external service (SendGrid, Twilio, etc.)
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_notification_log_tenant (tenant_id),
    INDEX idx_notification_log_user (user_id),
    INDEX idx_notification_log_status (status),
    INDEX idx_notification_log_channel (channel_type),
    INDEX idx_notification_log_scheduled (scheduled_at),
    INDEX idx_notification_log_created (created_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**In-App Notifications Table:**

```sql
CREATE TABLE in_app_notifications (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    user_id BIGINT NOT NULL REFERENCES users(id),
    notification_type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    action_url TEXT NULL,
    action_label VARCHAR(100) NULL,
    icon VARCHAR(50) NULL,
    priority VARCHAR(20) DEFAULT 'normal',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_in_app_notifications_tenant (tenant_id),
    INDEX idx_in_app_notifications_user (user_id),
    INDEX idx_in_app_notifications_read (is_read),
    INDEX idx_in_app_notifications_created (created_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Webhook Subscriptions Table:**

```sql
CREATE TABLE webhook_subscriptions (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    webhook_url TEXT NOT NULL,
    event_types JSONB NOT NULL,  -- Array of subscribed event types
    secret_key VARCHAR(255) NOT NULL,  -- For signature validation
    is_active BOOLEAN DEFAULT TRUE,
    retry_policy JSONB NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_webhook_subscriptions_tenant (tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Webhook Delivery Log Table:**

```sql
CREATE TABLE webhook_delivery_log (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    webhook_subscription_id BIGINT NOT NULL REFERENCES webhook_subscriptions(id),
    event_type VARCHAR(255) NOT NULL,
    payload JSONB NOT NULL,
    delivery_status VARCHAR(20) NOT NULL DEFAULT 'pending',  -- 'pending', 'delivered', 'failed'
    http_status_code INT NULL,
    response_body TEXT NULL,
    delivery_attempts INT DEFAULT 0,
    error_message TEXT NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_webhook_delivery_tenant (tenant_id),
    INDEX idx_webhook_delivery_subscription (webhook_subscription_id),
    INDEX idx_webhook_delivery_status (delivery_status),
    INDEX idx_webhook_delivery_created (created_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

### API Endpoints

All endpoints follow the RESTful pattern under `/api/v1/notifications/`:

**Notification Templates:**
- `GET /api/v1/notifications/templates` - List notification templates
- `POST /api/v1/notifications/templates` - Create custom template
- `GET /api/v1/notifications/templates/{id}` - Get template details
- `PATCH /api/v1/notifications/templates/{id}` - Update template
- `DELETE /api/v1/notifications/templates/{id}` - Delete custom template

**Event Subscriptions:**
- `GET /api/v1/notifications/subscriptions` - List user subscriptions
- `POST /api/v1/notifications/subscriptions` - Subscribe to event
- `DELETE /api/v1/notifications/subscriptions/{id}` - Unsubscribe

**User Preferences:**
- `GET /api/v1/notifications/preferences` - Get user preferences
- `PATCH /api/v1/notifications/preferences` - Update preferences
- `POST /api/v1/notifications/preferences/test` - Send test notification

**In-App Notifications:**
- `GET /api/v1/notifications/in-app` - List in-app notifications
- `GET /api/v1/notifications/in-app/unread-count` - Get unread count
- `POST /api/v1/notifications/in-app/{id}/mark-read` - Mark as read
- `POST /api/v1/notifications/in-app/mark-all-read` - Mark all as read
- `DELETE /api/v1/notifications/in-app/{id}` - Delete notification

**Notification History:**
- `GET /api/v1/notifications/history` - Get notification history
- `GET /api/v1/notifications/history/{id}` - Get delivery details

**Webhooks:**
- `GET /api/v1/notifications/webhooks` - List webhook subscriptions
- `POST /api/v1/notifications/webhooks` - Create webhook subscription
- `PATCH /api/v1/notifications/webhooks/{id}` - Update subscription
- `DELETE /api/v1/notifications/webhooks/{id}` - Delete subscription
- `GET /api/v1/notifications/webhooks/{id}/deliveries` - Get delivery log

**Real-Time (WebSocket):**
- `ws://app.domain/notifications` - WebSocket endpoint for real-time notifications

### Events

**Domain Events Emitted:**

```php
namespace Nexus\Erp\Notifications\Events;

class NotificationSentEvent
{
    public function __construct(
        public readonly NotificationLog $log,
        public readonly string $channel,
        public readonly string $recipient
    ) {}
}

class NotificationFailedEvent
{
    public function __construct(
        public readonly NotificationLog $log,
        public readonly string $errorMessage,
        public readonly int $attemptNumber
    ) {}
}

class WebhookDeliveredEvent
{
    public function __construct(
        public readonly WebhookDeliveryLog $delivery,
        public readonly int $httpStatusCode,
        public readonly string $responseBody
    ) {}
}
```

### Event Listeners

**Events from Other Modules:**

This module listens to ALL domain events from other modules and routes them to subscribed users based on their preferences. Examples:
- `WorkflowApprovedEvent` (SUB21) - Notify submitter
- `PurchaseOrderApprovedEvent` (SUB16) - Notify requestor
- `InvoiceOverdueEvent` (SUB12) - Notify AR team
- `PaymentReceivedEvent` (SUB09) - Notify sales team

---

## Implementation Plans

**Note:** Implementation plans follow the naming convention `PLAN{number}-implement-{component}.md`

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN22-implement-notifications-events.md | FR-NE-001 to FR-NE-008, BR-NE-001 to BR-NE-003 | MILESTONE 10 | Not Started |

**Implementation plan will be created separately using:** `.github/prompts/create-implementation-plan.prompt.md`

---

## Acceptance Criteria

### Functional Acceptance

- [ ] Multi-channel notifications functional (email, SMS, push, in-app, webhook)
- [ ] Notification templates with variable substitution working
- [ ] Event subscriptions with user preferences operational
- [ ] Notification scheduling working
- [ ] Delivery status tracking functional
- [ ] Notification grouping and digest mode working
- [ ] Notification history with read/unread status operational
- [ ] Real-time event streaming via WebSockets functional

### Technical Acceptance

- [ ] All API endpoints return correct responses per OpenAPI spec
- [ ] Notifications queued and delivered within 3 seconds (PR-NE-001)
- [ ] System supports 10,000+ notifications per minute (PR-NE-002)
- [ ] System scales to 1 million+ notifications per day per tenant (SCR-NE-001)
- [ ] Redis with Laravel Queue/Horizon operational (ARCH-NE-001)
- [ ] Pub/sub pattern for event broadcasting functional (ARCH-NE-002)
- [ ] Laravel Echo with WebSockets operational (ARCH-NE-003)

### Security Acceptance

- [ ] Rate limiting prevents notification spam (SR-NE-001)
- [ ] Webhook signatures validated for authenticity (SR-NE-002)

### Integration Acceptance

- [ ] Integration with all modules via event-driven architecture functional (IR-NE-001)
- [ ] External notification services integration working (IR-NE-002)
- [ ] Webhook endpoints for third-party systems operational (IR-NE-003)

---

## Testing Strategy

### Unit Tests

**Test Coverage Requirements:** Minimum 80% code coverage

**Key Test Areas:**
- Notification template variable substitution
- Event subscription matching logic
- Delivery retry logic with exponential backoff
- User preference filtering
- Webhook signature validation

**Example Tests:**
```php
test('notification retries with exponential backoff', function () {
    $notification = NotificationLog::factory()->failed()->create([
        'delivery_attempts' => 1,
    ]);
    
    $result = RetryNotificationAction::run($notification);
    
    expect($result)->toBeTrue();
    expect($notification->fresh()->delivery_attempts)->toBe(2);
    // Verify backoff delay increased
});

test('critical alerts cannot be disabled', function () {
    $user = User::factory()->create();
    $preference = UserNotificationPreference::factory()->create([
        'user_id' => $user->id,
        'channel_type' => 'email',
        'is_enabled' => false,
    ]);
    
    $notification = new SecurityAlertNotification($user);  // Critical alert
    
    $result = ShouldSendNotificationAction::run($notification, $user);
    
    expect($result)->toBeTrue();  // Critical alerts always sent
});
```

### Feature Tests

**API Integration Tests:**
- Send notification via API
- Update user preferences via API
- Subscribe to events via API
- Query notification history via API

**Example Tests:**
```php
test('can send notification via API', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/notifications/send', [
            'type' => 'custom',
            'channel' => 'email',
            'recipient' => $user->email,
            'subject' => 'Test Notification',
            'body' => 'This is a test message',
        ]);
    
    $response->assertCreated();
    expect(NotificationLog::count())->toBe(1);
});
```

### Integration Tests

**Cross-Module Integration:**
- Workflow approval triggers notification
- Payment received triggers notification
- Invoice overdue triggers notification
- Real-time WebSocket notification delivery

### Performance Tests

**Load Testing Scenarios:**
- Notification delivery: 3 seconds from event (PR-NE-001)
- 10,000+ notifications per minute during peak (PR-NE-002)
- 1 million+ notifications per day per tenant (SCR-NE-001)
- WebSocket connection handling for multiple concurrent users

---

## Dependencies

### Feature Module Dependencies

**From Master PRD Section D.2.1:**

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for notifications
- **SUB02 (Authentication & Authorization)** - User notification preferences
- **SUB03 (Audit Logging)** - Track notification delivery

**Optional Dependencies:**
- **SUB21 (Workflow Engine)** - Approval notifications
- All transactional modules - Business event notifications

### External Package Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "azaharizaman/erp-core": "^1.0",
    "lorisleiva/laravel-actions": "^2.0",
    "laravel/horizon": "^5.0",
    "pusher/pusher-php-server": "^7.0"
  },
  "require-dev": {
    "pestphp/pest": "^4.0"
  }
}
```

### Infrastructure Dependencies

- **Database:** PostgreSQL 14+ (for notification logs and preferences)
- **Cache:** Redis 6+ (for queue management and pub/sub)
- **Queue:** Redis with Laravel Horizon (for async notification processing)
- **WebSocket:** Pusher or Laravel WebSockets (for real-time notifications)
- **External Services:** SendGrid (email), Twilio (SMS), Firebase Cloud Messaging (push)

---

## Feature Module Structure

### Directory Structure (in Monorepo)

```
packages/notifications/
├── src/
│   ├── Actions/
│   │   ├── SendNotificationAction.php
│   │   ├── SubscribeToEventAction.php
│   │   └── DeliverWebhookAction.php
│   ├── Contracts/
│   │   ├── NotificationServiceContract.php
│   │   └── WebhookServiceContract.php
│   ├── Events/
│   │   ├── NotificationSentEvent.php
│   │   ├── NotificationFailedEvent.php
│   │   └── WebhookDeliveredEvent.php
│   ├── Listeners/
│   │   └── BroadcastEventToSubscribersListener.php
│   ├── Models/
│   │   ├── NotificationTemplate.php
│   │   ├── EventSubscription.php
│   │   ├── UserNotificationPreference.php
│   │   ├── NotificationLog.php
│   │   ├── InAppNotification.php
│   │   ├── WebhookSubscription.php
│   │   └── WebhookDeliveryLog.php
│   ├── Notifications/
│   │   └── CustomNotification.php
│   ├── Observers/
│   │   └── NotificationLogObserver.php
│   ├── Policies/
│   │   └── NotificationTemplatePolicy.php
│   ├── Repositories/
│   │   └── NotificationTemplateRepository.php
│   ├── Services/
│   │   ├── NotificationService.php
│   │   ├── WebhookService.php
│   │   ├── TemplateRenderService.php
│   │   └── DeliveryRetryService.php
│   ├── Broadcasting/
│   │   └── NotificationChannel.php
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Resources/
│   └── NotificationsServiceProvider.php
├── tests/
│   ├── Feature/
│   │   ├── NotificationDeliveryTest.php
│   │   ├── EventSubscriptionTest.php
│   │   └── WebhookTest.php
│   └── Unit/
│       ├── TemplateRenderTest.php
│       └── RetryLogicTest.php
├── database/
│   ├── migrations/
│   │   ├── 2025_01_01_000001_create_notification_templates_table.php
│   │   ├── 2025_01_01_000002_create_event_subscriptions_table.php
│   │   ├── 2025_01_01_000003_create_user_notification_preferences_table.php
│   │   ├── 2025_01_01_000004_create_notification_log_table.php
│   │   ├── 2025_01_01_000005_create_in_app_notifications_table.php
│   │   ├── 2025_01_01_000006_create_webhook_subscriptions_table.php
│   │   └── 2025_01_01_000007_create_webhook_delivery_log_table.php
│   └── factories/
│       └── NotificationTemplateFactory.php
├── routes/
│   └── api.php
├── config/
│   └── notifications.php
├── composer.json
└── README.md
```

---

## Migration Path

This is a new module with no existing functionality to migrate from.

**Initial Setup:**
1. Install package via Composer
2. Publish migrations and run `php artisan migrate`
3. Configure external notification services (SendGrid, Twilio, Firebase)
4. Seed default notification templates
5. Configure WebSocket broadcasting (Pusher or Laravel WebSockets)
6. Set up Laravel Horizon for queue management
7. Train users on notification preferences

---

## Success Metrics

From Master PRD Section B.3:

**Adoption Metrics:**
- User adoption of notification preferences > 90%
- In-app notification engagement rate > 70%
- Webhook subscription usage > 40% of tenants

**Performance Metrics:**
- Notification delivery time < 3 seconds from event (PR-NE-001)
- 10,000+ notifications per minute during peak (PR-NE-002)

**Reliability Metrics:**
- Notification delivery success rate > 99%
- Failed notification recovery rate > 95% (after retries)

**Operational Metrics:**
- Average notification response time < 5 minutes
- User notification satisfaction score > 4.5/5

---

## Assumptions & Constraints

### Assumptions

1. External notification services (SendGrid, Twilio, Firebase) operational
2. Users have provided valid contact information (email, phone)
3. WebSocket infrastructure configured and stable
4. Queue workers running for async notification processing
5. Users opt-in for non-critical notifications

### Constraints

1. Users must opt-in for non-critical notifications
2. Critical alerts (security, compliance) cannot be disabled
3. Failed notifications retry with exponential backoff up to 3 attempts
4. System scales to 1 million+ notifications per day per tenant
5. Notification delivery within 3 seconds of triggering event

---

## Monorepo Integration

### Development

- Lives in `/packages/notifications/` during development
- Main app uses Composer path repository to require locally:
  ```json
  {
    "repositories": [
      {
        "type": "path",
        "url": "./packages/notifications"
      }
    ],
    "require": {
      "azaharizaman/erp-notifications": "@dev"
    }
  }
  ```
- All changes committed to monorepo

### Release (v1.0)

- Tagged with monorepo version (e.g., v1.0.0)
- Published to Packagist as `azaharizaman/erp-notifications`
- Can be installed independently in external Laravel apps
- Semantic versioning: MAJOR.MINOR.PATCH

---

## References

- Master PRD: [../PRD01-MVP.md](../PRD01-MVP.md)
- Monorepo Strategy: [../PRD01-MVP.md#C.1](../PRD01-MVP.md#section-c1-core-architectural-strategy-the-monorepo)
- Feature Module Independence: [../PRD01-MVP.md#D.2.2](../PRD01-MVP.md#d22-feature-module-independence-requirements)
- Architecture Documentation: [../../architecture/](../../architecture/)
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- GitHub Copilot Instructions: [../../.github/copilot-instructions.md](../../.github/copilot-instructions.md)

---

**Next Steps:**
1. Review and approve this Sub-PRD
2. Create implementation plan: `PLAN22-implement-notifications-events.md` in `/docs/plan/`
3. Break down into GitHub issues
4. Assign to MILESTONE 10 from Master PRD Section F.2.4
5. Set up feature module structure in `/packages/notifications/`
