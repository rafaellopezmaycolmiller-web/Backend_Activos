<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'document_number' => $this->document_number,
            'job_title' => $this->job_title,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'mfa_required' => $this->mfa_required,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'area' => new AreaResource($this->whenLoaded('area')),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'permissions' => $this->resource->permissionCodes(),
        ];
    }
}
