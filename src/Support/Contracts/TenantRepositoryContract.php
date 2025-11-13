<?php

declare(strict_types=1);

namespace Nexus\Erp\Support\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Tenant repository contract
 *
 * @package Nexus\Erp\Support\Contracts
 */
interface TenantRepositoryContract extends RepositoryContract
{
    /**
     * Get paginated tenants with optional filters
     *
     * @param int $perPage Number of items per page
     * @param array<string, mixed> $filters Filter criteria
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Find tenant by domain
     *
     * @param string $domain
     * @return Model|null
     */
    public function findByDomain(string $domain): ?Model;
}
