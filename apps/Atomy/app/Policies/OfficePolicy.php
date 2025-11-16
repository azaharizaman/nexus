<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Office;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Office Policy
 * 
 * Authorization policy for Office model operations.
 */
class OfficePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any offices.
     */
    public function viewAny($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the office.
     */
    public function view($user, Office $office): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create offices.
     */
    public function create($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the office.
     */
    public function update($user, Office $office): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the office.
     */
    public function delete($user, Office $office): bool
    {
        // Prevent deletion if office has child offices
        if ($office->childOffices()->exists()) {
            return false;
        }

        // Prevent deletion if office has staff
        if ($office->staff()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the office.
     */
    public function restore($user, Office $office): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the office.
     */
    public function forceDelete($user, Office $office): bool
    {
        return $office->childOffices()->withTrashed()->count() === 0 &&
               $office->staff()->withTrashed()->count() === 0;
    }
}