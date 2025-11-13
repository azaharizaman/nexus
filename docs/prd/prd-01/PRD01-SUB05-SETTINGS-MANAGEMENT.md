# PRD01-SUB05: Settings Management System

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Mandatory Feature Modules - Core Infrastructure  
**Related Sub-PRDs:** PRD01-SUB01 (Multi-Tenancy), PRD01-SUB02 (Authentication)  
**Composer Package:** `azaharizaman/erp-settings-management`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Settings Management System provides **hierarchical configuration management supporting system-level, tenant-level, and module-level settings with encryption and caching**. This mandatory feature module enables flexible, secure, and performant configuration storage for the entire ERP system with support for typed values, default fallbacks, and event-driven change notification.

### Purpose

The Settings Management System solves the critical problem of **configuration flexibility and security** across a multi-tenant ERP system. It enables:

1. **Hierarchical Configuration:** System → Tenant → Module → User level settings inheritance
2. **Type Safety:** Strongly typed settings (string, int, bool, array, encrypted)
3. **Performance:** Redis/Memcached caching for frequently accessed settings
4. **Security:** Automatic encryption for sensitive configuration values
5. **Modularity:** Each module can define its own settings with validation rules
6. **Dynamic Updates:** Settings changes emit events for cache invalidation and real-time updates

### Scope

**Included in this Feature Module:**

- ✅ Hierarchical settings with inheritance (system → tenant → module)
- ✅ Type-safe setting values (string, integer, boolean, array, json, encrypted)
- ✅ Default values and fallback logic
- ✅ Automatic encryption for sensitive settings
- ✅ High-performance caching layer
- ✅ Settings versioning and change history
- ✅ Validation rules for setting values
- ✅ Event emission on setting changes
- ✅ Bulk import/export functionality
- ✅ Settings groups and categories

**Excluded from this Feature Module:**

- ❌ UI configuration builder (separate frontend concern)
- ❌ A/B testing framework (future enhancement)
- ❌ Feature flags system (future enhancement)

### Dependencies

**Mandatory Dependencies:**
- Laravel Framework v12.x
- PHP ≥ 8.2
- Redis or Memcached for caching
- PRD01-SUB01 (Multi-Tenancy System)

**Feature Module Dependencies:**
- **Mandatory:** SUB01 (Multi-Tenancy)
- **Optional:** SUB02 (Authentication) for user-level settings

### Composer Package Information

- **Package Name:** `azaharizaman/erp-settings-management`
- **Namespace:** `Nexus\Erp\SettingsManagement`
- **Monorepo Location:** `/packages/settings-management/`
- **Installation:** `composer require azaharizaman/erp-settings-management` (post v1.0 release)

---## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB05 (Settings Management). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-SM-001** | Support **Hierarchical Structure** (system-level, tenant-level, module-level, user-level) with inheritance | High | Planned |
| **FR-SM-002** | Provide **settings caching layer** with automatic invalidation on updates | High | Planned |
| **FR-SM-003** | Support **type-safe settings** with validation (string, int, bool, array, JSON) | High | Planned |
| **FR-SM-004** | Provide **Default Values** when setting not defined at tenant/module level | High | Planned |
| **FR-SM-005** | Support **encrypted settings** for sensitive data (API keys, credentials) | High | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-SM-001** | Settings MUST follow inheritance: **user → module → tenant → system** | Planned |
| **BR-SM-002** | Encrypted settings MUST be stored with **Laravel encryption** | Planned |
| **BR-SM-003** | Setting keys MUST be **namespaced: `module.category.key`** | Planned |
| **BR-SM-004** | Tenant settings MUST **NOT access other tenants' settings** | Planned |
| **BR-SM-005** | System settings can only be **modified by super admins** | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-SM-001** | Settings MUST support types: **string, integer, boolean, array, json, encrypted** | Planned |
| **DR-SM-002** | Settings MUST store: **key, value, type, scope (system/tenant/module), metadata** | Planned |
| **DR-SM-003** | Settings MUST include **validation rules in metadata** | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-SM-001** | Provide settings API for **all modules** to store/retrieve configuration | Planned |
| **IR-SM-002** | Integrate with **SUB01 (Multi-Tenancy)** for tenant-scoped settings | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-SM-001** | Frequently accessed settings MUST be **cached with TTL** to avoid database queries | Planned |
| **PR-SM-002** | Setting retrieval MUST complete in **< 10ms for cached values** | Planned |
| **PR-SM-003** | Cache invalidation MUST occur within **1 second of setting update** | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-SM-001** | **Encrypt sensitive values** (API keys, passwords, tokens) using AES-256 Laravel encryption | Planned |
| **SR-SM-002** | **Tenant isolation** MUST prevent cross-tenant setting access | Planned |
| **SR-SM-003** | Require **admin permission** for modifying system-level settings | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-SM-001** | Support **10,000+ settings** per tenant with efficient caching | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-SM-001** | Use **Redis/Memcached** for high-performance caching layer | Planned |
| **ARCH-SM-002** | Support **lazy loading** of settings to minimize memory footprint | Planned |
| **ARCH-SM-003** | Implement **observer pattern** for real-time setting updates | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-SM-001** | `SettingUpdatedEvent` | When setting value is changed | Planned |
| **EV-SM-002** | `SettingCreatedEvent` | When new setting is created | Planned |
| **EV-SM-003** | `CacheInvalidatedEvent` | When setting cache needs refresh | Planned |

---

## Technical Specifications

### Database Schema

**Settings Table:**

```sql
CREATE TABLE settings (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NULL, -- NULL for system-level settings
    module_name VARCHAR(100) NULL, -- NULL for tenant-level settings
    user_id BIGINT NULL, -- NULL for non-user settings
    key VARCHAR(255) NOT NULL,
    value TEXT NULL,
    type VARCHAR(50) NOT NULL, -- 'string', 'integer', 'boolean', 'array', 'json', 'encrypted'
    default_value TEXT NULL,
    is_encrypted BOOLEAN NOT NULL DEFAULT FALSE,
    metadata JSONB NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, module_name, user_id, key),
    INDEX idx_settings_tenant (tenant_id),
    INDEX idx_settings_module (module_name),
    INDEX idx_settings_key (key),
    INDEX idx_settings_scope (tenant_id, module_name, user_id)
);
```

**Settings Change History Table:**

```sql
CREATE TABLE settings_history (
    id BIGSERIAL PRIMARY KEY,
    setting_id BIGINT NOT NULL REFERENCES settings(id),
    old_value TEXT NULL,
    new_value TEXT NULL,
    changed_by_type VARCHAR(255) NULL,
    changed_by_id BIGINT NULL,
    changed_at TIMESTAMP NOT NULL,
    
    INDEX idx_settings_history_setting (setting_id),
    INDEX idx_settings_history_changed (changed_at)
);
```

**Metadata JSON Structure:**
```json
{
    "category": "email",
    "label": "SMTP Host",
    "description": "Mail server hostname",
    "validation": {
        "required": true,
        "type": "string",
        "max": 255
    },
    "ui": {
        "input_type": "text",
        "order": 1,
        "group": "mail_settings"
    }
}
```

### API Endpoints

All endpoints follow `/api/v1/settings` pattern:

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/v1/settings` | List all settings for current tenant/module | Yes |
| GET | `/api/v1/settings/{key}` | Get specific setting value | Yes |
| PUT | `/api/v1/settings/{key}` | Update setting value | Yes - Admin |
| DELETE | `/api/v1/settings/{key}` | Delete setting (reset to default) | Yes - Admin |
| POST | `/api/v1/settings/bulk` | Bulk update settings | Yes - Admin |
| GET | `/api/v1/settings/module/{module}` | Get all settings for specific module | Yes |
| GET | `/api/v1/settings/system` | Get system-level settings | Yes - Super Admin |
| PUT | `/api/v1/settings/system/{key}` | Update system setting | Yes - Super Admin |
| POST | `/api/v1/settings/export` | Export settings as JSON | Yes - Admin |
| POST | `/api/v1/settings/import` | Import settings from JSON | Yes - Admin |
| GET | `/api/v1/settings/{key}/history` | Get change history | Yes - Admin |

**Query Parameters:**

- `module` - Filter by module name
- `category` - Filter by category
- `scope` - Filter by scope (system, tenant, module, user)
- `encrypted_only` - Show only encrypted settings

**Request/Response Examples:**

**Get Setting:**
```json
// GET /api/v1/settings/mail.smtp.host

// Response 200 OK
{
    "data": {
        "key": "mail.smtp.host",
        "value": "smtp.gmail.com",
        "type": "string",
        "scope": "tenant",
        "is_encrypted": false,
        "default_value": "localhost",
        "metadata": {
            "category": "email",
            "label": "SMTP Host"
        },
        "updated_at": "2025-11-11T10:00:00Z"
    }
}
```

**Update Setting:**
```json
// PUT /api/v1/settings/mail.smtp.host
{
    "value": "smtp.mailtrap.io",
    "is_encrypted": false
}

// Response 200 OK
{
    "data": {
        "key": "mail.smtp.host",
        "value": "smtp.mailtrap.io",
        "updated_at": "2025-11-11T10:05:00Z"
    }
}
```

**Bulk Update:**
```json
// POST /api/v1/settings/bulk
{
    "settings": [
        {"key": "mail.smtp.host", "value": "smtp.mailtrap.io"},
        {"key": "mail.smtp.port", "value": "587"},
        {"key": "mail.smtp.encryption", "value": "tls"}
    ]
}

// Response 200 OK
{
    "data": {
        "updated": 3,
        "failed": 0
    }
}
```

### Service API

**Facade Usage:**
```php
use Nexus\Erp\SettingsManagement\Facades\Settings;

// Get setting with default fallback
$smtpHost = Settings::get('mail.smtp.host', 'localhost');

// Get typed value
$port = Settings::getInt('mail.smtp.port', 587);
$isEnabled = Settings::getBool('features.notifications.enabled', true);
$config = Settings::getArray('invoice.default_config', []);

// Get with scope resolution (user → module → tenant → system)
$value = Settings::get('editor.theme'); // Checks all scopes

// Set setting
Settings::set('mail.smtp.host', 'smtp.mailtrap.io');

// Set with encryption
Settings::setEncrypted('mail.smtp.password', 'secret123');

// Delete setting (reset to default)
Settings::forget('mail.smtp.host');

// Check if setting exists
if (Settings::has('custom.feature.enabled')) {
    // ...
}

// Get all settings for module
$mailSettings = Settings::module('mail')->all();

// Get all settings for category
$emailSettings = Settings::category('email')->all();
```

### Events

**Domain Events Emitted by this Feature Module:**

| Event Class | When Fired | Payload |
|-------------|-----------|---------|
| `SettingCreatedEvent` | After new setting created | `Setting $setting` |
| `SettingUpdatedEvent` | After setting value updated | `Setting $setting, $oldValue, $newValue` |
| `SettingDeletedEvent` | After setting deleted | `Setting $setting` |
| `SettingsBulkUpdatedEvent` | After bulk update operation | `array $updatedKeys` |

**Event Usage Example:**
```php
use Nexus\Erp\SettingsManagement\Events\SettingUpdatedEvent;

// Automatically emitted when setting changes
event(new SettingUpdatedEvent($setting, 'old-value', 'new-value'));
```

**Event Listeners (Cache Invalidation):**
```php
class InvalidateSettingCacheListener
{
    public function handle(SettingUpdatedEvent $event): void
    {
        Cache::forget("setting.{$event->setting->key}");
        Cache::tags(['settings'])->flush();
    }
}
```

---

## Implementation Plans

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN05-implement-settings-management.md | FR-SM-001, FR-SM-004, SR-SM-001, PR-SM-001, EV-SM-001, ARCH-SM-001 | MILESTONE 1 | Not Started |

---

## Acceptance Criteria

- [ ] Hierarchical settings with inheritance working
- [ ] Type-safe getters (getString, getInt, getBool, getArray)
- [ ] Default values applied correctly
- [ ] Encryption for sensitive settings functional
- [ ] Redis caching layer operational
- [ ] Cache invalidation on setting changes
- [ ] Events emitted on CRUD operations
- [ ] Validation rules enforced
- [ ] Tenant isolation enforced
- [ ] Import/export functionality working
- [ ] Change history tracking operational
- [ ] 100% test coverage

---

## Testing Strategy

### Unit Tests

```php
test('retrieves setting with default fallback', function () {
    $value = Settings::get('non.existent.key', 'default-value');
    
    expect($value)->toBe('default-value');
});

test('inherits settings from parent scope', function () {
    // System-level setting
    Setting::create(['key' => 'app.timezone', 'value' => 'UTC', 'tenant_id' => null]);
    
    // No tenant-level override
    $value = Settings::tenant($tenant)->get('app.timezone');
    
    expect($value)->toBe('UTC');
});

test('encrypts sensitive settings', function () {
    Settings::setEncrypted('api.secret_key', 'secret123');
    
    $setting = Setting::where('key', 'api.secret_key')->first();
    expect($setting->is_encrypted)->toBeTrue();
    expect($setting->value)->not->toBe('secret123'); // Encrypted
    
    $decrypted = Settings::get('api.secret_key');
    expect($decrypted)->toBe('secret123');
});
```

### Feature Tests

```php
test('caches frequently accessed settings', function () {
    Setting::create(['key' => 'app.name', 'value' => 'ERP System']);
    
    // First call hits database
    $value1 = Settings::get('app.name');
    
    // Second call hits cache
    DB::enableQueryLog();
    $value2 = Settings::get('app.name');
    
    expect(DB::getQueryLog())->toBeEmpty(); // No query = cached
});

test('invalidates cache on setting update', function () {
    $setting = Setting::create(['key' => 'app.name', 'value' => 'Old Name']);
    
    // Cache the value
    Settings::get('app.name');
    
    // Update setting
    Settings::set('app.name', 'New Name');
    
    // Cache should be invalidated
    $value = Settings::get('app.name');
    expect($value)->toBe('New Name');
});
```

---

## Dependencies

### Feature Module Dependencies

- **Mandatory:** SUB01 (Multi-Tenancy)
- **Optional:** SUB02 (Authentication)

### External Package Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `illuminate/cache` | ^12.0 | Redis/Memcached support |
| `illuminate/encryption` | ^12.0 | Value encryption |

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Cached Setting Retrieval | < 10ms |
| Uncached Setting Retrieval | < 50ms |
| Cache Hit Rate | > 90% |
| Cache Invalidation Time | < 1s |

---

## Monorepo Integration

- Development: `/packages/settings-management/`
- Published as: `azaharizaman/erp-settings-management`

---

## References

- Master PRD: [../PRD01-MVP.md](../PRD01-MVP.md)
- Laravel Cache: https://laravel.com/docs/cache
- Laravel Encryption: https://laravel.com/docs/encryption

---

**Document Status:** Draft - Pending Review  
**Last Updated:** November 11, 2025
