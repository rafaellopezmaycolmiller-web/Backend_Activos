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

        Area::query()->whereNotNull('parent_id')->where('is_active', true)->get()->each(function (Area $child): void {
            $parent = Area::query()->find($child->parent_id);
            if (! $parent) return;
            AssetLocation::query()->updateOrCreate(
                ['code' => 'OF-'.$parent->code.'-'.$child->code],
                ['area_id' => $parent->id, 'name' => $child->name, 'description' => 'Oficina interna de '.$parent->name.'.', 'is_active' => true],
            );
        });
    }

    public function down(): void
    {
        AssetLocation::query()->where('code', 'like', 'OF-%')->delete();
    }
};
