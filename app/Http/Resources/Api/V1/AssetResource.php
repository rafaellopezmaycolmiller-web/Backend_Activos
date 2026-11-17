<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'asset_code' => $this->asset_code, 'inventory_code' => $this->inventory_code,
            'name' => $this->name, 'description' => $this->description, 'brand' => $this->brand, 'model' => $this->model,
            'serial_number' => $this->serial_number, 'acquisition_date' => $this->acquisition_date?->toDateString(),
            'acquisition_value' => $this->acquisition_value, 'useful_life_years' => $this->useful_life_years,
            'current_version' => $this->current_version, 'is_active' => $this->is_active,
            'category' => new AssetCategoryResource($this->whenLoaded('category')),
            'status' => new AssetStatusResource($this->whenLoaded('status')),
            'area' => new AreaResource($this->whenLoaded('area')),
            'registered_area' => $this->whenLoaded('creator', function (): ?array {
                $area = $this->creator?->area;

                return $area === null ? null : ['id' => $area->id, 'code' => $area->code, 'name' => $area->name];
            }),
            'location' => new AssetLocationResource($this->whenLoaded('location')),
            'documents' => AssetDocumentResource::collection($this->whenLoaded('documents')),
            'versions' => AssetVersionResource::collection($this->whenLoaded('versions')),
        ];
    }
}
