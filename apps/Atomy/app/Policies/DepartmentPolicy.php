<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Department;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Department Policy
 * 
 * Authorization policy for Department model operations.
 */
class DepartmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any departments.
     */
    public function viewAny($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the department.
     */
    public function view($user, Department $department): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create departments.
     */
    public function create($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the department.
     */
    public function update($user, Department $department): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the department.
     */
    public function delete($user, Department $department): bool
    {
        // Prevent deletion if department has child departments
        if ($department->childDepartments()->exists()) {
            return false;
        }

        // Prevent deletion if department has staff
        if ($department->staff()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the department.
     */
    public function restore($user, Department $department): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the department.
     */
    public function forceDelete($user, Department $department): bool
    {
        return $department->childDepartments()->withTrashed()->count() === 0 &&
               $department->staff()->withTrashed()->count() === 0;
    }
}