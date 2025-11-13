<?php

declare(strict_types=1);

use Nexus\Erp\AuditLogging\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Unit Tests for Auditable Trait
 *
 * Tests trait behavior, configuration, and event handling.
 */
test('auditable trait provides default log name', function () {
    $model = new class extends Model
    {
        use Auditable;

        protected $table = 'test_models';
    };

    $logName = $model->auditLogName();

    expect($logName)->toBe('test_models');
});

test('auditable trait provides default auditable events', function () {
    $model = new class extends Model
    {
        use Auditable;
    };

    $events = $model->auditableEvents();

    expect($events)->toContain('created');
    expect($events)->toContain('updated');
    expect($events)->toContain('deleted');
    expect($events)->toHaveCount(3);
});

test('auditable trait allows custom log name override', function () {
    $model = new class extends Model
    {
        use Auditable;

        protected $table = 'test_models';

        protected function auditLogName(): string
        {
            return 'custom-log-name';
        }
    };

    $logName = $model->auditLogName();

    expect($logName)->toBe('custom-log-name');
});

test('auditable trait allows custom auditable events', function () {
    $model = new class extends Model
    {
        use Auditable;

        protected function auditableEvents(): array
        {
            return ['created', 'updated'];
        }
    };

    $events = $model->auditableEvents();

    expect($events)->toContain('created');
    expect($events)->toContain('updated');
    expect($events)->not->toContain('deleted');
    expect($events)->toHaveCount(2);
});

test('auditable trait should audit event returns correct value', function () {
    $model = new class extends Model
    {
        use Auditable;

        protected function auditableEvents(): array
        {
            return ['created', 'updated'];
        }
    };

    expect($model->shouldAuditEvent('created'))->toBeTrue();
    expect($model->shouldAuditEvent('updated'))->toBeTrue();
    expect($model->shouldAuditEvent('deleted'))->toBeFalse();
    expect($model->shouldAuditEvent('custom'))->toBeFalse();
});

test('auditable trait respects config for before after logging', function () {
    config(['audit-logging.enable_before_after' => true]);

    $model = new class extends Model
    {
        use Auditable;
    };

    expect($model->auditShouldLogBeforeAfter())->toBeTrue();

    config(['audit-logging.enable_before_after' => false]);

    $model2 = new class extends Model
    {
        use Auditable;
    };

    expect($model2->auditShouldLogBeforeAfter())->toBeFalse();
});

test('auditable trait allows custom before after override', function () {
    $model = new class extends Model
    {
        use Auditable;

        protected function auditShouldLogBeforeAfter(): bool
        {
            return true; // Always log before/after for this model
        }
    };

    expect($model->auditShouldLogBeforeAfter())->toBeTrue();
});

test('auditable trait provides default exclude attributes', function () {
    $model = new class extends Model
    {
        use Auditable;
    };

    $excluded = $model->auditExcludeAttributes();

    expect($excluded)->toContain('updated_at');
});

test('auditable trait allows custom exclude attributes', function () {
    $model = new class extends Model
    {
        use Auditable;

        protected function auditExcludeAttributes(): array
        {
            return ['updated_at', 'cached_total', 'computed_field'];
        }
    };

    $excluded = $model->auditExcludeAttributes();

    expect($excluded)->toContain('updated_at');
    expect($excluded)->toContain('cached_total');
    expect($excluded)->toContain('computed_field');
    expect($excluded)->toHaveCount(3);
});

test('auditable trait returns null for default description', function () {
    $model = new class extends Model
    {
        use Auditable;
    };

    $description = $model->auditDescription('created');

    expect($description)->toBeNull();
});

test('auditable trait allows custom description', function () {
    $model = new class extends Model
    {
        use Auditable;

        protected function auditDescription(string $event): ?string
        {
            return "Custom {$event} description";
        }
    };

    $description = $model->auditDescription('created');

    expect($description)->toBe('Custom created description');
});

test('auditable trait provides empty additional properties by default', function () {
    $model = new class extends Model
    {
        use Auditable;
    };

    $properties = $model->auditAdditionalProperties('created');

    expect($properties)->toBeArray();
    expect($properties)->toBeEmpty();
});

test('auditable trait allows custom additional properties', function () {
    $model = new class extends Model
    {
        use Auditable;

        protected function auditAdditionalProperties(string $event): array
        {
            return [
                'custom_field' => 'value',
                'event_type' => $event,
            ];
        }
    };

    $properties = $model->auditAdditionalProperties('created');

    expect($properties)->toHaveKey('custom_field');
    expect($properties)->toHaveKey('event_type');
    expect($properties['custom_field'])->toBe('value');
    expect($properties['event_type'])->toBe('created');
});
