<?php

declare(strict_types=1);

namespace Nexus\Uom\Contracts;

/**
 * UOM Unit Repository Contract
 *
 * Defines persistence operations for UOM units.
 * Implementations must provide CRUD operations and queries.
 */
interface UomUnitRepositoryInterface
{
    /**
     * Find a unit by ID
     *
     * @param int $id
     * @return UomUnitInterface|null
     */
    public function findById(int $id): ?UomUnitInterface;

    /**
     * Find a unit by code
     *
     * @param string $code
     * @return UomUnitInterface|null
     */
    public function findByCode(string $code): ?UomUnitInterface;

    /**
     * Get all units for a specific type
     *
     * @param int $typeId
     * @param bool $activeOnly
     * @return array<int, UomUnitInterface>
     */
    public function findByType(int $typeId, bool $activeOnly = true): array;

    /**
     * Get the base unit for a specific type
     *
     * @param int $typeId
     * @return UomUnitInterface|null
     */
    public function findBaseUnitForType(int $typeId): ?UomUnitInterface;

    /**
     * Get all active units
     *
     * @return array<int, UomUnitInterface>
     */
    public function getAllActive(): array;

    /**
     * Create a new unit
     *
     * @param array<string, mixed> $data
     * @return UomUnitInterface
     */
    public function create(array $data): UomUnitInterface;

    /**
     * Update an existing unit
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return UomUnitInterface
     */
    public function update(int $id, array $data): UomUnitInterface;

    /**
     * Delete a unit
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Search units by name or code
     *
     * @param string $query
     * @param int|null $typeId
     * @return array<int, UomUnitInterface>
     */
    public function search(string $query, ?int $typeId = null): array;
}
