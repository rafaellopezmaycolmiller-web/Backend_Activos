<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    public function view(User $user, User $targetUser): bool
    {
        return $user->is($targetUser)
            || $user->hasGlobalPermission('users.view')
            || ($user->hasPermission('users.view') && $user->area_id === $targetUser->area_id);
    }

    public function create(User $user): bool
    {
        return $user->hasGlobalPermission('users.create')
            && $user->hasGlobalPermission('roles.assign');
    }

    public function update(User $user, User $targetUser): bool
    {
        return $user->hasGlobalPermission('users.update')
            && $user->hasGlobalPermission('roles.assign')
            && ! $user->is($targetUser);
    }

    public function delete(User $user, User $targetUser): bool
    {
        return false;
    }

    public function restore(User $user, User $targetUser): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $targetUser): bool
    {
        return false;
    }
}
