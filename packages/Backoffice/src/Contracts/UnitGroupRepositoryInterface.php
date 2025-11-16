<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Unit Group Repository Interface
 * 
 * Defines the persistence contract for Unit Group entities.
 * All database operations for unit groups must go through this interface.
 */
interface UnitGroupRepositoryInterface
{
    /**
     * Find a unit group by its ID.
     *
     * @param int $id The unit group ID
     * @return UnitGroupInterface|null The unit group or null if not found
     */
    public function findById(int $id): ?UnitGroupInterface;

    /**
     * Find a unit group by its code.
     *
     * @param string $code The unit group code
     * @return UnitGroupInterface|null The unit group or null if not found
     */
    public function findByCode(string $code): ?UnitGroupInterface;

    /**
     * Get all unit groups.
     *
     * @param array $filters Optional filters (e.g., ['is_active' => true])
     * @return iterable<UnitGroupInterface> Collection of unit groups
     */
    public function getAll(array $filters = []): iterable;

    /**
     * Get all active unit groups.
     *
     * @return iterable<UnitGroupInterface> Collection of active unit groups
     */
    public function getAllActive(): iterable;

    /**
     * Create a new unit group.
     *
     * @param array $data Unit group data
     * @return UnitGroupInterface The created unit group
     */
    public function create(array $data): UnitGroupInterface;

    /**
     * Update an existing unit group.
     *
     * @param int $id The unit group ID
     * @param array $data Updated unit group data
     * @return UnitGroupInterface The updated unit group
     */
    public function update(int $id, array $data): UnitGroupInterface;

    /**
     * Delete a unit group.
     *
     * @param int $id The unit group ID
     * @return bool True if deleted successfully
     */
    public function delete(int $id): bool;

    /**
     * Check if a unit group exists.
     *
     * @param int $id The unit group ID
     * @return bool True if exists
     */
    public function exists(int $id): bool;
}
