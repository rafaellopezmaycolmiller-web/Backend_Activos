<?php

namespace App\Policies;

use App\Models\InventoryCampaign;
use App\Models\User;

class InventoryCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('inventory.view');
    }

    public function view(User $user, InventoryCampaign $campaign): bool
    {
        return $user->hasPermission('inventory.view') && ($user->hasGlobalPermission('inventory.view') || $user->area_id === $campaign->area_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('inventory.create');
    }

    public function update(User $user, InventoryCampaign $campaign): bool
    {
        return $user->hasPermission('inventory.manage') && ($user->hasGlobalPermission('inventory.manage') || $user->area_id === $campaign->area_id);
    }
}
