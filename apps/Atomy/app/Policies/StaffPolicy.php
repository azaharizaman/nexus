<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Staff;
use Nexus\Backoffice\Contracts\UserProviderContract;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Staff Policy
 * 
 * Authorization policy for Staff model operations.
 * Uses UserProviderContract to abstract user management dependencies.
 */
class StaffPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct(
        protected UserProviderContract $userProvider
    ) {}

    /**
     * Determine whether the user can view any staff.
     */
    public function viewAny($user): bool
    {
        return $this->userProvider->userHasPermission($user->id, 'backoffice.staff.viewAny');
    }

    /**
     * Determine whether the user can view the staff.
     */
    public function view($user, Staff $staff): bool
    {
        // Check if user has general view permission
        if ($this->userProvider->userHasPermission($user->id, 'backoffice.staff.view')) {
            return true;
        }
        
        // Check if user can access the staff's company
        return $this->userProvider->canUserAccessCompany($user->id, $staff->company_id);
    }

    /**
     * Determine whether the user can create staff.
     */
    public function create($user): bool
    {
        return $this->userProvider->userHasPermission($user->id, 'backoffice.staff.create');
    }

    /**
     * Determine whether the user can update the staff.
     */
    public function update($user, Staff $staff): bool
    {
        // Check if user has general update permission
        if ($this->userProvider->userHasPermission($user->id, 'backoffice.staff.update')) {
            return true;
        }
        
        // Check if user can access the staff's company
        return $this->userProvider->canUserAccessCompany($user->id, $staff->company_id);
    }

    /**
     * Determine whether the user can restore the staff.
     */
    public function restore($user, Staff $staff): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the staff.
     */
    public function delete($user, Staff $staff): bool
    {
        // Check if user has general delete permission
        if ($this->userProvider->userHasPermission($user->id, 'backoffice.staff.delete')) {
            return true;
        }
        
        // Check if user can access the staff's company
        return $this->userProvider->canUserAccessCompany($user->id, $staff->company_id);
    }

    /**
     * Determine whether the user can transfer the staff.
     */
    public function transfer($user, Staff $staff): bool
    {
        // Check if user has transfer permission
        if ($this->userProvider->userHasPermission($user->id, 'backoffice.staff.transfer')) {
            return true;
        }
        
        // Check if user can access the staff's company
        return $this->userProvider->canUserAccessCompany($user->id, $staff->company_id);
    }
}