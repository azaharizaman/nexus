<?php

declare(strict_types=1);

namespace Edward\Repositories;

use Nexus\Erp\Support\Contracts\UomRepositoryContract;
use Nexus\Erp\Enums\UomCategory;
use Nexus\Erp\Models\Uom;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * Database UOM Repository
 *
 * Handles all database operations for units of measure with tenant context.
 */
class DatabaseUomRepository implements UomRepositoryContract
{
    /**
     * Find UOM by code
     *
     * @param  string  $code
     * @return Uom|null
     */
    public function findByCode(string $code): ?Uom
    {
        return Uom::where('code', $code)
            ->first();
    }

    /**
     * Find all UOMs in a category
     *
     * @param  UomCategory  $category
     * @return Collection
     */
    public function findByCategory(UomCategory $category): Collection
    {
        return Uom::category($category)
            ->active()
            ->get();
    }

    /**
     * Find all active UOMs
     *
     * @return Collection
     */
    public function findActive(): Collection
    {
        return Uom::active()
            ->get();
    }

    /**
     * Find all system UOMs
     *
     * @return Collection
     */
    public function findSystem(): Collection
    {
        return Uom::system()
            ->active()
            ->get();
    }

    /**
     * Find all custom UOMs for current tenant
     *
     * @return Collection
     */
    public function findCustom(): Collection
    {
        return Uom::custom()
            ->active()
            ->get();
    }

    /**
     * Create a new UOM
     *
     * @param  array<string, mixed>  $data
     * @return Uom
     */
    public function create(array $data): Uom
    {
        // System UOMs should have null tenant_id to be globally available
        if (!empty($data['is_system'])) {
            $data['tenant_id'] = null;
        }

        $uom = Uom::create($data);

        return $uom->fresh();
    }

    /**
     * Update an existing UOM
     *
     * @param  Uom  $uom
     * @param  array<string, mixed>  $data
     * @return Uom
     */
    public function update(Uom $uom, array $data): Uom
    {
        $uom->update($data);

        return $uom->fresh();
    }

    /**
     * Delete a UOM (soft delete)
     *
     * @param  Uom  $uom
     * @return bool
     * @throws RuntimeException
     */
    public function delete(Uom $uom): bool
    {
        if ($this->isInUse($uom)) {
            throw new RuntimeException(
                sprintf(
                    'Cannot delete UOM "%s" because it is in use: %s',
                    $uom->code,
                    json_encode($this->getReferences($uom), JSON_THROW_ON_ERROR)
                )
            );
        }

        return (bool) $uom->delete();
    }

    /**
     * Force delete a UOM (permanent deletion)
     *
     * @param  Uom  $uom
     * @return bool
     */
    public function forceDelete(Uom $uom): bool
    {
        return (bool) $uom->forceDelete();
    }

    /**
     * Check if UOM is in use
     *
     * Checks if UOM is referenced in:
     * - inventory_items (uom_id)
     * - purchase_order_line_items (uom_id)
     * - sales_order_line_items (uom_id)
     *
     * @param  Uom  $uom
     * @return bool
     */
    public function isInUse(Uom $uom): bool
    {
        $references = $this->getReferences($uom);

        return array_sum($references) > 0;
    }

    /**
     * Get all references to a UOM
     *
     * @param  Uom  $uom
     * @return array<string, int>
     */
    public function getReferences(Uom $uom): array
    {
        $references = [];

        // Check inventory_items table (if exists)
        try {
            if (! empty($uom->id)) {
                // These would be actual queries to other modules
                // For now, returning empty array as placeholder
                $references['inventory_items'] = 0;
                $references['purchase_order_items'] = 0;
                $references['sales_order_items'] = 0;
            }
        } catch (\Exception $e) {
            // Table might not exist yet
        }

        return array_filter($references, fn ($count) => $count > 0);
    }
}
