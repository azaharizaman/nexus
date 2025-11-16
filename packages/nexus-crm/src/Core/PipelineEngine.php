<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Nexus\Crm\Enums\PipelineAction;
use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Models\CrmPipeline;
use Nexus\Crm\Models\CrmStage;

/**
 * Pipeline Engine
 *
 * Manages the execution of CRM pipelines with conditional transitions,
 * stage progression, and automated actions.
 */
class PipelineEngine
{
    public function __construct(
        private ConditionEvaluatorManager $conditionEvaluator,
        private AssignmentStrategyResolver $assignmentResolver,
        private IntegrationManager $integrationManager
    ) {}

    /**
     * Execute a pipeline transition for an entity.
     */
    public function transition(CrmEntity $entity, string $targetStageId, array $context = []): bool
    {
        $currentStage = $entity->currentStage;
        $targetStage = CrmStage::find($targetStageId);

        if (!$targetStage) {
            throw new \InvalidArgumentException("Target stage {$targetStageId} not found");
        }

        // Check if transition is allowed
        if (!$this->canTransition($entity, $currentStage, $targetStage, $context)) {
            return false;
        }

        // Execute exit actions for current stage
        if ($currentStage) {
            $this->executeStageActions($entity, $currentStage, 'exit', $context);
        }

        // Update entity stage
        $entity->update(['current_stage_id' => $targetStageId]);

        // Execute entry actions for target stage
        $this->executeStageActions($entity, $targetStage, 'entry', $context);

        // Check for automatic transitions
        $this->checkAutoTransitions($entity, $targetStage, $context);

        return true;
    }

    /**
     * Check if a transition is allowed.
     */
    public function canTransition(CrmEntity $entity, ?CrmStage $fromStage, CrmStage $toStage, array $context = []): bool
    {
        // Check stage configuration for transition conditions
        $config = $toStage->config ?? [];

        if (isset($config['entry_conditions'])) {
            foreach ($config['entry_conditions'] as $condition) {
                if (!$this->conditionEvaluator->evaluate($condition, $entity, $context)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Execute actions for a stage (entry/exit).
     */
    private function executeStageActions(CrmEntity $entity, CrmStage $stage, string $type, array $context = []): void
    {
        $config = $stage->config ?? [];
        $actions = $config["{$type}_actions"] ?? [];

        foreach ($actions as $action) {
            $this->executeAction($entity, $action, $context);
        }
    }

    /**
     * Execute a single action.
     */
    private function executeAction(CrmEntity $entity, array $action, array $context = []): void
    {
        $actionType = $action['type'] ?? '';

        match ($actionType) {
            PipelineAction::UPDATE_FIELD->value => $this->updateEntityField($entity, $action),
            PipelineAction::ASSIGN_USERS->value => $this->assignUsers($entity, $action),
            PipelineAction::SEND_NOTIFICATION->value => $this->sendNotification($entity, $action),
            PipelineAction::CREATE_TIMER->value => $this->createTimer($entity, $action),
            PipelineAction::EXECUTE_INTEGRATION->value => $this->executeIntegration($entity, $action, $context),
            default => throw new \InvalidArgumentException("Unknown action type: {$actionType}")
        };
    }

    /**
     * Update an entity field.
     */
    private function updateEntityField(CrmEntity $entity, array $action): void
    {
        $field = $action['field'] ?? '';
        $value = $action['value'] ?? null;

        if (!$field) {
            return;
        }

        $data = $entity->data ?? [];
        $data[$field] = $value;
        $entity->update(['data' => $data]);
    }

    /**
     * Assign users to the entity.
     */
    private function assignUsers(CrmEntity $entity, array $action): void
    {
        $strategy = $action['strategy'] ?? 'manual';
        $users = $this->assignmentResolver->resolve($strategy, $entity, $action);

        // Create assignments
        foreach ($users as $userId => $role) {
            $entity->assignments()->create([
                'user_id' => $userId,
                'role' => $role,
                'assigned_by' => auth()->id() ?? config('crm.system_user_id', 'system'),
                'assigned_at' => now(),
            ]);
        }
    }

    /**
     * Send a notification.
     */
    private function sendNotification(CrmEntity $entity, array $action): void
    {
        // Implementation depends on notification system
        // For now, just log it
        Log::info('CRM Notification', [
            'entity_id' => $entity->id,
            'action' => $action
        ]);
    }

    /**
     * Create a timer for the entity.
     */
    private function createTimer(CrmEntity $entity, array $action): void
    {
        $entity->timers()->create([
            'name' => $action['name'] ?? 'Auto Timer',
            'description' => $action['description'] ?? '',
            'type' => $action['timer_type'] ?? 'stage_timeout',
            'scheduled_at' => now()->addMinutes($action['delay_minutes'] ?? 60),
            'action_config' => $action['action_config'] ?? [],
        ]);
    }

    /**
     * Execute an integration.
     */
    private function executeIntegration(CrmEntity $entity, array $action, array $context = []): void
    {
        $this->integrationManager->execute($action['integration_type'], $entity, $action, $context);
    }

    /**
     * Check for automatic transitions from the current stage.
     */
    private function checkAutoTransitions(CrmEntity $entity, CrmStage $stage, array $context = []): void
    {
        $config = $stage->config ?? [];
        $autoTransitions = $config['auto_transitions'] ?? [];

        foreach ($autoTransitions as $transition) {
            $condition = $transition['condition'] ?? null;
            $targetStageId = $transition['target_stage_id'] ?? null;

            if ($condition && $targetStageId && $this->conditionEvaluator->evaluate($condition, $entity, $context)) {
                // Execute auto transition
                $this->transition($entity, $targetStageId, $context);
                break; // Only execute first matching auto transition
            }
        }
    }

    /**
     * Get available transitions for an entity.
     */
    public function getAvailableTransitions(CrmEntity $entity): Collection
    {
        $currentStage = $entity->currentStage;
        if (!$currentStage) {
            return collect();
        }

        $pipeline = $currentStage->pipeline;
        $allStages = $pipeline->stages;

        return $allStages->filter(function ($stage) use ($entity, $currentStage) {
            return $stage->id !== $currentStage->id && $this->canTransition($entity, $currentStage, $stage);
        });
    }
}