# Independent Testing Guide

This document explains how to run tests for the nexus-audit-log atomic package in complete isolation.

## Overview

The nexus-audit-log package has been refactored to achieve **Maximum Atomicity** compliance, which includes the ability to test the package independently without external dependencies or applications.

## Testing Architecture

### Test Environment
- **Testbench**: Uses Orchestra Testbench for isolated Laravel testing environment
- **Database**: In-memory SQLite for fast, isolated tests
- **Dependencies**: Only internal contracts and models, no external packages
- **Service Provider**: Tests package registration and binding in isolation

### Test Structure
```
tests/
├── bootstrap.php           # Test environment initialization
├── Pest.php               # Pest configuration  
├── TestCase.php           # Base test case with Orchestra Testbench
├── Feature/                # Feature tests for package functionality
│   └── IndependentTestabilityTest.php
└── Unit/                   # Unit tests (future)
```

## Running Tests

### Prerequisites
```bash
cd packages/nexus-audit-log
composer install
```

### Run All Tests
```bash
composer test
```

### Run with Coverage
```bash
composer test-coverage
```

### Run in CI Environment (with coverage requirements)
```bash
composer test-ci
```

### Run Isolated Tests (no dev dependencies)
```bash
composer test-isolated
```

## Test Capabilities

The independent test suite verifies:

### ✅ Package Registration
- Service provider loads correctly
- Contract bindings work in isolation
- Configuration is accessible

### ✅ Internal Models
- AuditLog model functionality
- Model scopes and relationships
- Database migrations

### ✅ Repository Layer
- Contract implementation
- Data persistence
- Query operations

### ✅ Domain Logic
- Business rules enforcement
- Data validation
- Event handling

### ✅ Atomic Compliance
- No external service dependencies
- No presentation layer components
- Independent testability

## Architecture Benefits

### Independent Testing Enables:
1. **Faster CI/CD**: Tests run without external service setup
2. **Reliable Testing**: No flaky external dependencies
3. **Isolated Development**: Package can be developed independently
4. **Contract Verification**: Internal contracts are properly tested
5. **Regression Prevention**: Changes verified in isolation

### What's NOT Tested Here:
- Integration with Spatie ActivityLog (tested in orchestration layer)
- HTTP controllers and routes (moved to Nexus\Erp)
- CLI commands (moved to Nexus\Erp)
- External service integrations (handled by adapters)

## Configuration

### Database
Tests use in-memory SQLite for speed and isolation:
```php
'database.connections.testing' => [
    'driver' => 'sqlite', 
    'database' => ':memory:',
]
```

### Package Config
```php
'audit-logging.storage_driver' => 'database'
'audit-logging.retention_days' => 30
'audit-logging.notify_high_value_events' => false
```

## Compliance Verification

To verify Maximum Atomicity compliance:

```bash
# 1. Test package in isolation
composer test

# 2. Check for external dependencies
composer show --installed --direct

# 3. Verify no presentation layer components
find src -name "*Controller*" -o -name "*Command*" -o -name "routes" | wc -l
# Should return 0

# 4. Verify package can be installed without dev dependencies (dry run)
composer install --no-dev --dry-run

# 5. Re-install with dev dependencies and run tests
composer install
composer test
```

## Integration Testing

For testing the complete system including orchestration:
- Use tests in the main Nexus\Erp package
- Integration tests should be in Edward demo application
- Use the adapter pattern to test external integrations

This separation ensures atomic packages remain focused on domain logic while orchestration handles presentation concerns.