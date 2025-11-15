<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

use Illuminate\Support\Collection;

/**
 * HRM-facing contract for organization queries.
 * Implementations are bound in ERP and may delegate to nexus-org-structure.
 */
interface OrganizationServiceContract
{
    public function getManager(string $employeeId): ?array;

    public function getSubordinates(string $employeeId): Collection;
}
