<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('assets')->where('asset_code', 'MDM-TEC-01')->update(['specifications' => json_encode([
            'processor' => 'Intel Core i5', 'generation' => '10.ª generación', 'ram' => '8 GB', 'ramType' => 'DDR4',
            'storage' => '512 GB', 'storageType' => 'SSD', 'gpu' => 'Integrada', 'screenSize' => '15.6 pulgadas',
            'os' => 'Windows 11 Pro', 'connection' => 'Wi‑Fi / Bluetooth',
        ], JSON_UNESCAPED_UNICODE)]);
        DB::table('assets')->where('asset_code', 'MDM-INF-MAYCOL')->update(['specifications' => json_encode([
            'material' => 'Estructura metálica y tapizado sintético', 'dimensions' => '60 × 60 × 115 cm', 'color' => 'Negro', 'condition' => 'Bueno',
        ], JSON_UNESCAPED_UNICODE)]);
        DB::table('assets')->where('asset_code', 'MDM-INF-2002')->update(['specifications' => json_encode([
            'material' => 'Madera', 'dimensions' => '45 × 45 × 90 cm', 'color' => 'Madera natural', 'condition' => 'Bueno',
        ], JSON_UNESCAPED_UNICODE)]);
    }

    public function down(): void
    {
        DB::table('assets')->whereIn('asset_code', ['MDM-TEC-01', 'MDM-INF-MAYCOL', 'MDM-INF-2002'])->update(['specifications' => null]);
    }
};
