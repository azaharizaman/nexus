<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Staff Transfer Repository Interface
 * 
 * Defines the persistence contract for Staff Transfer entities.
 * All database operations for staff transfers must go through this interface.
 */
interface StaffTransferRepositoryInterface
{
    /**
     * Find a staff transfer by its ID.
     *
     * @param int $id The staff transfer ID
     * @return StaffTransferInterface|null The staff transfer or null if not found
     */
    public function findById(int $id): ?StaffTransferInterface;

    /**
     * Get all staff transfers.
     *
     * @param array $filters Optional filters (e.g., ['status' => 'pending', 'staff_id' => 1])
     * @return iterable<StaffTransferInterface> Collection of staff transfers
     */
    public function getAll(array $filters = []): iterable;

    /**
     * Get transfers for a specific staff member.
     *
     * @param int $staffId The staff ID
     * @return iterable<StaffTransferInterface> Collection of staff transfers
     */
    public function getByStaff(int $staffId): iterable;

    /**
     * Get transfers by status.
     *
     * @param string $status The transfer status
     * @return iterable<StaffTransferInterface> Collection of staff transfers
     */
    public function getByStatus(string $status): iterable;

    /**
     * Get pending transfers.
     *
     * @return iterable<StaffTransferInterface> Collection of pending staff transfers
     */
    public function getPending(): iterable;

    /**
     * Get approved transfers.
     *
     * @return iterable<StaffTransferInterface> Collection of approved staff transfers
     */
    public function getApproved(): iterable;

    /**
     * Get transfers effective on or before a date.
     *
     * @param \DateTimeInterface $date The effective date
     * @return iterable<StaffTransferInterface> Collection of staff transfers
     */
    public function getEffectiveBy(\DateTimeInterface $date): iterable;

    /**
     * Create a new staff transfer.
     *
     * @param array $data Staff transfer data
     * @return StaffTransferInterface The created staff transfer
     */
    public function create(array $data): StaffTransferInterface;

    /**
     * Update an existing staff transfer.
     *
     * @param int $id The staff transfer ID
     * @param array $data Updated staff transfer data
     * @return StaffTransferInterface The updated staff transfer
     */
    public function update(int $id, array $data): StaffTransferInterface;

    /**
     * Delete a staff transfer.
     *
     * @param int $id The staff transfer ID
     * @return bool True if deleted successfully
     */
    public function delete(int $id): bool;

    /**
     * Check if a staff transfer exists.
     *
     * @param int $id The staff transfer ID
     * @return bool True if exists
     */
    public function exists(int $id): bool;
}
