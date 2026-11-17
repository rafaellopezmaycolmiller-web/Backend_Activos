<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Models\Area;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetStatus;
use App\RoleScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\CreatesAuthorizedUsers;
use Tests\TestCase;

class AssetControllerTest extends TestCase
{
    use CreatesAuthorizedUsers;
    use LazilyRefreshDatabase;

    public function test_authorized_user_creates_asset_with_initial_version(): void
    {
        $area = Area::factory()->create();
        $user = $this->createAuthorizedUser(['assets.view', 'assets.create'], RoleScope::OwnArea, $area);
        $category = AssetCategory::factory()->create(['code' => 'TEC', 'name' => 'Equipos informáticos y comunicaciones']);
        $status = AssetStatus::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/assets', [
            'asset_code' => 'MDM-TEC-1000', 'inventory_code' => 'INV-1000', 'category_id' => $category->id,
            'status_id' => $status->id, 'area_id' => $area->id, 'name' => 'Laptop de prueba', 'is_active' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.current_version', 1);
        $this->assertDatabaseHas('asset_versions', ['asset_id' => $response->json('data.id'), 'version' => 1, 'action' => 'created']);
        $this->assertDatabaseHas('audit_events', ['event' => AuditEventType::AssetCreated->value, 'actor_id' => $user->id]);
    }

    public function test_update_requires_reason_and_creates_new_version(): void
    {
        $area = Area::factory()->create();
        $user = $this->createAuthorizedUser(['assets.view', 'assets.update'], RoleScope::OwnArea, $area);
        $category = AssetCategory::factory()->create(['code' => 'TEC', 'name' => 'Equipos informáticos y comunicaciones']);
        $asset = Asset::factory()->for($area)->for($category, 'category')->create();
        $asset->versions()->create(['version' => 1, 'action' => 'created', 'snapshot' => ['name' => $asset->name]]);

        $this->actingAs($user)->putJson('/api/v1/assets/'.$asset->id, [
            'inventory_code' => $asset->inventory_code, 'category_id' => $asset->category_id, 'status_id' => $asset->status_id,
            'area_id' => $area->id, 'name' => 'Nombre corregido', 'is_active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['reason']);

        $this->actingAs($user)->putJson('/api/v1/assets/'.$asset->id, [
            'inventory_code' => $asset->inventory_code, 'category_id' => $asset->category_id, 'status_id' => $asset->status_id,
            'area_id' => $area->id, 'name' => 'Nombre corregido', 'is_active' => true, 'reason' => 'Corrección de inventario.',
        ])->assertOk()->assertJsonPath('data.current_version', 2);

        $this->assertDatabaseHas('asset_versions', ['asset_id' => $asset->id, 'version' => 2, 'reason' => 'Corrección de inventario.']);
    }

    public function test_area_scoped_user_cannot_read_asset_from_another_area(): void
    {
        $userArea = Area::factory()->create();
        $user = $this->createAuthorizedUser(['assets.view'], RoleScope::OwnArea, $userArea);
        $asset = Asset::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/assets/'.$asset->id)->assertForbidden();
    }

    public function test_asset_cannot_be_created_with_category_outside_project_scope(): void
    {
        $area = Area::factory()->create();
        $user = $this->createAuthorizedUser(['assets.create'], RoleScope::OwnArea, $area);
        $category = AssetCategory::factory()->create(['code' => 'VEH', 'name' => 'Vehículos']);
        $status = AssetStatus::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/assets', [
            'asset_code' => 'MDM-VEH-1000', 'category_id' => $category->id, 'status_id' => $status->id,
            'area_id' => $area->id, 'name' => 'Vehículo fuera de alcance', 'is_active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['category_id']);
    }
}
