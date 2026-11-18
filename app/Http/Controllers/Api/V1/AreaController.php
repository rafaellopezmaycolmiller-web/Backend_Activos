<?php

namespace App\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAreaRequest;
use App\Http\Requests\Api\V1\UpdateAreaRequest;
use App\Http\Resources\Api\V1\AreaResource;
use App\Models\Area;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AreaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Area::class);

        /** @var User $user */
        $user = $request->user();
        $query = Area::query();

        // A movement requester must be able to select a destination area;
        // write permissions on the movement itself still enforce the scope.
        if (! $user->hasGlobalPermission('areas.view') && ! $user->hasPermission('movements.request')) {
            $query->whereKey($user->area_id);
        }

        return AreaResource::collection(
            $query->orderBy('name')->orderBy('id')->get(),
        );
    }

    public function store(
        StoreAreaRequest $request,
        AuditLogger $auditLogger,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $attributes = $request->validated();
        $attributes['code'] = Str::upper($attributes['code']);

        $area = Area::create($attributes);

        $auditLogger->record(
            AuditEventType::AreaCreated,
            actor: $actor,
            subject: $area,
            metadata: ['attributes' => $area->only(['parent_id', 'code', 'name', 'is_active'])],
            area: $area,
        );

        return (new AreaResource($area))
            ->additional(['message' => 'Área creada correctamente.'])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Area $area): AreaResource
    {
        Gate::authorize('view', $area);

        return new AreaResource($area);
    }

    public function update(
        UpdateAreaRequest $request,
        Area $area,
        AuditLogger $auditLogger,
    ): AreaResource {
        /** @var User $actor */
        $actor = $request->user();
        $attributes = $request->validated();
        $attributes['code'] = Str::upper($attributes['code']);

        if ($this->wouldCreateCycle($area, $attributes['parent_id'])) {
            throw ValidationException::withMessages([
                'parent_id' => 'El área superior generaría un ciclo en la estructura.',
            ]);
        }

        if (! $attributes['is_active'] && $area->users()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'is_active' => 'No se puede desactivar un área con usuarios activos.',
            ]);
        }

        $before = $area->only(['parent_id', 'code', 'name', 'description', 'is_active']);
        $area->update($attributes);

        $auditLogger->record(
            AuditEventType::AreaUpdated,
            actor: $actor,
            subject: $area,
            metadata: [
                'before' => $before,
                'after' => $area->only(array_keys($before)),
            ],
            area: $area,
        );

        return (new AreaResource($area))
            ->additional(['message' => 'Área actualizada correctamente.']);
    }

    private function wouldCreateCycle(Area $area, ?int $parentId): bool
    {
        while ($parentId !== null) {
            if ($parentId === $area->getKey()) {
                return true;
            }

            $parentId = Area::query()->whereKey($parentId)->value('parent_id');
        }

        return false;
    }
}
