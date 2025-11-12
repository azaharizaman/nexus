<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Policies;

use Azaharizaman\Erp\SerialNumbering\Models\Sequence;
use Illuminate\Foundation\Auth\User;

/**
 * Sequence Policy
 *
 * Authorization policies for sequence operations.
 */
class SequencePolicy
{
    /**
     * Determine if the user can view any sequences.
     *
     * @param  User  $user  The authenticated user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view sequences in their tenant
        return true;
    }

    /**
     * Determine if the user can view a sequence.
     *
     * @param  User  $user  The authenticated user
     * @param  Sequence  $sequence  The sequence
     * @return bool
     */
    public function view(User $user, Sequence $sequence): bool
    {
        // Users can view sequences in their tenant
        return true;
    }

    /**
     * Determine if the user can create sequences.
     *
     * @param  User  $user  The authenticated user
     * @return bool
     */
    public function create(User $user): bool
    {
        // Check if user has 'manage-sequences' permission
        return $user->can('manage-sequences');
    }

    /**
     * Determine if the user can update a sequence.
     *
     * @param  User  $user  The authenticated user
     * @param  Sequence  $sequence  The sequence
     * @return bool
     */
    public function update(User $user, Sequence $sequence): bool
    {
        // Check if user has 'manage-sequences' permission
        return $user->can('manage-sequences');
    }

    /**
     * Determine if the user can delete a sequence.
     *
     * @param  User  $user  The authenticated user
     * @param  Sequence  $sequence  The sequence
     * @return bool
     */
    public function delete(User $user, Sequence $sequence): bool
    {
        // Check if user has 'manage-sequences' permission
        return $user->can('manage-sequences');
    }

    /**
     * Determine if the user can generate serial numbers.
     *
     * @param  User  $user  The authenticated user
     * @param  Sequence  $sequence  The sequence
     * @return bool
     */
    public function generate(User $user, Sequence $sequence): bool
    {
        // All authenticated users in same tenant can generate numbers
        return true;
    }

    /**
     * Determine if the user can reset a sequence.
     *
     * @param  User  $user  The authenticated user
     * @param  Sequence  $sequence  The sequence
     * @return bool
     */
    public function reset(User $user, Sequence $sequence): bool
    {
        // Check if user has 'reset-sequence' permission (admin only)
        return $user->can('reset-sequence');
    }

    /**
     * Determine if the user can override serial numbers.
     *
     * @param  User  $user  The authenticated user
     * @param  Sequence  $sequence  The sequence
     * @return bool
     */
    public function override(User $user, Sequence $sequence): bool
    {
        // Check if user has 'override-sequence-number' permission (super-admin only)
        return $user->can('override-sequence-number');
    }
}
