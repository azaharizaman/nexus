<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Contracts\AssignmentStrategyContract;

/**
 * Round Robin Assignment Strategy
 *
 * Assigns users in a round-robin fashion.
 */
class RoundRobinAssignmentStrategy implements AssignmentStrategyContract
{
    /**
     * Resolve users using round-robin assignment.
     */
    public function resolve(CrmEntity $entity, array $config = []): array
    {
        $availableUsers = $config['users'] ?? [];
        $role = $config['role'] ?? 'assignee';

        if (empty($availableUsers)) {
            return [];
        }

        // Simple round-robin: use hash of entity ID to determine assignment
        $userIndex = crc32($entity->id) % count($availableUsers);
        $selectedUser = $availableUsers[$userIndex];

        return [$selectedUser => $role];
    }
}