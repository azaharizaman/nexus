# ERP Core Package

Core functionality for the Laravel ERP system, providing:

- **Multi-tenancy**: Complete tenant isolation at the database and application level
- **Authentication**: Laravel Sanctum-based API authentication with security features
- **Audit Logging**: Comprehensive activity logging using Spatie Activity Log
- **Authorization**: Role-based access control (RBAC) using Spatie Permission
- **Base Models**: Tenant-aware Eloquent models with common functionality
- **Middleware**: Security and tenant resolution middleware
- **Events & Listeners**: Core system events for extensibility

## Installation

```bash
composer require azaharizaman/erp-core
```

## Configuration

Publish configuration files:

```bash
php artisan vendor:publish --tag=erp-core-config
```

Run migrations:

```bash
php artisan migrate
```

## Usage

### Multi-tenancy

All models extending the base tenant-aware model will automatically scope queries to the current tenant:

```php
use Azaharizaman\Erp\Core\Models\Tenant;
use Azaharizaman\Erp\Core\Traits\BelongsToTenant;

class YourModel extends Model
{
    use BelongsToTenant;
    
    // Your model will now be automatically scoped to the current tenant
}
```

### Authentication

The package includes complete authentication infrastructure:

```php
use Azaharizaman\Erp\Core\Services\TenantManager;

$tenantManager = app(TenantManager::class);
$tenant = $tenantManager->create([
    'name' => 'Acme Corporation',
    'domain' => 'acme.example.com',
]);
```

## Testing

```bash
composer test
```

## License

MIT License. See LICENSE for details.
