<?php

namespace Tests\Feature\Database;

use App\Models\Role;
use App\Models\User;
use App\RoleScope;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class IdentityAccessSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeded_roles_enforce_the_operational_access_model(): void
    {
        $this->seed(DatabaseSeeder::class);

        $administrator = $this->userWithRole('administrador@moralesmuni.gob.pe', 'administrador_tecnico');
        $patrimonyManager = $this->userWithRole('patrimonio@moralesmuni.gob.pe', 'jefe_patrimonio');
        $auditor = $this->userWithRole('oci@moralesmuni.gob.pe', 'oci_auditor');

        $this->assertTrue($administrator->hasGlobalPermission('users.create'));
        $this->assertTrue($administrator->hasGlobalPermission('assets.create'));
        $this->assertFalse($administrator->hasPermission('movements.approve'));
        $this->assertFalse($administrator->hasPermission('disposals.approve'));
        $this->assertTrue($administrator->mfa_required);
        $this->assertSame('+51906239450', $administrator->phone);

        $this->assertTrue($patrimonyManager->hasGlobalPermission('assets.create'));
        $this->assertTrue($patrimonyManager->hasGlobalPermission('movements.approve'));
        $this->assertTrue($patrimonyManager->hasGlobalPermission('disposals.approve'));
        $this->assertFalse($patrimonyManager->hasPermission('users.create'));
        $this->assertFalse($patrimonyManager->mfa_required);

        $this->assertTrue($auditor->hasGlobalPermission('assets.view'));
        $this->assertTrue($auditor->hasGlobalPermission('movements.view'));
        $this->assertTrue($auditor->hasGlobalPermission('inventory.view'));
        $this->assertFalse($auditor->hasPermission('assets.create'));
        $this->assertFalse($auditor->hasPermission('assets.update'));
        $this->assertFalse($auditor->mfa_required);

        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('assets', 0);
    }

    private function userWithRole(string $email, string $roleCode): User
    {
        $user = User::query()->where('email', $email)->firstOrFail();
        $role = Role::query()->where('code', $roleCode)->firstOrFail();

        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->getKey(),
            'role_id' => $role->getKey(),
            'scope' => RoleScope::Global->value,
        ]);

        return $user;
    }
}
