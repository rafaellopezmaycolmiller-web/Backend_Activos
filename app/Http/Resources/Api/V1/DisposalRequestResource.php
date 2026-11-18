<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisposalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'asset_id' => $this->asset_id, 'asset' => $this->whenLoaded('asset'), 'requested_by' => $this->requested_by, 'requester' => $this->whenLoaded('requester'), 'decided_by' => $this->decided_by, 'decider' => $this->whenLoaded('decider'), 'reviewed_by' => $this->reviewed_by, 'reviewer' => $this->whenLoaded('reviewer'), 'technical_evaluation_id' => $this->technical_evaluation_id, 'technical_evaluation' => $this->whenLoaded('technicalEvaluation'), 'status' => $this->status, 'authority_status' => $this->authority_status, 'reason' => $this->reason, 'cause' => $this->cause, 'documentation_notes' => $this->documentation_notes, 'decision_notes' => $this->decision_notes, 'rejection_reason' => $this->rejection_reason, 'requested_at' => $this->requested_at, 'reviewed_at' => $this->reviewed_at, 'decided_at' => $this->decided_at, 'documents' => DisposalRequestDocumentResource::collection($this->whenLoaded('documents'))];
    }
}
