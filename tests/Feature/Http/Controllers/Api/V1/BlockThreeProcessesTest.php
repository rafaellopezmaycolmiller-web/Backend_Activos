<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Area;
use App\Models\Asset;
use App\Models\AssetStatus;
use App\Models\InventoryCampaign;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAuthorizedUsers;
use Tests\TestCase;

class BlockThreeProcessesTest extends TestCase
{
    use CreatesAuthorizedUsers;
    use LazilyRefreshDatabase;

    public function test_movement_request_approval_reception_updates_asset_and_custody(): void
    {
        $source = Area::factory()->create(); $destination = Area::factory()->create();
        $requester = $this->createAuthorizedUser(['assets.view', 'movements.view', 'movements.request'], \App\RoleScope::OwnArea, $source);
        $approver = $this->createAuthorizedUser(['movements.view', 'movements.approve'], \App\RoleScope::OwnArea, $source);
        $receiver = $this->createAuthorizedUser(['movements.view', 'movements.receive'], \App\RoleScope::OwnArea, $destination);
        $asset = Asset::factory()->for($source)->create();
        $response = $this->actingAs($requester)->postJson('/api/v1/movement-requests', ['asset_id'=>$asset->id,'to_area_id'=>$destination->id,'to_user_id'=>$receiver->id,'reason'=>'Reubicación para prueba']);
        $response->assertCreated(); $movementId = $response->json('data.id');
        $this->actingAs($approver)->postJson('/api/v1/movement-requests/'.$movementId.'/decide', ['decision'=>'approved'])->assertOk();
        $this->actingAs($receiver)->postJson('/api/v1/movement-requests/'.$movementId.'/receive')->assertOk();
        $this->assertDatabaseHas('movement_requests', ['id'=>$movementId,'status'=>'received']);
        $this->assertDatabaseHas('assets', ['id'=>$asset->id,'area_id'=>$destination->id]);
        $this->assertDatabaseHas('custodianships', ['asset_id'=>$asset->id,'user_id'=>$receiver->id,'status'=>'active']);
    }

    public function test_inventory_campaign_records_check_and_rejects_duplicate(): void
    {
        $area = Area::factory()->create(); $user = $this->createAuthorizedUser(['inventory.view','inventory.create','inventory.check','inventory.manage','assets.view'], \App\RoleScope::OwnArea, $area); $asset = Asset::factory()->for($area)->create();
        $campaign = $this->actingAs($user)->postJson('/api/v1/inventory-campaigns', ['code'=>'CAM-TEST','name'=>'Campaña de prueba','area_id'=>$area->id])->assertCreated()->json('data.id');
        $this->actingAs($user)->postJson('/api/v1/inventory-campaigns/'.$campaign.'/start')->assertOk();
        $this->actingAs($user)->postJson('/api/v1/inventory-campaigns/'.$campaign.'/checks', ['asset_id'=>$asset->id,'result'=>'found'])->assertCreated();
        $this->actingAs($user)->postJson('/api/v1/inventory-campaigns/'.$campaign.'/checks', ['asset_id'=>$asset->id,'result'=>'found'])->assertUnprocessable();
        $this->assertDatabaseHas('inventory_checks', ['campaign_id'=>$campaign,'asset_id'=>$asset->id,'result'=>'found']);
    }

    public function test_reconciliation_csv_creates_differences_without_overwriting_asset(): void
    {
        Storage::fake('local'); $area = Area::factory()->create(); $user = $this->createAuthorizedUser(['reconciliation.view','reconciliation.create','assets.view'], \App\RoleScope::OwnArea, $area); $asset = Asset::factory()->for($area)->create(['asset_code'=>'MDM-CON-001']);
        $csv = UploadedFile::fake()->createWithContent('padron.csv', "asset_code,description,area,location,status\nMDM-CON-001,Nombre externo,OTRA,,\nNO-EN-SISTEMA,Otro,OTRA,,\n");
        $response = $this->actingAs($user)->post('/api/v1/reconciliations', ['code'=>'REC-TEST','name'=>'Conciliación ficticia','file'=>$csv], ['Accept'=>'application/json']);
        $response->assertCreated(); $this->assertDatabaseHas('reconciliation_runs', ['code'=>'REC-TEST']); $this->assertDatabaseHas('reconciliation_differences', ['asset_code'=>'MDM-CON-001','difference_type'=>'area_mismatch']); $this->assertDatabaseHas('reconciliation_differences', ['asset_code'=>'NO-EN-SISTEMA','difference_type'=>'missing_in_system']);
        $this->assertDatabaseHas('assets', ['id'=>$asset->id,'area_id'=>$area->id]);
    }
}
