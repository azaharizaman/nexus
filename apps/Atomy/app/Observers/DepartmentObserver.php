<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Department;

/**
 * Department Observer
 * 
 * Handles Department model events.
 */
class DepartmentObserver
{
    /**
     * Handle the Department "creating" event.
     */
    public function creating(Department $department): void
    {
        // Prevent circular references
        if ($this->wouldCreateCircularReference($department)) {
            throw new \InvalidArgumentException('Cannot create circular reference in department hierarchy.');
        }
    }

    /**
     * Handle the Department "created" event.
     */
    public function created(Department $department): void
    {
        // Log department creation
    }

    /**
     * Handle the Department "updating" event.
     */
    public function updating(Department $department): void
    {
        // Prevent circular references when updating parent
        if ($department->isDirty('parent_department_id') && $this->wouldCreateCircularReference($department)) {
            throw new \InvalidArgumentException('Cannot create circular reference in department hierarchy.');
        }
    }

    /**
     * Handle the Department "updated" event.
     */
    public function updated(Department $department): void
    {
        // Handle post-update logic
    }

    /**
     * Handle the Department "deleted" event.
     */
    public function deleted(Department $department): void
    {
        // Handle cleanup
    }

    /**
     * Check if setting the parent would create a circular reference.
     */
    protected function wouldCreateCircularReference(Department $department): bool
    {
        if (!$department->parent_department_id) {
            return false;
        }

        $parent = Department::find($department->parent_department_id);
        
        while ($parent) {
            if ($parent->id === $department->id) {
                return true;
            }
            $parent = $parent->parentDepartment;
        }

        return false;
    }
}