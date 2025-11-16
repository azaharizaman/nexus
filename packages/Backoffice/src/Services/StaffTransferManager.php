<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Services;

use Nexus\Backoffice\Contracts\StaffRepositoryInterface;
use Nexus\Backoffice\Contracts\StaffTransferRepositoryInterface;
use Nexus\Backoffice\Contracts\StaffInterface;
use Nexus\Backoffice\Contracts\StaffTransferInterface;
use Nexus\Backoffice\Exceptions\InvalidTransferException;

/**
 * Staff Transfer Manager Service
 * 
 * Framework-agnostic service for managing staff transfers.
 * Contains core business logic for staff transfer operations.
 */
class StaffTransferManager
{
    public function __construct(
        private readonly StaffRepositoryInterface $staffRepository,
        private readonly StaffTransferRepositoryInterface $transferRepository
    ) {}

    /**
     * Create a new staff transfer with validation.
     *
     * @param array $data Transfer data
     * @return StaffTransferInterface The created transfer
     * @throws InvalidTransferException If validation fails
     */
    public function createTransfer(array $data): StaffTransferInterface
    {
        $this->validateTransfer($data);

        return $this->transferRepository->create($data);
    }

    /**
     * Approve a staff transfer.
     *
     * @param int $transferId Transfer ID
     * @param int $approvedBy User ID who approved
     * @return StaffTransferInterface The approved transfer
     */
    public function approveTransfer(int $transferId, int $approvedBy): StaffTransferInterface
    {
        $now = new \DateTimeImmutable();
        
        return $this->transferRepository->update($transferId, [
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => $now,
        ]);
    }

    /**
     * Reject a staff transfer.
     *
     * @param int $transferId Transfer ID
     * @return StaffTransferInterface The rejected transfer
     */
    public function rejectTransfer(int $transferId): StaffTransferInterface
    {
        return $this->transferRepository->update($transferId, [
            'status' => 'rejected',
        ]);
    }

    /**
     * Cancel a staff transfer.
     *
     * @param int $transferId Transfer ID
     * @return StaffTransferInterface The cancelled transfer
     */
    public function cancelTransfer(int $transferId): StaffTransferInterface
    {
        return $this->transferRepository->update($transferId, [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Complete a staff transfer by applying the changes.
     *
     * @param int $transferId Transfer ID
     * @return StaffTransferInterface The completed transfer
     */
    public function completeTransfer(int $transferId): StaffTransferInterface
    {
        $transfer = $this->transferRepository->findById($transferId);
        if ($transfer === null) {
            throw new InvalidTransferException("Transfer not found: {$transferId}");
        }

        // Update staff with new assignment
        $staffUpdates = [];
        
        if ($transfer->getToDepartmentId() !== null) {
            $staffUpdates['department_id'] = $transfer->getToDepartmentId();
        }
        
        if ($transfer->getToPositionId() !== null) {
            $staffUpdates['position_id'] = $transfer->getToPositionId();
        }

        if (!empty($staffUpdates)) {
            $this->staffRepository->update($transfer->getStaffId(), $staffUpdates);
        }

        // Mark transfer as completed
        return $this->transferRepository->update($transferId, [
            'status' => 'completed',
        ]);
    }

    /**
     * Get transfer history for a staff member.
     *
     * @param int $staffId Staff ID
     * @return iterable<StaffTransferInterface> Collection of transfers
     */
    public function getStaffTransferHistory(int $staffId): iterable
    {
        return $this->transferRepository->getByStaff($staffId);
    }

    /**
     * Get pending transfers that are effective by a given date.
     *
     * @param \DateTimeInterface $date The effective date
     * @return iterable<StaffTransferInterface> Collection of transfers
     */
    public function getPendingTransfersByDate(\DateTimeInterface $date): iterable
    {
        $allEffective = $this->transferRepository->getEffectiveBy($date);
        $pending = [];

        foreach ($allEffective as $transfer) {
            if ($transfer->getStatus() === 'approved') {
                $pending[] = $transfer;
            }
        }

        return $pending;
    }

    /**
     * Validate transfer data.
     *
     * @param array $data Transfer data
     * @throws InvalidTransferException If validation fails
     */
    private function validateTransfer(array $data): void
    {
        // Validate staff exists
        if (!isset($data['staff_id']) || !$this->staffRepository->exists($data['staff_id'])) {
            throw new InvalidTransferException('Invalid staff member');
        }

        // Validate at least one change is specified
        $hasChange = isset($data['to_department_id']) 
            || isset($data['to_position_id']) 
            || isset($data['to_unit_id']);

        if (!$hasChange) {
            throw new InvalidTransferException('Transfer must specify at least one target (department, position, or unit)');
        }

        // Validate effective date is not in the past
        if (isset($data['effective_date'])) {
            $effectiveDate = $data['effective_date'];
            if ($effectiveDate instanceof \DateTimeInterface) {
                $today = new \DateTimeImmutable();
                $today = $today->setTime(0, 0, 0);
                
                if ($effectiveDate < $today) {
                    throw new InvalidTransferException('Effective date cannot be in the past');
                }
            }
        }
    }
}
