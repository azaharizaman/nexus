<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Company Repository Interface
 * 
 * Defines the persistence contract for Company entities.
 * All database operations for companies must go through this interface.
 */
interface CompanyRepositoryInterface
{
    /**
     * Find a company by its ID.
     *
     * @param int $id The company ID
     * @return CompanyInterface|null The company or null if not found
     */
    public function findById(int $id): ?CompanyInterface;

    /**
     * Find a company by its code.
     *
     * @param string $code The company code
     * @return CompanyInterface|null The company or null if not found
     */
    public function findByCode(string $code): ?CompanyInterface;

    /**
     * Get all companies.
     *
     * @param array $filters Optional filters (e.g., ['is_active' => true])
     * @return iterable<CompanyInterface> Collection of companies
     */
    public function getAll(array $filters = []): iterable;

    /**
     * Get all active companies.
     *
     * @return iterable<CompanyInterface> Collection of active companies
     */
    public function getAllActive(): iterable;

    /**
     * Get child companies of a parent company.
     *
     * @param int $parentId The parent company ID
     * @return iterable<CompanyInterface> Collection of child companies
     */
    public function getChildren(int $parentId): iterable;

    /**
     * Create a new company.
     *
     * @param array $data Company data
     * @return CompanyInterface The created company
     */
    public function create(array $data): CompanyInterface;

    /**
     * Update an existing company.
     *
     * @param int $id The company ID
     * @param array $data Updated company data
     * @return CompanyInterface The updated company
     */
    public function update(int $id, array $data): CompanyInterface;

    /**
     * Delete a company.
     *
     * @param int $id The company ID
     * @return bool True if deleted successfully
     */
    public function delete(int $id): bool;

    /**
     * Check if a company exists.
     *
     * @param int $id The company ID
     * @return bool True if exists
     */
    public function exists(int $id): bool;
}
