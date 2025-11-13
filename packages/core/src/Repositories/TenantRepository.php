<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Repositories;

use Nexus\Erp\Core\Contracts\TenantRepositoryContract;
use Nexus\Erp\Core\Models\Tenant;
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
     * Delete (soft delete) a tenant
     */
    public function delete(Tenant $tenant): bool
    {
        return $tenant->delete();
    }

    /**
     * Get paginated tenants with optional filters
     */
    public function paginate(int $perPage = 15, array $filters = [])
    {
        $query = Tenant::query();

        // Apply status filter
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply search filter
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get a query builder for tenants
     */
    public function query()
    {
        return Tenant::query();
    }
}
