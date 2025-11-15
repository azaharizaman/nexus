# Nexus CRM Package

**Version:** 1.0.0  
**Status:** Production Ready - All Phases Complete

A progressive CRM package for Nexus ERP that starts simple and scales with your needs. Supports everything from basic contact management to enterprise sales automation.

## Features

- ✅ **Level 1**: Trait-based CRM (no migrations required)
- ✅ **Level 2**: Database-driven CRM with pipelines
- ✅ **Level 3**: Enterprise features (planned for v1.1.0)
- ✅ **Extensible**: Custom conditions, assignments, integrations
- ✅ **Performant**: Cached dashboards, optimized queries
- ✅ **Well-documented**: Complete API docs and tutorials

## Installation

```bash
composer require nexus/crm
php artisan vendor:publish --provider="Nexus\Crm\CrmServiceProvider"
php artisan migrate
```

## Quick Start

### Level 1: Basic CRM (5 minutes)

Add CRM to any model:

```php
use Nexus\Crm\Traits\HasCrm;

class User extends Model
{
    use HasCrm;
}
```

Create contacts:

```php
$user->createContact([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
]);
```

### Level 2: Advanced CRM (20 minutes)

Create dynamic entities:

```php
use Nexus\Crm\Models\CrmDefinition;

$definition = CrmDefinition::create([
    'name' => 'Lead',
    'type' => 'lead',
    'schema' => [
        'first_name' => ['type' => 'string', 'required' => true],
        'email' => ['type' => 'string', 'required' => true],
        'budget' => ['type' => 'number'],
    ],
]);

use Nexus\Crm\Actions\CreateEntity;

$entity = app(CreateEntity::class)->execute([
    'definition_id' => $definition->id,
    'data' => [
        'first_name' => 'Jane',
        'email' => 'jane@example.com',
        'budget' => 50000,
    ],
]);
```

### Level 3: Pipeline Automation (30 minutes)

Set up automated workflows:

```php
use Nexus\Crm\Models\CrmPipeline;
use Nexus\Crm\Models\CrmStage;

$pipeline = CrmPipeline::create(['name' => 'Sales']);
$stage = CrmStage::create([
    'pipeline_id' => $pipeline->id,
    'name' => 'Qualified',
    'transition_conditions' => [
        ['field' => 'budget', 'operator' => 'greater_than', 'value' => 10000]
    ],
]);

use Nexus\Crm\Actions\TransitionEntity;

app(TransitionEntity::class)->execute($entity, 'qualified');
```

## Documentation

- 📖 **[API Documentation](docs/api.md)** - Complete reference
- 🚀 **[Tutorials](docs/tutorials.md)** - Step-by-step guides for all levels
- 🔄 **[Migration Guides](docs/migrations.md)** - Import from Salesforce, HubSpot, etc.
- ⚡ **[Performance Tuning](docs/performance.md)** - Optimization guide
- 📝 **[Changelog](CHANGELOG.md)** - Version history

## Extensibility

Register custom components:

```php
// Custom assignment strategy
$strategyResolver = app(\Nexus\Crm\Core\AssignmentStrategyResolver::class);
$strategyResolver->registerStrategy('custom', CustomStrategy::class);

// Custom condition evaluator
$evaluatorManager = app(\Nexus\Crm\Core\ConditionEvaluatorManager::class);
$evaluatorManager->registerEvaluator('custom', CustomEvaluator::class);

// Custom integration
$integrationManager = app(\Nexus\Crm\Core\IntegrationManager::class);
$integrationManager->registerIntegration('slack', SlackIntegration::class);
```

## Requirements

- PHP 8.3+
- Laravel 12+
- MySQL/PostgreSQL with JSON support

## Testing

```bash
composer test
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Support

- 📧 Email: azaharizaman@gmail.com
- 📚 Docs: [Full Documentation](docs/)
- 🐛 Issues: [GitHub Issues](https://github.com/azaharizaman/nexus-erp/issues)

---

**Built with ❤️ for the Laravel community**
$contacts = $user->getContacts();

// Update a contact
$user->updateContact($contactId, [
    'email' => 'john.doe@example.com',
]);

// Delete a contact
$user->deleteContact($contactId);

// Check permissions
if ($user->crm()->can('create_contact')) {
    // Add contact
}

// Get audit history
$history = $user->crm()->history();
```

## Features (Level 1)

- ✅ **Zero Database Migrations** - Store CRM data in model attributes
- ✅ **Type Validation** - Automatic validation of contact fields
- ✅ **Permission Checks** - Declarative permission system
- ✅ **Event-Driven** - Fires events for all CRM operations
- ✅ **Audit Trail** - Track all changes and operations
- ✅ **Independent Testing** - Fully testable in isolation

## Architecture

### Progressive Disclosure

| Level | Database | Features |
|-------|----------|----------|
| **1** | No | Trait-based CRM with model attributes |
| **2** | Yes | Database-driven leads, opportunities, pipelines |
| **3** | Yes | Enterprise features (SLA, escalation, delegation) |

### Atomic Design

- **Independent Package** - Zero dependencies on other Nexus packages
- **Contract-Driven** - All integrations via interfaces
- **Event-Driven** - Domain events for cross-package communication
- **Headless** - Pure API/CLI, no frontend dependencies

## Installation

```bash
composer require nexus/crm
```

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --provider="Nexus\Crm\CrmServiceProvider"
```

## Testing

```bash
composer test
```

## Roadmap

- **Phase 1** ✅ Basic CRM (Traits)
- **Phase 2** ✅ Sales Automation (Database)
- **Phase 3** 📋 Enterprise Features
- **Phase 4** 📋 Extensibility & Polish

## License

MIT