<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Area;
use App\AuditEventType;
use App\Models\AuditEvent;
use App\Models\Custodianship;
use App\Models\DisposalRequest;
use App\Models\InventoryCampaign;
use App\Models\MovementRequest;
use App\Models\ReconciliationRun;
use App\Models\TechnicalEvaluation;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function assets(): StreamedResponse
    {
        Gate::authorize('viewAny', Asset::class);
        $user = request()->user();
        $query = Asset::query()->with('category', 'status', 'area', 'location')->orderBy('asset_code');
        if (! $user->hasGlobalPermission('assets.view')) {
            $query->where('area_id', $user->area_id);
        }

return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Código', 'Nombre', 'Categoría', 'Tipo', 'Especificaciones', 'Estado', 'Área', 'Ubicación', 'Activo']);
            $query->chunk(200, function ($assets) use ($handle): void {
                foreach ($assets as $asset) {
                    fputcsv($handle, [$asset->asset_code, $asset->name, $asset->category?->name, $asset->asset_type, json_encode($asset->specifications, JSON_UNESCAPED_UNICODE), $asset->status?->name, $asset->area?->name, $asset->location?->name, $asset->is_active ? 'Sí' : 'No']);
                }
            });
            fclose($handle);
        }, 'reporte-activos-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('reports.view'), 403);
        $report = $request->string('report', 'assets')->toString();
        $rows = $this->rows($report, $request);
        return response()->json(['data' => $rows->values(), 'report' => $report, 'generated_at' => now()->toIso8601String(), 'prototype' => true]);
    }

    public function export(Request $request, AuditLogger $auditLogger): StreamedResponse
    {
        abort_unless($request->user()->hasPermission('reports.export'), 403);
        $report = $request->string('report', 'assets')->toString();
        $rows = $this->rows($report, $request);
        $auditLogger->record(AuditEventType::ReportExported, $request->user(), null, ['report' => $report, 'rows' => $rows->count()], $request->user()->area);
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($rows->isEmpty()) { fputcsv($handle, ['Sin resultados']); }
            else { fputcsv($handle, array_keys($rows->first())); $rows->each(fn (array $row) => fputcsv($handle, array_map(static fn ($v) => is_scalar($v) || $v === null ? $v : json_encode($v, JSON_UNESCAPED_UNICODE), $row))); }
            fclose($handle);
        }, 'reporte-'.$report.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function rows(string $report, Request $request)
    {
        $user = $request->user();
        $areaId = $request->integer('area_id') ?: null;
        $areaIds = $areaId ? $this->areaTreeIds($areaId) : [];
        $locationId = $request->integer('location_id') ?: null;
        $code = trim($request->string('asset_code')->toString());
        $status = trim($request->string('status')->toString());
        $from = $request->date('from'); $to = $request->date('to');
        return match ($report) {
            'assets' => Asset::with('category', 'status', 'area', 'location')->when(! $user->hasGlobalPermission('assets.view'), fn ($q) => $q->where('area_id', $user->area_id))->when($areaId, fn ($q) => $q->whereIn('area_id', $areaIds))->when($locationId, fn ($q) => $q->where('location_id', $locationId))->when($code !== '', fn ($q) => $q->where('asset_code', $code))->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('code', $request->string('category')->toString())))->when($request->filled('asset_type'), fn ($q) => $q->where('asset_type', $request->string('asset_type')->toString()))->when($status !== '', fn ($q) => $q->where('is_active', $status === 'active'))->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))->get()->map(fn (Asset $a) => ['asset_code' => $a->asset_code, 'name' => $a->name, 'category' => $a->category?->name, 'type' => $a->asset_type, 'specifications' => $a->specifications, 'status' => $a->status?->name, 'area' => $a->area?->name, 'location' => $a->location?->name, 'active' => $a->is_active && $a->status?->code !== 'BAJA' ? 'Sí' : 'No']),
            'custodians' => Custodianship::with('asset', 'user', 'assigner')->when(! $user->hasGlobalPermission('custody.view'), fn ($q) => $q->whereHas('asset', fn ($a) => $a->where('area_id', $user->area_id)))->get()->map(fn ($c) => ['asset_code' => $c->asset?->asset_code, 'asset' => $c->asset?->name, 'custodian' => $c->user?->name, 'status' => $c->status, 'started_at' => $c->started_at, 'ended_at' => $c->ended_at]),
            'movements' => MovementRequest::with('asset', 'fromArea', 'toArea')->when(! $user->hasGlobalPermission('movements.view'), fn ($q) => $q->whereHas('asset', fn ($a) => $a->where('area_id', $user->area_id)))->get()->map(fn ($m) => ['asset_code' => $m->asset?->asset_code, 'from' => $m->fromArea?->name, 'to' => $m->toArea?->name, 'status' => $m->status, 'reason' => $m->reason, 'requested_at' => $m->requested_at]),
            'inventory' => InventoryCampaign::with('area')->withCount('checks')->when(! $user->hasGlobalPermission('inventory.view'), fn ($q) => $q->where('area_id', $user->area_id))->get()->map(fn ($c) => ['code' => $c->code, 'name' => $c->name, 'area' => $c->area?->name, 'status' => $c->status, 'checks' => $c->checks_count]),
            'reconciliations' => ReconciliationRun::withCount(['differences', 'differences as pending_differences_count' => fn ($q) => $q->where('status', 'pending')])->get()->map(fn ($r) => ['code' => $r->code, 'name' => $r->name, 'source' => $r->source_file_name, 'differences' => $r->differences_count, 'pending' => $r->pending_differences_count, 'executed_at' => $r->executed_at]),
            'evaluations' => TechnicalEvaluation::with('asset', 'specialist')->when(! $user->hasGlobalPermission('evaluations.view'), fn ($q) => $q->whereHas('asset', fn ($a) => $a->where('area_id', $user->area_id)))->get()->map(fn ($e) => ['asset_code' => $e->asset?->asset_code, 'status' => $e->status, 'specialist' => $e->specialist?->name, 'reason' => $e->reason, 'diagnosis' => $e->diagnosis]),
            'disposals' => DisposalRequest::with('asset', 'requester', 'reviewer')->when(! $user->hasGlobalPermission('disposals.view'), fn ($q) => $q->whereHas('asset', fn ($a) => $a->where('area_id', $user->area_id)))->get()->map(fn ($d) => ['asset_code' => $d->asset?->asset_code, 'cause' => $d->cause, 'status' => $d->status, 'authority_status' => $d->authority_status, 'requester' => $d->requester?->name, 'reviewer' => $d->reviewer?->name]),
            'audit' => AuditEvent::with('actor', 'area')->when(! $user->hasGlobalPermission('audit.view'), fn ($q) => $q->where('area_id', $user->area_id))->latest('occurred_at')->get()->map(fn ($e) => ['event' => $e->event, 'actor' => $e->actor?->name, 'area' => $e->area?->name, 'subject_type' => $e->subject_type, 'subject_id' => $e->subject_id, 'occurred_at' => $e->occurred_at]),
            default => collect(),
        };
    }

    private function areaTreeIds(int $rootId): array
    {
        $ids = [$rootId];
        $pending = [$rootId];
        while ($pending !== []) {
            $children = Area::query()->whereIn('parent_id', $pending)->pluck('id')->all();
            $children = array_values(array_diff($children, $ids));
            $ids = [...$ids, ...$children];
            $pending = $children;
        }
        return $ids;
    }
}
