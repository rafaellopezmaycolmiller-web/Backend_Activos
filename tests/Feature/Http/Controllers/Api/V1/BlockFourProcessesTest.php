<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Area;
use App\Models\Asset;
use App\RoleScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAuthorizedUsers;
use Tests\TestCase;

class BlockFourProcessesTest extends TestCase
{
    use CreatesAuthorizedUsers;
    use LazilyRefreshDatabase;

    public function test_technical_evaluation_lifecycle_and_document_is_persisted(): void
    {
        Storage::fake('local');
        $area = Area::factory()->create();
        $user = $this->createAuthorizedUser(['assets.view', 'evaluations.view', 'evaluations.create', 'evaluations.update'], RoleScope::OwnArea, $area);
        $asset = Asset::factory()->for($area)->create(['asset_code' => 'EVAL-001']);

        $evaluation = $this->actingAs($user)->postJson('/api/v1/technical-evaluations', ['asset_id' => $asset->id, 'reason' => 'Verificar condición física'])->assertCreated()->json('data');
        $this->assertSame('requested', $evaluation['status']);
        $this->actingAs($user)->putJson('/api/v1/technical-evaluations/'.$evaluation['id'], ['status' => 'assigned'])->assertOk();
        $this->actingAs($user)->putJson('/api/v1/technical-evaluations/'.$evaluation['id'], ['status' => 'evaluated', 'diagnosis' => 'Diagnóstico ficticio', 'physical_condition' => 'Regular'])->assertOk();
        $this->actingAs($user)->putJson('/api/v1/technical-evaluations/'.$evaluation['id'], ['status' => 'reviewed', 'diagnosis' => 'Diagnóstico ficticio', 'recommendations' => 'Sustentar decisión'])->assertOk();
        $this->actingAs($user)->post('/api/v1/technical-evaluations/'.$evaluation['id'].'/documents', ['file' => UploadedFile::fake()->create('informe.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertOk();
        $this->assertDatabaseHas('technical_evaluations', ['id' => $evaluation['id'], 'status' => 'reviewed']);
        $this->assertDatabaseHas('technical_evaluation_documents', ['technical_evaluation_id' => $evaluation['id'], 'file_name' => 'informe.pdf']);
        $this->assertDatabaseHas('audit_events', ['event' => 'evaluations.document_uploaded']);
    }

    public function test_disposal_stays_pending_authority_and_does_not_deactivate_asset(): void
    {
        $area = Area::factory()->create();
        $user = $this->createAuthorizedUser(['assets.view', 'disposals.view', 'disposals.request', 'disposals.review', 'disposals.approve'], RoleScope::OwnArea, $area);
        $approver = $this->createAuthorizedUser(['assets.view', 'disposals.view', 'disposals.approve'], RoleScope::OwnArea, $area);
        $asset = Asset::factory()->for($area)->create(['is_active' => true]);
        $id = $this->actingAs($user)->postJson('/api/v1/assets/'.$asset->id.'/disposal-requests', ['reason' => 'Bien obsoleto de prueba', 'cause' => 'Obsolescencia'])->assertCreated()->json('data.id');
        $this->actingAs($user)->postJson('/api/v1/disposal-requests/'.$id.'/review', ['decision' => 'pending_authority', 'decision_notes' => 'Requiere validación de autoridad competente'])->assertOk()->assertJsonPath('data.status', 'pending_authority');
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'is_active' => true]);
        $this->actingAs($approver)->postJson('/api/v1/disposal-requests/'.$id.'/decide', ['decision' => 'approved'])->assertUnprocessable();
        $this->assertDatabaseHas('disposal_requests', ['id' => $id, 'status' => 'pending_authority']);
    }

    public function test_reports_are_filtered_and_exported_only_with_report_permission(): void
    {
        $area = Area::factory()->create();
        $user = $this->createAuthorizedUser(['assets.view', 'reports.view', 'reports.export'], RoleScope::OwnArea, $area);
        Asset::factory()->for($area)->create(['asset_code' => 'REP-001']);
        $this->actingAs($user)->getJson('/api/v1/reports?report=assets')->assertOk()->assertJsonPath('prototype', true)->assertJsonPath('data.0.asset_code', 'REP-001');
        $this->actingAs($user)->get('/api/v1/reports/export.csv?report=assets')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $withoutExport = $this->createAuthorizedUser(['reports.view'], RoleScope::OwnArea, $area);
        $this->actingAs($withoutExport)->get('/api/v1/reports/export.csv?report=assets')->assertForbidden();
    }
}
