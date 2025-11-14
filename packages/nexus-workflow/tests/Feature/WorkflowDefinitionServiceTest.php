<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Nexus\Workflow\Models\WorkflowDefinition;
use Nexus\Workflow\Services\WorkflowDefinitionService;

beforeEach(function () {
    $this->service = new WorkflowDefinitionService();
    
    $this->validDefinition = [
        'states' => ['pending', 'active', 'completed'],
        'transitions' => [
            [
                'name' => 'activate',
                'from' => 'pending',
                'to' => 'active',
            ],
            [
                'name' => 'complete',
                'from' => 'active',
                'to' => 'completed',
            ],
        ],
        'initial_state' => 'pending',
    ];
    
    $this->validWorkflowData = [
        'name' => 'Test Workflow',
        'key' => 'test-workflow',
        'description' => 'A test workflow',
        'definition' => $this->validDefinition,
    ];
});

describe('WorkflowDefinitionService - Create', function () {
    test('can create a new workflow definition', function () {
        $workflow = $this->service->create($this->validWorkflowData);

        expect($workflow)->toBeInstanceOf(WorkflowDefinition::class)
            ->and($workflow->name)->toBe('Test Workflow')
            ->and($workflow->key)->toBe('test-workflow')
            ->and($workflow->version)->toBe(1)
            ->and($workflow->is_active)->toBeFalse();
    });

    test('can create an active workflow', function () {
        $data = array_merge($this->validWorkflowData, ['is_active' => true]);
        $workflow = $this->service->create($data);

        expect($workflow->is_active)->toBeTrue();
    });

    test('throws validation error for invalid workflow key', function () {
        $data = array_merge($this->validWorkflowData, ['key' => 'Invalid Key!']);

        expect(fn() => $this->service->create($data))
            ->toThrow(ValidationException::class);
    });

    test('throws validation error for duplicate key', function () {
        $this->service->create($this->validWorkflowData);

        expect(fn() => $this->service->create($this->validWorkflowData))
            ->toThrow(ValidationException::class);
    });

    test('validates definition structure', function () {
        $data = $this->validWorkflowData;
        $data['definition'] = ['invalid' => 'structure'];

        expect(fn() => $this->service->create($data))
            ->toThrow(ValidationException::class);
    });

    test('validates initial state exists in states', function () {
        $data = $this->validWorkflowData;
        $data['definition']['initial_state'] = 'nonexistent';

        expect(fn() => $this->service->create($data))
            ->toThrow(ValidationException::class);
    });

    test('validates transition states exist', function () {
        $data = $this->validWorkflowData;
        $data['definition']['transitions'][0]['from'] = 'nonexistent';

        expect(fn() => $this->service->create($data))
            ->toThrow(ValidationException::class);
    });

    test('validates no duplicate transition names', function () {
        $data = $this->validWorkflowData;
        $data['definition']['transitions'][] = [
            'name' => 'activate', // Duplicate
            'from' => 'active',
            'to' => 'completed',
        ];

        expect(fn() => $this->service->create($data))
            ->toThrow(ValidationException::class);
    });
});

describe('WorkflowDefinitionService - Versioning', function () {
    test('can create a new version of existing workflow', function () {
        $workflow = $this->service->create($this->validWorkflowData);

        $newDefinition = [
            'states' => ['pending', 'active', 'suspended', 'completed'],
            'transitions' => [
                ['name' => 'activate', 'from' => 'pending', 'to' => 'active'],
                ['name' => 'suspend', 'from' => 'active', 'to' => 'suspended'],
                ['name' => 'complete', 'from' => 'active', 'to' => 'completed'],
            ],
            'initial_state' => 'pending',
        ];

        $version2 = $this->service->createVersion('test-workflow', $newDefinition);

        expect($version2->key)->toBe('test-workflow')
            ->and($version2->version)->toBe(2)
            ->and($version2->is_active)->toBeFalse()
            ->and($version2->definition['states'])->toHaveCount(4);
    });

    test('can create and activate new version', function () {
        $workflow = $this->service->create($this->validWorkflowData);

        $newDefinition = $this->validDefinition;
        $version2 = $this->service->createVersion('test-workflow', $newDefinition, null, true);

        expect($version2->is_active)->toBeTrue()
            ->and($workflow->fresh()->is_active)->toBeFalse();
    });

    test('throws error when creating version for nonexistent workflow', function () {
        expect(fn() => $this->service->createVersion('nonexistent', $this->validDefinition))
            ->toThrow(ValidationException::class);
    });

    test('can get all versions of a workflow', function () {
        $this->service->create($this->validWorkflowData);
        $this->service->createVersion('test-workflow', $this->validDefinition);
        $this->service->createVersion('test-workflow', $this->validDefinition);

        $versions = $this->service->getVersions('test-workflow');

        expect($versions)->toHaveCount(3)
            ->and($versions->first()->version)->toBe(3)
            ->and($versions->last()->version)->toBe(1);
    });
});

describe('WorkflowDefinitionService - Update', function () {
    test('can update inactive workflow', function () {
        $workflow = $this->service->create($this->validWorkflowData);

        $updated = $this->service->update($workflow->id, [
            'name' => 'Updated Workflow',
            'description' => 'Updated description',
        ]);

        expect($updated->name)->toBe('Updated Workflow')
            ->and($updated->description)->toBe('Updated description');
    });

    test('cannot update active workflow', function () {
        $workflow = $this->service->create(
            array_merge($this->validWorkflowData, ['is_active' => true])
        );

        expect(fn() => $this->service->update($workflow->id, ['name' => 'New Name']))
            ->toThrow(ValidationException::class);
    });
});

describe('WorkflowDefinitionService - Activation', function () {
    test('can activate a workflow', function () {
        $workflow = $this->service->create($this->validWorkflowData);

        $activated = $this->service->activate($workflow->id);

        expect($activated->is_active)->toBeTrue();
    });

    test('activating a workflow deactivates others with same key', function () {
        $v1 = $this->service->create($this->validWorkflowData);
        $v2 = $this->service->createVersion('test-workflow', $this->validDefinition);

        $this->service->activate($v1->id);
        expect($v1->fresh()->is_active)->toBeTrue();

        $this->service->activate($v2->id);
        expect($v2->fresh()->is_active)->toBeTrue()
            ->and($v1->fresh()->is_active)->toBeFalse();
    });

    test('can deactivate a workflow', function () {
        $workflow = $this->service->create(
            array_merge($this->validWorkflowData, ['is_active' => true])
        );

        $deactivated = $this->service->deactivate($workflow->id);

        expect($deactivated->is_active)->toBeFalse();
    });
});

describe('WorkflowDefinitionService - Delete', function () {
    test('can delete inactive workflow', function () {
        $workflow = $this->service->create($this->validWorkflowData);

        $result = $this->service->delete($workflow->id);

        expect($result)->toBeTrue()
            ->and(WorkflowDefinition::withTrashed()->find($workflow->id)->trashed())->toBeTrue();
    });

    test('cannot delete active workflow', function () {
        $workflow = $this->service->create(
            array_merge($this->validWorkflowData, ['is_active' => true])
        );

        expect(fn() => $this->service->delete($workflow->id))
            ->toThrow(ValidationException::class);
    });
});

describe('WorkflowDefinitionService - Retrieval', function () {
    test('can get active workflow by key', function () {
        $this->service->create($this->validWorkflowData);
        $v2 = $this->service->createVersion('test-workflow', $this->validDefinition);
        $this->service->activate($v2->id);

        $active = $this->service->getActive('test-workflow');

        expect($active->id)->toBe($v2->id)
            ->and($active->version)->toBe(2);
    });

    test('returns null for nonexistent active workflow', function () {
        $active = $this->service->getActive('nonexistent');

        expect($active)->toBeNull();
    });

    test('can get latest workflow version', function () {
        $this->service->create($this->validWorkflowData);
        $v2 = $this->service->createVersion('test-workflow', $this->validDefinition);

        $latest = $this->service->getLatest('test-workflow');

        expect($latest->id)->toBe($v2->id)
            ->and($latest->version)->toBe(2);
    });

    test('can list all active workflows', function () {
        $this->service->create(array_merge($this->validWorkflowData, ['is_active' => true]));
        $this->service->create([
            'name' => 'Another Workflow',
            'key' => 'another-workflow',
            'definition' => $this->validDefinition,
            'is_active' => true,
        ]);
        $this->service->create([
            'name' => 'Inactive Workflow',
            'key' => 'inactive-workflow',
            'definition' => $this->validDefinition,
            'is_active' => false,
        ]);

        $active = $this->service->listActive();

        expect($active)->toHaveCount(2);
    });
});

describe('WorkflowDefinitionService - Import/Export', function () {
    test('can export workflow to JSON', function () {
        $workflow = $this->service->create($this->validWorkflowData);

        $json = $this->service->exportToJson($workflow->id);
        $data = json_decode($json, true);

        expect($data)->toBeArray()
            ->and($data['name'])->toBe('Test Workflow')
            ->and($data['key'])->toBe('test-workflow')
            ->and($data['definition'])->toBe($this->validDefinition);
    });

    test('can import workflow from JSON', function () {
        $original = $this->service->create($this->validWorkflowData);
        $json = $this->service->exportToJson($original->id);

        // Delete original
        $this->service->delete($original->id);

        // Import
        $imported = $this->service->importFromJson($json);

        expect($imported->name)->toBe($original->name)
            ->and($imported->key)->toBe($original->key)
            ->and($imported->definition)->toBe($original->definition);
    });

    test('can import and activate workflow', function () {
        $original = $this->service->create($this->validWorkflowData);
        $json = $this->service->exportToJson($original->id);
        $this->service->delete($original->id);

        $imported = $this->service->importFromJson($json, null, true);

        expect($imported->is_active)->toBeTrue();
    });

    test('throws error for invalid JSON', function () {
        expect(fn() => $this->service->importFromJson('invalid json'))
            ->toThrow(ValidationException::class);
    });
});

describe('WorkflowDefinitionService - Clone', function () {
    test('can clone a workflow', function () {
        $this->service->create($this->validWorkflowData);

        $clone = $this->service->clone('test-workflow', 'cloned-workflow', 'Cloned Workflow');

        expect($clone->name)->toBe('Cloned Workflow')
            ->and($clone->key)->toBe('cloned-workflow')
            ->and($clone->version)->toBe(1)
            ->and($clone->is_active)->toBeFalse()
            ->and($clone->definition)->toBe($this->validDefinition);
    });

    test('cloned workflow uses default name if not provided', function () {
        $this->service->create($this->validWorkflowData);

        $clone = $this->service->clone('test-workflow', 'cloned-workflow');

        expect($clone->name)->toBe('Test Workflow (Copy)');
    });

    test('throws error when cloning nonexistent workflow', function () {
        expect(fn() => $this->service->clone('nonexistent', 'new-key'))
            ->toThrow(ValidationException::class);
    });

    test('throws error when clone key already exists', function () {
        $this->service->create($this->validWorkflowData);
        $this->service->create([
            'name' => 'Existing',
            'key' => 'existing-workflow',
            'definition' => $this->validDefinition,
        ]);

        expect(fn() => $this->service->clone('test-workflow', 'existing-workflow'))
            ->toThrow(ValidationException::class);
    });
});
