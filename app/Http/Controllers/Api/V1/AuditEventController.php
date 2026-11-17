<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuditEventResource;
use App\Models\AuditEvent;
use App\Models\Custodianship;
use App\Models\DisposalRequest;
use App\Models\InventoryCheck;
use App\Models\MovementRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuditEventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AuditEvent::class);

        /** @var User $user */
        $user = $request->user();
        $query = AuditEvent::query()->whereNull('archived_at')->with('actor.area', 'actor.roles', 'area', 'subject');

        if (! $user->hasGlobalPermission('audit.view')) {
            $query->where('area_id', $user->area_id);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->string('event')->toString());
        }

        $events = $query
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(max(1, min($request->integer('per_page', 25), 100)));

        $events->getCollection()->loadMorph('subject', [
            Custodianship::class => ['asset'],
            DisposalRequest::class => ['asset'],
            InventoryCheck::class => ['asset', 'campaign'],
            MovementRequest::class => ['asset', 'fromArea', 'toArea'],
        ]);

        return AuditEventResource::collection($events);
    }

    public function archive(Request $request, AuditEvent $auditEvent): JsonResponse
    {
        Gate::authorize('archive', $auditEvent);

        $reason = trim($request->string('reason')->toString());
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Indique el motivo de la corrección.']);
        }

        $auditEvent->forceFill([
            'archived_at' => now(),
            'archived_by' => $request->user()->getKey(),
            'archive_reason' => $reason,
        ])->saveQuietly();

        return response()->json(['message' => 'Evento archivado correctamente.']);
    }

    public function show(AuditEvent $auditEvent): AuditEventResource
    {
        Gate::authorize('view', $auditEvent);

        $auditEvent->load('actor.area', 'actor.roles', 'area', 'subject');
        $auditEvent->loadMorph('subject', [
            Custodianship::class => ['asset'],
            DisposalRequest::class => ['asset'],
            InventoryCheck::class => ['asset', 'campaign'],
            MovementRequest::class => ['asset', 'fromArea', 'toArea'],
        ]);

        return new AuditEventResource($auditEvent);
    }
}
