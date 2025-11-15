<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Workflows;

use Nexus\Manufacturing\Enums\WorkOrderStatus;

class WorkOrderWorkflow
{
    /**
     * Get the workflow definition for work orders.
     */
    public static function getDefinition(): array
    {
        return [
            'name' => 'work_order_workflow',
            'description' => 'Manages work order lifecycle from planning to completion',
            'initial_state' => WorkOrderStatus::PLANNED->value,
            'states' => [
                WorkOrderStatus::PLANNED->value => [
                    'label' => 'Planned',
                    'description' => 'Work order is planned but not yet released',
                ],
                WorkOrderStatus::RELEASED->value => [
                    'label' => 'Released',
                    'description' => 'Work order is released and ready for production',
                ],
                WorkOrderStatus::IN_PRODUCTION->value => [
                    'label' => 'In Production',
                    'description' => 'Work order is actively being produced',
                ],
                WorkOrderStatus::ON_HOLD->value => [
                    'label' => 'On Hold',
                    'description' => 'Work order is temporarily paused',
                ],
                WorkOrderStatus::COMPLETED->value => [
                    'label' => 'Completed',
                    'description' => 'Work order is completed',
                ],
                WorkOrderStatus::CANCELLED->value => [
                    'label' => 'Cancelled',
                    'description' => 'Work order is cancelled',
                ],
            ],
            'transitions' => [
                'release' => [
                    'from' => [WorkOrderStatus::PLANNED->value],
                    'to' => WorkOrderStatus::RELEASED->value,
                    'label' => 'Release',
                    'description' => 'Release work order for production',
                ],
                'start_production' => [
                    'from' => [WorkOrderStatus::RELEASED->value],
                    'to' => WorkOrderStatus::IN_PRODUCTION->value,
                    'label' => 'Start Production',
                    'description' => 'Begin production on work order',
                ],
                'pause' => [
                    'from' => [WorkOrderStatus::IN_PRODUCTION->value],
                    'to' => WorkOrderStatus::ON_HOLD->value,
                    'label' => 'Pause',
                    'description' => 'Temporarily pause work order',
                ],
                'resume' => [
                    'from' => [WorkOrderStatus::ON_HOLD->value],
                    'to' => WorkOrderStatus::IN_PRODUCTION->value,
                    'label' => 'Resume',
                    'description' => 'Resume paused work order',
                ],
                'complete' => [
                    'from' => [WorkOrderStatus::IN_PRODUCTION->value],
                    'to' => WorkOrderStatus::COMPLETED->value,
                    'label' => 'Complete',
                    'description' => 'Complete work order',
                ],
                'cancel_from_planned' => [
                    'from' => [WorkOrderStatus::PLANNED->value],
                    'to' => WorkOrderStatus::CANCELLED->value,
                    'label' => 'Cancel',
                    'description' => 'Cancel planned work order',
                ],
                'cancel_from_released' => [
                    'from' => [WorkOrderStatus::RELEASED->value],
                    'to' => WorkOrderStatus::CANCELLED->value,
                    'label' => 'Cancel',
                    'description' => 'Cancel released work order',
                ],
                'cancel_from_production' => [
                    'from' => [WorkOrderStatus::IN_PRODUCTION->value],
                    'to' => WorkOrderStatus::CANCELLED->value,
                    'label' => 'Cancel',
                    'description' => 'Cancel work order in production',
                ],
                'cancel_from_hold' => [
                    'from' => [WorkOrderStatus::ON_HOLD->value],
                    'to' => WorkOrderStatus::CANCELLED->value,
                    'label' => 'Cancel',
                    'description' => 'Cancel work order on hold',
                ],
            ],
        ];
    }

    /**
     * Get available transitions from current state.
     */
    public static function getAvailableTransitions(WorkOrderStatus $currentState): array
    {
        $definition = self::getDefinition();
        $availableTransitions = [];

        foreach ($definition['transitions'] as $transitionName => $transition) {
            if (in_array($currentState->value, $transition['from'])) {
                $availableTransitions[$transitionName] = [
                    'to' => $transition['to'],
                    'label' => $transition['label'],
                    'description' => $transition['description'],
                ];
            }
        }

        return $availableTransitions;
    }

    /**
     * Check if a transition is valid from current state.
     */
    public static function canTransition(WorkOrderStatus $currentState, string $transitionName): bool
    {
        $availableTransitions = self::getAvailableTransitions($currentState);
        return isset($availableTransitions[$transitionName]);
    }

    /**
     * Get the target state for a transition.
     */
    public static function getTransitionTarget(string $transitionName): ?string
    {
        $definition = self::getDefinition();
        return $definition['transitions'][$transitionName]['to'] ?? null;
    }
}
