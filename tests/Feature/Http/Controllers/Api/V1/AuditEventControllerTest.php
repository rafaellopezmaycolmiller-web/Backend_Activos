<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Models\Area;
use App\Models\Asset;
use App\Models\AuditEvent;
use App\RoleScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\CreatesAuthorizedUsers;
use Tests\TestCase;

class AuditEventControllerTest extends TestCase
{
    use CreatesAuthorizedUsers;
    use LazilyRefreshDatabase;

    public function test_own_area_scope_hides_events_from_other_area(): void
    {
        $ownArea = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $viewer = $this->createAuthorizedUser(['audit.view'], RoleScope::OwnArea, $ownArea);
        $visible = AuditEvent::factory()->for($ownArea)->create(['event' => 'visible.event']);
        $hidden = AuditEvent::factory()->for($otherArea)->create(['event' => 'hidden.event']);

        $this
            ->actingAs($viewer)
            ->getJson('/api/v1/audit-events')
            ->assertOk()
            ->assertJsonFragment(['event' => $visible->event])
            ->assertJsonMissing(['event' => $hidden->event]);
    }

    public function test_global_scope_lists_events_from_all_areas(): void
    {
        $viewer = $this->createAuthorizedUser(['audit.view'], RoleScope::Global);
        $event = AuditEvent::factory()->create();

        $this
            ->actingAs($viewer)
            ->getJson('/api/v1/audit-events')
            ->assertOk()
            ->assertJsonFragment(['id' => $event->getKey()]);
    }

    public function test_user_without_audit_permission_receives_403(): void
    {
        $viewer = $this->createAuthorizedUser([]);

        $this
            ->actingAs($viewer)
            ->getJson('/api/v1/audit-events')
            ->assertForbidden();
    }

    public function test_event_includes_human_context_for_actor_profile_area_and_asset(): void
    {
        $area = Area::factory()->create(['code' => 'TI', 'name' => 'Tecnologías de la Información']);
        $viewer = $this->createAuthorizedUser(['audit.view'], RoleScope::Global, $area);
        $role = $viewer->roles()->firstOrFail();
        $asset = Asset::factory()->for($area)->create([
            'asset_code' => 'MDM-TI-0001',
            'name' => 'Computadora de Tesorería',
        ]);

        AuditEvent::factory()->create([
            'actor_id' => $viewer->getKey(),
            'area_id' => $area->getKey(),
            'event' => AuditEventType::AssetUpdated->value,
            'subject_type' => $asset->getMorphClass(),
            'subject_id' => (string) $asset->getKey(),
            'metadata' => ['reason' => 'Se corrigió el número de serie.'],
        ]);

        $this
            ->actingAs($viewer)
            ->getJson('/api/v1/audit-events')
            ->assertOk()
            ->assertJsonPath('data.0.actor.name', $viewer->name)
            ->assertJsonPath('data.0.actor.job_title', $viewer->job_title)
            ->assertJsonPath('data.0.actor.area.name', $area->name)
            ->assertJsonPath('data.0.actor.roles.0.name', $role->name)
            ->assertJsonPath('data.0.subject.type', 'asset')
            ->assertJsonPath('data.0.subject.label', $asset->name)
            ->assertJsonPath('data.0.subject.reference', $asset->asset_code);
    }
}
