<?php

declare(strict_types=1);

namespace Nexus\Erp\Support\Adapters\OrgStructure;

use Illuminate\Support\Collection;
use Nexus\Hrm\Contracts\OrganizationServiceContract as HrmOrganizationServiceContract;
use Nexus\OrgStructure\Contracts\OrganizationServiceContract as OrgServiceContract;

class OrganizationServiceAdapter implements HrmOrganizationServiceContract
{
    public function __construct(private readonly OrgServiceContract $org)
    {
    }

    public function getManager(string $employeeId): ?array
    {
        return $this->org->getManager($employeeId);
    }

    public function getSubordinates(string $employeeId): Collection
    {
        return $this->org->getSubordinates($employeeId);
    }
}
