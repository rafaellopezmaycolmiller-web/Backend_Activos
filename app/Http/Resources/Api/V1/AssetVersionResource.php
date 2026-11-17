<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'version' => $this->version, 'action' => $this->action, 'snapshot' => $this->snapshot, 'changed_by' => $this->changed_by, 'reason' => $this->reason, 'created_at' => $this->created_at?->toIso8601String()];
    }
}
