<?php

namespace Tests\Concerns;

use App\Models\Area;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\RoleScope;
use Illuminate\Support\Str;

trait CreatesAuthorizedUsers
{
    /**
     * @param  array<int, string>  $permissions
     */
    protected function createAuthorizedUser(
        array $permissions,
        RoleScope $scope = RoleScope::OwnArea,
        ?Area $area = null,
    ): User {
        $user = User::factory()->for($area ?? Area::factory())->create();

        $this->grantPermissions($user, $permissions, $scope);

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function grantPermissions(
        User $user,
        array $permissions,
        RoleScope $scope = RoleScope::OwnArea,
    ): Role {
        $permissionModels = collect($permissions)->map(
            fn (string $code): Permission => Permission::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name' => Str::headline($code),
                    'module' => Str::before($code, '.'),
                ],
            ),
        );

        $role = Role::factory()->create();
        $role->permissions()->sync($permissionModels->pluck('id'));
        $user->roles()->attach($role, ['scope' => $scope->value]);

        return $role;
    }

    /**
     * @return array<string, string>
     */
    protected function statefulHeaders(): array
    {
        return [
            'Origin' => 'http://127.0.0.1:5173',
            'Referer' => 'http://127.0.0.1:5173/',
        ];
    }
}
