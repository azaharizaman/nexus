<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Staff;
use App\Models\StaffTransfer;
use Nexus\Backoffice\Enums\StaffTransferStatus;

/**
 * Staff Transfer Policy
 * 
 * Authorization policy for staff transfer operations including creation,
 * approval, rejection, and cancellation of transfer requests.
 * 
 * @package Nexus\BackofficeManagement\Policies
 */
class StaffTransferPolicy
{
    /**
     * Determine whether the user can view any transfers.
     */
    public function viewAny(Staff $user): bool
    {
        // HR staff and managers can view all transfers
        return $this->isHRStaff($user) || $this->isManager($user);
    }

    /**
     * Determine whether the user can view the transfer.
     */
    public function view(Staff $user, StaffTransfer $transfer): bool
    {
        // Users can view their own transfers
        if ($user->id === $transfer->staff_id) {
            return true;
        }
        
        // Users can view transfers they requested
        if ($user->id === $transfer->requested_by_id) {
            return true;
        }
        
        // Supervisors can view their subordinates' transfers
        if ($this->isSupervisorOf($user, $transfer->staff)) {
            return true;
        }
        
        // HR staff and senior managers can view all transfers
        return $this->isHRStaff($user) || $this->isSeniorManager($user);
    }

    /**
     * Determine whether the user can create transfers.
     */
    public function create(Staff $user): bool
    {
        // Only active staff can create transfer requests
        return $user->is_active && $user->status->value === 'active';
    }

    /**
     * Determine whether the user can request a transfer for another staff member.
     */
    public function createForOther(Staff $user, Staff $targetStaff): bool
    {
        // HR staff can create transfers for anyone
        if ($this->isHRStaff($user)) {
            return true;
        }
        
        // Managers can create transfers for their subordinates
        if ($this->isSupervisorOf($user, $targetStaff)) {
            return true;
        }
        
        // Senior managers can create transfers for anyone in their department/office
        if ($this->isSeniorManager($user)) {
            return $this->isInSameOrganizationalUnit($user, $targetStaff);
        }
        
        return false;
    }

    /**
     * Determine whether the user can update the transfer.
     */
    public function update(Staff $user, StaffTransfer $transfer): bool
    {
        // Cannot update final status transfers
        if ($transfer->status->isFinal()) {
            return false;
        }
        
        // Transfer requestor can update pending transfers
        if ($user->id === $transfer->requested_by_id && 
            $transfer->status === StaffTransferStatus::PENDING) {
            return true;
        }
        
        // HR staff can update non-final transfers
        if ($this->isHRStaff($user)) {
            return !$transfer->status->isFinal();
        }
        
        return false;
    }

    /**
     * Determine whether the user can approve the transfer.
     */
    public function approve(Staff $user, StaffTransfer $transfer): bool
    {
        // Only pending transfers can be approved
        if (!$transfer->canBeApproved()) {
            return false;
        }
        
        // Cannot approve own transfer request
        if ($user->id === $transfer->requested_by_id) {
            return false;
        }
        
        // HR staff can approve any transfer
        if ($this->isHRStaff($user)) {
            return true;
        }
        
        // Senior managers can approve transfers within their scope
        if ($this->isSeniorManager($user)) {
            return $this->isWithinApprovalScope($user, $transfer);
        }
        
        return false;
    }

    /**
     * Determine whether the user can reject the transfer.
     */
    public function reject(Staff $user, StaffTransfer $transfer): bool
    {
        // Only pending transfers can be rejected
        if (!$transfer->canBeRejected()) {
            return false;
        }
        
        // HR staff can reject any transfer
        if ($this->isHRStaff($user)) {
            return true;
        }
        
        // Senior managers can reject transfers within their scope
        if ($this->isSeniorManager($user)) {
            return $this->isWithinApprovalScope($user, $transfer);
        }
        
        return false;
    }

    /**
     * Determine whether the user can cancel the transfer.
     */
    public function cancel(Staff $user, StaffTransfer $transfer): bool
    {
        // Cannot cancel final status transfers
        if (!$transfer->canBeCancelled()) {
            return false;
        }
        
        // Transfer requestor can cancel their own request
        if ($user->id === $transfer->requested_by_id) {
            return true;
        }
        
        // The staff member can cancel their own transfer
        if ($user->id === $transfer->staff_id) {
            return true;
        }
        
        // HR staff can cancel any transfer
        if ($this->isHRStaff($user)) {
            return true;
        }
        
        // Senior managers can cancel transfers within their scope
        if ($this->isSeniorManager($user)) {
            return $this->isWithinApprovalScope($user, $transfer);
        }
        
        return false;
    }

    /**
     * Determine whether the user can process (complete) the transfer.
     */
    public function process(Staff $user, StaffTransfer $transfer): bool
    {
        // Only approved transfers can be processed
        if (!$transfer->status->canBeProcessed()) {
            return false;
        }
        
        // Only process transfers that are due
        if (!$transfer->isDueForProcessing()) {
            return false;
        }
        
        // HR staff can process any transfer
        if ($this->isHRStaff($user)) {
            return true;
        }
        
        // System/automated processing
        return false;
    }

    /**
     * Determine whether the user can delete the transfer.
     */
    public function delete(Staff $user, StaffTransfer $transfer): bool
    {
        // Only HR staff can delete transfers, and only if they're not completed
        return $this->isHRStaff($user) && 
               $transfer->status !== StaffTransferStatus::COMPLETED;
    }

    // ===== HELPER METHODS =====

    /**
     * Check if user is HR staff.
     */
    protected function isHRStaff(Staff $user): bool
    {
        // Check if user is in HR department or has HR role
        return $user->department?->code === 'HR' ||
               str_contains(strtolower($user->position?->name ?? ''), 'hr') ||
               str_contains(strtolower($user->position?->name ?? ''), 'human resource');
    }

    /**
     * Check if user is a manager.
     */
    protected function isManager(Staff $user): bool
    {
        return $user->subordinates()->exists() ||
               str_contains(strtolower($user->position?->name ?? ''), 'manager') ||
               str_contains(strtolower($user->position?->name ?? ''), 'supervisor') ||
               str_contains(strtolower($user->position?->name ?? ''), 'director') ||
               str_contains(strtolower($user->position?->name ?? ''), 'head');
    }

    /**
     * Check if user is a senior manager.
     */
    protected function isSeniorManager(Staff $user): bool
    {
        return str_contains(strtolower($user->position?->name ?? ''), 'director') ||
               str_contains(strtolower($user->position?->name ?? ''), 'head') ||
               str_contains(strtolower($user->position?->name ?? ''), 'chief') ||
               str_contains(strtolower($user->position?->name ?? ''), 'vp') ||
               str_contains(strtolower($user->position?->name ?? ''), 'vice president') ||
               $user->getReportingLevel() <= 0; // Top-level executives only
    }

    /**
     * Check if user is supervisor of the given staff.
     */
    protected function isSupervisorOf(Staff $user, Staff $staff): bool
    {
        return $staff->supervisor_id === $user->id ||
               $staff->getAncestors()->contains('id', $user->id);
    }

    /**
     * Check if users are in the same organizational unit.
     */
    protected function isInSameOrganizationalUnit(Staff $user, Staff $targetStaff): bool
    {
        // Same office
        if ($user->office_id === $targetStaff->office_id) {
            return true;
        }
        
        // Same department
        if ($user->department_id && $user->department_id === $targetStaff->department_id) {
            return true;
        }
        
        // User's office is parent of target's office
        if ($user->office && $targetStaff->office) {
            return $targetStaff->office->getAncestors()->contains('id', $user->office_id);
        }
        
        return false;
    }

    /**
     * Check if transfer is within user's approval scope.
     */
    protected function isWithinApprovalScope(Staff $user, StaffTransfer $transfer): bool
    {
        // Can approve if user is in the approval chain for both source and destination
        $canApproveSource = $this->isInSameOrganizationalUnit($user, $transfer->staff);
        $canApproveDestination = $user->office_id === $transfer->to_office_id ||
                                ($user->office && $transfer->toOffice->getAncestors()->contains('id', $user->office_id));
        
        return $canApproveSource || $canApproveDestination;
    }
}