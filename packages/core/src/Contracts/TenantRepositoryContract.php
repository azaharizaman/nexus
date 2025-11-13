<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Contracts;

use Nexus\Erp\Core\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

/**
 * Tenant Repository Contract
 *
 * Defines the interface for tenant data access operations.
 */
interface TenantRepositoryContract
{
    /**
     * Find a tenant by ID
     *
     * @param  string  $id  The tenant UUID
     */
    public function findById(string $id): ?Tenant;

    /**
     * Find a tenant by domain
     *
     * @param  string  $domain  The tenant domain
     */
    public function findByDomain(string $domain): ?Tenant;

    /**
     * Get all tenants
     *
     * @return Collection<int, Tenant>
     */
    public function all(): Collection;

    /**
     * Create a new tenant
     *
     * @param  array<string, mixed>  $data  Tenant data
     */
    public function create(array $data): Tenant;

    /**
     * Update an existing tenant
     *
     * @param  Tenant  $tenant  The tenant to update
     * @param  array<string, mixed>  $data  Updated data
     */
    public function update(Tenant $tenant, array $data): bool;

    /**
     * Delete (soft delete) a tenant
     *
     * @param  Tenant  $tenant  The tenant to delete
     */
    public function delete(Tenant $tenant): bool;

    /**
     * Get paginated tenants with optional filters
     *
     * @param  int  $perPage  Number of items per page
     * @param  array<string, mixed>  $filters  Filter criteria (status, search)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $filters = []);

    /**
     * Get a query builder for tenants
     *
     * @return \Illuminate\Database\Eloquent\Builder<Tenant>
     */
    public function query();
}
