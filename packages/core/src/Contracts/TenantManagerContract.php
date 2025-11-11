<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Contracts;

use Azaharizaman\Erp\Core\Models\Tenant;

/**
 * Tenant Manager Contract
 *
 * Defines the interface for tenant management operations including
 * context switching, impersonation, and tenant lifecycle management.
 */
interface TenantManagerContract
{
    /**
     * Create a new tenant with validation
     *
     * @param  array<string, mixed>  $data  Tenant data (name, domain, email, etc.)
     * @return Tenant The created tenant instance
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails
     */
    public function create(array $data): Tenant;

    /**
     * Set the active tenant in the current request context
     *
     * @param  Tenant  $tenant  The tenant to set as active
     */
    public function setActive(Tenant $tenant): void;

    /**
     * Get the current active tenant from context
     *
     * @return Tenant|null The active tenant or null if none set
     */
    public function current(): ?Tenant;

    /**
     * Impersonate a tenant for support operations
     *
     * Allows support staff to access a tenant's context while
     * maintaining an audit trail of the impersonation.
     *
     * @param  Tenant  $tenant  The tenant to impersonate
     * @param  string  $reason  The reason for impersonation (for audit)
     */
    public function impersonate(Tenant $tenant, string $reason): void;

    /**
     * Stop tenant impersonation and restore original context
     */
    public function stopImpersonation(): void;
}
