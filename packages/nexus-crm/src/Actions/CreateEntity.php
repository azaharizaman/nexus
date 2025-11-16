<?php

declare(strict_types=1);

namespace Nexus\Crm\Actions;

use Nexus\Crm\Models\CrmDefinition;
use Nexus\Crm\Models\CrmEntity;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Create Entity Action
 *
 * Creates a new CRM entity with validation and initial setup.
 */
class CreateEntity
{
    use AsAction;

    /**
     * Execute the action.
     */
    public function handle(string $entityType, string $definitionName, array $data, array $options = []): CrmEntity
    {
        // Find the definition
        $definition = CrmDefinition::where('type', $entityType)
            ->where('name', $definitionName)
            ->where('is_active', true)
            ->first();

        if (!$definition) {
            throw new \InvalidArgumentException("Definition not found: {$entityType}/{$definitionName}");
        }

        // Validate data against schema
        $this->validateData($data, $definition->schema);

        // Create the entity
        $entity = CrmEntity::create([
            'entity_type' => $entityType,
            'definition_id' => $definition->id,
            'owner_id' => $options['owner_id'] ?? (auth()->id() ?? throw new \RuntimeException('Owner ID is required')),
            'data' => $data,
            'status' => $options['initial_status'] ?? 'draft',
            'priority' => $options['priority'] ?? 5,
            'score' => $options['score'] ?? 0,
        ]);

        // Set initial stage if pipeline exists
        if ($definition->pipeline_config) {
            $this->setInitialStage($entity, $definition);
        }

        return $entity;
    }

    /**
     * Validate data against the schema.
     */
    private function validateData(array $data, array $schema): void
    {
        // Basic validation - in a real implementation, this would use
        // a proper JSON schema validator
        foreach ($schema as $field => $rules) {
            if (($rules['required'] ?? false) && empty($data[$field])) {
                throw new \InvalidArgumentException("Field '{$field}' is required");
            }
        }
    }

    /**
     * Set the initial stage for the entity.
     */
    private function setInitialStage(CrmEntity $entity, CrmDefinition $definition): void
    {
        $pipelineConfig = $definition->pipeline_config ?? [];
        $initialStageId = $pipelineConfig['initial_stage_id'] ?? null;

        if ($initialStageId) {
            $entity->update(['current_stage_id' => $initialStageId]);
        }
    }
}