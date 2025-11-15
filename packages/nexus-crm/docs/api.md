# Nexus CRM API Documentation

## Overview

The Nexus CRM package provides a comprehensive, database-driven CRM system for Laravel applications. It supports progressive disclosure from simple trait-based CRM (Level 1) to full enterprise pipeline automation (Level 3).

## Installation

```bash
composer require nexus/crm
```

Publish the configuration and migrations:

```bash
php artisan vendor:publish --provider="Nexus\Crm\CrmServiceProvider"
php artisan migrate
```

## Quick Start

### Level 1: Basic CRM (Trait-based)

Add the `HasCrm` trait to your User model:

```php
use Nexus\Crm\Traits\HasCrm;

class User extends Model
{
    use HasCrm;
}
```

Create contacts:

```php
$user = User::find(1);
$contact = $user->createContact([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
]);
```

### Level 2: Database-driven CRM

Create a CRM definition:

```php
use Nexus\Crm\Models\CrmDefinition;

$definition = CrmDefinition::create([
    'name' => 'lead',
    'type' => 'lead',
    'schema' => [
        'first_name' => ['type' => 'string', 'required' => true],
        'email' => ['type' => 'string', 'required' => true],
        'company' => ['type' => 'string'],
    ],
]);
```

Create entities:

```php
use Nexus\Crm\Actions\CreateEntity;

$entity = app(CreateEntity::class)->execute([
    'definition_id' => $definition->id,
    'data' => [
        'first_name' => 'Jane',
        'email' => 'jane@example.com',
        'company' => 'Acme Corp',
    ],
]);
```

### Level 3: Pipeline Automation

Create pipelines and stages, then transition entities:

```php
use Nexus\Crm\Actions\TransitionEntity;

app(TransitionEntity::class)->execute($entity, 'qualified');
```

## Core Classes

### Models

- `CrmDefinition`: Defines CRM entity schemas
- `CrmEntity`: CRM entities with dynamic fields
- `CrmPipeline`: Pipeline configurations
- `CrmStage`: Pipeline stages with conditions
- `CrmAssignment`: User assignments to entities
- `CrmTimer`: SLA and escalation timers

### Services

- `CrmDashboard`: Dashboard data and metrics
- `PipelineEngine`: Pipeline transition logic
- `ConditionEvaluatorManager`: Evaluates transition conditions
- `AssignmentStrategyResolver`: Resolves user assignments
- `IntegrationManager`: Manages external integrations

### Actions

- `CreateEntity`: Creates new CRM entities
- `TransitionEntity`: Transitions entities through pipelines

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=crm-config
```

Available options in `config/crm.php`:

```php
return [
    'default_pipeline' => 'sales',
    'assignment_strategies' => [
        'manual' => \Nexus\Crm\Core\ManualAssignmentStrategy::class,
        // Add custom strategies
    ],
    'integrations' => [
        'email' => \Nexus\Crm\Core\EmailIntegration::class,
        // Add custom integrations
    ],
];
```

## Extensibility

### Custom Condition Evaluators

Implement `ConditionEvaluatorContract`:

```php
use Nexus\Crm\Contracts\ConditionEvaluatorContract;

class CustomConditionEvaluator implements ConditionEvaluatorContract
{
    public function evaluate(array $condition, $entity, array $context = []): bool
    {
        // Custom logic
        return true;
    }
}
```

Register in a service provider:

```php
$manager = app(\Nexus\Crm\Core\ConditionEvaluatorManager::class);
$manager->registerEvaluator('custom', CustomConditionEvaluator::class);
```

### Custom Assignment Strategies

Implement `AssignmentStrategyContract`:

```php
use Nexus\Crm\Contracts\AssignmentStrategyContract;

class CustomAssignmentStrategy implements AssignmentStrategyContract
{
    public function resolve($entity, array $config = []): array
    {
        // Return array of user IDs
        return [1, 2, 3];
    }
}
```

Register:

```php
$resolver = app(\Nexus\Crm\Core\AssignmentStrategyResolver::class);
$resolver->registerStrategy('custom', CustomAssignmentStrategy::class);
```

### Custom Integrations

Implement `IntegrationContract`:

```php
use Nexus\Crm\Contracts\IntegrationContract;

class SlackIntegration implements IntegrationContract
{
    public function execute($entity, array $config, array $context = []): void
    {
        // Send Slack notification
    }
}
```

Register:

```php
$manager = app(\Nexus\Crm\Core\IntegrationManager::class);
$manager->registerIntegration('slack', SlackIntegration::class);
```

## Events

The package fires the following events:

- `Nexus\Crm\Events\ContactCreatedEvent`
- `Nexus\Crm\Events\ContactUpdatedEvent`
- `Nexus\Crm\Events\ContactDeletedEvent`

Listen to these events to extend functionality.

## API Reference

### CrmDashboard

```php
$dashboard = app(CrmDashboard::class);

// User dashboard
$data = $dashboard->forUser('user-id');

// Team dashboard
$data = $dashboard->forTeam(['user1', 'user2']);
```

### PipelineEngine

```php
$engine = app(PipelineEngine::class);

// Check if transition is possible
$canTransition = $engine->canTransition($entity, 'stage-id');

// Execute transition
$engine->transition($entity, 'stage-id');
```

## Performance Tuning

- Dashboard data is cached for 5 minutes by default
- Use database indexes on frequently queried fields
- Consider queueing heavy integrations
- Monitor query performance with Laravel Debugbar

## Security

- All models use fillable properties
- Input validation in actions
- Use Laravel's authorization for access control
- Sanitize data before storage

## Troubleshooting

### Common Issues

1. **Migration errors**: Ensure database supports JSON fields
2. **Pipeline not transitioning**: Check condition definitions
3. **Assignment failures**: Verify user permissions
4. **Integration timeouts**: Use queues for external calls

### Debug Mode

Enable debug logging in Laravel's config:

```php
'log_level' => env('LOG_LEVEL', 'debug'),
```

Check logs in `storage/logs/laravel.log`.