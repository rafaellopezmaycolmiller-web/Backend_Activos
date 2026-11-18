<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'area_id' => $this->area_id, 'area' => $this->whenLoaded('area'), 'created_by' => $this->created_by, 'status' => $this->status, 'started_at' => $this->started_at, 'closed_at' => $this->closed_at, 'notes' => $this->notes, 'checks_count' => $this->when(isset($this->checks_count), $this->checks_count), 'assets_total' => $this->when(isset($this->assets_total), $this->assets_total), 'found_count' => $this->when(isset($this->found_count), $this->found_count), 'not_found_count' => $this->when(isset($this->not_found_count), $this->not_found_count), 'location_mismatch_count' => $this->when(isset($this->location_mismatch_count), $this->location_mismatch_count), 'pending_count' => $this->when(isset($this->pending_count), $this->pending_count)];
    }
}
