<?php

namespace Tests\Feature\Policies;

use App\Models\Area;
use App\Models\User;
use App\Policies\UserPolicy;
use App\RoleScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\CreatesAuthorizedUsers;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use CreatesAuthorizedUsers;
    use LazilyRefreshDatabase;

    public function test_global_permission_can_view_user_from_another_area(): void
    {
        $viewer = $this->createAuthorizedUser(['users.view'], RoleScope::Global);
        $target = User::factory()->create();

        $this->assertTrue((new UserPolicy)->view($viewer, $target));
    }

    public function test_own_area_permission_can_view_only_same_area(): void
    {
        $ownArea = Area::factory()->create();
        $viewer = $this->createAuthorizedUser(['users.view'], RoleScope::OwnArea, $ownArea);
        $sameArea = User::factory()->for($ownArea)->create();
        $otherArea = User::factory()->create();

        $policy = new UserPolicy;

        $this->assertTrue($policy->view($viewer, $sameArea));
        $this->assertFalse($policy->view($viewer, $otherArea));
    }

    public function test_management_requires_global_create_and_assign_permissions(): void
    {
        $manager = $this->createAuthorizedUser(['users.create'], RoleScope::Global);
        $this->grantPermissions($manager, ['roles.assign'], RoleScope::OwnArea);

        $this->assertFalse((new UserPolicy)->create($manager));
    }
}
