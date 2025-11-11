<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Repositories;

use Azaharizaman\Erp\Core\Contracts\TenantRepositoryContract;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

/**
 * Tenant Repository
 *
 * Handles data access operations for tenants.
 */
class TenantRepository implements TenantRepositoryContract
{
    /**
     * Find a tenant by ID
     */
    public function findById(string $id): ?Tenant
    {
        return Tenant::find($id);
    }

    /**
     * Find a tenant by domain
     */
    public function findByDomain(string $domain): ?Tenant
    {
        return Tenant::where('domain', $domain)->first();
    }

    /**
     * Get all tenants
     *
     * @return Collection<int, Tenant>
     */
    public function all(): Collection
    {
        return Tenant::all();
    }

    /**
     * Create a new tenant
     */
    public function create(array $data): Tenant
    {
        return Tenant::create($data);
    }

    /**
     * Update an existing tenant
     */
    public function update(Tenant $tenant, array $data): bool
    {
        return $tenant->update($data);
    }

    /**
     * Archive (soft delete) a tenant
     */
    public function archive(Tenant $tenant): bool
    {
        return $tenant->delete();
    }

    /**
     * Get a query builder for tenants
     */
    public function query()
    {
        return Tenant::query();
    }
}
