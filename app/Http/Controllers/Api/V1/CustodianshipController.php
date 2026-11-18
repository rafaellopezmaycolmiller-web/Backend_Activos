<?php

namespace App\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustodianshipRequest;
use App\Http\Resources\Api\V1\CustodianshipResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Asset;
use App\Models\Custodianship;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CustodianshipController extends Controller
{
    public function index(Asset $asset): AnonymousResourceCollection
    {
        Gate::authorize('view', $asset);

        return CustodianshipResource::collection($asset->custodianships()->with('user', 'assigner')->latest('started_at')->paginate(25));
    }

    public function candidates(Asset $asset): AnonymousResourceCollection
    {
        Gate::authorize('update', $asset);

        return UserResource::collection(
            User::query()
                ->with('area', 'roles.permissions')
                ->where('is_active', true)
                ->where('area_id', $asset->area_id)
                ->orderBy('name')
                ->get(),
        );
    }

    public function store(StoreCustodianshipRequest $request, Asset $asset, AuditLogger $auditLogger): CustodianshipResource
    {
        Gate::authorize('update', $asset);
        $actor = $request->user();
        $custodianship = DB::transaction(function () use ($request, $asset, $actor): Custodianship {
            $asset->custodianships()->where('status', 'active')->update(['status' => 'ended', 'ended_at' => now()]);

            return $asset->custodianships()->create(['user_id' => $request->integer('user_id'), 'assigned_by' => $actor->id, 'started_at' => now(), 'status' => 'active', 'notes' => $request->string('notes')->toString() ?: null]);
        });
        $auditLogger->record(AuditEventType::CustodyAssigned, $actor, $custodianship, ['asset_id' => $asset->id, 'user_id' => $custodianship->user_id], $asset->area);

        return new CustodianshipResource($custodianship->load('user', 'assigner'));
    }
}
