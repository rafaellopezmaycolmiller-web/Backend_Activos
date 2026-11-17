<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\AssetStatus;
use Illuminate\Database\Seeder;

class AssetCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['code' => 'TEC', 'name' => 'Equipos informáticos y comunicaciones'],
            ['code' => 'MOB', 'name' => 'Mobiliario municipal'],
        ] as $item) {
            AssetCategory::query()->updateOrCreate(['code' => $item['code']], [...$item, 'is_active' => true]);
        }
        AssetCategory::query()
            ->whereNotIn('code', AssetCategory::PROJECT_SCOPE_CODES)
            ->update(['is_active' => false]);
        foreach ([
            ['code' => 'OPERATIVO', 'name' => 'Operativo'],
            ['code' => 'MANTENIMIENTO', 'name' => 'En mantenimiento'],
            ['code' => 'BAJA_PROPUESTA', 'name' => 'Baja en evaluación'],
            ['code' => 'BAJA', 'name' => 'Dado de baja'],
        ] as $item) {
            AssetStatus::query()->updateOrCreate(['code' => $item['code']], [...$item, 'is_active' => true]);
        }
        $mainLocations = [
            'PAT' => 'Oficina de Patrimonio',
            'LOG' => 'Almacén municipal',
            'TI' => 'Oficina de Tecnologías de la Información',
            'OGA' => 'Oficina General de Administración',
            'OCI' => 'Oficina del Órgano de Control Institucional',
        ];
        foreach (Area::query()->where('code', '!=', 'MDM')->get() as $area) {
            AssetLocation::query()->updateOrCreate(['area_id' => $area->id, 'code' => 'PRINCIPAL'], ['name' => $mainLocations[$area->code] ?? 'Oficina principal', 'description' => 'Ubicación principal del área para la asignación de bienes.', 'is_active' => true]);
        }
    }
}
