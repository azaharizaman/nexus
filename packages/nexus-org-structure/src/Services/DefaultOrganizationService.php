<?php

declare(strict_types=1);

namespace Nexus\OrgStructure\Services;

use Illuminate\Support\Collection;
use Nexus\OrgStructure\Contracts\OrganizationServiceContract;
use Nexus\OrgStructure\Models\Assignment;
use Nexus\OrgStructure\Models\OrgUnit;
use Nexus\OrgStructure\Models\Position;

class DefaultOrganizationService implements OrganizationServiceContract
{
    public function getOrgUnit(string $orgUnitId): ?array
    {
        $unit = OrgUnit::query()->find($orgUnitId);
        return $unit?->toArray();
    }

    public function getPosition(string $positionId): ?array
    {
        $pos = Position::query()->find($positionId);
        return $pos?->toArray();
    }

    public function getManager(string $employeeId): ?array
    {
        $assignment = Assignment::query()
            ->where('employee_id', $employeeId)
            ->where('is_primary', true)
            ->latest('effective_from')
            ->first();

        if (!$assignment) {
            return null;
        }

        // In a minimal implementation, manager can be derived from reporting lines table via a query layer
        // For now, return null; orchestration can enrich this via reporting lines if needed
        return null;
    }

    public function getSubordinates(string $employeeId): Collection
    {
        // Placeholder - return empty collection until reporting query implemented
        return collect();
    }

    public function getAssignmentsForEmployee(string $employeeId): Collection
    {
        return Assignment::query()
            ->where('employee_id', $employeeId)
            ->orderByDesc('effective_from')
            ->get()
            ->map(fn ($a) => $a->toArray());
    }

    public function resolveReportingChain(string $employeeId): Collection
    {
        // Placeholder implementation; can be enhanced later
        return collect();
    }
}
