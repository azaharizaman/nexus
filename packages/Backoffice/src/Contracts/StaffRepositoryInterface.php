<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Staff Repository Interface
 * 
 * Defines the persistence contract for Staff entities.
 * All database operations for staff members must go through this interface.
 */
interface StaffRepositoryInterface
{
    /**
     * Find a staff member by their ID.
     *
     * @param int $id The staff ID
     * @return StaffInterface|null The staff or null if not found
     */
    public function findById(int $id): ?StaffInterface;

    /**
     * Find a staff member by their employee number.
     *
     * @param string $employeeNumber The employee number
     * @return StaffInterface|null The staff or null if not found
     */
    public function findByEmployeeNumber(string $employeeNumber): ?StaffInterface;

    /**
     * Find a staff member by their user ID.
     *
     * @param int $userId The user ID
     * @return StaffInterface|null The staff or null if not found
     */
    public function findByUserId(int $userId): ?StaffInterface;

    /**
     * Get all staff members.
     *
     * @param array $filters Optional filters (e.g., ['is_active' => true, 'department_id' => 1])
     * @return iterable<StaffInterface> Collection of staff
     */
    public function getAll(array $filters = []): iterable;

    /**
     * Get all active staff members.
     *
     * @return iterable<StaffInterface> Collection of active staff
     */
    public function getAllActive(): iterable;

    /**
     * Get staff members by department.
     *
     * @param int $departmentId The department ID
     * @return iterable<StaffInterface> Collection of staff
     */
    public function getByDepartment(int $departmentId): iterable;

    /**
     * Get staff members by position.
     *
     * @param int $positionId The position ID
     * @return iterable<StaffInterface> Collection of staff
     */
    public function getByPosition(int $positionId): iterable;

    /**
     * Get staff members reporting to a manager.
     *
     * @param int $managerId The manager's staff ID
     * @return iterable<StaffInterface> Collection of staff
     */
    public function getDirectReports(int $managerId): iterable;

    /**
     * Get staff members by status.
     *
     * @param string $status The staff status
     * @return iterable<StaffInterface> Collection of staff
     */
    public function getByStatus(string $status): iterable;

    /**
     * Create a new staff member.
     *
     * @param array $data Staff data
     * @return StaffInterface The created staff
     */
    public function create(array $data): StaffInterface;

    /**
     * Update an existing staff member.
     *
     * @param int $id The staff ID
     * @param array $data Updated staff data
     * @return StaffInterface The updated staff
     */
    public function update(int $id, array $data): StaffInterface;

    /**
     * Delete a staff member.
     *
     * @param int $id The staff ID
     * @return bool True if deleted successfully
     */
    public function delete(int $id): bool;

    /**
     * Check if a staff member exists.
     *
     * @param int $id The staff ID
     * @return bool True if exists
     */
    public function exists(int $id): bool;
}
