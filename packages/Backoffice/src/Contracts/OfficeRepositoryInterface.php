<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Office Repository Interface
 * 
 * Defines the persistence contract for Office entities.
 * All database operations for offices must go through this interface.
 */
interface OfficeRepositoryInterface
{
    /**
     * Find an office by its ID.
     *
     * @param int $id The office ID
     * @return OfficeInterface|null The office or null if not found
     */
    public function findById(int $id): ?OfficeInterface;

    /**
     * Find an office by its code.
     *
     * @param string $code The office code
     * @return OfficeInterface|null The office or null if not found
     */
    public function findByCode(string $code): ?OfficeInterface;

    /**
     * Get all offices.
     *
     * @param array $filters Optional filters (e.g., ['is_active' => true, 'company_id' => 1])
     * @return iterable<OfficeInterface> Collection of offices
     */
    public function getAll(array $filters = []): iterable;

    /**
     * Get all active offices.
     *
     * @return iterable<OfficeInterface> Collection of active offices
     */
    public function getAllActive(): iterable;

    /**
     * Get offices by company.
     *
     * @param int $companyId The company ID
     * @return iterable<OfficeInterface> Collection of offices
     */
    public function getByCompany(int $companyId): iterable;

    /**
     * Get child offices of a parent office.
     *
     * @param int $parentId The parent office ID
     * @return iterable<OfficeInterface> Collection of child offices
     */
    public function getChildren(int $parentId): iterable;

    /**
     * Create a new office.
     *
     * @param array $data Office data
     * @return OfficeInterface The created office
     */
    public function create(array $data): OfficeInterface;

    /**
     * Update an existing office.
     *
     * @param int $id The office ID
     * @param array $data Updated office data
     * @return OfficeInterface The updated office
     */
    public function update(int $id, array $data): OfficeInterface;

    /**
     * Delete an office.
     *
     * @param int $id The office ID
     * @return bool True if deleted successfully
     */
    public function delete(int $id): bool;

    /**
     * Check if an office exists.
     *
     * @param int $id The office ID
     * @return bool True if exists
     */
    public function exists(int $id): bool;
}
