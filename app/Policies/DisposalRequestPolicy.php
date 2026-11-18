<?php

namespace App\Policies;

use App\Models\DisposalRequest;
use App\Models\User;

class DisposalRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('disposals.view');
    }

    public function view(User $user, DisposalRequest $request): bool
    {
        return $user->hasPermission('disposals.view') && ($user->hasGlobalPermission('disposals.view') || $user->area_id === $request->asset->area_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('disposals.request');
    }

    public function decide(User $user, DisposalRequest $request): bool
    {
        return $user->hasPermission('disposals.approve') && $request->requested_by !== $user->id && ($user->hasGlobalPermission('disposals.approve') || $user->area_id === $request->asset->area_id);
    }

    public function review(User $user, DisposalRequest $request): bool
    {
        return $user->hasPermission('disposals.review') && ($user->hasGlobalPermission('disposals.view') || $user->area_id === $request->asset->area_id);
    }
}
