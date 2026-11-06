<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any user models.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.manage');
    }

    /**
     * Determine whether the user can view the user model.
     */
    public function view(User $actor, User $target): bool
    {
        return $actor->can('users.manage');
    }

    /**
     * Determine whether the user can create user models.
     */
    public function create(User $actor): bool
    {
        return $actor->can('users.manage');
    }

    /**
     * Determine whether the user can update the user model.
     */
    public function update(User $actor, User $target): bool
    {
        return $actor->can('users.manage');
    }

    /**
     * Determine whether the user can toggle status (activate/deactivate).
     */
    public function toggleStatus(User $actor, User $target): bool
    {
        return $actor->can('users.manage');
    }

    /**
     * Determine whether the user can reset password for target.
     */
    public function resetPassword(User $actor, User $target): bool
    {
        return $actor->can('users.manage');
    }
}
