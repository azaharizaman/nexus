<?php

declare(strict_types=1);

namespace Nexus\Erp\Contracts;

use Nexus\Erp\Enums\UomCategory;
use Nexus\Erp\Models\Uom;
use Illuminate\Database\Eloquent\Collection;

/**
 * UOM Repository Contract
 *
 * Defines the interface for accessing and managing units of measure.
 * Implementations must handle tenant context, validation, and reference checking.
 */
interface UomRepositoryContract
{
    /**
     * Find UOM by code
     *
     * Searches for UOM by code within the current tenant context.
     *
     * @param  string  $code  UOM code (e.g., 'm', 'kg', 'L')
     * @return Uom|null
     */
    public function findByCode(string $code): ?Uom;

    /**
     * Find all UOMs in a category
     *
     * Returns all active UOMs (system and custom) in the given category.
     *
     * @param  UomCategory  $category
     * @return Collection
     */
    public function findByCategory(UomCategory $category): Collection;

    /**
     * Find all active UOMs
     *
     * Returns only active UOMs, respecting tenant context.
     *
     * @return Collection
     */
    public function findActive(): Collection;

    /**
     * Find all system UOMs
     *
     * System UOMs are global and not tenant-specific.
     *
     * @return Collection
     */
    public function findSystem(): Collection;

    /**
     * Find all custom UOMs for current tenant
     *
     * Custom UOMs are tenant-specific.
     *
     * @return Collection
     */
    public function findCustom(): Collection;

    /**
     * Create a new UOM
     *
     * @param  array<string, mixed>  $data  UOM data (code, name, symbol, category, conversion_factor, etc.)
     * @return Uom
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(array $data): Uom;

    /**
     * Update an existing UOM
     *
     * @param  Uom  $uom
     * @param  array<string, mixed>  $data  Updated data
     * @return Uom
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Uom $uom, array $data): Uom;

    /**
     * Delete a UOM (soft delete)
     *
     * @param  Uom  $uom
     * @return bool
     * @throws \RuntimeException If UOM is in use
     */
    public function delete(Uom $uom): bool;

    /**
     * Force delete a UOM (permanent deletion)
     *
     * @param  Uom  $uom
     * @return bool
     */
    public function forceDelete(Uom $uom): bool;

    /**
     * Check if UOM is in use (referenced by other models)
     *
     * Prevents deletion of UOMs that are referenced in inventory items,
     * purchase orders, sales orders, etc.
     *
     * @param  Uom  $uom
     * @return bool
     */
    public function isInUse(Uom $uom): bool;

    /**
     * Get all references to a UOM
     *
     * Returns detailed information about where the UOM is used.
     *
     * @param  Uom  $uom
     * @return array<string, int>  Array of model name => count
     */
    public function getReferences(Uom $uom): array;
}
