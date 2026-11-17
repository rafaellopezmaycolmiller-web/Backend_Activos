<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Role;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\CreatesAuthorizedUsers;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use CreatesAuthorizedUsers;
    use LazilyRefreshDatabase;

    public function test_user_with_permission_can_list_roles(): void
    {
        $user = $this->createAuthorizedUser(['roles.view']);
        $role = Role::factory()->create();

        $this
            ->actingAs($user)
            ->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonFragment(['id' => $role->getKey()]);
    }

    public function test_user_without_permission_receives_403(): void
    {
        $user = $this->createAuthorizedUser([]);

        $this
            ->actingAs($user)
            ->getJson('/api/v1/roles')
            ->assertForbidden();
    }
}
