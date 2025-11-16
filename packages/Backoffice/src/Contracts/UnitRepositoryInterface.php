<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Unit Repository Interface
 * 
 * Defines the persistence contract for Unit entities.
 * All database operations for units must go through this interface.
 */
interface UnitRepositoryInterface
{
    /**
     * Find a unit by its ID.
     *
     * @param int $id The unit ID
     * @return UnitInterface|null The unit or null if not found
     */
    public function findById(int $id): ?UnitInterface;

    /**
     * Find a unit by its code.
     *
     * @param string $code The unit code
     * @return UnitInterface|null The unit or null if not found
     */
    public function findByCode(string $code): ?UnitInterface;

    /**
     * Get all units.
     *
     * @param array $filters Optional filters (e.g., ['is_active' => true, 'unit_group_id' => 1])
     * @return iterable<UnitInterface> Collection of units
     */
    public function getAll(array $filters = []): iterable;

    /**
     * Get all active units.
     *
     * @return iterable<UnitInterface> Collection of active units
     */
    public function getAllActive(): iterable;

    /**
     * Get units by unit group.
     *
     * @param int $unitGroupId The unit group ID
     * @return iterable<UnitInterface> Collection of units
     */
    public function getByUnitGroup(int $unitGroupId): iterable;

    /**
     * Get child units of a parent unit.
     *
     * @param int $parentId The parent unit ID
     * @return iterable<UnitInterface> Collection of child units
     */
    public function getChildren(int $parentId): iterable;

    /**
     * Create a new unit.
     *
     * @param array $data Unit data
     * @return UnitInterface The created unit
     */
    public function create(array $data): UnitInterface;

    /**
     * Update an existing unit.
     *
     * @param int $id The unit ID
     * @param array $data Updated unit data
     * @return UnitInterface The updated unit
     */
    public function update(int $id, array $data): UnitInterface;

    /**
     * Delete a unit.
     *
     * @param int $id The unit ID
     * @return bool True if deleted successfully
     */
    public function delete(int $id): bool;

    /**
     * Check if a unit exists.
     *
     * @param int $id The unit ID
     * @return bool True if exists
     */
    public function exists(int $id): bool;
}
