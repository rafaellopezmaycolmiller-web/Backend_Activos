<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustodianshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'user_id' => $this->user_id,
            'responsible_name' => $this->responsible_name,
            'responsible_document' => $this->responsible_document,
            'area_id' => $this->area_id,
            'location_id' => $this->location_id,
            'assigned_by' => $this->assigned_by,
            'user' => $this->whenLoaded('user', fn (): ?array => $this->user === null ? ($this->responsible_name === null ? null : ['id' => 0, 'name' => $this->responsible_name, 'email' => $this->responsible_document]) : ['id' => $this->user->id, 'name' => $this->user->name, 'email' => $this->user->email]),
            'assigner' => $this->whenLoaded('assigner', fn (): array => ['id' => $this->assigner->id, 'name' => $this->assigner->name, 'email' => $this->assigner->email]),
            'status' => $this->status,
            'notes' => $this->notes,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
        ];
    }
}
