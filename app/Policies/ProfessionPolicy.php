<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Profession;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfessionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any professions.
     */
    public function viewAny(User $user)
    {
        return $user->can('view-metadata');
    }

    /**
     * Determine whether the user can view the profession.
     */
    public function view(User $user, Profession $profession)
    {
        return $user->can('view-metadata');
    }

    /**
     * Determine whether the user can create professions.
     */
    public function create(User $user)
    {
        return tenancy()->initialized && $user->can('manage-metadata');
    }

    /**
     * Determine whether the user can update the profession.
     */
    public function update(User $user, Profession $profession)
    {
        return tenancy()->initialized && $user->can('manage-metadata');
    }

    /**
     * Determine whether the user can delete the profession.
     */
    public function delete(User $user, Profession $profession)
    {
        return tenancy()->initialized && $user->can('delete-metadata');
    }
}
