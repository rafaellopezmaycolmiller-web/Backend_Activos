<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDocument;
use App\Models\AssetLocation;
use App\Models\AssetStatus;
use App\Models\Role;
use App\Models\User;
use App\RoleScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Scenario reproducible para la sustentación. No se ejecuta salvo que
 * DEMO_SCENARIO_ENABLED=true y nunca debe usarse para datos municipales reales.
 */
class DemoScenarioSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local') && ! (bool) env('DEMO_SCENARIO_ENABLED', true)) {
            $this->command?->warn('DemoScenarioSeeder omitido: DEMO_SCENARIO_ENABLED no está activo.');

            return;
        }

        $password = (string) env('DEMO_ASSISTANT_PASSWORD', 'DemoAyudante2026!');
        $assets = $this->assetCatalog();
        $imageRoot = database_path('seeders/demo-assets');

        foreach (array_unique(array_column($assets, 'image')) as $image) {
            if (! is_file($imageRoot.'/'.$image)) {
                throw new RuntimeException("Falta la imagen de demostración: {$image}");
            }
        }

        DB::transaction(function () use ($password, $assets, $imageRoot): void {
            $users = [];
            foreach ($this->assistants() as $item) {
                $area = Area::query()->where('code', $item['area'])->firstOrFail();
                $role = Role::query()->where('name', $item['role'])->firstOrFail();
                $user = User::query()->updateOrCreate(
                    ['email' => $item['email']],
                    [
                        'area_id' => $area->id,
                        'name' => $item['name'],
                        'job_title' => $item['job_title'],
                        'password' => $password,
                        'email_verified_at' => now(),
                        'is_active' => true,
                        'mfa_required' => false,
                        'failed_login_attempts' => 0,
                        'locked_until' => null,
                    ],
                );
                $user->roles()->sync([$role->id => ['scope' => RoleScope::OwnArea->value]]);
                $users[$user->email] = $user;
            }

            $status = AssetStatus::query()->where('code', 'OPERATIVO')->firstOrFail();
            foreach ($assets as $item) {
                $creator = $users[$item['owner']];
                $area = Area::query()->where('code', $item['area'])->firstOrFail();
                $category = AssetCategory::query()->where('code', $item['category'])->firstOrFail();
                $location = AssetLocation::query()->where('area_id', $area->id)->where('code', 'PRINCIPAL')->firstOrFail();
                $asset = Asset::query()->updateOrCreate(
                    ['asset_code' => $item['code']],
                    [
                        'inventory_code' => 'INV-'.$item['code'],
                        'category_id' => $category->id,
                        'status_id' => $status->id,
                        'area_id' => $area->id,
                        'location_id' => $location->id,
                        'name' => $item['name'],
                        'description' => 'Bien ficticio para demostración; registrado por '.$creator->name.'.',
                        'brand' => $item['brand'],
                        'model' => $item['model'],
                        'serial_number' => $item['serial'],
                        'acquisition_date' => now()->toDateString(),
                        'useful_life_years' => 5,
                        'current_version' => 1,
                        'created_by' => $creator->id,
                        'updated_by' => $creator->id,
                        'is_active' => true,
                    ],
                );

                if ($asset->wasRecentlyCreated) {
                    $asset->versions()->create([
                        'version' => 1,
                        'action' => 'created',
                        'snapshot' => ['asset_code' => $asset->asset_code, 'name' => $asset->name],
                        'changed_by' => $creator->id,
                    ]);
                }

                $path = $imageRoot.'/'.$item['image'];
                AssetDocument::query()->where('asset_id', $asset->id)->where('path', 'like', 'demo-assets/%')->delete();
                $target = storage_path('app/private/demo-assets/'.$item['image']);
                if (! is_dir(dirname($target))) {
                    mkdir(dirname($target), 0755, true);
                }
                copy($path, $target);
                AssetDocument::query()->create([
                    'asset_id' => $asset->id,
                    'document_type' => 'photo',
                    'file_name' => $item['image'],
                    'disk' => 'private',
                    'path' => 'demo-assets/'.$item['image'],
                    'mime_type' => 'image/png',
                    'size' => filesize($target),
                    'sha256' => hash_file('sha256', $target),
                    'uploaded_by' => $creator->id,
                    'metadata' => ['source' => 'Escenario ficticio de demostración', 'demo_only' => true],
                ]);
            }

            // Corrige registros demo heredados que atribuían la creación a OCI.
            // OCI puede ser el área designada/destino, pero no el registrador.
            $patrimonyCreator = User::query()->where('email', 'patrimonio@moralesmuni.gob.pe')->firstOrFail();
            $legacyOciAssets = Asset::query()->whereHas('creator.area', static function ($query): void {
                $query->where('code', 'OCI');
            })->get();
            foreach ($legacyOciAssets as $legacyAsset) {
                $legacyAsset->forceFill([
                    'created_by' => $patrimonyCreator->id,
                    'updated_by' => $patrimonyCreator->id,
                    'description' => str_replace(
                        ['Auditoría OCI', 'Órgano de Control Institucional'],
                        'Responsable de Patrimonio',
                        (string) $legacyAsset->description,
                    ),
                ])->saveQuietly();

                AssetDocument::query()
                    ->where('asset_id', $legacyAsset->id)
                    ->where('metadata->demo_only', true)
                    ->update(['uploaded_by' => $patrimonyCreator->id]);
            }
        });

        $this->command?->info('Escenario demo cargado: 6 ayudantes y 18 activos con fotografías.');
    }

    private function assistants(): array
    {
        return [
            ['name' => 'Ana Lucía Rivera', 'email' => 'ana.rivera@moralesmuni.gob.pe', 'area' => 'TI', 'role' => 'Asistente de TI', 'job_title' => 'Asistente de soporte tecnológico'],
            ['name' => 'Luis Fernando Quispe', 'email' => 'luis.quispe@moralesmuni.gob.pe', 'area' => 'TI', 'role' => 'Asistente de TI', 'job_title' => 'Asistente de infraestructura tecnológica'],
            ['name' => 'Carla Milagros Torres', 'email' => 'carla.torres@moralesmuni.gob.pe', 'area' => 'PAT', 'role' => 'Asistente de Patrimonio', 'job_title' => 'Asistente de control patrimonial'],
            ['name' => 'Diego Andrés Salazar', 'email' => 'diego.salazar@moralesmuni.gob.pe', 'area' => 'PAT', 'role' => 'Asistente de Patrimonio', 'job_title' => 'Asistente de inventario patrimonial'],
            ['name' => 'Sofía Elena Paredes', 'email' => 'sofia.paredes@moralesmuni.gob.pe', 'area' => 'OCI', 'role' => 'Asistente de OCI', 'job_title' => 'Asistente de auditoría institucional'],
            ['name' => 'Marco Antonio Vega', 'email' => 'marco.vega@moralesmuni.gob.pe', 'area' => 'OCI', 'role' => 'Asistente de OCI', 'job_title' => 'Asistente de control y seguimiento'],
        ];
    }

    private function assetCatalog(): array
    {
        $rows = [
            ['AYU-TI-ANA-001', 'ana.rivera@moralesmuni.gob.pe', 'TI', 'TEC', 'Switch administrable de red', 'NetLink', 'SW-24G', 'SW-AYU-ANA-001', 'switch-cutout.png'],
            ['AYU-TI-ANA-002', 'ana.rivera@moralesmuni.gob.pe', 'TI', 'TEC', 'Sistema de respaldo eléctrico UPS', 'PowerSafe', 'PS-1500', 'UPS-AYU-ANA-002', 'ups-cutout.png'],
            ['AYU-TI-ANA-003', 'ana.rivera@moralesmuni.gob.pe', 'TI', 'TEC', 'Proyector multimedia institucional', 'ViewPro', 'VP-3200', 'PRO-AYU-ANA-003', 'projector-cutout.png'],
            ['AYU-TI-LUIS-001', 'luis.quispe@moralesmuni.gob.pe', 'TI', 'TEC', 'Monitor LED para soporte técnico', 'VisionOffice', 'VO-24F', 'MON-AYU-LUIS-001', 'monitor-cutout.png'],
            ['AYU-TI-LUIS-002', 'luis.quispe@moralesmuni.gob.pe', 'TI', 'TEC', 'Impresora multifuncional de soporte', 'PrintOffice', 'PO-MF420', 'IMP-AYU-LUIS-002', 'printer-cutout.png'],
            ['AYU-TI-LUIS-003', 'luis.quispe@moralesmuni.gob.pe', 'TI', 'TEC', 'Escáner documental de soporte', 'ScanPro', 'SP-410', 'ESC-AYU-LUIS-003', 'scanner-cutout.png'],
            ['AYU-PAT-CARLA-001', 'carla.torres@moralesmuni.gob.pe', 'PAT', 'MOB', 'Archivador metálico de cuatro gavetas', 'MetalOffice', 'MO-4G', 'ARC-AYU-CARLA-001', 'cabinet-cutout.png'],
            ['AYU-PAT-CARLA-002', 'carla.torres@moralesmuni.gob.pe', 'PAT', 'MOB', 'Estantería metálica para archivo', 'MetalStore', 'MS-180', 'EST-AYU-CARLA-002', 'shelf-cutout.png'],
            ['AYU-PAT-CARLA-003', 'carla.torres@moralesmuni.gob.pe', 'PAT', 'MOB', 'Mesa de trabajo patrimonial', 'Mobiliario Andino', 'MA-160', 'MES-AYU-CARLA-003', 'table-cutout.png'],
            ['AYU-PAT-DIEGO-001', 'diego.salazar@moralesmuni.gob.pe', 'PAT', 'MOB', 'Silla operativa para inventario', 'ErgoMuni', 'EM-01', 'SIL-AYU-DIEGO-001', 'chair-cutout.png'],
            ['AYU-PAT-DIEGO-002', 'diego.salazar@moralesmuni.gob.pe', 'PAT', 'MOB', 'Archivador auxiliar de patrimonio', 'MetalOffice', 'MO-2G', 'ARC-AYU-DIEGO-002', 'cabinet-cutout.png'],
            ['AYU-PAT-DIEGO-003', 'diego.salazar@moralesmuni.gob.pe', 'PAT', 'MOB', 'Estantería auxiliar de expedientes', 'MetalStore', 'MS-150', 'EST-AYU-DIEGO-003', 'shelf-cutout.png'],
            ['AYU-OCI-SOFIA-001', 'diego.salazar@moralesmuni.gob.pe', 'OCI', 'TEC', 'Escáner para expedientes de control', 'ScanPro', 'SP-520', 'ESC-AYU-SOFIA-001', 'scanner-cutout.png'],
            ['AYU-OCI-SOFIA-002', 'diego.salazar@moralesmuni.gob.pe', 'OCI', 'TEC', 'Monitor LED para revisión documental', 'VisionOffice', 'VO-27Q', 'MON-AYU-SOFIA-002', 'monitor-cutout.png'],
            ['AYU-OCI-SOFIA-003', 'diego.salazar@moralesmuni.gob.pe', 'OCI', 'TEC', 'Impresora de informes de auditoría', 'PrintOffice', 'PO-410', 'IMP-AYU-SOFIA-003', 'printer-cutout.png'],
            ['AYU-OCI-MARCO-001', 'carla.torres@moralesmuni.gob.pe', 'OCI', 'MOB', 'Archivador de documentación reservada', 'MetalOffice', 'MO-4G', 'ARC-AYU-MARCO-001', 'cabinet-cutout.png'],
            ['AYU-OCI-MARCO-002', 'carla.torres@moralesmuni.gob.pe', 'OCI', 'MOB', 'Silla ergonómica de auditoría', 'ErgoMuni', 'EM-02', 'SIL-AYU-MARCO-002', 'chair-cutout.png'],
            ['AYU-OCI-MARCO-003', 'carla.torres@moralesmuni.gob.pe', 'OCI', 'TEC', 'Monitor para análisis de evidencias', 'VisionOffice', 'VO-24F', 'MON-AYU-MARCO-003', 'monitor-cutout.png'],
        ];

        return array_map(fn (array $row): array => ['code' => $row[0], 'owner' => $row[1], 'area' => $row[2], 'category' => $row[3], 'name' => $row[4], 'brand' => $row[5], 'model' => $row[6], 'serial' => $row[7], 'image' => $row[8]], $rows);
    }
}
