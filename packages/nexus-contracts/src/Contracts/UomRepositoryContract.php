<?php

declare(strict_types=1);

namespace Nexus\Contracts\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Unit of Measure repository contract
 *
 * @package Nexus\Contracts
 */
interface UomRepositoryContract extends RepositoryContract
{
    /**
     * Find UOM by code
     *
     * @param string $code
     * @return Model|null
     */
    public function findByCode(string $code): ?Model;

    /**
     * Find all UOMs in a category
     *
     * @param string $categoryId
     * @return Collection
     */
    public function findByCategory(string $categoryId): Collection;

    /**
     * Check if UOM code exists
     *
     * @param string $code
     * @param string|null $excludeId
     * @return bool
     */
    public function codeExists(string $code, ?string $excludeId = null): bool;
}
