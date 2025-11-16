<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\StaffTransfer;
use Nexus\Backoffice\Enums\StaffTransferStatus;
use Illuminate\Support\Facades\Log;

/**
 * Staff Transfer Observer
 * 
 * Handles automatic events when staff transfers are created, updated, or processed.
 * Manages the actual staff record updates when transfers are completed.
 * 
 * @package Nexus\BackofficeManagement\Observers
 */
class StaffTransferObserver
{
    /**
     * Handle the StaffTransfer "creating" event.
     * 
     * @param StaffTransfer $transfer
     * @return void
     */
    public function creating(StaffTransfer $transfer): void
    {
        // Capture current staff data for the transfer record
        $staff = $transfer->staff;
        $attributes = $transfer->getAttributes();
        
        // Only auto-fill from_* fields if they weren't explicitly set in the attributes
        if (!array_key_exists('from_office_id', $attributes) && $staff->office_id) {
            $transfer->from_office_id = $staff->office_id;
        }
        
        if (!array_key_exists('from_department_id', $attributes) && $staff->department_id) {
            $transfer->from_department_id = $staff->department_id;
        }
        
        if (!array_key_exists('from_supervisor_id', $attributes) && $staff->supervisor_id) {
            $transfer->from_supervisor_id = $staff->supervisor_id;
        }
        
        if (!array_key_exists('from_position_id', $attributes) && $staff->position_id) {
            $transfer->from_position_id = $staff->position_id;
        }
        
        // Set default status if not provided
        if (!$transfer->status) {
            $transfer->status = StaffTransferStatus::PENDING;
        }
        
        // Set requested timestamp if immediate transfer
        if ($transfer->is_immediate && !$transfer->requested_at) {
            $transfer->requested_at = now();
        }
        
        Log::info('Staff transfer request created', [
            'transfer_id' => $transfer->id,
            'staff_id' => $transfer->staff_id,
            'from_office_id' => $transfer->from_office_id,
            'to_office_id' => $transfer->to_office_id,
            'effective_date' => $transfer->effective_date,
            'is_immediate' => $transfer->is_immediate,
        ]);
    }

    /**
     * Handle the StaffTransfer "created" event.
     * 
     * @param StaffTransfer $transfer
     * @return void
     */
    public function created(StaffTransfer $transfer): void
    {
        // If it's an immediate transfer, automatically approve and process it
        if ($transfer->is_immediate) {
            Log::info('Processing immediate transfer', [
                'transfer_id' => $transfer->id,
                'staff_id' => $transfer->staff_id,
            ]);
            
            // Auto-approve immediate transfers
            $transfer->update([
                'status' => StaffTransferStatus::APPROVED,
                'approved_at' => now(),
                'approved_by_id' => $transfer->requested_by_id, // Auto-approved by requestor
            ]);
        }
        
        // Send notification to relevant parties
        $this->sendTransferNotifications($transfer, 'created');
    }

    /**
     * Handle the StaffTransfer "updating" event.
     * 
     * @param StaffTransfer $transfer
     * @return void
     */
    public function updating(StaffTransfer $transfer): void
    {
        // Check if status is changing
        if ($transfer->isDirty('status')) {
            $oldStatus = $transfer->getOriginal('status');
            $newStatus = $transfer->status;
            
            Log::info('Transfer status changing', [
                'transfer_id' => $transfer->id,
                'staff_id' => $transfer->staff_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus->value,
            ]);
            
            // Validate status transition
            $this->validateStatusTransition($transfer, $oldStatus?->value, $newStatus);
        }
    }

    /**
     * Handle the StaffTransfer "updated" event.
     * 
     * @param StaffTransfer $transfer
     * @return void
     */
    public function updated(StaffTransfer $transfer): void
    {
        // Check if status was changed
        if ($transfer->wasChanged('status')) {
            $this->handleStatusChange($transfer);
            
            // Send notifications for status changes
            $this->sendTransferNotifications($transfer, 'status_changed');
        }
        
        // If transfer was approved and is due for processing, process it immediately
        if ($transfer->status === StaffTransferStatus::APPROVED && 
            $transfer->isDueForProcessing()) {
            
            Log::info('Transfer is due for processing, processing now', [
                'transfer_id' => $transfer->id,
                'staff_id' => $transfer->staff_id,
                'effective_date' => $transfer->effective_date,
            ]);
            
            $this->processTransfer($transfer);
        }
    }

    /**
     * Handle the StaffTransfer "deleting" event.
     * 
     * @param StaffTransfer $transfer
     * @return void
     */
    public function deleting(StaffTransfer $transfer): bool
    {
        // Prevent deletion of completed transfers
        if ($transfer->status === StaffTransferStatus::COMPLETED) {
            Log::warning('Attempted to delete completed transfer', [
                'transfer_id' => $transfer->id,
                'staff_id' => $transfer->staff_id,
            ]);
            
            return false; // Prevent deletion
        }
        
        Log::info('Staff transfer being deleted', [
            'transfer_id' => $transfer->id,
            'staff_id' => $transfer->staff_id,
            'status' => $transfer->status->value,
        ]);
        
        return true; // Allow deletion
    }

    // ===== HELPER METHODS =====

    /**
     * Handle status change events.
     * 
     * @param StaffTransfer $transfer
     * @return void
     */
    protected function handleStatusChange(StaffTransfer $transfer): void
    {
        $newStatus = $transfer->status;
        
        switch ($newStatus) {
            case StaffTransferStatus::APPROVED:
                $this->handleApproval($transfer);
                break;
                
            case StaffTransferStatus::REJECTED:
                $this->handleRejection($transfer);
                break;
                
            case StaffTransferStatus::COMPLETED:
                $this->handleCompletion($transfer);
                break;
                
            case StaffTransferStatus::CANCELLED:
                $this->handleCancellation($transfer);
                break;
        }
    }

    /**
     * Handle transfer approval.
     * 
     * @param StaffTransfer $transfer
     * @return void
     */
    protected function handleApproval(StaffTransfer $transfer): void
    {
        Log::info('Transfer approved', [
            'transfer_id' => $transfer->id,
            'staff_id' => $transfer->staff_id,
            'approved_by' => $transfer->approved_by_id,
            'effective_date' => $transfer->effective_date,
        ]);
        
        // If effective date is today or in the past, process immediately
        if ($transfer->isDueForProcessing()) {
            $this->processTransfer($transfer);
        }
    }

    /**
     * Handle transfer rejection.
     * 
     * @param StaffTransfer $transfer
     * @return void
     */
    protected function handleRejection(StaffTransfer $transfer): void
    {
        Log::info('Transfer rejected', [
            'transfer_id' => $transfer->id,
            'staff_id' => $transfer->staff_id,
            'rejected_by' => $transfer->rejected_by_id,
            'rejection_reason' => $transfer->rejection_reason,
        ]);
    }

    /**
     * Handle transfer completion.
     * 
     * @param StaffTransfer $transfer
     * @return void
     */
    protected function handleCompletion(StaffTransfer $transfer): void
    {
        Log::info('Transfer completed', [
            'transfer_id' => $transfer->id,
            'staff_id' => $transfer->staff_id,
            'processed_by' => $transfer->processed_by_id,
            'completed_at' => $transfer->completed_at,
        ]);
        
        // Update staff assignments and reporting relationships
        $this->updateStaffAssignments($transfer);
    }

    /**
     * Handle transfer cancellation.
     * 
     * @param StaffTransfer $transfer
     * @return void
     */
    protected function handleCancellation(StaffTransfer $transfer): void
    {
        Log::info('Transfer cancelled', [
            'transfer_id' => $transfer->id,
            'staff_id' => $transfer->staff_id,
            'cancelled_at' => $transfer->cancelled_at,
        ]);
    }

    /**
     * Process the transfer by updating staff record.
     * 
     * @param StaffTransfer $transfer
     * @return void
     */
    protected function processTransfer(StaffTransfer $transfer): void
    {
        try {
            // Mark as completed
            $transfer->update([
                'status' => StaffTransferStatus::COMPLETED,
                'completed_at' => now(),
                'processed_by_id' => $transfer->approved_by_id, // Auto-processed
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to process transfer', [
                'transfer_id' => $transfer->id,
                'staff_id' => $transfer->staff_id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Update staff assignments after transfer completion.
     * 
     * @param StaffTransfer $transfer
     * @return void
     */
    protected function updateStaffAssignments(StaffTransfer $transfer): void
    {
        $staff = $transfer->staff;
        $updates = [];
        
        // Update office assignment
        if ($transfer->to_office_id !== $staff->office_id) {
            $updates['office_id'] = $transfer->to_office_id;
        }
        
        // Update department assignment (only if from_department_id is set, indicating a department change)
        if ($transfer->from_department_id !== null) {
            if ($transfer->to_department_id !== $staff->department_id) {
                $updates['department_id'] = $transfer->to_department_id;
            }
        }
        
        // Update supervisor assignment (only if from_supervisor_id is set, indicating a supervisor change)
        if ($transfer->from_supervisor_id !== null) {
            if ($transfer->to_supervisor_id !== $staff->supervisor_id) {
                $updates['supervisor_id'] = $transfer->to_supervisor_id;
            }
        }
        
        // Update position assignment (only if from_position_id is set, indicating a position change)
        if ($transfer->from_position_id !== null) {
            if ($transfer->to_position_id !== $staff->position_id) {
                $updates['position_id'] = $transfer->to_position_id;
            }
        }
        
        if (!empty($updates)) {
            $staff->update($updates);
            
            Log::info('Staff assignments updated after transfer', [
                'transfer_id' => $transfer->id,
                'staff_id' => $transfer->staff_id,
                'updates' => $updates,
            ]);
        }
    }

    /**
     * Validate status transition.
     * 
     * @param StaffTransfer $transfer
     * @param string|null $oldStatus
     * @param StaffTransferStatus $newStatus
     * @return void
     */
    protected function validateStatusTransition(StaffTransfer $transfer, ?string $oldStatus, StaffTransferStatus $newStatus): void
    {
        // Allow initial status setting
        if (!$oldStatus) {
            return;
        }
        
        $oldStatusEnum = StaffTransferStatus::from($oldStatus);
        
        // Validate transition rules
        $validTransitions = [
            StaffTransferStatus::PENDING->value => [
                StaffTransferStatus::APPROVED,
                StaffTransferStatus::REJECTED,
                StaffTransferStatus::CANCELLED,
            ],
            StaffTransferStatus::APPROVED->value => [
                StaffTransferStatus::COMPLETED,
                StaffTransferStatus::CANCELLED,
            ],
            // Final statuses cannot transition
            StaffTransferStatus::REJECTED->value => [],
            StaffTransferStatus::COMPLETED->value => [],
            StaffTransferStatus::CANCELLED->value => [],
        ];
        
        $allowedTransitions = $validTransitions[$oldStatus] ?? [];
        
        if (!in_array($newStatus, $allowedTransitions)) {
            throw new \InvalidArgumentException(
                "Invalid status transition from {$oldStatus} to {$newStatus->value}"
            );
        }
    }

    /**
     * Send notifications for transfer events.
     * 
     * @param StaffTransfer $transfer
     * @param string $event
     * @return void
     */
    protected function sendTransferNotifications(StaffTransfer $transfer, string $event): void
    {
        // This would typically integrate with a notification system
        // For now, we'll just log the notification requirement
        
        Log::info('Transfer notification should be sent', [
            'transfer_id' => $transfer->id,
            'staff_id' => $transfer->staff_id,
            'event' => $event,
            'status' => $transfer->status->value,
            'to_office' => $transfer->toOffice->name ?? 'Unknown',
        ]);
        
        // In a real implementation, you might:
        // - Send email notifications
        // - Create in-app notifications
        // - Integrate with external systems
        // - Update workflow management systems
    }
}