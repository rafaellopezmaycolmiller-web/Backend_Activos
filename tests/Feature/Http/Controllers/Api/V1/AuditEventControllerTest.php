<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Area;
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
}
