<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use Nexus\Backoffice\Contracts\UserProviderContract;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Company Policy
 * 
 * Authorization policy for Company model operations.
 * Uses UserProviderContract to abstract user management dependencies.
 */
class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct(
        protected UserProviderContract $userProvider
    ) {}

    /**
     * Determine whether the user can view any companies.
     */
    public function viewAny($user): bool
    {
        return $this->userProvider->userHasPermission($user->id, 'backoffice.company.viewAny');
    }

    /**
     * Determine whether the user can view the company.
     */
    public function view($user, Company $company): bool
    {
        // Check if user has general view permission
        if ($this->userProvider->userHasPermission($user->id, 'backoffice.company.view')) {
            return true;
        }
        
        // Check if user can access this specific company
        return $this->userProvider->canUserAccessCompany($user->id, $company->id);
    }

    /**
     * Determine whether the user can create companies.
     */
    public function create($user): bool
    {
        return $this->userProvider->userHasPermission($user->id, 'backoffice.company.create');
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update($user, Company $company): bool
    {
        // Check if user has general update permission
        if ($this->userProvider->userHasPermission($user->id, 'backoffice.company.update')) {
            return true;
        }
        
        // Check if user can access this specific company
        return $this->userProvider->canUserAccessCompany($user->id, $company->id);
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete($user, Company $company): bool
    {
        // Check if user has delete permission
        if (!$this->userProvider->userHasPermission($user->id, 'backoffice.company.delete')) {
            return false;
        }

        // Prevent deletion if company has child companies
        if ($company->childCompanies()->exists()) {
            return false;
        }

        // Prevent deletion if company has offices
        if ($company->offices()->exists()) {
            return false;
        }

        // Prevent deletion if company has departments
        if ($company->departments()->exists()) {
            return false;
        }

        return $this->userProvider->canUserAccessCompany($user->id, $company->id);
    }

    /**
     * Determine whether the user can restore the company.
     */
    public function restore($user, Company $company): bool
    {
        return $this->userProvider->userHasPermission($user->id, 'backoffice.company.restore');
    }

    /**
     * Determine whether the user can manage hierarchy.
     */
    public function manageHierarchy($user, Company $company): bool
    {
        return $this->userProvider->userHasPermission($user->id, 'backoffice.company.manageHierarchy');
    }

    /**
     * Determine whether the user can permanently delete the company.
     */
    public function forceDelete($user, Company $company): bool
    {
        // Only allow force delete if no related entities exist
        return $company->childCompanies()->withTrashed()->count() === 0 &&
               $company->offices()->withTrashed()->count() === 0 &&
               $company->departments()->withTrashed()->count() === 0;
    }
}