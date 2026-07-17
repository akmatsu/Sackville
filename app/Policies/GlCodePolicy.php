<?php

namespace App\Policies;

use App\Models\GlCode;
use App\Models\User;

class GlCodePolicy
{
    // TODO: confirm with Freedom / Brooke / Katie - replace with responsibilities-based
    // authorization once the `responsibilities` table exists. For v1, any authenticated
    // panel user may manage GL codes.

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
    public function view(User $user, GlCode $glCode): bool
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
    public function update(User $user, GlCode $glCode): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GlCode $glCode): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GlCode $glCode): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GlCode $glCode): bool
    {
        return false;
    }
}
