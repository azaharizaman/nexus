# Laravel ERP System - Monorepo

![Status: In Development](https://img.shields.io/badge/status-In%20Development-yellow)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue)
![Laravel](https://img.shields.io/badge/Laravel-12+-red)
![License](https://img.shields.io/badge/license-MIT-green)

## 🎥 Project Explainer Video

[![Laravel ERP System Explainer](https://img.youtube.com/vi/X9IVHM1Mpl4/maxresdefault.jpg)](https://youtu.be/X9IVHM1Mpl4)

**Watch the video above** to get a comprehensive overview of the Laravel ERP System architecture, features, and capabilities.

---

**Enterprise-grade, headless ERP backend system** built with Laravel 12+ and PHP 8.2+. Designed to rival SAP, Odoo, and Microsoft Dynamics while maintaining superior modularity, extensibility, and agentic capabilities.

This is a **monorepo** containing:
- 📦 **Modular packages** in `packages/` directory
- 🚀 **Main application** in `apps/headless-erp-app/` directory

---

## 🎯 Overview

This is a **headless, API-first ERP system** providing comprehensive business management capabilities through RESTful APIs and CLI commands. No UI components, no views, no frontend assets - just a robust, scalable backend ready to integrate with any frontend or automated system.

### Key Characteristics

- 🏗️ **Architecture:** Monorepo with modular packages
- 🏗️ **Design:** Headless backend-only system (API + CLI)
- 🔌 **Integration:** RESTful APIs (`/api/v1/`) and Artisan commands (`erp:`)
- 🎨 **Patterns:** Contract-driven, Domain-driven, Event-driven
- 🤖 **Target Users:** AI agents, custom frontends, automated systems
- 🧩 **Modularity:** Enable/disable modules without system-wide impact
- 🔒 **Security:** Zero-trust model for critical operations

---

## � Repository Structure

```
laravel-erp/
├── apps/
│   └── headless-erp-app/        # Main Laravel application
│       ├── app/
│       ├── bootstrap/
│       ├── config/
│       ├── database/
│       ├── public/
│       ├── resources/
│       ├── routes/
│       ├── storage/
│       ├── tests/
│       └── composer.json
├── packages/
│   └── core/                     # Core ERP package
│       ├── src/                  # Source code (Nexus\Erp\Core namespace)
│       ├── tests/                # Package tests
│       ├── composer.json         # Package dependencies
│       └── README.md             # Package documentation
├── docs/                         # Documentation
├── composer.json                 # Monorepo root composer.json
└── README.md                     # This file
```

---

## �📋 Table of Contents

- [Features](#-features)
- [Technology Stack](#-technology-stack)
- [Architecture](#-architecture)
- [Getting Started](#-getting-started)
- [Development](#-development)
- [Documentation](#-documentation)
- [Testing](#-testing)
- [Contributing](#-contributing)
- [License](#-license)

---

## ✨ Features

### Core Infrastructure (packages/core)
- ✅ **Multi-Tenancy System** - Complete tenant isolation with automatic scoping
- ✅ **Authentication & Authorization** - Sanctum API tokens + Spatie Permission
- ✅ **Audit Logging** - Complete activity tracking with Spatie Activitylog
- ✅ **Serial Numbering** - Configurable document numbering system
- ✅ **Settings Management** - Tenant-scoped configuration system

### Backoffice Management
- ✅ **Company Management** - Multi-company support with laravel-backoffice package
- ✅ **Office Management** - Office hierarchy with location tracking
- ✅ **Department Management** - Department structure with cost centers
- ✅ **Staff Management** - Employee records with organizational hierarchy

### Inventory Management
- 🚧 **Item Master** - Product/material master data (Planned)
- 🚧 **Warehouse Management** - Multi-warehouse support (Planned)
- 🚧 **Stock Management** - Real-time inventory tracking (Planned)
- 🚧 **UOM Management** - Unit of measure conversions (Planned)

### Sales Management
- 🚧 **Customer Management** - Customer master data (Planned)
- 🚧 **Sales Quotation** - Quote generation and tracking (Planned)
- 🚧 **Sales Order** - Order processing (Planned)
- 🚧 **Pricing Management** - Dynamic pricing rules (Planned)

### Purchasing Management
- 🚧 **Vendor Management** - Supplier master data (Planned)
- 🚧 **Purchase Requisition** - Internal purchase requests (Planned)
- 🚧 **Purchase Order** - PO creation and tracking (Planned)
- 🚧 **Goods Receipt** - Receiving and QC (Planned)

### Accounting (Future)
- 📅 General Ledger
- 📅 Accounts Payable/Receivable
- 📅 Financial Reporting

---

## 🛠️ Technology Stack

### Core Framework
- **PHP:** ≥ 8.2 (Using latest features: readonly, enums, union types)
- **Laravel:** ≥ 12.x (Streamlined directory structure)
- **Database:** Agnostic (MySQL, PostgreSQL, SQLite, SQL Server)

### Key Packages

#### Business Packages (Internal)
```json
{
  "azaharizaman/laravel-uom-management": "dev-main",
  "azaharizaman/laravel-inventory-management": "dev-main",
  "azaharizaman/laravel-backoffice": "dev-main",
  "azaharizaman/laravel-serial-numbering": "dev-main"
}
```

#### Development Tools (Mandatory)
```json
{
  "laravel/scout": "^10.0",          // MANDATORY: Search on all models
  "laravel/pulse": "^1.0",           // Performance monitoring
  "pestphp/pest": "^4.0",            // MANDATORY: Testing framework
  "laravel/pint": "^1.0"             // MANDATORY: Code style
}
```

#### Architecture Support
```json
{
  "lorisleiva/laravel-actions": "^2.0",     // Action pattern
  "spatie/laravel-permission": "^6.0",      // Authorization
  "spatie/laravel-model-status": "^2.0",    // Status management
  "spatie/laravel-activitylog": "^4.0",     // Audit logging
  "brick/math": "^0.12"                      // Decimal precision
}
```

---

## 🏛️ Architecture

### Monorepo Structure

This project follows a **monorepo architecture** as specified in [PRD01-MVP.md](docs/prd/PRD01-MVP.md):

- **`apps/headless-erp-app/`** - Main Laravel application
  - Minimal Laravel setup that requires packages from `packages/`
  - Contains HTTP layer, configuration, and application-specific logic
  
- **`packages/core/`** - Core ERP functionality package
  - Namespace: `Nexus\Erp\Core`
  - Multi-tenancy, authentication, audit logging
  - Independent, publishable package

Future packages will be added to `packages/` directory (accounting, inventory, sales, etc.)

### Design Patterns

1. **Contract-Driven Development** - All dependencies abstracted behind interfaces
2. **Domain-Driven Design** - Strict domain boundaries with clear responsibilities
3. **Event-Driven Architecture** - Cross-domain communication via events
4. **Repository Pattern** - Data access abstraction layer
5. **Action Pattern** - Business operations using Laravel Actions
6. **Package Decoupling** - External packages wrapped behind contracts

### Package Structure

Each package in `packages/` follows this structure:
```
{package-name}/
├── src/                 # Source code
│   ├── Actions/         # Business operations
│   ├── Contracts/       # Interfaces
│   ├── Events/          # Domain events
│   ├── Listeners/       # Event handlers
│   ├── Models/          # Eloquent models
│   ├── Policies/        # Authorization
│   ├── Repositories/    # Data access
│   ├── Services/        # Business logic
│   └── {Package}ServiceProvider.php
├── tests/               # Package tests
├── composer.json        # Package dependencies
└── README.md            # Package documentation
```

### Application Structure

```
apps/headless-erp-app/
├── app/
│   ├── Console/         # CLI Commands
│   ├── Http/            # API Controllers, Requests, Resources
│   ├── Models/          # Application models (User)
│   ├── Providers/       # Service providers
│   └── Support/         # Helper utilities & contracts
│       ├── Contracts/   # Interface definitions
│       ├── Services/    # Package adapters
│       └── Traits/      # Reusable model traits
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
└── composer.json
```
├── Repositories/     # Data access
└── Services/         # Business logic
```

---

## 🚀 Getting Started

### Prerequisites

- PHP ≥ 8.2
- Composer
- Database (MySQL 8.0+ / PostgreSQL 13+ / SQLite 3.35+)
- Node.js & NPM (for asset compilation if needed)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/azaharizaman/laravel-erp.git
   cd laravel-erp
   ```

2. **Navigate to the main application**
   ```bash
   cd apps/headless-erp-app
   ```

3. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=laravel_erp
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed initial data (optional)**
   ```bash
   php artisan db:seed
   ```

8. **Start development server**
   ```bash
   php artisan serve
   ```

The API will be available at `http://localhost:8000/api/v1/`

### Working with Packages

The core package is symlinked from `packages/core/` to `vendor/azaharizaman/erp-core/` using Composer's path repository feature. Any changes to `packages/core/src/` are immediately reflected in the application.

---

## 💻 Development

### Monorepo Workflow

1. **Root-level commands** (from project root):
   ```bash
   composer test         # Run all tests
   composer lint         # Lint all code
   ```

2. **Application commands** (from `apps/headless-erp-app/`):
   ```bash
   composer install      # Install app dependencies
   php artisan serve     # Start server
   php artisan test      # Run app tests
   ```

3. **Package commands** (from `packages/core/`):
   ```bash
   composer test         # Run package tests
   composer lint         # Lint package code
   ```

### Coding Standards

**🚨 CRITICAL:** Before writing any code, read:
- [CODING_GUIDELINES.md](CODING_GUIDELINES.md) - Mandatory coding standards
- [.github/copilot-instructions.md](.github/copilot-instructions.md) - Development patterns

### Key Development Rules

1. **Type Safety:** All files MUST have `declare(strict_types=1);`
2. **Type Hints:** All methods MUST declare parameter types and return types
3. **PHPDoc:** All public/protected methods MUST have complete PHPDoc blocks
4. **Contracts First:** Define interfaces before implementation
5. **Repository Pattern:** NO direct Model access in services
6. **Package Decoupling:** Use contracts, not direct package dependencies
7. **Testing:** Use Pest v4+ syntax (NO PHPUnit classes)
8. **Multi-Tenancy:** Use `BelongsToTenant` trait on all tenant models
9. **Search:** Use `IsSearchable` trait on all models (Laravel Scout)
10. **Audit Logging:** Use `HasActivityLogging` trait for important models

### Code Quality Tools

```bash
# Format code (MANDATORY before commit)
./vendor/bin/pint

# Run tests
./vendor/bin/pest

# Run with coverage
./vendor/bin/pest --coverage

# Parallel test execution
./vendor/bin/pest --parallel
```

### Creating New Features

1. **Define Contract** in `app/Domains/{Domain}/Contracts/`
2. **Create Repository** implementing contract
3. **Create Action** for business logic
4. **Create Controller** for API endpoints
5. **Add Tests** using Pest
6. **Run Pint** to format code

Example:
```bash
# Create new domain action
php artisan make:action Inventory/CreateItemAction

# Create feature test
php artisan make:test Inventory/CreateItemTest --pest

# Format and test
./vendor/bin/pint
./vendor/bin/pest
```

---

## 📚 Documentation

### Core Documentation
- **[CODING_GUIDELINES.md](CODING_GUIDELINES.md)** - Comprehensive coding standards
- **[GitHub Copilot Instructions](.github/copilot-instructions.md)** - AI-assisted development guide
- **[Package Decoupling Strategy](docs/architecture/PACKAGE-DECOUPLING-STRATEGY.md)** - Architecture patterns

### Feature Documentation
- **[Multi-Tenancy System](docs/middleware-tenant-resolution.md)** - Tenant isolation guide
- **[Sanctum Authentication](docs/SANCTUM_AUTHENTICATION.md)** - API authentication

### Implementation Plans
All PRDs are located in `plan/` directory:
- `PRD-01` through `PRD-05`: Infrastructure (Multi-tenancy, Auth, Audit, Serial, Settings)
- `PRD-06` through `PRD-09`: Backoffice (Company, Office, Department, Staff)
- `PRD-10` through `PRD-13`: Inventory (Items, Warehouse, Stock, UOM)
- `PRD-14` through `PRD-17`: Sales (Customers, Quotation, Orders, Pricing)
- `PRD-18` through `PRD-21`: Purchasing (Vendors, Requisition, PO, Receipt)

---

## 🧪 Testing

### Test Structure
```
tests/
├── Unit/           # Unit tests (isolated, no database)
├── Feature/        # Feature tests (with database)
└── Pest.php        # Pest configuration
```

### Running Tests

```bash
# All tests
./vendor/bin/pest

# Specific test file
./vendor/bin/pest tests/Feature/Auth/LoginTest.php

# Specific test
./vendor/bin/pest --filter="can create tenant"

# With coverage
./vendor/bin/pest --coverage --min=80

# Parallel execution
./vendor/bin/pest --parallel
```

### Test Requirements

- ✅ ALL tests MUST use Pest v4+ syntax (NO PHPUnit classes)
- ✅ Feature tests MUST use `RefreshDatabase` trait
- ✅ Unit tests MUST NOT touch the database
- ✅ Use factories for test data creation
- ✅ Test API endpoints for authentication and authorization
- ✅ Test cross-tenant access prevention

---

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

1. **Read Documentation First**
   - [CODING_GUIDELINES.md](CODING_GUIDELINES.md)
   - [.github/copilot-instructions.md](.github/copilot-instructions.md)

2. **Create Feature Branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

3. **Follow Coding Standards**
   - Use `declare(strict_types=1);`
   - Add type hints to all methods
   - Write complete PHPDoc blocks
   - Use repository pattern
   - Write Pest tests

4. **Run Quality Checks**
   ```bash
   ./vendor/bin/pint
   ./vendor/bin/pest
   ```

5. **Commit with Conventional Commits**
   ```bash
   git commit -m "feat: add customer management API"
   git commit -m "fix: resolve tenant isolation bug"
   git commit -m "docs: update README with new features"
   ```

6. **Create Pull Request**
   - Provide clear description
   - Reference related issues
   - Ensure all tests pass
   - Request review from maintainers

### Commit Message Format

- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `refactor:` - Code refactoring
- `test:` - Test additions or changes
- `chore:` - Maintenance tasks

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

---

## 🙏 Acknowledgments

Built with:
- [Laravel](https://laravel.com) - PHP Framework
- [Spatie Packages](https://spatie.be/open-source) - Laravel ecosystem tools
- [Laravel Actions](https://laravelactions.com) - Action pattern implementation
- [Pest PHP](https://pestphp.com) - Testing framework

---

## 📞 Support

- **Documentation:** Check `docs/` directory and PRD files in `plan/`
- **Issues:** [GitHub Issues](https://github.com/azaharizaman/laravel-erp/issues)
- **Discussions:** [GitHub Discussions](https://github.com/azaharizaman/laravel-erp/discussions)

---

**Version:** 1.0.0-dev  
**Last Updated:** November 10, 2025  
**Status:** Active Development
