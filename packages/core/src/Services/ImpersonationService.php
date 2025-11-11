<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Services;

use Azaharizaman\Erp\Core\Contracts\TenantManagerContract;
use Azaharizaman\Erp\Core\Contracts\TenantRepositoryContract;
use Azaharizaman\Erp\Core\Events\TenantImpersonationEndedEvent;
use Azaharizaman\Erp\Core\Events\TenantImpersonationStartedEvent;
use Azaharizaman\Erp\Core\Models\Tenant;
use App\Models\User;
use App\Support\Contracts\ActivityLoggerContract;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

/**
 * Impersonation Service
 *
 * Manages tenant impersonation for administrative and support purposes.
 * Stores impersonation state in Redis with configurable timeout.
 */
class ImpersonationService
{
    /**
     * Redis key prefix for impersonation data
     */
    protected const CACHE_PREFIX = 'impersonation:';

    /**
     * Create a new impersonation service instance
     */
    public function __construct(
        protected readonly TenantManagerContract $tenantManager,
        protected readonly TenantRepositoryContract $tenantRepository,
        protected readonly ActivityLoggerContract $activityLogger
    ) {}

    /**
     * Start impersonation of a tenant
     *
     * @param  User  $user  The user starting impersonation
     * @param  Tenant  $tenant  The tenant to impersonate
     * @param  string  $reason  The reason for impersonation
     *
     * @throws AuthorizationException If user is not authorized
     * @throws \RuntimeException If user is not authenticated
     */
    public function startImpersonation(User $user, Tenant $tenant, string $reason): void
    {
        // Check authorization using Gate
        if (! Gate::forUser($user)->allows('impersonate-tenant', $tenant)) {
            throw new AuthorizationException('Unauthorized to impersonate this tenant');
        }

        // Get original tenant ID (may be null for system admins)
        $originalTenantId = $user->tenant_id;

        // Store impersonation context in Redis
        $cacheKey = $this->getCacheKey($user->id);
        $timeout = config('erp-core.impersonation_timeout', 3600);

        $impersonationData = [
            'original_tenant_id' => $originalTenantId,
            'target_tenant_id' => $tenant->id,
            'reason' => $reason,
            'started_at' => now()->timestamp,
            'user_id' => $user->id,
        ];

        Cache::put($cacheKey, $impersonationData, $timeout);

        // Set new tenant as active
        $this->tenantManager->setActive($tenant);

        // Log activity
        $this->activityLogger->log(
            'Tenant impersonation started',
            $tenant,
            $user,
            [
                'reason' => $reason,
                'original_tenant_id' => $originalTenantId,
            ]
        );

        // Dispatch event
        event(new TenantImpersonationStartedEvent($tenant, $user->id, $reason));
    }

    /**
     * End impersonation and restore original tenant
     *
     * @param  User  $user  The user ending impersonation
     */
    public function endImpersonation(User $user): void
    {
        $cacheKey = $this->getCacheKey($user->id);
        $impersonationData = Cache::get($cacheKey);

        if ($impersonationData === null) {
            // No active impersonation
            return;
        }

        // Calculate duration
        $duration = now()->timestamp - $impersonationData['started_at'];

        // Get target tenant for event
        $targetTenant = $this->tenantRepository->findById((string) $impersonationData['target_tenant_id']);

        // Restore original tenant if it existed
        if ($impersonationData['original_tenant_id'] !== null) {
            $originalTenant = $this->tenantRepository->findById((string) $impersonationData['original_tenant_id']);
            if ($originalTenant !== null) {
                $this->tenantManager->setActive($originalTenant);
            }
        }

        // Log activity
        if ($targetTenant !== null) {
            $this->activityLogger->log(
                'Tenant impersonation ended',
                $targetTenant,
                $user,
                [
                    'duration' => $duration,
                    'original_tenant_id' => $impersonationData['original_tenant_id'],
                ]
            );
        }

        // Clear impersonation cache
        Cache::forget($cacheKey);

        // Dispatch event
        if ($targetTenant !== null) {
            event(new TenantImpersonationEndedEvent($targetTenant, $user->id, $duration));
        }
    }

    /**
     * Check if user is currently impersonating a tenant
     *
     * @param  User  $user  The user to check
     */
    public function isImpersonating(User $user): bool
    {
        $cacheKey = $this->getCacheKey($user->id);

        return Cache::has($cacheKey);
    }

    /**
     * Get the original tenant before impersonation
     *
     * @param  User  $user  The user
     */
    public function getOriginalTenant(User $user): ?Tenant
    {
        $cacheKey = $this->getCacheKey($user->id);
        $impersonationData = Cache::get($cacheKey);

        if ($impersonationData === null || $impersonationData['original_tenant_id'] === null) {
            return null;
        }

        return $this->tenantRepository->findById((string) $impersonationData['original_tenant_id']);
    }

    /**
     * Get the Redis cache key for a user's impersonation
     *
     * @param  int  $userId  The user ID
     */
    protected function getCacheKey(int $userId): string
    {
        return self::CACHE_PREFIX . $userId;
    }
}
