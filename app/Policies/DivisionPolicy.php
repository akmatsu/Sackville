<?php

namespace App\Policies;

use App\Models\Division;
use App\Models\User;

class DivisionPolicy
{
    // TODO: confirm with Freedom / Brooke / Katie - replace with responsibilities-based
    // authorization once the `responsibilities` table exists. For v1, any authenticated
    // panel user may manage divisions.

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Division $division): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Division $division): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Division $division): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Division $division): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Division $division): bool
    {
        return false;
    }
}
