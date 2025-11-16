<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Office Type Repository Interface
 * 
 * Defines the persistence contract for Office Type entities.
 * All database operations for office types must go through this interface.
 */
interface OfficeTypeRepositoryInterface
{
    /**
     * Find an office type by its ID.
     *
     * @param int $id The office type ID
     * @return OfficeTypeInterface|null The office type or null if not found
     */
    public function findById(int $id): ?OfficeTypeInterface;

    /**
     * Find an office type by its code.
     *
     * @param string $code The office type code
     * @return OfficeTypeInterface|null The office type or null if not found
     */
    public function findByCode(string $code): ?OfficeTypeInterface;

    /**
     * Get all office types.
     *
     * @param array $filters Optional filters (e.g., ['status' => 'active'])
     * @return iterable<OfficeTypeInterface> Collection of office types
     */
    public function getAll(array $filters = []): iterable;

    /**
     * Get office types by status.
     *
     * @param string $status The office type status
     * @return iterable<OfficeTypeInterface> Collection of office types
     */
    public function getByStatus(string $status): iterable;

    /**
     * Create a new office type.
     *
     * @param array $data Office type data
     * @return OfficeTypeInterface The created office type
     */
    public function create(array $data): OfficeTypeInterface;

    /**
     * Update an existing office type.
     *
     * @param int $id The office type ID
     * @param array $data Updated office type data
     * @return OfficeTypeInterface The updated office type
     */
    public function update(int $id, array $data): OfficeTypeInterface;

    /**
     * Delete an office type.
     *
     * @param int $id The office type ID
     * @return bool True if deleted successfully
     */
    public function delete(int $id): bool;

    /**
     * Check if an office type exists.
     *
     * @param int $id The office type ID
     * @return bool True if exists
     */
    public function exists(int $id): bool;
}
