<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\User;

class FundPolicy
{
    // TODO: confirm with Freedom / Brooke / Katie - replace with responsibilities-based
    // authorization once the `responsibilities` table exists. For v1, any authenticated
    // panel user may manage funds.

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
    public function view(User $user, Fund $fund): bool
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
    public function update(User $user, Fund $fund): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Fund $fund): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Fund $fund): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Fund $fund): bool
    {
        return false;
    }
}
