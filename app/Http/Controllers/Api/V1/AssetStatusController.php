<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AssetStatusResource;
use App\Models\AssetStatus;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AssetStatusController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AssetStatus::class);

        return AssetStatusResource::collection(AssetStatus::query()->orderBy('name')->get());
    }
}
