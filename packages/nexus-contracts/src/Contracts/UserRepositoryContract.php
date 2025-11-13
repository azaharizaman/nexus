<?php

declare(strict_types=1);

namespace Nexus\Contracts\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * User repository contract
 *
 * @package Nexus\Contracts
 */
interface UserRepositoryContract extends RepositoryContract
{
    /**
     * Find user by email
     *
     * @param string $email
     * @param string|null $tenantId
     * @return Model|null
     */
    public function findByEmail(string $email, ?string $tenantId = null): ?Model;

    /**
     * Find user by email and tenant
     *
     * @param string $email
     * @param string $tenantId
     * @return Model|null
     */
    public function findByEmailAndTenant(string $email, string $tenantId): ?Model;
}
