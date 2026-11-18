<?php

namespace App\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTechnicalEvaluationRequest;
use App\Http\Requests\Api\V1\UpdateTechnicalEvaluationRequest;
use App\Http\Resources\Api\V1\TechnicalEvaluationResource;
use App\Models\Asset;
use App\Models\TechnicalEvaluation;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TechnicalEvaluationController extends Controller
{
    public function show(Request $request, TechnicalEvaluation $technicalEvaluation): TechnicalEvaluationResource
    {
        abort_unless($request->user()->hasPermission('evaluations.view'), 403);
        $evaluation = $technicalEvaluation->load('asset.category', 'asset.status', 'asset.area', 'requester', 'specialist', 'documents');
        Gate::authorize('view', $evaluation->asset);
        return new TechnicalEvaluationResource($evaluation);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->hasPermission('evaluations.view'), 403);
        $query = TechnicalEvaluation::with('asset.category', 'asset.status', 'asset.area', 'requester', 'specialist', 'documents')->latest();
        if (! $request->user()->hasGlobalPermission('evaluations.view')) {
            $query->whereHas('asset', fn ($q) => $q->where('area_id', $request->user()->area_id));
        }
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());
        return TechnicalEvaluationResource::collection($query->paginate(25));
    }

    public function store(StoreTechnicalEvaluationRequest $request, AuditLogger $auditLogger): TechnicalEvaluationResource
    {
        $asset = Asset::query()->with('area')->findOrFail($request->integer('asset_id'));
        Gate::authorize('view', $asset);
        $assignedTo = $request->integer('assigned_to') ?: null;
        if ($assignedTo !== null && ! $request->user()->hasPermission('evaluations.assign')) $assignedTo = null;
        $evaluation = TechnicalEvaluation::create([
            'asset_id' => $asset->id, 'requested_by' => $request->user()->id, 'assigned_to' => $assignedTo,
            'status' => $assignedTo ? 'assigned' : 'requested', 'reason' => $request->string('reason')->toString(),
            'observations' => $request->string('observations')->toString() ?: null, 'requested_at' => now(),
            'assigned_at' => $assignedTo ? now() : null,
        ]);
        $auditLogger->record($assignedTo ? AuditEventType::EvaluationAssigned : AuditEventType::EvaluationRequested, $request->user(), $evaluation, ['asset_id' => $asset->id], $asset->area);
        return new TechnicalEvaluationResource($evaluation->load('asset.category', 'asset.status', 'asset.area', 'requester', 'specialist', 'documents'));
    }

    public function update(UpdateTechnicalEvaluationRequest $request, TechnicalEvaluation $technicalEvaluation, AuditLogger $auditLogger): TechnicalEvaluationResource
    {
        $evaluation = $technicalEvaluation->load('asset.area');
        Gate::authorize('view', $evaluation->asset);
        $newStatus = $request->string('status')->toString();
        $allowed = ['requested' => ['assigned'], 'assigned' => ['evaluated'], 'evaluated' => ['reviewed'], 'reviewed' => []];
        if (! in_array($newStatus, $allowed[$evaluation->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'Transición de evaluación no permitida.']);
        }
        $evaluation->update([
            'status' => $newStatus, 'physical_condition' => $request->string('physical_condition')->toString() ?: null,
            'diagnosis' => $request->string('diagnosis')->toString() ?: null, 'recommendations' => $request->string('recommendations')->toString() ?: null,
            'observations' => $request->string('observations')->toString() ?: null,
            'assigned_at' => $newStatus === 'assigned' ? now() : $evaluation->assigned_at,
            'evaluated_at' => $newStatus === 'evaluated' ? now() : $evaluation->evaluated_at,
            'reviewed_at' => $newStatus === 'reviewed' ? now() : $evaluation->reviewed_at,
        ]);
        $auditLogger->record($newStatus === 'reviewed' ? AuditEventType::EvaluationCompleted : AuditEventType::EvaluationAssigned, $request->user(), $evaluation, ['from' => $technicalEvaluation->getOriginal('status'), 'to' => $newStatus], $evaluation->asset->area);
        return new TechnicalEvaluationResource($evaluation->refresh()->load('asset.category', 'asset.status', 'asset.area', 'requester', 'specialist', 'documents'));
    }

    public function upload(Request $request, TechnicalEvaluation $technicalEvaluation, AuditLogger $auditLogger): TechnicalEvaluationResource
    {
        abort_unless($request->user()->hasPermission('evaluations.update'), 403);
        $request->validate(['file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp']]);
        $evaluation = $technicalEvaluation->load('asset.area');
        Gate::authorize('view', $evaluation->asset);
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $path = $file->store('technical-evaluations/'.$evaluation->id, 'local');
        $evaluation->documents()->create(['uploaded_by' => $request->user()->id, 'file_name' => $file->getClientOriginalName(), 'path' => $path, 'mime_type' => $file->getMimeType() ?: 'application/octet-stream', 'size' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath())]);
        $auditLogger->record(AuditEventType::EvaluationDocumentUploaded, $request->user(), $evaluation, ['file_name' => $file->getClientOriginalName()], $evaluation->asset->area);
        return new TechnicalEvaluationResource($evaluation->refresh()->load('asset.category', 'asset.status', 'asset.area', 'requester', 'specialist', 'documents'));
    }

    public function document(Request $request, TechnicalEvaluation $technicalEvaluation, int $document)
    {
        abort_unless($request->user()->hasPermission('evaluations.view'), 403);
        $evaluation = $technicalEvaluation->load('asset.area'); Gate::authorize('view', $evaluation->asset);
        $record = $evaluation->documents()->findOrFail($document);
        abort_unless(Storage::disk('local')->exists($record->path), 404);
        return Storage::disk('local')->download($record->path, $record->file_name, ['Content-Type' => $record->mime_type]);
    }
}
