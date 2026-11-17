<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Gate;

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
            fputcsv($handle, ['Código', 'Nombre', 'Categoría', 'Estado', 'Área', 'Ubicación', 'Activo']);
            $query->chunk(200, function ($assets) use ($handle): void {
                foreach ($assets as $asset) {
                    fputcsv($handle, [$asset->asset_code, $asset->name, $asset->category?->name, $asset->status?->name, $asset->area?->name, $asset->location?->name, $asset->is_active ? 'Sí' : 'No']);
                }
            });
            fclose($handle);
        }, 'reporte-activos-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
