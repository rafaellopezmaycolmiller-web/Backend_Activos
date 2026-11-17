<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Models\Area;
use App\Models\User;
use App\RoleScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\CreatesAuthorizedUsers;
use Tests\TestCase;

class AreaControllerTest extends TestCase
{
    use CreatesAuthorizedUsers;
    use LazilyRefreshDatabase;

    public function test_global_administrator_creates_area_and_audit_event(): void
    {
        $administrator = $this->createAuthorizedUser(
            ['areas.manage'],
            RoleScope::Global,
        );

        $response = $this
            ->actingAs($administrator)
            ->postJson('/api/v1/areas', [
                'parent_id' => null,
                'code' => 'pat-2',
                'name' => 'Patrimonio II',
                'description' => 'Área de prueba.',
                'is_active' => true,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.code', 'PAT-2');
        $this->assertDatabaseHas('audit_events', [
            'event' => AuditEventType::AreaCreated->value,
            'actor_id' => $administrator->getKey(),
        ]);
    }

    public function test_update_rejects_hierarchy_cycle(): void
    {
        $administrator = $this->createAuthorizedUser(
            ['areas.manage'],
            RoleScope::Global,
        );
        $parent = Area::factory()->create();
        $child = Area::factory()->create(['parent_id' => $parent->getKey()]);

        $this
            ->actingAs($administrator)
            ->putJson('/api/v1/areas/'.$parent->getKey(), [
                'parent_id' => $child->getKey(),
                'code' => $parent->code,
                'name' => $parent->name,
                'description' => $parent->description,
                'is_active' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_update_rejects_deactivation_when_area_has_active_users(): void
    {
        $administrator = $this->createAuthorizedUser(
            ['areas.manage'],
            RoleScope::Global,
        );
        $area = Area::factory()->create();
        User::factory()->for($area)->create();

        $this
            ->actingAs($administrator)
            ->putJson('/api/v1/areas/'.$area->getKey(), [
                'parent_id' => null,
                'code' => $area->code,
                'name' => $area->name,
                'description' => $area->description,
                'is_active' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active']);
    }
}
