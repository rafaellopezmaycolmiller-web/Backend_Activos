<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\RoleScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\CreatesAuthorizedUsers;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use CreatesAuthorizedUsers;
    use LazilyRefreshDatabase;

    public function test_user_with_permission_receives_phase_two_metrics(): void
    {
        $user = $this->createAuthorizedUser(
            ['dashboard.view', 'users.view', 'audit.view'],
            RoleScope::Global,
        );

        $this
            ->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.phase', 'F2')
            ->assertJsonStructure([
                'data' => [
                    'users_total',
                    'users_active',
                    'areas_active',
                    'audit_events_24h',
                    'phase_name',
                ],
            ]);
    }

    public function test_user_without_dashboard_permission_receives_403(): void
    {
        $user = $this->createAuthorizedUser([]);

        $this
            ->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertForbidden();
    }
}
