<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicalEvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'asset_id' => $this->asset_id, 'asset' => $this->whenLoaded('asset'),
            'requested_by' => $this->requested_by, 'requester' => $this->whenLoaded('requester'),
            'assigned_to' => $this->assigned_to, 'specialist' => $this->whenLoaded('specialist'),
            'status' => $this->status, 'reason' => $this->reason, 'physical_condition' => $this->physical_condition,
            'diagnosis' => $this->diagnosis, 'recommendations' => $this->recommendations, 'observations' => $this->observations,
            'requested_at' => $this->requested_at, 'assigned_at' => $this->assigned_at, 'evaluated_at' => $this->evaluated_at,
            'reviewed_at' => $this->reviewed_at, 'documents' => TechnicalEvaluationDocumentResource::collection($this->whenLoaded('documents')),
        ];
    }
}
