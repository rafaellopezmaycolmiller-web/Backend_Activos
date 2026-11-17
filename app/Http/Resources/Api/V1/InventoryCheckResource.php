<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryCheckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'campaign_id' => $this->campaign_id, 'asset_id' => $this->asset_id, 'asset' => $this->whenLoaded('asset'), 'checked_by' => $this->checked_by, 'result' => $this->result, 'observed_area_id' => $this->observed_area_id, 'observed_location_id' => $this->observed_location_id, 'checked_at' => $this->checked_at, 'notes' => $this->notes];
    }
}
