<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovementRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'asset_id' => $this->asset_id, 'asset' => $this->whenLoaded('asset'),
            'from_area_id' => $this->from_area_id, 'from_area' => $this->whenLoaded('fromArea'),
            'from_location_id' => $this->from_location_id, 'from_location' => $this->whenLoaded('fromLocation'),
            'to_area_id' => $this->to_area_id, 'to_area' => $this->whenLoaded('toArea'),
            'to_location_id' => $this->to_location_id, 'to_location' => $this->whenLoaded('toLocation'),
            'requested_by' => $this->requested_by, 'approved_by' => $this->approved_by, 'received_by' => $this->received_by,
            'status' => $this->status, 'reason' => $this->reason, 'rejection_reason' => $this->rejection_reason,
            'requested_at' => $this->requested_at, 'decided_at' => $this->decided_at, 'received_at' => $this->received_at,
        ];
    }
}
