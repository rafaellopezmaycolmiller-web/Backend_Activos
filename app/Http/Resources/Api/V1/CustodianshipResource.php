<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustodianshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'asset_id' => $this->asset_id, 'user_id' => $this->user_id, 'assigned_by' => $this->assigned_by, 'status' => $this->status, 'notes' => $this->notes, 'started_at' => $this->started_at, 'ended_at' => $this->ended_at];
    }
}
