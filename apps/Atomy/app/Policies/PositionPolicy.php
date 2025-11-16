<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Position;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Position Policy
 * 
 * Authorization logic for Position operations.
 */
class PositionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any positions.
     */
    public function viewAny($user): bool
    {
        // Default: allow all authenticated users to view positions
        return true;
    }

    /**
     * Determine if user can view a specific position.
     */
    public function view($user, Position $position): bool
    {
        // Default: allow all authenticated users to view positions
        return true;
    }

    /**
     * Determine if user can create positions.
     */
    public function create($user): bool
    {
        // Default: restrict position creation to authorized users
        // Implement your authorization logic here
        return false;
    }

    /**
     * Determine if user can update a position.
     */
    public function update($user, Position $position): bool
    {
        // Default: restrict position updates to authorized users
        // Implement your authorization logic here
        return false;
    }

    /**
     * Determine if user can delete a position.
     */
    public function delete($user, Position $position): bool
    {
        // Cannot delete positions that have staff assigned
        if ($position->staff()->exists()) {
            return false;
        }

        // Default: restrict position deletion to authorized users
        // Implement your authorization logic here
        return false;
    }

    /**
     * Determine if user can restore a deleted position.
     */
    public function restore($user, Position $position): bool
    {
        // Default: restrict position restoration to authorized users
        // Implement your authorization logic here
        return false;
    }

    /**
     * Determine if user can permanently delete a position.
     */
    public function forceDelete($user, Position $position): bool
    {
        // Default: restrict permanent deletion to authorized users
        // Implement your authorization logic here
        return false;
    }
}
