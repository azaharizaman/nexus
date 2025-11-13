# Audit Logging Package

Comprehensive audit logging system for Laravel ERP with event-based recording, searchable audit trails, and compliance-ready immutable logs.

## Features

- ✅ **Automatic Audit Logging**: Track all CRUD operations using the `Auditable` trait
- ✅ **Async Processing**: Queue-based logging prevents performance impact
- ✅ **Multi-Tenancy Support**: Automatic tenant isolation for all logs
- ✅ **Sensitive Field Masking**: Automatically redact passwords, tokens, and secrets
- ✅ **Before/After State Tracking**: Capture complete change history for high-value entities
- ✅ **Comprehensive Search**: Filter by user, date, event type, entity, and full-text search
- ✅ **Multiple Export Formats**: Export to CSV, JSON, and PDF
- ✅ **Retention Policies**: Automated purging with configurable retention periods
- ✅ **Event-Driven Architecture**: Integration with other modules via events
- ✅ **RESTful API**: Complete API for log retrieval and management
- ✅ **Authorization**: Role-based access control with policies

## Installation

1. Add package to your `composer.json`:

```bash
composer require azaharizaman/erp-audit-logging:dev-main
```

2. Publish configuration:

```bash
php artisan vendor:publish --tag=audit-logging-config
```

3. Run migrations:

```bash
php artisan migrate
```

## Usage

### Basic Usage with Auditable Trait

Add the `Auditable` trait to any model you want to audit:

```php
use Nexus\Erp\AuditLogging\Traits\Auditable;

class Invoice extends Model
{
    use Auditable;
    
    // Optional: Customize audit logging
    protected function auditLogName(): string
    {
        return 'invoices';
    }
    
    protected function auditableEvents(): array
    {
        return ['created', 'updated', 'deleted'];
    }
    
    protected function auditShouldLogBeforeAfter(): bool
    {
        return true; // Log before/after state for auditing
    }
}
```

### System Activity Logging

For cron jobs, queue workers, or CLI commands:

```php
use Nexus\Erp\AuditLogging\Traits\LogsSystemActivity;

class ImportDataCommand extends Command
{
    use LogsSystemActivity;
    
    public function handle(): void
    {
        $batchId = $this->logBatchStart('Data Import', 1000);
        
        // ... import logic ...
        
        $this->logBatchComplete($batchId, 'Data Import', 950, 50);
    }
}
```

### API Endpoints

#### List Audit Logs

```http
GET /api/v1/audit-logs?event=updated&date_from=2025-01-01&per_page=50
Authorization: Bearer {token}
```

#### Get Specific Log

```http
GET /api/v1/audit-logs/{id}
Authorization: Bearer {token}
```

#### Export Logs

```http
POST /api/v1/audit-logs/export
Authorization: Bearer {token}
Content-Type: application/json

{
    "format": "csv",
    "event": "created",
    "date_from": "2025-01-01",
    "date_to": "2025-12-31",
    "max_records": 10000
}
```

#### Get Statistics

```http
GET /api/v1/audit-logs/statistics?date_from=2025-01-01
Authorization: Bearer {token}
```

### Artisan Commands

#### Purge Expired Logs

```bash
# Purge logs older than configured retention period (default: 7 years)
php artisan audit:purge-expired

# Dry run to see what would be purged
php artisan audit:purge-expired --dry-run

# Purge for specific tenant
php artisan audit:purge-expired --tenant=abc-123

# Override retention period
php artisan audit:purge-expired --days=365
```

### Configuration

Key configuration options in `config/audit-logging.php`:

```php
return [
    'enabled' => true,
    'queue_connection' => 'redis',
    'retention_days' => 2555, // 7 years for compliance
    'mask_sensitive_fields' => ['password', 'token', 'secret'],
    'enable_before_after' => true,
    'notify_high_value_events' => false,
    'high_value_entities' => [
        'App\\Models\\Invoice',
        'App\\Models\\Payment',
    ],
];
```

## Authorization

The package uses Laravel policies for authorization. Required permissions:

- `view-audit-logs`: View audit logs
- `export-audit-logs`: Export logs to files
- `purge-audit-logs`: Purge old logs (super-admin only)

Configure permissions in your `User` model or roles:

```php
// In RoleSeeder or PermissionSeeder
Permission::create(['name' => 'view-audit-logs']);
Permission::create(['name' => 'export-audit-logs']);

$adminRole->givePermissionTo(['view-audit-logs', 'export-audit-logs']);
```

## Events

The package dispatches these events for integration:

### ActivityLoggedEvent

Dispatched when an activity is logged:

```php
Event::listen(ActivityLoggedEvent::class, function ($event) {
    // React to logged activities
    if ($event->isHighValueEntity()) {
        // Send notification
    }
});
```

### LogRetentionExpiredEvent

Dispatched when logs are purged:

```php
Event::listen(LogRetentionExpiredEvent::class, function ($event) {
    // Notify admins about purged logs
});
```

## Compliance

This package helps meet regulatory requirements:

- **SOX (Sarbanes-Oxley)**: 7-year retention for financial records
- **GDPR**: Audit trail of data access and modifications
- **HIPAA**: Complete audit logging for healthcare data
- **GAAP**: Financial transaction audit requirements

## Performance

- **Async Logging**: All writes are queued to prevent request blocking
- **Indexed Queries**: Optimized database indexes for fast searches
- **Batch Operations**: Efficient handling of large datasets
- **Configurable Limits**: Control memory and performance impact

## Security

- **Tenant Isolation**: Users can only view logs from their tenant
- **Sensitive Field Masking**: Automatic redaction of sensitive data
- **Append-Only Storage**: No updates or deletes allowed
- **Authorization**: Role-based access control

## Testing

Run tests:

```bash
composer test
```

## License

MIT License

## Support

For issues or questions, please create an issue on GitHub.
