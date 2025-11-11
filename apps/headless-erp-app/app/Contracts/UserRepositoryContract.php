<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * User Repository Contract
 *
 * Defines the interface for user data access operations with tenant scoping.
 * All implementations must ensure tenant isolation for security.
 */
interface UserRepositoryContract
{
    /**
     * Find a user by ID
     *
     * @param  string  $id  UUID of the user
     * @return User|null The user or null if not found
     */
    public function findById(string $id): ?User;

    /**
     * Find a user by email within a specific tenant
     *
     * @param  string  $email  User's email address
     * @param  string  $tenantId  UUID of the tenant
     * @return User|null The user or null if not found
     */
    public function findByEmail(string $email, string $tenantId): ?User;

    /**
     * Create a new user
     *
     * @param  array<string, mixed>  $data  User data including name, email, password, tenant_id
     * @return User The created user
     */
    public function create(array $data): User;

    /**
     * Update an existing user
     *
     * @param  User  $user  The user to update
     * @param  array<string, mixed>  $data  Updated user data
     * @return User The updated user
     */
    public function update(User $user, array $data): User;

    /**
     * Soft delete a user
     *
     * @param  User  $user  The user to delete
     * @return bool True if deleted successfully
     */
    public function delete(User $user): bool;

    /**
     * Get users by tenant with optional filters and pagination
     *
     * @param  string  $tenantId  UUID of the tenant
     * @param  array<string, mixed>  $filters  Optional filters (status, search, etc.)
     * @param  int  $perPage  Number of items per page
     * @return LengthAwarePaginator Paginated users
     */
    public function getByTenant(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Count total users in a tenant
     *
     * @param  string  $tenantId  UUID of the tenant
     * @return int Total count
     */
    public function countByTenant(string $tenantId): int;

    /**
     * Get all active users in a tenant
     *
     * @param  string  $tenantId  UUID of the tenant
     * @return Collection<int, User> Collection of active users
     */
    public function getActiveUsers(string $tenantId): Collection;

    /**
     * Unlock a locked user account
     *
     * @param  User  $user  The user to unlock
     * @return bool True if unlocked successfully
     */
    public function unlockAccount(User $user): bool;
}
