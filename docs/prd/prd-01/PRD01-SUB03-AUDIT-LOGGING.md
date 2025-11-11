# PRD01-SUB03: Audit Logging System

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Mandatory Feature Modules - Core Infrastructure  
**Related Sub-PRDs:** PRD01-SUB01 (Multi-Tenancy), PRD01-SUB02 (Authentication), PRD01-SUB05 (Settings Management)  
**Composer Package:** `azaharizaman/erp-audit-logging`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Audit Logging System provides **comprehensive activity logging system tracking all critical operations with event-based recording and searchable audit trails**. This mandatory feature module ensures complete audit trails for compliance, security monitoring, and operational insights by capturing all CRUD operations with full context including actor, timestamp, before/after states, and tenant isolation.

### Purpose

The Audit Logging System solves the critical problem of **traceable system activity** for compliance, security, and operational analysis. It enables:

1. **Compliance Requirements:** Meet regulatory requirements (SOX, GDPR, HIPAA) for audit trails
2. **Security Monitoring:** Detect unauthorized access and suspicious activities
3. **Operational Insights:** Track user behavior and system usage patterns
4. **Debugging Support:** Reproduce issues by reviewing historical state changes
5. **Data Recovery:** Restore previous states using before/after snapshots

### Scope

**Included in this Feature Module:**

- ✅ Activity logging for all CRUD operations
- ✅ Before/after state capture for data changes
- ✅ Tenant-scoped audit trail isolation
- ✅ Actor identification (user, system, API client)
- ✅ Searchable and filterable audit logs
- ✅ Audit export capabilities (CSV, JSON)
- ✅ Log immutability with append-only storage
- ✅ High-volume log optimization
- ✅ Automatic log retention policies
- ✅ Integration with all ERP modules

**Excluded from this Feature Module:**

- ❌ Real-time alerting (handled by SUB22)
- ❌ Long-term archival to S3/cold storage (future enhancement)
- ❌ Blockchain-based immutability (future enhancement)
- ❌ Video/screenshot capture (out of scope)

### Dependencies

**Mandatory Dependencies:**
- Laravel Framework v12.x
- PHP ≥ 8.2
- MongoDB or PostgreSQL with JSONB support
- PRD01-SUB01 (Multi-Tenancy System)

**Feature Module Dependencies:**
- **Mandatory:** SUB01 (Multi-Tenancy) - Required for tenant-scoped logging

### Composer Package Information

- **Package Name:** `azaharizaman/erp-audit-logging`
- **Namespace:** `Azaharizaman\Erp\AuditLogging`
- **Monorepo Location:** `/packages/audit-logging/`
- **Installation:** `composer require azaharizaman/erp-audit-logging` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB03 (Audit Logging & Activity Tracking). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-AL-001** | Capture **Activity Logs** for all CRUD operations with actor, timestamp, IP address, user agent, and request context | High | Planned |
| **FR-AL-002** | Provide **Search and Filter** capabilities on logs by user, date range, event type, and entity | High | Planned |
| **FR-AL-003** | Implement **Log Retention Policy** with automatic archival or deletion after configurable period | Medium | Planned |
| **FR-AL-004** | Attach **Data Context (before/after states)** for high-value transactional records using JSON diff format | High | Planned |
| **FR-AL-005** | Provide **Audit Export** capability in multiple formats (CSV, JSON, PDF) with filtering and date ranges | Medium | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-AL-001** | All logs MUST be **immutable once created** - no updates or deletes allowed | Planned |
| **BR-AL-002** | Log entries MUST include minimum fields: id, tenant_id, user_id, event, subject_type, subject_id, properties, created_at | Planned |
| **BR-AL-003** | High-value transactions (invoices, payments, inventory) MUST **log before/after state** | Planned |
| **BR-AL-004** | System-generated logs (cron jobs, queue workers) MUST **identify as "system" actor** | Planned |
| **BR-AL-005** | Logs older than retention period (default: **7 years**) can be archived/deleted | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-AL-001** | Activity log storage MUST support **flexible schema for properties (JSON/JSONB)** | Planned |
| **DR-AL-002** | Logs MUST include **tenant_id** for tenant isolation | Planned |
| **DR-AL-003** | Logs MUST store **before/after snapshots as JSON** for audit trail | Planned |
| **DR-AL-004** | **IP address, user agent, and request ID** MUST be captured for API requests | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-AL-001** | Receive events from **all modules** (SUB02-SUB25) for centralized logging | Planned |
| **IR-AL-002** | Integrate with **SUB01 (Multi-Tenancy)** for automatic tenant_id injection | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-AL-001** | Logging operations should not add more than **10% overhead** to request processing time | Planned |
| **PR-AL-002** | Log writes MUST be **asynchronous using queue system** to avoid blocking requests | Planned |
| **PR-AL-003** | Log queries MUST return results in **< 500ms** for 90th percentile | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-AL-001** | Enforce **Tenant Isolation** on all log queries - users can only view their tenant's logs | Planned |
| **SR-AL-002** | Optionally support **Log Immutability** through append-only storage with hash chain verification | Planned |
| **SR-AL-003** | Sensitive fields (passwords, tokens, credit cards) MUST be **masked in logs** | Planned |
| **SR-AL-004** | Log exports MUST require **admin permission** and be audit logged themselves | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-AL-001** | Support **1 million+ log entries** per tenant per month with efficient partitioning | Planned |

### Compliance Requirements (CR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **CR-AL-001** | Maintain **7-year audit trail** for financial transactions (SOX, GAAP compliance) | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-AL-001** | Use **document store (MongoDB) or JSONB** for flexible, append-only log schema | Planned |
| **ARCH-AL-002** | Implement **queue-based asynchronous logging** to prevent performance impact | Planned |
| **ARCH-AL-003** | Support **pluggable storage drivers** (MongoDB, PostgreSQL, Elasticsearch) | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-AL-001** | `ActivityLoggedEvent` | When any system activity is logged | Planned |
| **EV-AL-002** | `LogRetentionExpiredEvent` | When logs exceed retention period and need archival | Planned |

---

## Technical Specifications

### Database Schema

**Activity Logs Table (PostgreSQL with JSONB):**

```sql
CREATE TABLE activity_log (
    id BIGSERIAL PRIMARY KEY,
    log_name VARCHAR(255) NULL,
    description TEXT NOT NULL,
    subject_type VARCHAR(255) NULL,
    subject_id BIGINT NULL,
    causer_type VARCHAR(255) NULL,
    causer_id BIGINT NULL,
    tenant_id UUID NOT NULL,
    properties JSONB NULL,
    event VARCHAR(255) NULL,
    batch_uuid UUID NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    request_id VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_activity_tenant (tenant_id),
    INDEX idx_activity_subject (subject_type, subject_id),
    INDEX idx_activity_causer (causer_type, causer_id),
    INDEX idx_activity_log_name (log_name),
    INDEX idx_activity_created (created_at),
    INDEX idx_activity_event (event)
);
```

**Properties JSON Structure:**
```json
{
    "attributes": {
        "status": "completed",
        "total": 1500.00
    },
    "old": {
        "status": "pending",
        "total": 0
    },
    "custom": {
        "notes": "Approved by manager",
        "reason": "Customer request"
    }
}
```

**MongoDB Schema (Alternative):**

```json
{
    "_id": ObjectId,
    "log_name": "invoice",
    "description": "Invoice created",
    "subject": {
        "type": "App\\Models\\Invoice",
        "id": 12345
    },
    "causer": {
        "type": "App\\Models\\User",
        "id": 67890
    },
    "tenant_id": "uuid-here",
    "properties": {
        "attributes": {...},
        "old": {...}
    },
    "event": "created",
    "batch_uuid": "uuid-here",
    "context": {
        "ip_address": "192.168.1.100",
        "user_agent": "Mozilla/5.0...",
        "request_id": "req-uuid"
    },
    "created_at": ISODate("2025-11-11T10:00:00Z")
}
```

### API Endpoints

All endpoints follow `/api/v1/audit` pattern:

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/v1/audit/logs` | List activity logs (tenant-scoped) | Yes - Admin |
| GET | `/api/v1/audit/logs/{id}` | Get specific log entry details | Yes - Admin |
| GET | `/api/v1/audit/logs/subject/{type}/{id}` | Get all logs for specific subject | Yes - Admin |
| GET | `/api/v1/audit/logs/causer/{type}/{id}` | Get all logs by specific causer | Yes - Admin |
| POST | `/api/v1/audit/logs/export` | Export logs with filters | Yes - Admin |
| GET | `/api/v1/audit/logs/search` | Search logs with full-text | Yes - Admin |
| GET | `/api/v1/audit/stats` | Get audit statistics | Yes - Admin |

**Query Parameters:**

- `log_name` - Filter by log category
- `event` - Filter by event type (created, updated, deleted)
- `from_date` - Start date (ISO 8601)
- `to_date` - End date (ISO 8601)
- `subject_type` - Filter by model type
- `causer_id` - Filter by user
- `page` - Pagination page number
- `per_page` - Results per page (max 100)

**Request/Response Examples:**

**List Logs:**
```json
// GET /api/v1/audit/logs?log_name=invoice&from_date=2025-11-01&per_page=20

// Response 200 OK
{
    "data": [
        {
            "id": 12345,
            "log_name": "invoice",
            "description": "Invoice created",
            "subject": {
                "type": "Invoice",
                "id": 5678
            },
            "causer": {
                "type": "User",
                "id": 9012,
                "name": "John Doe"
            },
            "properties": {
                "attributes": {
                    "invoice_number": "INV-2025-001",
                    "total": 1500.00,
                    "status": "draft"
                }
            },
            "event": "created",
            "ip_address": "192.168.1.100",
            "created_at": "2025-11-11T10:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 150
    }
}
```

**Export Logs:**
```json
// POST /api/v1/audit/logs/export
{
    "format": "csv",
    "filters": {
        "log_name": "invoice",
        "from_date": "2025-01-01",
        "to_date": "2025-12-31"
    }
}

// Response 200 OK
{
    "data": {
        "download_url": "https://api.example.com/downloads/audit-export-uuid.csv",
        "expires_at": "2025-11-11T12:00:00Z",
        "total_records": 1500
    }
}
```

### Events

**Domain Events Emitted by this Feature Module:**

| Event Class | When Fired | Payload |
|-------------|-----------|---------|
| `ActivityLoggedEvent` | After activity log created | `ActivityLog $log` |
| `AuditExportRequestedEvent` | When audit export requested | `User $user, array $filters` |
| `AuditExportCompletedEvent` | When export generation complete | `string $downloadUrl, int $recordCount` |

**Event Usage Example:**
```php
use Azaharizaman\Erp\AuditLogging\Events\ActivityLoggedEvent;

// Automatically emitted by logging system
event(new ActivityLoggedEvent($activityLog));
```

### Event Listeners

**Events this Feature Module Listens To:**

All domain events from other modules are captured automatically through the logging facade.

---

## Implementation Plans

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN03-implement-audit-logging.md | FR-AL-001, FR-AL-004, FR-AL-005, SR-AL-001, SR-AL-002, PR-AL-001, ARCH-AL-001, ARCH-AL-002 | MILESTONE 1 | Not Started |

---

## Acceptance Criteria

- [ ] All CRUD operations automatically logged
- [ ] Before/after state capture for critical models
- [ ] Tenant isolation enforced on queries
- [ ] Asynchronous logging does not block requests
- [ ] Logs immutable (no updates/deletes allowed)
- [ ] Export functionality works (CSV, JSON)
- [ ] Search and filter capabilities functional
- [ ] Sensitive data masked in logs
- [ ] Performance overhead < 10%
- [ ] 100% test coverage

---

## Testing Strategy

### Unit Tests

```php
test('activity log captures before and after state', function () {
    $invoice = Invoice::factory()->create(['status' => 'draft']);
    
    activity()->log('Invoice updated');
    $invoice->update(['status' => 'approved']);
    
    $log = Activity::latest()->first();
    expect($log->properties['old']['status'])->toBe('draft');
    expect($log->properties['attributes']['status'])->toBe('approved');
});

test('sensitive fields are masked in logs', function () {
    $user = User::factory()->create(['password' => 'secret']);
    
    activity()->log('User created');
    
    $log = Activity::latest()->first();
    expect($log->properties['attributes']['password'])->toBe('***MASKED***');
});
```

---

## Dependencies

### Feature Module Dependencies

- **Mandatory:** SUB01 (Multi-Tenancy)

### External Package Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `spatie/laravel-activitylog` | ^4.0 | Base activity logging |
| `mongodb/laravel-mongodb` | ^4.0 | MongoDB support (optional) |

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Log Write Performance | < 10ms per log |
| Query Performance | < 500ms for 90th percentile |
| Storage Growth | < 10GB per 100k transactions |

---

## Monorepo Integration

- Development: `/packages/audit-logging/`
- Published as: `azaharizaman/erp-audit-logging`

---

## References

- Master PRD: [../PRD01-MVP.md](../PRD01-MVP.md)
- Spatie Activity Log: https://spatie.be/docs/laravel-activitylog

---

**Document Status:** Draft - Pending Review  
**Last Updated:** November 11, 2025
