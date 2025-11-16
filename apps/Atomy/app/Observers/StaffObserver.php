<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Staff;
use Nexus\Backoffice\Enums\StaffStatus;
use Nexus\Backoffice\Exceptions\InvalidResignationException;

/**
 * Staff Observer
 * 
 * Handles Staff model events.
 */
class StaffObserver
{
    /**
     * Handle the Staff "creating" event.
     */
    public function creating(Staff $staff): void
    {
        // Ensure staff has either office or department (or both)
        if (!$staff->office_id && !$staff->department_id) {
            throw new \InvalidArgumentException('Staff must belong to at least one office or department.');
        }

        $this->validateResignationData($staff);
        $this->validateReportingRelationships($staff);
    }

    /**
     * Handle the Staff "created" event.
     */
    public function created(Staff $staff): void
    {
        // Log staff creation
    }

    /**
     * Handle the Staff "updating" event.
     */
    public function updating(Staff $staff): void
    {
        // Ensure staff has either office or department (or both)
        if (!$staff->office_id && !$staff->department_id) {
            throw new \InvalidArgumentException('Staff must belong to at least one office or department.');
        }

        $this->validateResignationData($staff);
        $this->handleResignationStatusChanges($staff);
        $this->validateReportingRelationships($staff);
    }

    /**
     * Handle the Staff "updated" event.
     */
    public function updated(Staff $staff): void
    {
        // Log resignation status changes if needed
        if ($staff->wasChanged('status') && $staff->status === StaffStatus::RESIGNED) {
            // You could add logging here if needed
        }
    }

    /**
     * Handle the Staff "deleted" event.
     */
    public function deleted(Staff $staff): void
    {
        // Handle cleanup - remove from units
        $staff->units()->detach();
    }

    /**
     * Handle the Staff "restored" event.
     */
    public function restored(Staff $staff): void
    {
        // Handle restoration logic
    }

    /**
     * Validate resignation data consistency.
     */
    private function validateResignationData(Staff $staff): void
    {
        // If resignation date is set, ensure it's not in the past for new entries
        if ($staff->resignation_date && !$staff->exists) {
            if ($staff->resignation_date < now()->toDateString()) {
                throw new InvalidResignationException(
                    'Resignation date cannot be in the past for new staff entries.'
                );
            }
        }

        // If status is RESIGNED, ensure resigned_at is set
        if ($staff->status === StaffStatus::RESIGNED && !$staff->resigned_at) {
            $staff->resigned_at = now();
        }

        // If status is RESIGNED, ensure is_active is false
        if ($staff->status === StaffStatus::RESIGNED) {
            $staff->is_active = false;
        }

        // If resignation is cancelled, clean up related fields
        if ($staff->isDirty('resignation_date') && !$staff->resignation_date) {
            $staff->resignation_reason = null;
        }
    }

    /**
     * Handle resignation status changes.
     */
    private function handleResignationStatusChanges(Staff $staff): void
    {
        // If changing TO resigned status
        if ($staff->isDirty('status') && $staff->status === StaffStatus::RESIGNED) {
            if (!$staff->resigned_at) {
                $staff->resigned_at = now();
            }
            $staff->is_active = false;
        }

        // If changing FROM resigned status to active status
        $originalStatus = $staff->getOriginal('status');
        if ($staff->isDirty('status') && 
            ($originalStatus === StaffStatus::RESIGNED->value || $originalStatus === StaffStatus::RESIGNED) && 
            $staff->status === StaffStatus::ACTIVE) {
            // Clear resignation data when reactivating
            $staff->resignation_date = null;
            $staff->resignation_reason = null;
            $staff->resigned_at = null;
            $staff->is_active = true;
        }
    }

    /**
     * Validate reporting relationships to prevent circular references and invalid assignments.
     */
    private function validateReportingRelationships(Staff $staff): void
    {
        // Skip validation if no supervisor is being set
        if (!$staff->supervisor_id) {
            return;
        }

        // Cannot report to self
        if ($staff->supervisor_id === $staff->id) {
            throw new \InvalidArgumentException('Staff cannot report to themselves.');
        }

        // If this is an update, we need to check for circular references
        if ($staff->exists) {
            $supervisor = Staff::find($staff->supervisor_id);
            if ($supervisor && $staff->wouldCreateCircularReference($supervisor)) {
                throw new \InvalidArgumentException(
                    'Cannot assign supervisor: would create circular reporting relationship.'
                );
            }
        }

        // Ensure supervisor exists and is active
        $supervisor = Staff::find($staff->supervisor_id);
        if (!$supervisor) {
            throw new \InvalidArgumentException('Supervisor does not exist.');
        }

        if (!$supervisor->is_active) {
            throw new \InvalidArgumentException('Cannot report to inactive staff member.');
        }

        // Ensure supervisor is not resigned
        if ($supervisor->isResigned()) {
            throw new \InvalidArgumentException('Cannot report to resigned staff member.');
        }

        // Optional: Ensure supervisor is in same company (if business rule requires it)
        $staffCompany = $staff->getCompany();
        $supervisorCompany = $supervisor->getCompany();
        
        if ($staffCompany && $supervisorCompany && $staffCompany->id !== $supervisorCompany->id) {
            throw new \InvalidArgumentException(
                'Staff and supervisor must belong to the same company.'
            );
        }
    }
}