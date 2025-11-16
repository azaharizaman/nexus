<?php

declare(strict_types=1);

namespace App\Observers;

use Nexus\Backoffice\Exceptions\CircularReferenceException;
use App\Models\Company;

/**
 * Company Observer
 * 
 * Handles Company model events.
 */
class CompanyObserver
{
    /**
     * Handle the Company "creating" event.
     */
    public function creating(Company $company): void
    {
        // Prevent circular references
        if ($this->wouldCreateCircularReference($company)) {
            throw new CircularReferenceException('Cannot set parent: Circular reference detected');
        }
    }

    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        // Log company creation or fire events if needed
    }

    /**
     * Handle the Company "updating" event.
     */
    public function updating(Company $company): void
    {
        // Prevent circular references when updating parent
        if ($company->isDirty('parent_company_id') && $this->wouldCreateCircularReference($company)) {
            throw new CircularReferenceException('Cannot set parent: Circular reference detected');
        }
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        // Handle any post-update logic
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        // Handle cleanup when company is deleted
    }

    /**
     * Handle the Company "restored" event.
     */
    public function restored(Company $company): void
    {
        // Handle restoration logic
    }

    /**
     * Handle the Company "force deleted" event.
     */
    public function forceDeleted(Company $company): void
    {
        // Handle permanent deletion cleanup
    }

    /**
     * Check if setting the parent would create a circular reference.
     */
    protected function wouldCreateCircularReference(Company $company): bool
    {
        if (!$company->parent_company_id) {
            return false;
        }

        // Check if the parent company is a descendant of this company
        $parent = Company::find($company->parent_company_id);
        
        while ($parent) {
            if ($parent->id === $company->id) {
                return true;
            }
            $parent = $parent->parentCompany;
        }

        return false;
    }
}