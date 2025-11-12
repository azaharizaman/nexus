# Settings Management Package

Hierarchical Settings Management System for Laravel ERP with multi-level scopes, encryption, and high-performance caching.

## Features

✅ **Hierarchical Settings** - Support for system, tenant, module, and user-level settings with automatic inheritance  
✅ **Type Safety** - Strongly-typed values (string, integer, boolean, array, json, encrypted)  
✅ **Multi-Tenant** - Complete tenant isolation with automatic tenant context injection  
✅ **High-Performance Caching** - Redis/Memcached caching with automatic invalidation  
✅ **Encryption** - AES-256 encryption for sensitive settings (API keys, passwords)  
✅ **RESTful API** - Complete CRUD operations with bulk update and import/export  
✅ **Event-Driven** - Settings changes dispatch events for other modules to react  
✅ **Search** - Laravel Scout integration for searchable settings  
✅ **Audit Trail** - Complete change history with user attribution  

## Installation

1. Add the package to your composer.json:

```bash
composer require azaharizaman/erp-settings-management:dev-main
```

2. Publish configuration:

```bash
php artisan vendor:publish --tag=settings-management-config
```

3. Run migrations:

```bash
php artisan migrate
```

4. (Optional) Seed default system settings:

```bash
php artisan db:seed --class="Azaharizaman\\Erp\\SettingsManagement\\Database\\Seeders\\DefaultSettingsSeeder"
```

## Usage

### Using the Facade

```php
use Azaharizaman\Erp\SettingsManagement\Facades\Settings;

// Get a setting (with hierarchical resolution)
$value = Settings::get('email.smtp.host');
$value = Settings::get('email.smtp.host', 'smtp.gmail.com'); // With default

// Set a setting
Settings::set('email.smtp.host', 'smtp.gmail.com', 'string', 'tenant');

// Check if setting exists
if (Settings::has('email.smtp.host')) {
    // ...
}

// Delete a setting
Settings::forget('email.smtp.host', 'tenant');

// Get all settings for a scope
$allSettings = Settings::all('tenant');

// Set multiple settings at once
Settings::setMany([
    'app.name' => 'My ERP',
    'app.timezone' => 'Asia/Kuala_Lumpur',
], 'string', 'tenant');
```

### Using Dependency Injection

```php
use Azaharizaman\Erp\SettingsManagement\Contracts\SettingsServiceContract;

class MyService
{
    public function __construct(
        private readonly SettingsServiceContract $settings
    ) {}
    
    public function example(): void
    {
        $value = $this->settings->get('my.setting');
    }
}
```

### Hierarchical Resolution

Settings are resolved in this order: **user → module → tenant → system**

```php
// This will check:
// 1. User-level setting (if user context available)
// 2. Module-level setting (if module context provided)
// 3. Tenant-level setting (if tenant context available)
// 4. System-level setting
// 5. Default value from metadata
// 6. Provided default value
$value = Settings::get('pagination.per_page', 15);
```

### Encrypted Settings

```php
// Store encrypted value
Settings::set('stripe.api_key', 'sk_test_...', 'encrypted', 'tenant');

// Retrieve (automatically decrypted)
$apiKey = Settings::get('stripe.api_key');

// Note: Encrypted values are masked in API responses unless user has 'view-encrypted-settings' permission
```

### Scope-Specific Settings

```php
// System-level (requires super-admin)
Settings::set('app.maintenance_mode', true, 'boolean', 'system');

// Tenant-level
Settings::set('company.name', 'ACME Corp', 'string', 'tenant');

// Module-level
Settings::set('inventory.low_stock_threshold', 10, 'integer', 'module', [], null, 'inventory');

// User-level
Settings::set('ui.dark_mode', true, 'boolean', 'user', [], null, null, auth()->id());
```

## API Endpoints

All endpoints are prefixed with `/api/v1/settings` and require authentication.

### List Settings
```http
GET /api/v1/settings?scope=tenant&module_name=inventory
```

### Get Specific Setting
```http
GET /api/v1/settings/{key}?scope=tenant
```

### Create Setting
```http
POST /api/v1/settings
Content-Type: application/json

{
  "key": "email.smtp.host",
  "value": "smtp.gmail.com",
  "type": "string",
  "scope": "tenant",
  "metadata": {
    "label": "SMTP Host",
    "description": "SMTP server hostname"
  }
}
```

### Update Setting
```http
PATCH /api/v1/settings/{key}
Content-Type: application/json

{
  "value": "smtp.office365.com",
  "type": "string"
}
```

### Delete Setting
```http
DELETE /api/v1/settings/{key}?scope=tenant
```

### Bulk Update
```http
POST /api/v1/settings/bulk
Content-Type: application/json

{
  "scope": "tenant",
  "settings": [
    {
      "key": "email.from.address",
      "value": "noreply@example.com",
      "type": "string"
    },
    {
      "key": "email.from.name",
      "value": "My Company",
      "type": "string"
    }
  ]
}
```

### Export Settings
```http
GET /api/v1/settings/export?scope=tenant&format=json
GET /api/v1/settings/export?scope=tenant&format=csv
```

### Import Settings
```http
POST /api/v1/settings/import
Content-Type: multipart/form-data

file: settings.json
scope: tenant
overwrite: true
```

## Artisan Commands

### Warm Cache
Pre-load settings into cache for improved performance:

```bash
# Warm all settings
php artisan erp:settings:warm-cache

# Warm specific scope
php artisan erp:settings:warm-cache --scope=tenant

# Warm specific tenant
php artisan erp:settings:warm-cache --tenant=1
```

## Events

The package dispatches the following events:

- `SettingCreatedEvent` - When a new setting is created
- `SettingUpdatedEvent` - When a setting value is changed
- `CacheInvalidatedEvent` - When setting cache is invalidated

### Listening to Events

```php
use Azaharizaman\Erp\SettingsManagement\Events\SettingUpdatedEvent;

Event::listen(SettingUpdatedEvent::class, function ($event) {
    // React to setting changes
    logger()->info("Setting {$event->key} updated", [
        'old' => $event->oldValue,
        'new' => $event->newValue,
        'scope' => $event->scope,
    ]);
});
```

## Configuration

The package configuration is located at `config/settings-management.php`. Key options:

```php
return [
    // Enable/disable caching
    'cache' => [
        'enabled' => env('SETTINGS_CACHE_ENABLED', true),
        'ttl' => env('SETTINGS_CACHE_TTL', 3600),
    ],
    
    // Encryption settings
    'encryption' => [
        'enabled' => env('SETTINGS_ENCRYPTION_ENABLED', true),
    ],
    
    // Hierarchical resolution order
    'scope_hierarchy' => ['user', 'module', 'tenant', 'system'],
    
    // Supported types
    'supported_types' => [
        'string', 'integer', 'boolean', 'array', 'json', 'encrypted',
    ],
];
```

## Authorization

### Policies

The package includes a `SettingPolicy` with scope-based authorization:

- **System settings**: Only super-admins can manage
- **Tenant settings**: Admins of the tenant can manage
- **Module settings**: Admins of the tenant can manage
- **User settings**: Users can manage their own settings

### Gates

- `export-settings` - Export settings (admin or super-admin)
- `import-settings` - Import settings (admin or super-admin)
- `view-encrypted-settings` - View decrypted values (admin or super-admin)
- `manage-system-settings` - Manage system-level settings (super-admin only)

## Performance

### Caching Strategy

The package uses a cache-aside pattern:

1. Check cache first (Redis/Memcached)
2. If miss, query database
3. Cache the result with TTL
4. Invalidate cache on updates

### Cache Warming

For best performance, warm the cache during deployment:

```bash
php artisan erp:settings:warm-cache
```

### Expected Performance

- Cached reads: < 1ms
- Uncached reads: < 10ms
- Writes: < 50ms (including cache invalidation)

## Testing

Run tests with Pest:

```bash
./vendor/bin/pest packages/settings-management/tests
```

## Security

- **Encryption**: Sensitive values are encrypted using Laravel's encryption (AES-256-CBC)
- **Tenant Isolation**: Settings are strictly isolated by tenant_id
- **Authorization**: All operations check user permissions via policies
- **Audit Trail**: All changes are recorded in `settings_history` table

## License

MIT License. See LICENSE file for details.

## Support

For issues and questions, please open an issue on GitHub.
