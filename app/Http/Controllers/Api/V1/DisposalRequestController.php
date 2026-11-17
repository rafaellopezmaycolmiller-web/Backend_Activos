<?php

namespace App\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DecideDisposalRequest;
use App\Http\Requests\Api\V1\StoreDisposalRequest;
use App\Http\Resources\Api\V1\DisposalRequestResource;
use App\Models\Asset;
use App\Models\DisposalRequest;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DisposalRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', DisposalRequest::class);
        $query = DisposalRequest::query()->with('asset.category', 'asset.status')->latest();
        if (! $request->user()->hasGlobalPermission('disposals.view')) {
            $query->whereHas('asset', fn ($q) => $q->where('area_id', $request->user()->area_id));
        }

return DisposalRequestResource::collection($query->paginate(25));
    }

    public function store(StoreDisposalRequest $request, Asset $asset, AuditLogger $auditLogger): DisposalRequestResource
    {
        Gate::authorize('view', $asset);
        $disposal = DisposalRequest::create(['asset_id' => $asset->id, 'requested_by' => $request->user()->id, 'status' => 'pending', 'reason' => $request->string('reason')->toString(), 'requested_at' => now()]);
        $auditLogger->record(AuditEventType::DisposalRequested, $request->user(), $disposal, ['asset_id' => $asset->id], $asset->area);

        return new DisposalRequestResource($disposal->load('asset.category', 'asset.status'));
    }

    public function decide(DecideDisposalRequest $request, DisposalRequest $disposalRequest, AuditLogger $auditLogger): DisposalRequestResource
    {
        Gate::authorize('decide', $disposalRequest);
        if ($disposalRequest->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Solo se pueden decidir solicitudes pendientes.']);
        } $approved = $request->string('decision')->toString() === 'approved';
        DB::transaction(function () use ($approved, $request, $disposalRequest): void {
            $disposalRequest->update(['status' => $approved ? 'approved' : 'rejected', 'decided_by' => $request->user()->id, 'rejection_reason' => $approved ? null : $request->string('rejection_reason')->toString(), 'decided_at' => now()]);
            if ($approved) {
                $asset = $disposalRequest->asset;
                $asset->update(['is_active' => false, 'current_version' => $asset->current_version + 1, 'updated_by' => $request->user()->id]);
                $asset->versions()->create(['version' => $asset->current_version, 'action' => 'disposed', 'snapshot' => $asset->only(['asset_code', 'area_id', 'location_id', 'name', 'status_id', 'is_active']), 'changed_by' => $request->user()->id, 'reason' => $disposalRequest->reason]);
            }
        });
        $auditLogger->record($approved ? AuditEventType::DisposalApproved : AuditEventType::DisposalRejected, $request->user(), $disposalRequest, ['decision' => $disposalRequest->status], $disposalRequest->asset->area);

        return new DisposalRequestResource($disposalRequest->refresh()->load('asset.category', 'asset.status'));
    }
}
