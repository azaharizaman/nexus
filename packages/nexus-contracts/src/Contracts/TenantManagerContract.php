<?php

declare(strict_types=1);

namespace Nexus\Contracts\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Tenant manager service contract
 *
 * @package Nexus\Contracts
 */
interface TenantManagerContract
{
    /**
     * Get the currently active tenant
     *
     * @return Model|null
     */
    public function current(): ?Model;

    /**
     * Set the active tenant
     *
     * @param Model|null $tenant
     * @return void
     */
    public function setActive(?Model $tenant): void;

    /**
     * Get the current tenant ID
     *
     * @return string|int|null
     */
    public function getCurrentId(): string|int|null;

    /**
     * Clear the active tenant
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Check if a tenant is currently active
     *
     * @return bool
     */
    public function hasActive(): bool;
}
