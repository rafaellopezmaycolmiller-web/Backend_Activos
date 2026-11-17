<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use App\RoleScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\CreatesAuthorizedUsers;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use CreatesAuthorizedUsers;
    use LazilyRefreshDatabase;

    public function test_global_administrator_creates_user_with_role_and_audit_event(): void
    {
        $administrator = $this->createAuthorizedUser(
            ['users.create', 'roles.assign'],
            RoleScope::Global,
        );
        $area = Area::factory()->create();
        $role = Role::factory()->create();

        $response = $this
            ->actingAs($administrator)
            ->postJson('/api/v1/users', [
                'area_id' => $area->getKey(),
                'name' => 'Asistente Patrimonial',
                'document_number' => '77889911',
                'job_title' => 'Asistente',
                'phone' => '987654321',
                'email' => 'ASISTENTE@MORALES.GOB.PE',
                'password' => 'PasswordSegura123!',
                'password_confirmation' => 'PasswordSegura123!',
                'is_active' => true,
                'mfa_required' => true,
                'failed_login_attempts' => 99,
                'role_assignments' => [
                    ['role_id' => $role->getKey(), 'scope' => RoleScope::OwnArea->value],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.email', 'asistente@morales.gob.pe');

        $createdUser = User::query()->where('email', 'asistente@morales.gob.pe')->firstOrFail();

        $this->assertSame(0, $createdUser->failed_login_attempts);
        $this->assertFalse($createdUser->mfa_required);
        $this->assertSame('+51987654321', $createdUser->phone);
        $this->assertDatabaseHas('role_user', [
            'user_id' => $createdUser->getKey(),
            'role_id' => $role->getKey(),
            'scope' => RoleScope::OwnArea->value,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event' => AuditEventType::UserCreated->value,
            'actor_id' => $administrator->getKey(),
            'subject_id' => (string) $createdUser->getKey(),
        ]);
    }

    public function test_technical_administrator_gets_sms_verification_and_requires_phone(): void
    {
        $administrator = $this->createAuthorizedUser(
            ['users.create', 'roles.assign'],
            RoleScope::Global,
        );
        $area = Area::factory()->create();
        $role = Role::factory()->create(['code' => 'administrador_tecnico']);

        $this
            ->actingAs($administrator)
            ->postJson('/api/v1/users', [
                'area_id' => $area->getKey(),
                'name' => 'Administrador TI',
                'document_number' => '70605040',
                'job_title' => 'Administrador técnico',
                'phone' => '906239450',
                'email' => 'administrador.ti@morales.gob.pe',
                'password' => 'PasswordSegura123!',
                'password_confirmation' => 'PasswordSegura123!',
                'is_active' => true,
                'role_assignments' => [
                    ['role_id' => $role->getKey(), 'scope' => RoleScope::Global->value],
                ],
            ])
            ->assertCreated();

        $createdUser = User::query()->where('email', 'administrador.ti@morales.gob.pe')->firstOrFail();

        $this->assertTrue($createdUser->mfa_required);
        $this->assertSame('+51906239450', $createdUser->phone);
    }

    public function test_technical_administrator_without_phone_receives_422(): void
    {
        $administrator = $this->createAuthorizedUser(
            ['users.create', 'roles.assign'],
            RoleScope::Global,
        );
        $area = Area::factory()->create();
        $role = Role::factory()->create(['code' => 'administrador_tecnico']);

        $this
            ->actingAs($administrator)
            ->postJson('/api/v1/users', [
                'area_id' => $area->getKey(),
                'name' => 'Administrador TI',
                'document_number' => '70605041',
                'job_title' => 'Administrador técnico',
                'phone' => null,
                'email' => 'administrador.sin.telefono@morales.gob.pe',
                'password' => 'PasswordSegura123!',
                'password_confirmation' => 'PasswordSegura123!',
                'is_active' => true,
                'role_assignments' => [
                    ['role_id' => $role->getKey(), 'scope' => RoleScope::Global->value],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);

        $this->assertDatabaseMissing('users', ['email' => 'administrador.sin.telefono@morales.gob.pe']);
    }

    public function test_user_without_management_permission_receives_403(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $this
            ->actingAs($user)
            ->postJson('/api/v1/users', [
                'area_id' => $user->area_id,
                'name' => 'Usuario Nuevo',
                'document_number' => '11223344',
                'job_title' => 'Asistente',
                'email' => 'nuevo@morales.gob.pe',
                'password' => 'PasswordSegura123!',
                'password_confirmation' => 'PasswordSegura123!',
                'is_active' => true,
                'mfa_required' => false,
                'role_assignments' => [
                    ['role_id' => $role->getKey(), 'scope' => RoleScope::OwnArea->value],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'nuevo@morales.gob.pe']);
    }

    public function test_own_area_scope_does_not_expose_users_from_another_area(): void
    {
        $ownArea = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $viewer = $this->createAuthorizedUser(['users.view'], RoleScope::OwnArea, $ownArea);
        $visible = User::factory()->for($ownArea)->create(['name' => 'Usuario Visible']);
        $hidden = User::factory()->for($otherArea)->create(['name' => 'Usuario Oculto']);

        $this
            ->actingAs($viewer)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonFragment(['name' => $visible->name])
            ->assertJsonMissing(['name' => $hidden->name]);
    }

    public function test_search_treats_query_as_data_and_returns_matching_user(): void
    {
        $viewer = $this->createAuthorizedUser(['users.view'], RoleScope::Global);
        User::factory()->create(['name' => 'María Patrimonio']);

        $this
            ->actingAs($viewer)
            ->getJson('/api/v1/users?search=maría')
            ->assertOk()
            ->assertJsonFragment(['name' => 'María Patrimonio']);
    }
}
