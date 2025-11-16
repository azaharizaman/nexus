<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Office;

/**
 * Office Observer
 * 
 * Handles Office model events.
 */
class OfficeObserver
{
    /**
     * Handle the Office "creating" event.
     */
    public function creating(Office $office): void
    {
        // Prevent circular references
        if ($this->wouldCreateCircularReference($office)) {
            throw new \InvalidArgumentException('Cannot create circular reference in office hierarchy.');
        }
    }

    /**
     * Handle the Office "created" event.
     */
    public function created(Office $office): void
    {
        // Log office creation
    }

    /**
     * Handle the Office "updating" event.
     */
    public function updating(Office $office): void
    {
        // Prevent circular references when updating parent
        if ($office->isDirty('parent_office_id') && $this->wouldCreateCircularReference($office)) {
            throw new \InvalidArgumentException('Cannot create circular reference in office hierarchy.');
        }
    }

    /**
     * Handle the Office "updated" event.
     */
    public function updated(Office $office): void
    {
        // Handle post-update logic
    }

    /**
     * Handle the Office "deleted" event.
     */
    public function deleted(Office $office): void
    {
        // Handle cleanup
    }

    /**
     * Check if setting the parent would create a circular reference.
     */
    protected function wouldCreateCircularReference(Office $office): bool
    {
        if (!$office->parent_office_id) {
            return false;
        }

        $parent = Office::find($office->parent_office_id);
        
        while ($parent) {
            if ($parent->id === $office->id) {
                return true;
            }
            $parent = $parent->parentOffice;
        }

        return false;
    }
}