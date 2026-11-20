<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Area;
use App\Models\DisposalRequest;
use App\Models\InventoryCampaign;
use App\Models\InventoryCheck;
use App\Models\MovementRequest;
use App\Models\ReconciliationDifference;
use App\Models\ReconciliationRun;
use App\Models\TechnicalEvaluation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Carga resultados ficticios para visualizar los procesos P-03 a P-08 en local. */
class ProcessDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local') && ! (bool) env('DEMO_SCENARIO_ENABLED', false)) {
            $this->command?->warn('ProcessDemoSeeder omitido fuera de local.');
            return;
        }

        DB::transaction(function (): void {
            $pat = User::query()->where('email', 'patrimonio@moralesmuni.gob.pe')->firstOrFail();
            $admin = User::query()->where('email', 'administrador@moralesmuni.gob.pe')->firstOrFail();
            $assistantPat = User::query()->where('email', 'carla.torres@moralesmuni.gob.pe')->firstOrFail();
            $assistantTi = User::query()->where('email', 'ana.rivera@moralesmuni.gob.pe')->firstOrFail();
            $oci = User::query()->where('email', 'oci@moralesmuni.gob.pe')->firstOrFail();
            $patArea = Area::query()->where('code', 'PAT')->firstOrFail();
            $logArea = Area::query()->where('code', 'LOG')->firstOrFail();
            $ociArea = Area::query()->where('code', 'OCI')->firstOrFail();
            $patLocation = $patArea->assetLocations()->where('code', 'PRINCIPAL')->firstOrFail();
            $logLocation = $logArea->assetLocations()->where('code', 'PRINCIPAL')->firstOrFail();
            $ociLocation = $ociArea->assetLocations()->where('code', 'PRINCIPAL')->firstOrFail();
            $assets = Asset::query()->whereIn('asset_code', ['MDM-DEMO-001', 'MDM-DEMO-005', 'MDM-DEMO-006', 'MDM-DEMO-007', 'MDM-DEMO-008'])->get()->keyBy('asset_code');

            $this->movement($assets['MDM-DEMO-001'], $patArea, $patLocation, $logArea, $logLocation, $pat, $assistantPat, 'pending', 'Traslado de equipo a Logística para apoyo de inventario.');
            $this->movement($assets['MDM-DEMO-005'], $patArea, $patLocation, $ociArea, $ociLocation, $pat, $assistantPat, 'rejected', 'Solicitud rechazada: el destino requiere validación previa.');
            $this->movement($assets['MDM-DEMO-006'], $patArea, $patLocation, $logArea, $logLocation, $pat, $assistantPat, 'received', 'Traslado ejecutado para custodia temporal en almacén.');

            $active = InventoryCampaign::query()->firstOrCreate(['code' => 'INV-DEMO-2026-A'], ['name' => 'Inventario de bienes tecnológicos y mobiliario', 'area_id' => $patArea->id, 'created_by' => $pat->id, 'status' => 'in_progress', 'started_at' => now()->subDays(2), 'notes' => 'Campaña ficticia para demostración.']);
            $closed = InventoryCampaign::query()->firstOrCreate(['code' => 'INV-DEMO-2026-C'], ['name' => 'Inventario cerrado con hallazgos', 'area_id' => $patArea->id, 'created_by' => $pat->id, 'status' => 'closed', 'started_at' => now()->subDays(8), 'closed_at' => now()->subDays(5), 'notes' => 'Campaña ficticia cerrada conservando diferencias.']);
            if (! $active->checks()->exists()) {
                InventoryCheck::create(['campaign_id' => $active->id, 'asset_id' => $assets['MDM-DEMO-001']->id, 'checked_by' => $assistantPat->id, 'result' => 'found', 'observed_area_id' => $patArea->id, 'observed_location_id' => $patLocation->id, 'checked_at' => now()->subDay(), 'notes' => 'Encontrado en la ubicación registrada.']);
                InventoryCheck::create(['campaign_id' => $active->id, 'asset_id' => $assets['MDM-DEMO-007']->id, 'checked_by' => $assistantPat->id, 'result' => 'location_mismatch', 'observed_area_id' => $logArea->id, 'observed_location_id' => $logLocation->id, 'checked_at' => now()->subHours(8), 'notes' => 'Encontrado en Logística; requiere revisión.']);
            }
            if (! $closed->checks()->exists()) {
                InventoryCheck::create(['campaign_id' => $closed->id, 'asset_id' => $assets['MDM-DEMO-008']->id, 'checked_by' => $pat->id, 'result' => 'not_found', 'observed_area_id' => $patArea->id, 'observed_location_id' => null, 'checked_at' => now()->subDays(6), 'notes' => 'No localizado durante la verificación.']);
            }

            $run = ReconciliationRun::query()->firstOrCreate(['code' => 'REC-DEMO-2026-A'], ['name' => 'Conciliación ficticia del padrón de noviembre', 'area_id' => $patArea->id, 'created_by' => $pat->id, 'source_file_name' => 'padron-demo-noviembre.csv', 'source_path' => 'reconciliations/demo/padron-demo-noviembre.csv', 'status' => 'completed', 'executed_at' => now()->subDay(), 'notes' => 'Fuente CSV ficticia; no es integración SIGA/MEF/SINABIP.']);
            if (! $run->differences()->exists()) {
                ReconciliationDifference::create(['reconciliation_run_id' => $run->id, 'asset_id' => $assets['MDM-DEMO-001']->id, 'asset_code' => $assets['MDM-DEMO-001']->asset_code, 'difference_type' => 'location_mismatch', 'system_snapshot' => ['area' => 'Patrimonio', 'location' => 'Oficina principal'], 'reference_snapshot' => ['area' => 'Patrimonio', 'location' => 'Almacén municipal'], 'status' => 'pending']);
                ReconciliationDifference::create(['reconciliation_run_id' => $run->id, 'asset_code' => 'REF-DEMO-999', 'difference_type' => 'missing_in_system', 'reference_snapshot' => ['description' => 'Bien ficticio pendiente de validar'], 'status' => 'in_review', 'reviewed_by' => $pat->id, 'reviewed_at' => now()->subHours(4), 'resolution_notes' => 'Pendiente de confirmar existencia física.']);
            }

            $evaluation = TechnicalEvaluation::query()->firstOrCreate(['asset_id' => $assets['MDM-DEMO-008']->id, 'reason' => 'Evaluación técnica preventiva de bien ficticio'], ['requested_by' => $pat->id, 'assigned_to' => $assistantTi->id, 'status' => 'reviewed', 'physical_condition' => 'Regular', 'diagnosis' => 'Requiere mantenimiento preventivo y revisión de componentes.', 'recommendations' => 'Programar mantenimiento; no cambiar el estado patrimonial sin decisión autorizada.', 'observations' => 'Evaluación creada para demostración local.', 'requested_at' => now()->subDays(3), 'assigned_at' => now()->subDays(3), 'evaluated_at' => now()->subDays(2), 'reviewed_at' => now()->subDay()]);
            DisposalRequest::query()->firstOrCreate(['asset_id' => $assets['MDM-DEMO-008']->id, 'reason' => 'Expediente ficticio vinculado a evaluación técnica'], ['requested_by' => $pat->id, 'technical_evaluation_id' => $evaluation->id, 'status' => 'pending_authority', 'authority_status' => 'pending_validation', 'cause' => 'Obsolescencia propuesta', 'documentation_notes' => 'Documentación ficticia pendiente de validación institucional.', 'requested_at' => now()->subDay(), 'reviewed_by' => $pat->id, 'reviewed_at' => now()->subHours(6), 'decision_notes' => 'No se ejecuta baja definitiva sin autoridad confirmada.']);
        });

        $this->command?->info('Procesos demo cargados: movimientos, inventarios, conciliación, evaluación y baja pendiente.');
    }

    private function movement(Asset $asset, Area $fromArea, $fromLocation, Area $toArea, $toLocation, User $approver, User $requester, string $status, string $reason): void
    {
        $movement = MovementRequest::query()->firstOrCreate(['asset_id' => $asset->id, 'reason' => $reason], ['from_area_id' => $fromArea->id, 'from_location_id' => $fromLocation->id, 'to_area_id' => $toArea->id, 'to_location_id' => $toLocation->id, 'to_user_id' => null, 'requested_by' => $requester->id, 'status' => $status, 'approved_by' => $status === 'pending' ? null : $approver->id, 'received_by' => $status === 'received' ? $approver->id : null, 'requested_at' => now()->subDays(2), 'decided_at' => $status === 'pending' ? null : now()->subDay(), 'received_at' => $status === 'received' ? now()->subHours(12) : null, 'rejection_reason' => $status === 'rejected' ? 'Destino no validado para esta solicitud.' : null]);
        if ($status === 'received' && $movement->wasRecentlyCreated) {
            $asset->update(['area_id' => $toArea->id, 'location_id' => $toLocation->id, 'updated_by' => $approver->id]);
        }
    }
}
