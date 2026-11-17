<?php

namespace App\Policies;

use App\Models\MovementRequest;
use App\Models\User;

class MovementRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('movements.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MovementRequest $movementRequest): bool
    {
        return $user->hasPermission('movements.view') && ($user->hasGlobalPermission('movements.view') || $user->area_id === $movementRequest->from_area_id || $user->area_id === $movementRequest->to_area_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('movements.request');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MovementRequest $movementRequest): bool
    {
        return $user->hasPermission('movements.approve') && $movementRequest->requested_by !== $user->id && ($user->hasGlobalPermission('movements.approve') || $user->area_id === $movementRequest->from_area_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MovementRequest $movementRequest): bool
    {
        return false;
    }

    public function approve(User $user, MovementRequest $movementRequest): bool
    {
        return $user->hasPermission('movements.approve')
            && $movementRequest->requested_by !== $user->id
            && ($user->hasGlobalPermission('movements.approve') || $user->area_id === $movementRequest->from_area_id);
    }

    public function receive(User $user, MovementRequest $movementRequest): bool
    {
        return $user->hasPermission('movements.receive')
            && ($user->hasGlobalPermission('movements.receive') || $user->area_id === $movementRequest->to_area_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MovementRequest $movementRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MovementRequest $movementRequest): bool
    {
        return false;
    }
}
