<?php

namespace App\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DecideMovementRequest;
use App\Http\Requests\Api\V1\StoreMovementRequest;
use App\Http\Resources\Api\V1\MovementRequestResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Asset;
use App\Models\AssetLocation;
use App\Models\Custodianship;
use App\Models\User;
use App\Models\MovementRequest;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MovementRequestController extends Controller
{
    public function recipients(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->hasPermission('movements.request'), 403);
        $query = User::query()->where('is_active', true)->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->integer('area_id')))->with('area');
        return UserResource::collection($query->orderBy('name')->limit(100)->get());
    }
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', MovementRequest::class);
        $actor = $request->user();
        $query = MovementRequest::query()->with('asset.category', 'asset.status', 'fromArea', 'toArea', 'fromLocation', 'toLocation', 'destinationCustodian');
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
        if ($request->filled('to_user_id') && ! User::query()->whereKey($request->integer('to_user_id'))->where('is_active', true)->where('area_id', $request->integer('to_area_id'))->exists()) {
            throw ValidationException::withMessages(['to_user_id' => 'El custodio receptor debe estar activo y pertenecer al área destino.']);
        }
        if (MovementRequest::query()->where('asset_id', $asset->id)->whereIn('status', ['pending', 'approved'])->exists()) {
            throw ValidationException::withMessages(['asset_id' => 'El activo ya tiene una solicitud de movimiento activa.']);
        }
        $movement = MovementRequest::create([...$request->validated(), 'from_area_id' => $asset->area_id, 'from_location_id' => $asset->location_id, 'requested_by' => $actor->id, 'status' => 'pending', 'requested_at' => now()]);
        $auditLogger->record(AuditEventType::MovementRequested, $actor, $movement, ['asset_id' => $asset->id, 'to_area_id' => $movement->to_area_id], $asset->area);

        return new MovementRequestResource($movement->load('asset.category', 'asset.status', 'fromArea', 'toArea', 'fromLocation', 'toLocation', 'destinationCustodian'));
    }

    public function show(MovementRequest $movementRequest): MovementRequestResource
    {
        Gate::authorize('view', $movementRequest);

        return new MovementRequestResource($movementRequest->load('asset.category', 'asset.status', 'fromArea', 'toArea', 'fromLocation', 'toLocation', 'destinationCustodian'));
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
            $movementRequest->refresh();
            $asset = Asset::query()->lockForUpdate()->findOrFail($movementRequest->asset_id);
            if ($movementRequest->status !== 'approved') {
                throw ValidationException::withMessages(['status' => 'La solicitud ya fue procesada.']);
            }
            if ($asset->area_id !== $movementRequest->from_area_id) {
                throw ValidationException::withMessages(['status' => 'El activo cambió de área y la solicitud debe revisarse nuevamente.']);
            }
            $before = $asset->only(['area_id', 'location_id']);
            $asset->update(['area_id' => $movementRequest->to_area_id, 'location_id' => $movementRequest->to_location_id, 'current_version' => $asset->current_version + 1, 'updated_by' => $actor->id]);
            $asset->versions()->create(['version' => $asset->current_version, 'action' => 'moved', 'snapshot' => $asset->only(['asset_code', 'area_id', 'location_id', 'name', 'status_id']), 'changed_by' => $actor->id, 'reason' => $movementRequest->reason]);
            if ($movementRequest->to_user_id !== null) {
                Custodianship::query()->where('asset_id', $asset->id)->where('status', 'active')->update(['status' => 'ended', 'ended_at' => now()]);
                Custodianship::create(['asset_id' => $asset->id, 'user_id' => $movementRequest->to_user_id, 'assigned_by' => $actor->id, 'started_at' => now(), 'status' => 'active', 'notes' => 'Custodia actualizada al recibir movimiento #'.$movementRequest->id]);
            }
            $movementRequest->update(['status' => 'received', 'received_by' => $actor->id, 'received_at' => now()]);
            $auditLogger->record(AuditEventType::MovementReceived, $actor, $movementRequest, ['before' => $before, 'after' => ['area_id' => $asset->area_id, 'location_id' => $asset->location_id]], $asset->area);
        });

        return new MovementRequestResource($movementRequest->refresh()->load('asset.category', 'asset.status', 'fromArea', 'toArea', 'fromLocation', 'toLocation', 'destinationCustodian'));
    }
}
