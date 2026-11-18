<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Area;
use App\Models\Asset;
use App\Models\User;
use App\RoleScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\CreatesAuthorizedUsers;
use Tests\TestCase;

class CustodianshipControllerTest extends TestCase
{
    use CreatesAuthorizedUsers;
    use LazilyRefreshDatabase;

    public function test_authorized_user_can_list_candidates_and_assign_custodian_with_history(): void
    {
        $area = Area::factory()->create();
        $actor = $this->createAuthorizedUser(['assets.update', 'custody.assign', 'custody.view'], RoleScope::Global, $area);
        $firstCustodian = User::factory()->for($area)->create(['name' => 'Custodio inicial']);
        $secondCustodian = User::factory()->for($area)->create(['name' => 'Custodio nuevo']);
        $asset = Asset::factory()->for($area)->create();

        $this->actingAs($actor)
            ->getJson("/api/v1/assets/{$asset->id}/custodianship-candidates")
            ->assertOk()
            ->assertJsonFragment(['name' => $firstCustodian->name])
            ->assertJsonFragment(['name' => $secondCustodian->name]);

        $this->actingAs($actor)
            ->postJson("/api/v1/assets/{$asset->id}/custodianships", [
                'user_id' => $firstCustodian->id,
                'notes' => 'Asignación inicial de prueba.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'active');

        $this->actingAs($actor)
            ->postJson("/api/v1/assets/{$asset->id}/custodianships", [
                'user_id' => $secondCustodian->id,
                'notes' => 'Cambio de custodio de prueba.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('custodianships', [
            'asset_id' => $asset->id,
            'user_id' => $firstCustodian->id,
            'status' => 'ended',
        ]);
        $this->assertDatabaseHas('custodianships', [
            'asset_id' => $asset->id,
            'user_id' => $secondCustodian->id,
            'status' => 'active',
        ]);
    }

    public function test_user_from_another_area_cannot_assign_custody(): void
    {
        $assetArea = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $actor = $this->createAuthorizedUser(['assets.update', 'custody.assign'], RoleScope::OwnArea, $otherArea);
        $custodian = User::factory()->for($assetArea)->create();
        $asset = Asset::factory()->for($assetArea)->create();

        $this->actingAs($actor)
            ->postJson("/api/v1/assets/{$asset->id}/custodianships", ['user_id' => $custodian->id])
            ->assertForbidden();
    }
}
