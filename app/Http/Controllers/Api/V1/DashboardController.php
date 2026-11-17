<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Asset;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        Gate::authorize('view-dashboard');

        /** @var User $user */
        $user = $request->user();

        $users = User::query();

        if (! $user->hasGlobalPermission('users.view')) {
            $users->where('area_id', $user->area_id);
        }

        $auditEvents = AuditEvent::query()->where('occurred_at', '>=', now()->subDay());
        $assets = Asset::query();

        if (! $user->hasGlobalPermission('assets.view')) {
            $assets->where('area_id', $user->area_id);
        }

        if (! $user->hasGlobalPermission('audit.view')) {
            $auditEvents->where('area_id', $user->area_id);
        }

        return response()->json([
            'data' => [
                'users_total' => (clone $users)->count(),
                'users_active' => (clone $users)->where('is_active', true)->count(),
                'areas_active' => Area::query()->where('is_active', true)->count(),
                'assets_total' => (clone $assets)->count(),
                'assets_active' => (clone $assets)->where('is_active', true)->count(),
                'audit_events_24h' => $user->hasPermission('audit.view')
                    ? $auditEvents->count()
                    : null,
                'phase' => 'F2',
                'phase_name' => 'Maestros y ficha trazable de activos',
            ],
        ]);
    }
}
