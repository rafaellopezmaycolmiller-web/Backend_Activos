<?php

namespace App\Policies;

use App\Models\AssetLocation;
use App\Models\User;

class AssetLocationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('locations.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AssetLocation $assetLocation): bool
    {
        return $user->hasPermission('locations.view') && ($user->hasGlobalPermission('locations.view') || $user->area_id === $assetLocation->area_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasGlobalPermission('locations.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AssetLocation $assetLocation): bool
    {
        return $user->hasGlobalPermission('locations.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AssetLocation $assetLocation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AssetLocation $assetLocation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AssetLocation $assetLocation): bool
    {
        return false;
    }
}
