<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Nexus\Crm\Contracts\AssignmentStrategyContract;
use Nexus\Crm\Models\CrmAssignment;
use Nexus\Crm\Models\CrmEntity;

/**
 * Load Balance Assignment Strategy
 *
 * Assigns users based on current workload.
 */
class LoadBalanceAssignmentStrategy implements AssignmentStrategyContract
{
    /**
     * Resolve users using load balancing.
     */
    public function resolve(CrmEntity $entity, array $config = []): array
    {
        $availableUsers = $config['users'] ?? [];
        $role = $config['role'] ?? 'assignee';

        if (empty($availableUsers)) {
            return [];
        }

        // Find user with least assignments
        $userWorkloads = [];
        foreach ($availableUsers as $userId) {
            $workload = CrmAssignment::where('user_id', $userId)
                ->where('is_active', true)
                ->count();
            $userWorkloads[$userId] = $workload;
        }

        $selectedUser = array_keys($userWorkloads, min($userWorkloads))[0];

        return [$selectedUser => $role];
    }
}