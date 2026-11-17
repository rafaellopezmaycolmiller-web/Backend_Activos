<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisposalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'asset_id' => $this->asset_id, 'asset' => $this->whenLoaded('asset'), 'requested_by' => $this->requested_by, 'decided_by' => $this->decided_by, 'status' => $this->status, 'reason' => $this->reason, 'rejection_reason' => $this->rejection_reason, 'requested_at' => $this->requested_at, 'decided_at' => $this->decided_at];
    }
}
