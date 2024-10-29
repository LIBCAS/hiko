<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProfessionCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfessionCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any profession categories.
     */
    public function viewAny(User $user)
    {
        return $user->can('view-metadata');
    }

    /**
     * Determine whether the user can view the profession category.
     */
    public function view(User $user, ProfessionCategory $professionCategory)
    {
        return $user->can('view-metadata');
    }

    /**
     * Determine whether the user can create profession categories.
     */
    public function create(User $user)
    {
        return tenancy()->initialized && $user->can('manage-metadata');
    }

    /**
     * Determine whether the user can update the profession category.
     */
    public function update(User $user, ProfessionCategory $professionCategory)
    {
        return tenancy()->initialized && $user->can('manage-metadata');
    }

    /**
     * Determine whether the user can delete the profession category.
     */
    public function delete(User $user, ProfessionCategory $professionCategory)
    {
        return tenancy()->initialized && $user->can('delete-metadata');
    }
}
