<?php

namespace App\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DecideMovementRequest;
use App\Http\Requests\Api\V1\StoreMovementRequest;
use App\Http\Resources\Api\V1\MovementRequestResource;
use App\Models\Asset;
use App\Models\AssetLocation;
use App\Models\MovementRequest;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MovementRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', MovementRequest::class);
        $actor = $request->user();
        $query = MovementRequest::query()->with('asset.category', 'asset.status', 'fromArea', 'toArea', 'fromLocation', 'toLocation');
        if (! $actor->hasGlobalPermission('movements.view')) {
            $query->where(fn ($builder) => $builder->where('from_area_id', $actor->area_id)->orWhere('to_area_id', $actor->area_id));
        }

        return MovementRequestResource::collection($query->latest()->paginate(25));
    }

    public function store(StoreMovementRequest $request, AuditLogger $auditLogger): MovementRequestResource
    {
        $actor = $request->user();
        $asset = Asset::query()->findOrFail($request->integer('asset_id'));
        Gate::authorize('view', $asset);
        if ($asset->area_id === $request->integer('to_area_id')) {
            throw ValidationException::withMessages(['to_area_id' => 'El área destino debe ser diferente al área actual.']);
        }
        if ($request->filled('to_location_id') && ! AssetLocation::query()->whereKey($request->integer('to_location_id'))->where('area_id', $request->integer('to_area_id'))->exists()) {
            throw ValidationException::withMessages(['to_location_id' => 'La ubicación destino no pertenece al área seleccionada.']);
        }
        $movement = MovementRequest::create([...$request->validated(), 'from_area_id' => $asset->area_id, 'from_location_id' => $asset->location_id, 'requested_by' => $actor->id, 'status' => 'pending', 'requested_at' => now()]);
        $auditLogger->record(AuditEventType::MovementRequested, $actor, $movement, ['asset_id' => $asset->id, 'to_area_id' => $movement->to_area_id], $asset->area);

        return new MovementRequestResource($movement->load('asset.category', 'asset.status', 'fromArea', 'toArea', 'fromLocation', 'toLocation'));
    }

    public function show(MovementRequest $movementRequest): MovementRequestResource
    {
        Gate::authorize('view', $movementRequest);

        return new MovementRequestResource($movementRequest->load('asset.category', 'asset.status', 'fromArea', 'toArea', 'fromLocation', 'toLocation'));
    }

    public function decide(DecideMovementRequest $request, MovementRequest $movementRequest, AuditLogger $auditLogger): MovementRequestResource
    {
        Gate::authorize('approve', $movementRequest);
        if ($movementRequest->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Solo se pueden decidir solicitudes pendientes.']);
        }
        $approved = $request->string('decision')->toString() === 'approved';
        $movementRequest->update(['status' => $approved ? 'approved' : 'rejected', 'approved_by' => $request->user()->id, 'rejection_reason' => $approved ? null : $request->string('rejection_reason')->toString(), 'decided_at' => now()]);
        $auditLogger->record($approved ? AuditEventType::MovementApproved : AuditEventType::MovementRejected, $request->user(), $movementRequest, ['decision' => $movementRequest->status], $movementRequest->fromArea);

        return new MovementRequestResource($movementRequest->load('asset.category', 'asset.status', 'fromArea', 'toArea', 'fromLocation', 'toLocation'));
    }

    public function receive(MovementRequest $movementRequest, AuditLogger $auditLogger): MovementRequestResource
    {
        Gate::authorize('receive', $movementRequest);
        if ($movementRequest->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Solo se pueden recibir solicitudes aprobadas.']);
        }
        $actor = request()->user();
        DB::transaction(function () use ($movementRequest, $auditLogger, $actor): void {
            $asset = $movementRequest->asset;
            if ($asset->area_id !== $movementRequest->from_area_id) {
                throw ValidationException::withMessages(['status' => 'El activo cambió de área y la solicitud debe revisarse nuevamente.']);
            }
            $before = $asset->only(['area_id', 'location_id']);
            $asset->update(['area_id' => $movementRequest->to_area_id, 'location_id' => $movementRequest->to_location_id, 'current_version' => $asset->current_version + 1, 'updated_by' => $actor->id]);
            $asset->versions()->create(['version' => $asset->current_version, 'action' => 'moved', 'snapshot' => $asset->only(['asset_code', 'area_id', 'location_id', 'name', 'status_id']), 'changed_by' => $actor->id, 'reason' => $movementRequest->reason]);
            $movementRequest->update(['status' => 'received', 'received_by' => $actor->id, 'received_at' => now()]);
            $auditLogger->record(AuditEventType::MovementReceived, $actor, $movementRequest, ['before' => $before, 'after' => ['area_id' => $asset->area_id, 'location_id' => $asset->location_id]], $asset->area);
        });

        return new MovementRequestResource($movementRequest->refresh()->load('asset.category', 'asset.status', 'fromArea', 'toArea', 'fromLocation', 'toLocation'));
    }
}
