<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('assets.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Asset $asset): bool
    {
        return $user->hasGlobalPermission('assets.view')
            || ($user->hasPermission('assets.view') && ($user->area_id === $asset->area_id || $asset->created_by === $user->id));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('assets.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.update') && ($user->hasGlobalPermission('assets.update') || $user->area_id === $asset->area_id);
    }

    public function uploadDocument(User $user, Asset $asset): bool
    {
        return $user->hasPermission('asset_documents.upload')
            && ($user->hasGlobalPermission('asset_documents.upload') || $user->area_id === $asset->area_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Asset $asset): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Asset $asset): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Asset $asset): bool
    {
        return false;
    }
}
