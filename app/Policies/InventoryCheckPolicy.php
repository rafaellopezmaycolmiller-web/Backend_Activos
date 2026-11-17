<?php

namespace App\Policies;

use App\Models\User;

class InventoryCheckPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermission('inventory.check');
    }
}
