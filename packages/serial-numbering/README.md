# Serial Numbering System

[![Latest Version](https://img.shields.io/packagist/v/azaharizaman/erp-serial-numbering.svg)](https://packagist.org/packages/azaharizaman/erp-serial-numbering)
[![License](https://img.shields.io/packagist/l/azaharizaman/erp-serial-numbering.svg)](LICENSE.md)

Automated serial number generation system for Laravel ERP with configurable patterns, atomic generation, and tenant isolation.

## Features

- **Configurable Patterns**: Support for variables like `{YEAR}`, `{MONTH}`, `{COUNTER}`, `{TENANT}`, etc.
- **Atomic Generation**: Row-level locking with `SELECT FOR UPDATE` prevents race conditions
- **Tenant Isolation**: Multi-tenant support with automatic tenant-scoped sequences
- **Reset Periods**: Daily, monthly, yearly, or never reset counters
- **Manual Override**: Admin capability to set specific numbers with audit logging
- **Preview Mode**: See next number without consuming the counter
- **Transaction Safe**: Full rollback support on failures
- **Event-Driven**: Dispatches events for generation, reset, and override operations

## Installation

```bash
composer require azaharizaman/erp-serial-numbering
```

Publish configuration:

```bash
php artisan vendor:publish --tag=serial-numbering-config
```

Run migrations:

```bash
php artisan migrate
```

## Usage

### Creating a Sequence

```php
use Nexus\Erp\SerialNumbering\Contracts\SequenceRepositoryContract;

$repository = app(SequenceRepositoryContract::class);

$sequence = $repository->create([
    'tenant_id' => '123',
    'sequence_name' => 'invoices',
    'pattern' => 'INV-{YEAR}-{COUNTER:5}',
    'reset_period' => 'yearly',
    'padding' => 5,
]);
```

### Generating Serial Numbers

```php
use Nexus\Erp\SerialNumbering\Actions\GenerateSerialNumberAction;

$number = GenerateSerialNumberAction::run('123', 'invoices', [
    'tenant_code' => 'ACME',
]);
// Returns: INV-2025-00001
```

### Pattern Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `{YEAR}` | 4-digit year | `2025` |
| `{YEAR:2}` | 2-digit year | `25` |
| `{MONTH}` | 2-digit month | `11` |
| `{DAY}` | 2-digit day | `12` |
| `{COUNTER}` | Auto-increment with default padding | `00001` |
| `{COUNTER:N}` | Counter with N-digit padding | `{COUNTER:8}` = `00000001` |
| `{PREFIX}` | Custom prefix from context | Configurable |
| `{TENANT}` | Tenant code from context | `ACME` |
| `{DEPARTMENT}` | Department code from context | `IT` |

### Example Patterns

```php
'INV-{YEAR}-{COUNTER:5}'                    // INV-2025-00001
'PO-{YEAR:2}{MONTH}-{COUNTER:4}'            // PO-2511-0001
'{TENANT}-RCP-{YEAR}-{MONTH}-{DAY}-{COUNTER:3}'  // ACME-RCP-2025-11-12-001
```

### API Endpoints

All endpoints require `auth:sanctum` middleware:

```
GET    /api/v1/sequences              - List all sequences
POST   /api/v1/sequences              - Create new sequence
GET    /api/v1/sequences/{name}       - Get sequence details
PATCH  /api/v1/sequences/{name}       - Update sequence
DELETE /api/v1/sequences/{name}       - Delete sequence

POST   /api/v1/sequences/{name}/generate  - Generate new number
GET    /api/v1/sequences/{name}/preview   - Preview next number
POST   /api/v1/sequences/{name}/reset     - Reset counter (admin)
POST   /api/v1/sequences/{name}/override  - Override number (super-admin)
```

### Preview Without Consuming

```php
use Nexus\Erp\SerialNumbering\Actions\PreviewSerialNumberAction;

$preview = PreviewSerialNumberAction::run('123', 'invoices');
// Returns next number without incrementing counter
```

### Resetting Sequences

```php
use Nexus\Erp\SerialNumbering\Actions\ResetSequenceAction;

ResetSequenceAction::run('123', 'invoices', 'End of year reset');
```

### Events

Listen to sequence events:

```php
Event::listen(SequenceGeneratedEvent::class, function ($event) {
    // $event->tenantId
    // $event->sequenceName
    // $event->generatedNumber
    // $event->counterValue
});

Event::listen(SequenceResetEvent::class, function ($event) {
    // Handle reset
});

Event::listen(SequenceOverriddenEvent::class, function ($event) {
    // Handle override
});
```

## Configuration

See `config/serial-numbering.php` for all options:

```php
return [
    'enabled' => true,
    'default_padding' => 5,
    'default_reset_period' => 'yearly',
    'enable_override' => false,
    'log_generations' => true,
    'cache_sequences' => true,
    'cache_ttl' => 3600,
];
```

## Testing

```bash
composer test
```

## Security

This package prevents race conditions using database row-level locking. All operations are transaction-safe with automatic rollback on failures.

## License

MIT License. See [LICENSE](LICENSE.md) for details.

## Credits

- [Azahari Zaman](https://github.com/azaharizaman)
- [All Contributors](../../contributors)
