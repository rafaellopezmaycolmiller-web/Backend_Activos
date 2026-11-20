<?php

use App\Models\Area;
use App\Models\AssetLocation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asset_locations') || ! Schema::hasTable('areas')) return;
        Area::query()->where('is_active', true)->get()->each(function (Area $area): void {
            AssetLocation::query()->updateOrCreate(
                ['code' => 'UBI-'.$area->code],
                ['area_id' => $area->id, 'name' => 'Ambiente principal · '.$area->name, 'description' => 'Ubicación inicial de la unidad organizacional.', 'is_active' => true],
            );
        });
    }

    public function down(): void
    {
        AssetLocation::query()->where('code', 'like', 'UBI-%')->delete();
    }
};
