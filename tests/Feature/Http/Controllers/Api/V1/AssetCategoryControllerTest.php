<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\RoleScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\CreatesAuthorizedUsers;
use Tests\TestCase;

class AssetCategoryControllerTest extends TestCase
{
    use CreatesAuthorizedUsers;
    use LazilyRefreshDatabase;

    public function test_category_outside_project_scope_cannot_be_created(): void
    {
        $user = $this->createAuthorizedUser(['asset_categories.manage'], RoleScope::Global);

        $this->actingAs($user)->postJson('/api/v1/asset-categories', [
            'code' => 'VEH', 'name' => 'Vehículos', 'is_active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['code']);
    }

    public function test_project_scope_category_can_be_created(): void
    {
        $user = $this->createAuthorizedUser(['asset_categories.manage'], RoleScope::Global);

        $this->actingAs($user)->postJson('/api/v1/asset-categories', [
            'code' => 'MOB', 'name' => 'Mobiliario municipal', 'is_active' => true,
        ])->assertCreated()->assertJsonPath('data.code', 'MOB');
    }
}
