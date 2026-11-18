<?php

namespace App\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreInventoryCampaignRequest;
use App\Http\Requests\Api\V1\StoreInventoryCheckRequest;
use App\Http\Resources\Api\V1\InventoryCampaignResource;
use App\Http\Resources\Api\V1\InventoryCheckResource;
use App\Models\Asset;
use App\Models\InventoryCampaign;
use App\Models\InventoryCheck;
use App\Services\AuditLogger;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class InventoryCampaignController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', InventoryCampaign::class);

        $campaigns = InventoryCampaign::query()->with('area')->withCount('checks')->latest()->paginate(25);
        $campaigns->getCollection()->transform(fn (InventoryCampaign $campaign) => $this->withStats($campaign));
        return InventoryCampaignResource::collection($campaigns);
    }

    public function store(StoreInventoryCampaignRequest $request, AuditLogger $auditLogger): InventoryCampaignResource
    {
        $campaign = InventoryCampaign::create([...$request->validated(), 'created_by' => $request->user()->id, 'status' => 'planned']);
        $auditLogger->record(AuditEventType::InventoryCampaignCreated, $request->user(), $campaign, ['code' => $campaign->code], $campaign->area);

        return new InventoryCampaignResource($campaign->load('area'));
    }

    public function show(InventoryCampaign $inventoryCampaign): InventoryCampaignResource
    {
        Gate::authorize('view', $inventoryCampaign);

        return new InventoryCampaignResource($this->withStats($inventoryCampaign->load('area')->loadCount('checks')));
    }

    public function checks(InventoryCampaign $inventoryCampaign): AnonymousResourceCollection
    {
        Gate::authorize('view', $inventoryCampaign);

        return InventoryCheckResource::collection($inventoryCampaign->checks()->with('asset.category', 'asset.status')->latest('checked_at')->paginate(50));
    }

    public function check(StoreInventoryCheckRequest $request, InventoryCampaign $inventoryCampaign, AuditLogger $auditLogger): InventoryCheckResource
    {
        Gate::authorize('view', $inventoryCampaign);
        Gate::authorize('create', InventoryCheck::class);
        if ($inventoryCampaign->status !== 'in_progress') {
            throw ValidationException::withMessages(['status' => 'La campaña debe estar en curso para registrar verificaciones.']);
        }
        $asset = Asset::query()->findOrFail($request->integer('asset_id'));
        if ($inventoryCampaign->area_id !== $asset->area_id) {
            throw ValidationException::withMessages(['asset_id' => 'El activo no pertenece al alcance de la campaña.']);
        }
        if (! $request->user()->hasGlobalPermission('inventory.check') && $request->user()->area_id !== $asset->area_id) {
            throw ValidationException::withMessages(['asset_id' => 'El activo no pertenece al alcance del usuario.']);
        }
        $check = DB::transaction(function () use ($request, $inventoryCampaign): InventoryCheck {
            if ($inventoryCampaign->checks()->where('asset_id', $request->integer('asset_id'))->exists()) {
                throw ValidationException::withMessages(['asset_id' => 'El activo ya fue verificado en esta campaña.']);
            }
            return $inventoryCampaign->checks()->create([...$request->validated(), 'checked_by' => $request->user()->id, 'checked_at' => now()]);
        });
        $auditLogger->record(AuditEventType::InventoryCheckRecorded, $request->user(), $check, ['result' => $check->result, 'campaign_id' => $inventoryCampaign->id], $inventoryCampaign->area);

        return new InventoryCheckResource($check->load('asset.category', 'asset.status'));
    }

    public function start(InventoryCampaign $inventoryCampaign, AuditLogger $auditLogger): InventoryCampaignResource
    {
        Gate::authorize('update', $inventoryCampaign);
        if ($inventoryCampaign->status !== 'planned') {
            throw ValidationException::withMessages(['status' => 'Solo se puede iniciar una campaña planificada.']);
        }
        $inventoryCampaign->update(['status' => 'in_progress', 'started_at' => now()]);
        $auditLogger->record(AuditEventType::InventoryCampaignStarted, request()->user(), $inventoryCampaign, [], $inventoryCampaign->area);

        return new InventoryCampaignResource($inventoryCampaign->load('area'));
    }

    public function close(InventoryCampaign $inventoryCampaign, AuditLogger $auditLogger): InventoryCampaignResource
    {
        Gate::authorize('update', $inventoryCampaign);
        if ($inventoryCampaign->status !== 'in_progress') {
            throw ValidationException::withMessages(['status' => 'Solo se puede cerrar una campaña en curso.']);
        }
        $inventoryCampaign->update(['status' => 'closed', 'closed_at' => now()]);
        $auditLogger->record(AuditEventType::InventoryCampaignClosed, request()->user(), $inventoryCampaign, ['checks' => $inventoryCampaign->checks()->count()], $inventoryCampaign->area);

        return new InventoryCampaignResource($inventoryCampaign->load('area'));
    }

    private function withStats(InventoryCampaign $campaign): InventoryCampaign
    {
        $total = Asset::query()->where('area_id', $campaign->area_id)->where('is_active', true)->count();
        $counts = $campaign->checks()->selectRaw("result, count(*) as aggregate")->groupBy('result')->pluck('aggregate', 'result');
        $campaign->setAttribute('assets_total', $total);
        $campaign->setAttribute('found_count', (int) ($counts['found'] ?? 0));
        $campaign->setAttribute('not_found_count', (int) ($counts['not_found'] ?? 0));
        $campaign->setAttribute('location_mismatch_count', (int) ($counts['location_mismatch'] ?? 0));
        $campaign->setAttribute('pending_count', max(0, $total - $campaign->checks()->count()));
        return $campaign;
    }
}
