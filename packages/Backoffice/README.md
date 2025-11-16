# Nexus\Backoffice

[![Latest Version](https://img.shields.io/packagist/v/nexus/backoffice.svg?style=flat-square)](https://packagist.org/packages/nexus/backoffice)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

A **framework-agnostic** PHP package for managing hierarchical organizational structures including companies, offices, departments, staff, and units.

## Architecture

This package follows the **Nexus Monorepo Architecture** principles:

- ✅ **Framework-Agnostic**: Pure PHP business logic with no Laravel dependencies
- ✅ **Contract-Driven**: All data structures and persistence needs defined via interfaces
- ✅ **Service Layer**: Business logic encapsulated in reusable services
- ✅ **Zero Database Logic**: No migrations, models, or ORM code
- ✅ **Publishable**: Self-contained unit ready for Packagist

## What's Included

### Contracts (Interfaces)

**Data Structure Interfaces:**
- `CompanyInterface`, `OfficeInterface`, `DepartmentInterface`
- `StaffInterface`, `UnitInterface`, `UnitGroupInterface`
- `PositionInterface`, `OfficeTypeInterface`, `StaffTransferInterface`

**Repository Interfaces:**
- Complete CRUD interfaces for all entities
- Hierarchy navigation methods
- Specialized query methods

### Services

- `CompanyManager` - Company management with hierarchy validation
- `StaffTransferManager` - Staff transfer workflow management

### Enums & Exceptions

- Domain-specific enums for statuses and types
- Comprehensive exception hierarchy

## Installation

```bash
composer require nexus/backoffice
```

## Usage

### 1. Implement the Contracts

```php
use Nexus\Backoffice\Contracts\CompanyInterface;

class Company extends Model implements CompanyInterface
{
    // Implement interface methods
}
```

### 2. Create Repository Implementations

```php
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;

class CompanyRepository implements CompanyRepositoryInterface
{
    // Implement repository methods
}
```

### 3. Use Services

```php
use Nexus\Backoffice\Services\CompanyManager;

$manager = new CompanyManager($companyRepository);
$company = $manager->createCompany($data);
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
