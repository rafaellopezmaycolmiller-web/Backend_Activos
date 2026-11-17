<?php

namespace App\Services;

use App\AuditEventType;
use App\Models\Area;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogger
{
    public function __construct(private Request $request) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        AuditEventType|string $event,
        ?User $actor = null,
        ?Model $subject = null,
        array $metadata = [],
        ?Area $area = null,
    ): AuditEvent {
        return AuditEvent::create([
            'actor_id' => $actor?->getKey(),
            'area_id' => $area?->getKey() ?? $actor?->area_id,
            'event' => $event instanceof AuditEventType ? $event->value : $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject === null ? null : (string) $subject->getKey(),
            'request_id' => $this->requestId(),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }

    private function requestId(): string
    {
        if ($this->request->attributes->has('audit_request_id')) {
            return (string) $this->request->attributes->get('audit_request_id');
        }

        $requestId = (string) Str::uuid();
        $this->request->attributes->set('audit_request_id', $requestId);

        return $requestId;
    }
}
