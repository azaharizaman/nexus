<?php

declare(strict_types=1);

namespace Nexus\Uom\Contracts;

/**
 * UOM Conversion Repository Contract
 *
 * Defines persistence operations for UOM conversions.
 */
interface UomConversionRepositoryInterface
{
    /**
     * Find a conversion by ID
     *
     * @param int $id
     * @return UomConversionInterface|null
     */
    public function findById(int $id): ?UomConversionInterface;

    /**
     * Find a conversion between two units
     *
     * @param int $sourceUnitId
     * @param int $targetUnitId
     * @return UomConversionInterface|null
     */
    public function findConversion(int $sourceUnitId, int $targetUnitId): ?UomConversionInterface;

    /**
     * Get all conversions for a specific unit
     *
     * @param int $unitId
     * @return array<int, UomConversionInterface>
     */
    public function findByUnit(int $unitId): array;

    /**
     * Create a new conversion
     *
     * @param array<string, mixed> $data
     * @return UomConversionInterface
     */
    public function create(array $data): UomConversionInterface;

    /**
     * Update an existing conversion
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return UomConversionInterface
     */
    public function update(int $id, array $data): UomConversionInterface;

    /**
     * Delete a conversion
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}
