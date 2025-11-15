<?php

declare(strict_types=1);

namespace Nexus\OrgStructure\Contracts;

use Illuminate\Support\Collection;

interface OrganizationServiceContract
{
    public function getOrgUnit(string $orgUnitId): ?array;

    public function getPosition(string $positionId): ?array;

    public function getManager(string $employeeId): ?array;

    public function getSubordinates(string $employeeId): Collection;

    public function getAssignmentsForEmployee(string $employeeId): Collection;

    /**
     * Returns chain from employee up to root (inclusive) as array of assignments/org-units.
     */
    public function resolveReportingChain(string $employeeId): Collection;
}
