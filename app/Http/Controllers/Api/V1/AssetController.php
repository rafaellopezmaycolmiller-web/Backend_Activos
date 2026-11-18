<?php

namespace App\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAssetRequest;
use App\Http\Requests\Api\V1\UpdateAssetRequest;
use App\Http\Resources\Api\V1\AssetDocumentResource;
use App\Http\Resources\Api\V1\AssetResource;
use App\Models\Asset;
use App\Models\AssetDocument;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Asset::class);
        $actor = $request->user();
        $query = Asset::query()->with('category', 'status', 'area', 'location', 'documents', 'creator.area');
        if (! $actor->hasGlobalPermission('assets.view')) {
            $query->where('area_id', $actor->area_id);
        }
        if ($request->boolean('mine')) {
            $query->where('created_by', $actor->id);
        }
        if ($request->filled('search')) {
            $search = '%'.strtolower($request->string('search')->trim()).'%';
            $query->where(fn ($builder) => $builder
                ->whereRaw('LOWER(asset_code) LIKE ?', [$search])
                ->orWhereRaw('LOWER(name) LIKE ?', [$search])
                ->orWhereRaw("LOWER(COALESCE(serial_number, '')) LIKE ?", [$search]));
        }

        return AssetResource::collection($query->orderBy('name')->paginate(min(50, max(1, $request->integer('per_page', 20)))));
    }

    public function store(StoreAssetRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $actor = $request->user();
        $attributes = collect($request->validated())->except('reason')->all();
        $attributes['created_by'] = $actor->id;
        $attributes['updated_by'] = $actor->id;
        $attributes['current_version'] = 1;
        $asset = DB::transaction(function () use ($attributes, $actor, $auditLogger): Asset {
            $asset = Asset::create($attributes);
            $asset->versions()->create(['version' => 1, 'action' => 'created', 'snapshot' => $this->snapshot($asset), 'changed_by' => $actor->id]);
            $auditLogger->record(AuditEventType::AssetCreated, $actor, $asset, ['version' => 1], $asset->area);

            return $asset;
        });

        return (new AssetResource($asset->load('category', 'status', 'area', 'location', 'documents', 'versions', 'creator.area')))
            ->additional(['message' => 'Activo registrado correctamente.'])
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Asset $asset): AssetResource
    {
        Gate::authorize('view', $asset);

        return new AssetResource($asset->load('category', 'status', 'area', 'location', 'documents', 'versions', 'creator.area'));
    }

    public function byCode(Request $request, string $assetCode): AssetResource
    {
        $asset = Asset::query()->where('asset_code', $assetCode)->firstOrFail();
        Gate::authorize('view', $asset);

        return new AssetResource($asset->load('category', 'status', 'area', 'location', 'documents', 'versions', 'creator.area'));
    }

    public function update(UpdateAssetRequest $request, Asset $asset, AuditLogger $auditLogger): AssetResource
    {
        $actor = $request->user();
        $before = $this->snapshot($asset);
        $validated = $request->validated();
        $reason = $validated['reason'];
        unset($validated['reason']);
        DB::transaction(function () use ($asset, $validated, $actor, $auditLogger, $reason, $before): void {
            $asset->update([...$validated, 'updated_by' => $actor->id, 'current_version' => $asset->current_version + 1]);
            $asset->versions()->create(['version' => $asset->current_version, 'action' => 'updated', 'snapshot' => $this->snapshot($asset), 'changed_by' => $actor->id, 'reason' => $reason]);
            $auditLogger->record(AuditEventType::AssetUpdated, $actor, $asset, ['before' => $before, 'after' => $this->snapshot($asset), 'reason' => $reason], $asset->area);
        });

        return (new AssetResource($asset->refresh()->load('category', 'status', 'area', 'location', 'documents', 'versions', 'creator.area')))
            ->additional(['message' => 'Activo actualizado y versionado.']);
    }

    public function documents(Asset $asset): AnonymousResourceCollection
    {
        Gate::authorize('view', $asset);

        return AssetDocumentResource::collection($asset->documents()->latest()->paginate(25));
    }

    public function showDocument(Asset $asset, AssetDocument $document): Response
    {
        Gate::authorize('view', $asset);
        abort_unless($document->asset_id === $asset->getKey(), Response::HTTP_NOT_FOUND);

        $fileName = str_replace(['"', "\r", "\n"], '', $document->file_name);

        return Storage::disk($document->disk)->response($document->path, $fileName, [
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    public function uploadDocument(Request $request, Asset $asset, AuditLogger $auditLogger): JsonResponse
    {
        Gate::authorize('uploadDocument', $asset);
        $request->validate([
            'document_type' => ['required', Rule::in(['technical_sheet', 'invoice', 'act', 'photo', 'other'])],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);
        $file = $request->file('file');
        $sha256 = hash_file('sha256', $file->getRealPath());
        $existingDocument = $asset->documents()->where('sha256', $sha256)->first();

        if ($existingDocument !== null) {
            return (new AssetDocumentResource($existingDocument))
                ->additional(['message' => 'Este documento ya estaba registrado para el activo.'])
                ->response()->setStatusCode(Response::HTTP_OK);
        }

        $path = $file->store('assets/'.$asset->asset_code, 'private');
        $document = $asset->documents()->create([
            'document_type' => $request->string('document_type')->toString(),
            'file_name' => $file->getClientOriginalName(), 'disk' => 'private', 'path' => $path,
            'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'sha256' => $sha256,
            'uploaded_by' => $request->user()->id,
        ]);
        $auditLogger->record(AuditEventType::AssetDocumentUploaded, $request->user(), $asset, ['document_id' => $document->id, 'document_type' => $document->document_type], $asset->area);

        return (new AssetDocumentResource($document))->additional(['message' => 'Documento almacenado correctamente.'])->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /** @return array<string, mixed> */
    private function snapshot(Asset $asset): array
    {
        return $asset->only(['asset_code', 'inventory_code', 'category_id', 'status_id', 'area_id', 'location_id', 'name', 'description', 'brand', 'model', 'serial_number', 'acquisition_date', 'acquisition_value', 'useful_life_years', 'is_active']);
    }
}
