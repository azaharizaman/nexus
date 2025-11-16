# Nexus Sequencing - Atomic Serial Number Generation

**Framework-Agnostic Package for Atomic Serial Number Generation**

[![License](https://img.shields.io/packagist/l/nexus/sequencing.svg)](LICENSE)

**Pure PHP package providing atomic, transaction-safe serial number generation with configurable patterns, scope isolation, and comprehensive audit trails.**

---

## 🎯 What This Package Does

The **Nexus Sequencing** package provides **one core capability**: generating **unique, sequential base identifiers** with atomic database locking.

### ✅ This Package Handles:
- **Atomic Counter Management** - Race-condition-free number generation
- **Pattern Formatting** - Flexible templates like `INV-{YEAR}-{COUNTER:5}`
- **Scope Isolation** - Multi-tenant or multi-department sequences
- **Transaction Safety** - Automatic rollback on failures
- **Reset Periods** - Daily, monthly, yearly, or never
- **Framework-Agnostic Core** - Pure PHP business logic

### ❌ This Package Does NOT Handle:
- **Database Persistence** - Applications provide Eloquent/Doctrine implementations
- **Sub-Identifiers** (versions `/v2`, spawns `(a)`, copies `1 of 3`) - Your application manages these
- **Document Status** (active/voided) - Use workflow packages for lifecycle management
- **Business Logic** - Package is agnostic to what the numbers represent
- **Laravel-Specific Features** - No controllers, models, or migrations in package

---

## 📐 Architecture

This package follows the **Nexus Monorepo Architecture** principles:

### Package Structure (Framework-Agnostic)
```
packages/Sequencing/
├── composer.json           # Pure PHP, no Laravel dependencies
├── LICENSE                 # MIT License
├── README.md              # This file
└── src/
    ├── Contracts/         # Interfaces defining the package API
    │   ├── SequenceInterface.php
    │   ├── SerialNumberLogInterface.php
    │   ├── SequenceRepositoryInterface.php
    │   ├── SerialNumberLogRepositoryInterface.php
    │   ├── PatternParserServiceInterface.php
    │   └── GenerationServiceInterface.php
    ├── Core/              # Framework-agnostic business logic
    │   ├── Services/
    │   ├── ValueObjects/
    │   ├── Engine/
    │   └── Variables/
    ├── Exceptions/        # Domain-specific exceptions
    │   ├── SequenceNotFoundException.php
    │   ├── InvalidPatternException.php
    │   ├── GenerationException.php
    │   └── SequenceConfigurationException.php
    └── Enums/            # Enumerations (ResetPeriod, etc.)
```

### Application Layer (Laravel Example in apps/Atomy)
```
apps/Atomy/
├── database/migrations/
│   ├── *_create_serial_number_sequences_table.php
│   └── *_create_serial_number_logs_table.php
├── app/
│   ├── Models/
│   │   ├── Sequence.php              # implements SequenceInterface
│   │   └── SerialNumberLog.php       # implements SerialNumberLogInterface
│   └── Repositories/Sequencing/
│       ├── SequenceRepository.php    # implements SequenceRepositoryInterface
│       └── SerialNumberLogRepository.php
└── app/Providers/AtomyServiceProvider.php  # IoC bindings
```

---

## 📦 Installation

### Step 1: Install Package

```bash
composer require nexus/sequencing
```

### Step 2: Implement in Your Application

Since this is a framework-agnostic package, you need to provide:

1. **Database Schema** - Create migrations for `serial_number_sequences` and `serial_number_logs` tables
2. **Models** - Create models that implement `SequenceInterface` and `SerialNumberLogInterface`
3. **Repositories** - Create repositories that implement the repository interfaces
4. **IoC Bindings** - Bind the interfaces to your concrete implementations

**Example for Laravel:**

See the complete implementation in `apps/Atomy` directory of the [monorepo](https://github.com/azaharizaman/nexus).

---

## 🚀 Usage

### Core Services

The package provides framework-agnostic services via interfaces:

```php
use Nexus\Sequencing\Contracts\SequenceRepositoryInterface;
use Nexus\Sequencing\Contracts\GenerationServiceInterface;

// Inject via your IoC container
class InvoiceService
{
    public function __construct(
        private readonly SequenceRepositoryInterface $sequenceRepo,
        private readonly GenerationServiceInterface $generationService
    ) {}

    public function createInvoice(array $data): Invoice
    {
        // Find sequence configuration
        $sequence = $this->sequenceRepo->find('tenant-123', 'invoices');
        
        // Generate unique invoice number
        $invoiceNumber = $this->generationService->generate($sequence, [
            'department_code' => 'SALES',
        ]);
        
        // Create invoice with generated number
        return Invoice::create([
            'invoice_number' => $invoiceNumber,
            'amount' => $data['amount'],
            // ...
        ]);
    }
}
```

---

## 📚 Table of Contents

1. [Installation](#installation)
2. [Architecture](#architecture)
3. [Usage](#usage)
4. [Pattern Variables Reference](#pattern-variables-reference)
5. [Reset Periods](#reset-periods)
6. [Contracts (Interfaces)](#contracts-interfaces)
7. [Core Services](#core-services)
8. [Events](#events)
9. [Testing](#testing)
10. [License](#license)

---

## 🔌 Contracts (Interfaces)

The package exposes the following interfaces that your application must implement:

### Data Structure Contracts

#### `SequenceInterface`
Defines what a Sequence IS (data structure):
- `getId()`, `getTenantId()`, `getSequenceName()`
- `getPattern()`, `getResetPeriod()`, `getPadding()`
- `getStepSize()`, `getResetLimit()`, `getCurrentValue()`
- `getLastResetAt()`, `getMetadata()`, `getVersion()`
- `getCreatedAt()`, `getUpdatedAt()`

#### `SerialNumberLogInterface`
Defines what a log entry IS (immutable audit record):
- `getId()`, `getSequenceId()`, `getGeneratedNumber()`
- `getCounterValue()`, `getContext()`, `getActionType()`
- `getReason()`, `getCauserId()`, `getCreatedAt()`

### Repository Contracts

#### `SequenceRepositoryInterface`
Defines HOW to save/find sequences (persistence):
- `find()`, `findById()`, `create()`, `update()`, `delete()`
- `lockAndIncrement()` - Atomic counter increment with locking
- `reset()`, `override()`, `exists()`, `getAllForTenant()`

#### `SerialNumberLogRepositoryInterface`
Defines HOW to save/find log entries:
- `create()`, `findBySequence()`, `findByGeneratedNumber()`
- `getLastGenerated()`, `getHistory()`

### Service Contracts

#### `PatternParserServiceInterface`
Pattern parsing and variable substitution:
- `parse()`, `validate()`, `getVariables()`, `preview()`

#### `GenerationServiceInterface`
Core generation orchestration:
- `generate()`, `preview()`, `validate()`, `needsReset()`

---

## 🛠️ Core Services

The package includes framework-agnostic implementations in `src/Core/`:

### GenerationService
Orchestrates the complete generation workflow:
1. Validates pattern syntax
2. Checks if reset is needed
3. Atomically increments counter (via repository)
4. Evaluates pattern with context
5. Returns generated number

### ValidationService
Validates serial numbers against pattern definitions:
- Syntax validation
- Regex matching
- Variable format checking

### PatternParserService
Handles pattern variable substitution:
- Built-in variables: `{YEAR}`, `{MONTH}`, `{DAY}`, `{COUNTER}`
- Custom variables: `{TENANT}`, `{DEPARTMENT}`, `{PREFIX}`, etc.
- Padding support: `{COUNTER:5}` = `00001`

---

## 📝 Pattern Variables Reference

### Built-in Date Variables

| Variable | Description | Example | Format |
|----------|-------------|---------|--------|
| `{YEAR}` | 4-digit year | `2025` | `YYYY` |
| `{YEAR:2}` | 2-digit year | `25` | `YY` |
| `{MONTH}` | 2-digit month | `11` | `MM` |
| `{DAY}` | 2-digit day | `16` | `DD` |

### Counter Variable

| Variable | Description | Example | Notes |
|----------|-------------|---------|-------|
| `{COUNTER}` | Auto-increment with default padding | `00001` | Uses `padding` from sequence config |
| `{COUNTER:N}` | Counter with N-digit padding | `{COUNTER:8}` = `00000001` | Overrides sequence padding |

### Context Variables

Pass custom variables via context array:

```php
$generationService->generate($sequence, [
    'tenant_code' => 'ACME',
    'department_code' => 'IT',
    'prefix' => 'URGENT',
]);
```

Pattern: `{PREFIX}-{DEPARTMENT}-{COUNTER:5}`  
Result: `URGENT-IT-00001`

---

## 🔄 Reset Periods

Control when the counter resets to initial value:

| Reset Period | Behavior | Use Case |
|--------------|----------|----------|
| **never** | Counter never resets | Lifetime sequential numbers |
| **daily** | Resets at midnight | Daily reports |
| **monthly** | Resets on 1st of month | Monthly invoices |
| **yearly** | Resets on Jan 1st | Annual contracts |

---

## 🎭 Events

Your application layer can dispatch events (if using Laravel):

```php
// Example event dispatching in your repository
use Nexus\Sequencing\Events\SequenceGeneratedEvent;

event(new SequenceGeneratedEvent(
    $sequence,
    $generatedNumber,
    $counterValue
));
```

Available event classes:
- `SequenceGeneratedEvent`
- `SequenceResetEvent`
- `SequenceOverriddenEvent`

---

## 🧪 Testing

The Core services are pure PHP and easily testable:

```php
use Nexus\Sequencing\Core\Services\GenerationService;
use PHPUnit\Framework\TestCase;

class GenerationServiceTest extends TestCase
{
    public function testGeneratesUniqueNumber(): void
    {
        // Mock your repository
        $mockRepo = $this->createMock(CounterRepositoryInterface::class);
        $mockRepo->expects($this->once())
            ->method('lockAndIncrement')
            ->willReturn(1);
        
        $service = new GenerationService($mockRepo, ...);
        
        $result = $service->generate($sequenceConfig, $context);
        
        $this->assertMatchesPattern('/INV-2025-\d{5}/', $result);
    }
}
```

---

## 📄 License

MIT License - See [LICENSE](LICENSE)

---

## 🤝 Contributing

This package is part of the [Nexus Monorepo](https://github.com/azaharizaman/nexus).

### Development Setup

```bash
# Clone the monorepo
git clone https://github.com/azaharizaman/nexus.git
cd nexus/packages/Sequencing

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyse
```

---

## 📖 Documentation

- **[Monorepo Architecture](../../.github/copilot-instructions.md)** - Complete architectural guidelines
- **[REQUIREMENTS.md](REQUIREMENTS.md)** - Detailed requirements and specifications
- **[REFACTORED_REQUIREMENTS.md](../../REFACTORED_REQUIREMENTS.md)** - Implementation tracking

---

## 🏆 Package Status

**Version:** 2.0.0 (Refactored)  
**Status:** ✅ Framework-Agnostic Complete

### Architectural Compliance:
✅ Zero Laravel dependencies  
✅ Comprehensive contracts (6 interfaces)  
✅ Domain-specific exceptions  
✅ Pure PHP Core services  
✅ Complete test coverage  
✅ Publishable to Packagist  

*This package maintains **Maximum Atomicity** - it does one thing exceptionally well: providing the framework-agnostic business logic for generating unique, sequential base identifiers.*
