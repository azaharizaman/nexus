<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Department Repository Interface
 * 
 * Defines the persistence contract for Department entities.
 * All database operations for departments must go through this interface.
 */
interface DepartmentRepositoryInterface
{
    /**
     * Find a department by its ID.
     *
     * @param int $id The department ID
     * @return DepartmentInterface|null The department or null if not found
     */
    public function findById(int $id): ?DepartmentInterface;

    /**
     * Find a department by its code.
     *
     * @param string $code The department code
     * @return DepartmentInterface|null The department or null if not found
     */
    public function findByCode(string $code): ?DepartmentInterface;

    /**
     * Get all departments.
     *
     * @param array $filters Optional filters (e.g., ['is_active' => true, 'office_id' => 1])
     * @return iterable<DepartmentInterface> Collection of departments
     */
    public function getAll(array $filters = []): iterable;

    /**
     * Get all active departments.
     *
     * @return iterable<DepartmentInterface> Collection of active departments
     */
    public function getAllActive(): iterable;

    /**
     * Get departments by office.
     *
     * @param int $officeId The office ID
     * @return iterable<DepartmentInterface> Collection of departments
     */
    public function getByOffice(int $officeId): iterable;

    /**
     * Get child departments of a parent department.
     *
     * @param int $parentId The parent department ID
     * @return iterable<DepartmentInterface> Collection of child departments
     */
    public function getChildren(int $parentId): iterable;

    /**
     * Create a new department.
     *
     * @param array $data Department data
     * @return DepartmentInterface The created department
     */
    public function create(array $data): DepartmentInterface;

    /**
     * Update an existing department.
     *
     * @param int $id The department ID
     * @param array $data Updated department data
     * @return DepartmentInterface The updated department
     */
    public function update(int $id, array $data): DepartmentInterface;

    /**
     * Delete a department.
     *
     * @param int $id The department ID
     * @return bool True if deleted successfully
     */
    public function delete(int $id): bool;

    /**
     * Check if a department exists.
     *
     * @param int $id The department ID
     * @return bool True if exists
     */
    public function exists(int $id): bool;
}
