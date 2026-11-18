<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicalEvaluationDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'file_name' => $this->file_name, 'mime_type' => $this->mime_type, 'size' => $this->size, 'sha256' => $this->sha256, 'uploaded_by' => $this->uploaded_by, 'created_at' => $this->created_at];
    }
}
