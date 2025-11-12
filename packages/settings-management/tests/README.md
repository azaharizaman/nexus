# Settings Management Tests

This directory contains tests for the Settings Management package.

## Test Structure

```
tests/
├── Pest.php                          # Pest configuration
├── TestCase.php                      # Base test case
├── Unit/
│   └── SettingsServiceTest.php      # Unit tests for service layer
└── Feature/
    ├── SettingsApiTest.php          # API endpoint tests
    └── SettingsHierarchyTest.php    # Hierarchical resolution tests
```

## Running Tests

### Prerequisites

1. Install dependencies in the main app:
```bash
cd apps/headless-erp-app
composer install
```

2. Configure your test database in `.env.testing` or use in-memory SQLite.

### Run All Tests

```bash
cd /path/to/laravel-erp
./vendor/bin/pest packages/settings-management/tests
```

### Run Specific Test Files

```bash
# Unit tests only
./vendor/bin/pest packages/settings-management/tests/Unit

# Feature tests only
./vendor/bin/pest packages/settings-management/tests/Feature

# Specific test file
./vendor/bin/pest packages/settings-management/tests/Feature/SettingsApiTest.php
```

### Run with Coverage

```bash
./vendor/bin/pest packages/settings-management/tests --coverage
```

## Test Categories

### Unit Tests (15 tests)
- Type casting (string, integer, boolean, array, json)
- Encryption and decryption
- Storage format conversion
- Cache key generation
- Scope hierarchy resolution

### Feature Tests: API (14 tests)
- Create, read, update, delete settings
- List settings with filtering
- Bulk operations
- Import/export (JSON, CSV)
- Validation
- Authentication and authorization
- Tenant isolation

### Feature Tests: Hierarchy & Integration (15 tests)
- System, tenant, module, user-level resolution
- Hierarchical overriding
- Cache warming
- Cache invalidation
- Change history tracking
- Type handling (boolean, array, json, encrypted)

## Test Coverage Goals

- **Target**: 80%+ code coverage
- **Unit Tests**: Core service logic
- **Feature Tests**: API endpoints and integration
- **Edge Cases**: Validation, authorization, tenant isolation

## Notes

- Tests use `RefreshDatabase` trait for isolated test database
- Each test uses factory-created test data
- Cache is flushed before each test to ensure isolation
- Tests verify both database state and API responses
