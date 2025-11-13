# Nexus Contracts

Core contract interfaces for the Nexus ERP system.

## Overview

This package provides the foundational interface definitions (contracts) that all other Nexus packages depend on. By defining contracts in a separate package, we achieve:

- **Loose Coupling**: Packages depend on interfaces, not concrete implementations
- **Testability**: Easy to mock and test components
- **Flexibility**: Implementations can be swapped without changing dependent code
- **Clear API**: Contracts define the public API surface

## Contracts Included

### Repository Contracts

- `RepositoryContract` - Base repository interface
- `TenantRepositoryContract` - Tenant data access
- `UserRepositoryContract` - User data access
- `UomRepositoryContract` - Unit of Measure data access

### Service Contracts

- `TenantManagerContract` - Tenant management service
- `ActivityLoggerContract` - Activity logging service
- `SearchServiceContract` - Search functionality
- `TokenServiceContract` - API token management

## Installation

This package is installed automatically as a dependency of other Nexus packages.

```bash
composer require nexus/contracts
```

## Usage

Other packages implement these contracts:

```php
use Nexus\Contracts\Contracts\TenantRepositoryContract;

class TenantRepository implements TenantRepositoryContract
{
    public function findById(int|string $id): ?Model
    {
        // Implementation
    }
    
    // ... other methods
}
```

Then bind the implementation in a service provider:

```php
$this->app->bind(TenantRepositoryContract::class, TenantRepository::class);
```

## Architecture

This follows the **Dependency Inversion Principle** - high-level modules (business logic) depend on abstractions (contracts), not on low-level modules (implementations).

```
Business Logic → Contracts ← Implementations
```

## License

Proprietary
