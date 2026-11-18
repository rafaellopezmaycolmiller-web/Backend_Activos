<?php

namespace App\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DecideDisposalRequest;
use App\Http\Requests\Api\V1\StoreDisposalRequest;
use App\Http\Requests\Api\V1\ReviewDisposalRequest;
use App\Http\Resources\Api\V1\DisposalRequestResource;
use App\Models\Asset;
use App\Models\DisposalRequest;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DisposalRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', DisposalRequest::class);
        $query = DisposalRequest::query()->with('asset.category', 'asset.status', 'asset.area', 'technicalEvaluation', 'requester', 'reviewer', 'decider', 'documents')->latest();
        if (! $request->user()->hasGlobalPermission('disposals.view')) {
            $query->whereHas('asset', fn ($q) => $q->where('area_id', $request->user()->area_id));
        }

return DisposalRequestResource::collection($query->paginate(25));
    }

    public function store(StoreDisposalRequest $request, Asset $asset, AuditLogger $auditLogger): DisposalRequestResource
    {
        Gate::authorize('view', $asset);
        $disposal = DisposalRequest::create(['asset_id' => $asset->id, 'requested_by' => $request->user()->id, 'status' => 'pending', 'authority_status' => 'pending_validation', 'reason' => $request->string('reason')->toString(), 'cause' => $request->string('cause')->toString(), 'technical_evaluation_id' => $request->integer('technical_evaluation_id') ?: null, 'documentation_notes' => $request->string('documentation_notes')->toString() ?: null, 'requested_at' => now()]);
        $auditLogger->record(AuditEventType::DisposalRequested, $request->user(), $disposal, ['asset_id' => $asset->id], $asset->area);

        return new DisposalRequestResource($disposal->load('asset.category', 'asset.status', 'asset.area', 'technicalEvaluation', 'requester'));
    }

    public function review(ReviewDisposalRequest $request, DisposalRequest $disposalRequest, AuditLogger $auditLogger): DisposalRequestResource
    {
        Gate::authorize('review', $disposalRequest);
        if (! in_array($disposalRequest->status, ['pending', 'under_review'], true)) {
            throw ValidationException::withMessages(['status' => 'El expediente ya no está pendiente de revisión.']);
        }
        $decision = $request->string('decision')->toString();
        $disposalRequest->update(['status' => $decision === 'rejected' ? 'rejected' : 'pending_authority', 'authority_status' => $decision === 'rejected' ? 'not_required' : 'pending_validation', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'decision_notes' => $request->string('decision_notes')->toString(), 'rejection_reason' => $decision === 'rejected' ? $request->string('decision_notes')->toString() : null]);
        $auditLogger->record(AuditEventType::DisposalReviewed, $request->user(), $disposalRequest, ['decision' => $disposalRequest->status], $disposalRequest->asset->area);
        return new DisposalRequestResource($disposalRequest->refresh()->load('asset.category', 'asset.status', 'technicalEvaluation', 'requester', 'reviewer'));
    }

    public function decide(DecideDisposalRequest $request, DisposalRequest $disposalRequest, AuditLogger $auditLogger): DisposalRequestResource
    {
        Gate::authorize('decide', $disposalRequest);
        if ($disposalRequest->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Solo se pueden decidir solicitudes pendientes.']);
        }
        $approved = $request->string('decision')->toString() === 'approved';
        if ($approved) {
            throw ValidationException::withMessages(['decision' => 'La autoridad competente para aprobar y ejecutar la baja aún no está validada institucionalmente. El expediente queda bloqueado en revisión.']);
        }
        $disposalRequest->update(['status' => 'rejected', 'authority_status' => 'not_required', 'decided_by' => $request->user()->id, 'rejection_reason' => $request->string('rejection_reason')->toString(), 'decided_at' => now()]);
        $auditLogger->record(AuditEventType::DisposalRejected, $request->user(), $disposalRequest, ['decision' => $disposalRequest->status], $disposalRequest->asset->area);

        return new DisposalRequestResource($disposalRequest->refresh()->load('asset.category', 'asset.status'));
    }

    public function upload(Request $request, DisposalRequest $disposalRequest, AuditLogger $auditLogger): DisposalRequestResource
    {
        Gate::authorize('view', $disposalRequest->asset);
        abort_unless($request->user()->hasPermission('disposals.update') || $request->user()->hasPermission('disposals.create'), 403);
        $request->validate(['file' => ['required','file','max:10240','mimes:pdf,jpg,jpeg,png,webp']]);
        /** @var UploadedFile $file */ $file = $request->file('file');
        $path = $file->store('disposals/'.$disposalRequest->id, 'local');
        $disposalRequest->documents()->create(['uploaded_by'=>$request->user()->id,'file_name'=>$file->getClientOriginalName(),'path'=>$path,'mime_type'=>$file->getMimeType() ?: 'application/octet-stream','size'=>$file->getSize(),'sha256'=>hash_file('sha256',$file->getRealPath())]);
        $auditLogger->record(AuditEventType::DisposalDocumentUploaded, $request->user(), $disposalRequest, ['file_name'=>$file->getClientOriginalName()], $disposalRequest->asset->area);
        return new DisposalRequestResource($disposalRequest->refresh()->load('asset.category','asset.status','asset.area','technicalEvaluation','requester','documents'));
    }

    public function document(Request $request, DisposalRequest $disposalRequest, int $document)
    {
        Gate::authorize('view', $disposalRequest->asset);
        abort_unless($request->user()->hasPermission('disposals.view'), 403);
        $record = $disposalRequest->documents()->findOrFail($document); abort_unless(Storage::disk('local')->exists($record->path), 404);
        return Storage::disk('local')->download($record->path, $record->file_name, ['Content-Type'=>$record->mime_type]);
    }
}
