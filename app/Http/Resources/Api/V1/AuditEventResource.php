<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'actor' => $this->whenLoaded('actor', fn () => $this->actor === null ? null : [
                'id' => $this->actor->getKey(),
                'name' => $this->actor->name,
                'email' => $this->actor->email,
            ]),
            'area' => new AreaResource($this->whenLoaded('area')),
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'request_id' => $this->request_id,
            'ip_address' => $this->ip_address,
            'metadata' => $this->metadata,
            'occurred_at' => $this->occurred_at->toIso8601String(),
        ];
    }
}
