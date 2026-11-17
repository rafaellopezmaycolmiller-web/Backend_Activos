<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Asset;
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
        foreach (Area::query()->where('code', '!=', 'MDM')->get() as $area) {
            AssetLocation::query()->updateOrCreate(['area_id' => $area->id, 'code' => 'PRINCIPAL'], ['name' => 'Ambiente principal', 'description' => 'Ubicación inicial pendiente de inventario físico.', 'is_active' => true]);
        }
        if (app()->environment(['local', 'testing'])) {
            $area = Area::query()->where('code', 'PAT')->first();
            $category = AssetCategory::query()->where('code', 'TEC')->first();
            $status = AssetStatus::query()->where('code', 'OPERATIVO')->first();
            $location = AssetLocation::query()->where('area_id', $area?->id)->first();
            if ($area && $category && $status && $location) {
                $asset = Asset::query()->firstOrCreate(['asset_code' => 'MDM-TEC-0001'], ['inventory_code' => 'INV-0001', 'category_id' => $category->id, 'status_id' => $status->id, 'area_id' => $area->id, 'location_id' => $location->id, 'name' => 'Computadora portátil de demostración', 'description' => 'Registro de ejemplo para validar el flujo de ficha y versiones.', 'brand' => 'Lenovo', 'model' => 'ThinkPad demo', 'serial_number' => 'DEMO-0001', 'is_active' => true]);
                if ($asset->versions()->doesntExist()) {
                    $asset->versions()->create(['version' => 1, 'action' => 'created', 'snapshot' => $asset->only(['asset_code', 'inventory_code', 'category_id', 'status_id', 'area_id', 'location_id', 'name', 'description', 'brand', 'model', 'serial_number', 'is_active'])]);
                }
            }
        }
    }
}
