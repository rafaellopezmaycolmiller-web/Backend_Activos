<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAssetCategoryRequest;
use App\Http\Requests\Api\V1\UpdateAssetCategoryRequest;
use App\Http\Resources\Api\V1\AssetCategoryResource;
use App\Models\AssetCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AssetCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AssetCategory::class);

        return AssetCategoryResource::collection(
            AssetCategory::query()
                ->whereIn('code', AssetCategory::PROJECT_SCOPE_CODES)
                ->orderBy('name')
                ->get(),
        );
    }

    public function store(StoreAssetCategoryRequest $request): AssetCategoryResource
    {
        return new AssetCategoryResource(AssetCategory::create($request->validated()));
    }

    public function show(AssetCategory $assetCategory): AssetCategoryResource
    {
        Gate::authorize('view', $assetCategory);

        return new AssetCategoryResource($assetCategory);
    }

    public function update(UpdateAssetCategoryRequest $request, AssetCategory $assetCategory): AssetCategoryResource
    {
        $assetCategory->update($request->validated());

        return new AssetCategoryResource($assetCategory);
    }
}
