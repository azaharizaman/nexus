# Quick Start Guide

Get started with the Serial Numbering System in 5 minutes.

## Installation

### 1. Install Package

Add to your `composer.json`:

```json
{
    "require": {
        "azaharizaman/erp-serial-numbering": "dev-main"
    },
    "repositories": [
        {
            "type": "path",
            "url": "./packages/serial-numbering"
        }
    ]
}
```

Then run:

```bash
composer require azaharizaman/erp-serial-numbering
```

### 2. Run Migrations

```bash
php artisan migrate
```

### 3. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=serial-numbering-config
```

### 4. Seed Default Sequences (Optional)

Update tenant_id in `DefaultSequenceSeeder.php`, then:

```bash
php artisan db:seed --class=Nexus\\Erp\\SerialNumbering\\Database\\Seeders\\DefaultSequenceSeeder
```

## Basic Usage

### Create a Sequence

```php
use Nexus\Erp\SerialNumbering\Contracts\SequenceRepositoryContract;

$repository = app(SequenceRepositoryContract::class);

$sequence = $repository->create([
    'tenant_id' => '1',
    'sequence_name' => 'invoices',
    'pattern' => 'INV-{YEAR}-{COUNTER:5}',
    'reset_period' => 'yearly',
    'padding' => 5,
]);
```

### Generate Numbers

```php
use Nexus\Erp\SerialNumbering\Actions\GenerateSerialNumberAction;

// Simple generation
$number = GenerateSerialNumberAction::run('1', 'invoices');
// Returns: INV-2025-00001

// With context variables
$number = GenerateSerialNumberAction::run('1', 'invoices', [
    'tenant_code' => 'ACME',
    'department_code' => 'SALES',
]);
```

### Preview Next Number

```php
use Nexus\Erp\SerialNumbering\Actions\PreviewSerialNumberAction;

$preview = PreviewSerialNumberAction::run('1', 'invoices');
// Returns next number WITHOUT incrementing counter
```

## API Usage

All endpoints require authentication with `auth:sanctum`.

### List Sequences

```bash
GET /api/v1/sequences
Authorization: Bearer {token}
```

### Create Sequence

```bash
POST /api/v1/sequences
Authorization: Bearer {token}
Content-Type: application/json

{
    "sequence_name": "invoices",
    "pattern": "INV-{YEAR}-{COUNTER:5}",
    "reset_period": "yearly",
    "padding": 5
}
```

### Generate Number

```bash
POST /api/v1/sequences/invoices/generate
Authorization: Bearer {token}
Content-Type: application/json

{
    "context": {
        "tenant_code": "ACME",
        "department_code": "SALES"
    }
}

Response:
{
    "data": {
        "generated_number": "INV-2025-00001",
        "sequence_name": "invoices"
    }
}
```

### Preview Number

```bash
GET /api/v1/sequences/invoices/preview
Authorization: Bearer {token}

Response:
{
    "data": {
        "preview_number": "INV-2025-00002",
        "sequence_name": "invoices"
    }
}
```

## Common Patterns

### Invoice Numbering

```php
'pattern' => 'INV-{YEAR}-{COUNTER:5}'
// Output: INV-2025-00001, INV-2025-00002, ...
```

### Purchase Orders

```php
'pattern' => 'PO-{YEAR:2}{MONTH}-{COUNTER:4}'
// Output: PO-2511-0001, PO-2511-0002, ...
```

### Tenant-Specific

```php
'pattern' => '{TENANT}-INV-{COUNTER:6}'
// Output: ACME-INV-000001, ACME-INV-000002, ...
```

### Daily Reset

```php
'pattern' => 'DO-{YEAR:2}{MONTH}{DAY}-{COUNTER:3}',
'reset_period' => 'daily'
// Output: DO-251112-001, DO-251112-002, ...
// Next day: DO-251113-001, DO-251113-002, ...
```

## Permissions

Set up these permissions in your user/role system:

- `manage-sequences` - Create, update, delete sequences
- `reset-sequence` - Reset sequence counters (admin)
- `override-sequence-number` - Manually override numbers (super-admin)

## Events

Listen to sequence events in your `EventServiceProvider`:

```php
use Nexus\Erp\SerialNumbering\Events\SequenceGeneratedEvent;

Event::listen(SequenceGeneratedEvent::class, function ($event) {
    Log::info('Serial number generated', [
        'tenant' => $event->tenantId,
        'sequence' => $event->sequenceName,
        'number' => $event->generatedNumber,
    ]);
});
```

## Troubleshooting

### Numbers Not Generating

Check:
1. Sequence exists for tenant
2. Pattern is valid
3. User has permission
4. Tenant context is set

### Race Conditions

The package uses `SELECT FOR UPDATE` to prevent race conditions. If you see deadlocks:
1. Check database transaction isolation level
2. Ensure migrations have proper indexes
3. Consider increasing database timeout

### Pattern Not Working

Validate pattern:

```php
use Nexus\Erp\SerialNumbering\Services\PatternParserService;

$parser = app(PatternParserService::class);
$isValid = $parser->validate('YOUR-PATTERN-HERE');
```

## Next Steps

- Read the [README.md](README.md) for detailed documentation
- Check [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) for architecture details
- Review test files for more usage examples
- Configure reset periods for your sequences
- Set up event listeners for audit logging

## Support

For issues or questions:
- Check the documentation in `/docs/prd/PRD01-SUB04-SERIAL-NUMBERING.md`
- Review implementation plan in `/docs/plan/PRD01-SUB04-PLAN01-implement-serial-numbering.md`
- Open an issue on GitHub
