<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'is_system' => $this->is_system,
            'scope' => $this->whenPivotLoaded('role_user', fn () => $this->pivot->scope),
            'permissions' => $this->whenLoaded(
                'permissions',
                fn () => $this->permissions->pluck('code')->sort()->values()->all(),
            ),
        ];
    }
}
