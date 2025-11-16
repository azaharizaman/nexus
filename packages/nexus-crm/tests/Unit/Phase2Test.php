<?php

declare(strict_types=1);

use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Models\CrmDefinition;
use Nexus\Crm\Models\CrmPipeline;
use Nexus\Crm\Models\CrmStage;
use Nexus\Crm\Actions\CreateEntity;
use Nexus\Crm\Actions\TransitionEntity;
use Nexus\Crm\Services\CrmDashboard;

it('can create a crm entity', function () {
    // Create a definition first
    $definition = CrmDefinition::create([
        'name' => 'test_lead',
        'type' => 'lead',
        'schema' => [
            'first_name' => ['type' => 'string', 'required' => true],
            'email' => ['type' => 'string', 'required' => true],
        ],
        'is_active' => true,
    ]);

    $data = [
        'first_name' => 'John',
        'email' => 'john@example.com',
    ];

    $entity = CreateEntity::run('lead', 'test_lead', $data);

    expect($entity)->toBeInstanceOf(CrmEntity::class);
    expect($entity->entity_type)->toBe('lead');
    expect($entity->data['first_name'])->toBe('John');
});

it('can transition entity through pipeline stages', function () {
    // Create pipeline and stages
    $pipeline = CrmPipeline::create([
        'name' => 'Lead Pipeline',
        'entity_type' => 'lead',
        'config' => [],
        'is_active' => true,
    ]);

    $stage1 = CrmStage::create([
        'name' => 'New Lead',
        'pipeline_id' => $pipeline->id,
        'order' => 1,
        'config' => [],
        'is_active' => true,
    ]);

    $stage2 = CrmStage::create([
        'name' => 'Qualified',
        'pipeline_id' => $pipeline->id,
        'order' => 2,
        'config' => [],
        'is_active' => true,
    ]);

    // Create entity
    $definition = CrmDefinition::create([
        'name' => 'test_lead',
        'type' => 'lead',
        'schema' => [
            'first_name' => ['type' => 'string', 'required' => true],
        ],
        'pipeline_config' => ['initial_stage_id' => $stage1->id],
        'is_active' => true,
    ]);

    $entity = CreateEntity::run('lead', 'test_lead', ['first_name' => 'John']);

    // Transition to next stage
    $result = TransitionEntity::run($entity, $stage2->id);

    expect($result)->toBeTrue();
    expect($entity->fresh()->current_stage_id)->toBe($stage2->id);
});

it('provides dashboard data for user', function () {
    $userId = 'user-123';

    // Create definitions for different entity types
    $leadDefinition = CrmDefinition::create([
        'name' => 'test_lead',
        'type' => 'lead',
        'schema' => ['name' => ['type' => 'string', 'required' => true]],
        'is_active' => true,
    ]);

    $opportunityDefinition = CrmDefinition::create([
        'name' => 'test_opportunity',
        'type' => 'opportunity',
        'schema' => ['name' => ['type' => 'string', 'required' => true]],
        'is_active' => true,
    ]);

    $entity1 = CrmEntity::create([
        'entity_type' => 'lead',
        'definition_id' => $leadDefinition->id,
        'data' => ['name' => 'Lead 1'],
        'status' => 'pending',
        'assigned_users' => [$userId],
    ]);

    $entity2 = CrmEntity::create([
        'entity_type' => 'opportunity',
        'definition_id' => $opportunityDefinition->id,
        'data' => ['name' => 'Opp 1'],
        'status' => 'active',
        'assigned_users' => [$userId],
        'score' => 85,
    ]);

    $dashboard = app(CrmDashboard::class);
    $data = $dashboard->forUser($userId);

    expect($data)->toHaveKey('pending_leads');
    expect($data)->toHaveKey('active_opportunities');
    expect($data['pending_leads'])->toHaveCount(1);
    expect($data['active_opportunities'])->toHaveCount(1);
});