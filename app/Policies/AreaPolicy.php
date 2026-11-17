<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;

class AreaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('areas.view');
    }

    public function view(User $user, Area $area): bool
    {
        return $user->hasGlobalPermission('areas.view')
            || ($user->hasPermission('areas.view') && $user->area_id === $area->getKey());
    }

    public function create(User $user): bool
    {
        return $user->hasGlobalPermission('areas.manage');
    }

    public function update(User $user, Area $area): bool
    {
        return $user->hasGlobalPermission('areas.manage');
    }

    public function delete(User $user, Area $area): bool
    {
        return false;
    }

    public function restore(User $user, Area $area): bool
    {
        return false;
    }

    public function forceDelete(User $user, Area $area): bool
    {
        return false;
    }
}
