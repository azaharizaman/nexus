<?php

declare(strict_types=1);

namespace Nexus\Erp\Repositories;

use Nexus\Erp\Contracts\UserRepositoryContract;
use Nexus\Erp\Models\User;
use Nexus\Erp\Enums\UserStatus;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * User Repository
 *
 * Implements user data access operations with automatic tenant scoping.
 * Uses the BelongsToTenant trait on the User model for tenant isolation.
 */
#[Bind(UserRepositoryContract::class)]
class UserRepository implements UserRepositoryContract
{
    /**
     * Create a new repository instance
     */
    public function __construct(
        private readonly User $model
    ) {
    }

    /**
     * Find a user by ID
     *
     * @param  string  $id  UUID of the user
     * @return User|null The user or null if not found
     */
    public function findById(string $id): ?User
    {
        return $this->model->find($id);
    }

    /**
     * Find a user by email within a specific tenant
     *
     * @param  string  $email  User's email address
     * @param  string  $tenantId  UUID of the tenant
     * @return User|null The user or null if not found
     */
    public function findByEmail(string $email, string $tenantId): ?User
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();
    }

    /**
     * Create a new user
     *
     * @param  array<string, mixed>  $data  User data including name, email, password, tenant_id
     * @return User The created user
     *
     * @throws \RuntimeException If email already exists in tenant
     */
    public function create(array $data): User
    {
        // Validate email uniqueness per tenant
        if ($this->emailExistsInTenant($data['email'], $data['tenant_id'])) {
            throw new \RuntimeException('Email already exists in this tenant');
        }

        // Hash password if provided and not already hashed
        if (isset($data['password']) && ! Hash::isHashed($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Set default status if not provided
        if (! isset($data['status'])) {
            $data['status'] = UserStatus::ACTIVE;
        }

        return $this->model->create($data);
    }

    /**
     * Update an existing user
     *
     * @param  User  $user  The user to update
     * @param  array<string, mixed>  $data  Updated user data
     * @return User The updated user
     *
     * @throws \RuntimeException If email conflicts with another user in tenant
     */
    public function update(User $user, array $data): User
    {
        // If email is being changed, validate uniqueness
        if (isset($data['email']) && $data['email'] !== $user->email) {
            if ($this->emailExistsInTenant($data['email'], $user->tenant_id, $user->id)) {
                throw new \RuntimeException('Email already exists in this tenant');
            }
        }

        // Hash password if provided and not already hashed
        if (isset($data['password']) && ! Hash::isHashed($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return $user->fresh();
    }

    /**
     * Soft delete a user
     *
     * @param  User  $user  The user to delete
     * @return bool True if deleted successfully
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Get users by tenant with optional filters and pagination
     *
     * @param  string  $tenantId  UUID of the tenant
     * @param  array<string, mixed>  $filters  Optional filters (status, search, etc.)
     * @param  int  $perPage  Number of items per page
     * @return LengthAwarePaginator Paginated users
     */
    public function getByTenant(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);

        // Apply status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply search filter (name or email)
        if (isset($filters['search']) && ! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply is_admin filter
        if (isset($filters['is_admin'])) {
            $query->where('is_admin', $filters['is_admin']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Count total users in a tenant
     *
     * @param  string  $tenantId  UUID of the tenant
     * @return int Total count
     */
    public function countByTenant(string $tenantId): int
    {
        return $this->model->where('tenant_id', $tenantId)->count();
    }

    /**
     * Get all active users in a tenant
     *
     * @param  string  $tenantId  UUID of the tenant
     * @return Collection<int, User> Collection of active users
     */
    public function getActiveUsers(string $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', UserStatus::ACTIVE)
            ->get();
    }

    /**
     * Unlock a locked user account
     *
     * @param  User  $user  The user to unlock
     * @return bool True if unlocked successfully
     */
    public function unlockAccount(User $user): bool
    {
        $user->locked_until = null;
        $user->failed_login_attempts = 0;

        return $user->save();
    }

    /**
     * Check if email exists in tenant
     *
     * @param  string  $email  Email to check
     * @param  string  $tenantId  Tenant ID
     * @param  string|null  $excludeUserId  User ID to exclude from check (for updates)
     * @return bool True if email exists
     */
    private function emailExistsInTenant(string $email, string $tenantId, ?string $excludeUserId = null): bool
    {
        $query = $this->model
            ->where('tenant_id', $tenantId)
            ->where('email', $email);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->exists();
    }
}
