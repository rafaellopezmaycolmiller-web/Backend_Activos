<?php

namespace App\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        /** @var User $actor */
        $actor = $request->user();
        $query = User::query()->with('area', 'roles.permissions');

        if (! $actor->hasGlobalPermission('users.view')) {
            $query->where('area_id', $actor->area_id);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();

            $normalizedSearch = Str::lower($search);

            $query->where(function ($query) use ($normalizedSearch): void {
                $query
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.$normalizedSearch.'%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%'.$normalizedSearch.'%'])
                    ->orWhere('document_number', 'like', '%'.$normalizedSearch.'%');
            });
        }

        $users = $query
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(max(1, min($request->integer('per_page', 20), 50)));

        return UserResource::collection($users);
    }

    public function store(
        StoreUserRequest $request,
        AuditLogger $auditLogger,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();
        $assignments = $validated['role_assignments'];
        $attributes = Arr::except($validated, ['role_assignments', 'password_confirmation']);
        $attributes['email'] = Str::lower($attributes['email']);

        $user = DB::transaction(function () use ($attributes, $assignments, $actor, $auditLogger): User {
            $user = User::create($attributes);
            $user->roles()->sync($this->roleSyncPayload($assignments));

            $auditLogger->record(
                AuditEventType::UserCreated,
                actor: $actor,
                subject: $user,
                metadata: [
                    'area_id' => $user->area_id,
                    'role_ids' => collect($assignments)->pluck('role_id')->all(),
                ],
                area: $user->area,
            );

            return $user;
        });

        return (new UserResource($user->load('area', 'roles.permissions')))
            ->additional(['message' => 'Usuario creado correctamente.'])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(User $user): UserResource
    {
        Gate::authorize('view', $user);

        return new UserResource(
            $user->load('area', 'roles.permissions'),
        );
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        AuditLogger $auditLogger,
    ): UserResource {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();
        $assignments = $validated['role_assignments'];
        $attributes = Arr::except($validated, ['role_assignments']);
        $attributes['email'] = Str::lower($attributes['email']);

        DB::transaction(function () use ($user, $attributes, $assignments, $actor, $auditLogger): void {
            $before = $user->only([
                'area_id',
                'name',
                'document_number',
                'job_title',
                'phone',
                'email',
                'is_active',
                'mfa_required',
            ]);

            $user->update($attributes);
            $user->roles()->sync($this->roleSyncPayload($assignments));

            if (! $user->is_active) {
                DB::table('sessions')->where('user_id', $user->getKey())->delete();
                $user->tokens()->delete();
            }

            $auditLogger->record(
                AuditEventType::UserUpdated,
                actor: $actor,
                subject: $user,
                metadata: [
                    'before' => $before,
                    'after' => $user->only(array_keys($before)),
                    'role_ids' => collect($assignments)->pluck('role_id')->all(),
                ],
                area: $user->area,
            );
        });

        return (new UserResource($user->load('area', 'roles.permissions')))
            ->additional(['message' => 'Usuario actualizado correctamente.']);
    }

    /**
     * @param  array<int, array{role_id: int, scope: string}>  $assignments
     * @return array<int, array{scope: string}>
     */
    private function roleSyncPayload(array $assignments): array
    {
        return collect($assignments)
            ->mapWithKeys(fn (array $assignment): array => [
                $assignment['role_id'] => ['scope' => $assignment['scope']],
            ])
            ->all();
    }
}
