<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuditEventResource;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AuditEventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AuditEvent::class);

        /** @var User $user */
        $user = $request->user();
        $query = AuditEvent::query()->with('actor', 'area');

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

        return AuditEventResource::collection($events);
    }

    public function show(AuditEvent $auditEvent): AuditEventResource
    {
        Gate::authorize('view', $auditEvent);

        return new AuditEventResource(
            $auditEvent->load('actor', 'area'),
        );
    }
}
