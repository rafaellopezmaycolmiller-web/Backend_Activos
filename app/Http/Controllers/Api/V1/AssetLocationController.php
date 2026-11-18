<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAssetLocationRequest;
use App\Http\Requests\Api\V1\UpdateAssetLocationRequest;
use App\Http\Resources\Api\V1\AssetLocationResource;
use App\Models\AssetLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AssetLocationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AssetLocation::class);
        $query = AssetLocation::query()->with('area');
        // Movement requesters need destination locations for the selection
        // form; the movement endpoint validates that the location belongs to
        // the chosen destination area.
        if (! $request->user()->hasGlobalPermission('locations.view') && ! $request->user()->hasPermission('movements.request')) {
            $query->where('area_id', $request->user()->area_id);
        }

        return AssetLocationResource::collection($query->orderBy('name')->get());
    }

    public function store(StoreAssetLocationRequest $request): AssetLocationResource
    {
        return new AssetLocationResource(AssetLocation::create($request->validated())->load('area'));
    }

    public function show(AssetLocation $assetLocation): AssetLocationResource
    {
        Gate::authorize('view', $assetLocation);

        return new AssetLocationResource($assetLocation->load('area'));
    }

    public function update(UpdateAssetLocationRequest $request, AssetLocation $assetLocation): AssetLocationResource
    {
        $assetLocation->update($request->validated());

        return new AssetLocationResource($assetLocation->load('area'));
    }
}
