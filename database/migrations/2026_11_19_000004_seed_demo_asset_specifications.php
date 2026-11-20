<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'Laptop' => ['processor' => 'Intel Core i5', 'generation' => '11.ª generación', 'ram' => '8 GB', 'ramType' => 'DDR4', 'storage' => '512 GB', 'storageType' => 'SSD', 'gpu' => 'Integrada', 'screenSize' => '14 pulgadas', 'os' => 'Windows 11 Pro', 'connection' => 'Wi‑Fi / Bluetooth'],
            'Computadora de escritorio' => ['processor' => 'Intel Core i5', 'generation' => '11.ª generación', 'ram' => '16 GB', 'ramType' => 'DDR4', 'storage' => '1 TB', 'storageType' => 'SSD', 'gpu' => 'Integrada', 'os' => 'Windows 11 Pro', 'connection' => 'Ethernet'],
            'Servidor' => ['processor' => 'Intel Xeon E-2336', 'cores' => '6 núcleos', 'ram' => '32 GB', 'storage' => '2 TB', 'storageType' => 'HDD empresarial', 'raid' => 'RAID 1', 'os' => 'Ubuntu Server 22.04 LTS', 'rack' => 'Rack principal'],
            'Switch de red' => ['ports' => '24 puertos', 'speed' => '10/100/1000 Mbps', 'managed' => 'Sí', 'power' => 'PoE+', 'uplink' => '4 puertos SFP', 'rack' => 'Rack de comunicaciones'],
            'UPS' => ['capacity' => '1500 VA / 900 W', 'autonomy' => '15 minutos', 'outlets' => '8 salidas', 'battery' => 'Batería sellada AGM', 'topology' => 'Interactiva'],
            'Impresora' => ['technology' => 'Láser', 'speed' => '30 ppm', 'resolution' => '1200 × 1200 dpi', 'color' => 'Monocromática', 'paperSize' => 'A4 / Carta', 'duplex' => 'Sí', 'connection' => 'Ethernet / USB'],
            'Monitor' => ['screenSize' => '24 pulgadas', 'resolution' => 'Full HD 1920 × 1080', 'panel' => 'LED IPS', 'refreshRate' => '60 Hz', 'ports' => 'HDMI / VGA'],
            'Escáner' => ['speed' => '25 ppm', 'resolution' => '600 dpi', 'size' => 'A4', 'duplex' => 'Sí', 'connection' => 'USB 3.0'],
            'Proyector' => ['lumens' => '3,200 lúmenes', 'resolution' => 'WXGA 1280 × 800', 'technology' => 'DLP', 'connection' => 'HDMI / VGA'],
            'Tablet' => ['processor' => 'Octa-core 2.0 GHz', 'ram' => '4 GB', 'storage' => '64 GB', 'screenSize' => '10.1 pulgadas', 'os' => 'Android 13', 'connection' => 'Wi‑Fi / Bluetooth'],
            'Lector de códigos' => ['technology' => 'Láser 1D/2D', 'connection' => 'USB', 'range' => 'Hasta 30 cm'],
            'Silla' => ['material' => 'Estructura metálica y tapizado textil', 'dimensions' => '60 × 60 × 90 cm', 'color' => 'Negro', 'condition' => 'Bueno'],
            'Escritorio' => ['material' => 'Melamina y estructura metálica', 'dimensions' => '120 × 60 × 75 cm', 'color' => 'Madera / gris', 'condition' => 'Bueno'],
            'Mesa' => ['material' => 'Melamina', 'dimensions' => '160 × 80 × 75 cm', 'color' => 'Madera', 'condition' => 'Bueno'],
            'Archivador' => ['material' => 'Metal', 'dimensions' => '90 × 45 × 135 cm', 'color' => 'Gris', 'condition' => 'Bueno'],
            'Estantería' => ['material' => 'Metal', 'dimensions' => '180 × 90 × 40 cm', 'color' => 'Gris', 'condition' => 'Bueno'],
            'Carretilla' => ['material' => 'Acero', 'dimensions' => '120 × 60 × 100 cm', 'color' => 'Azul', 'condition' => 'Bueno'],
        ];

        DB::table('assets')->where(fn ($query) => $query->where('asset_code', 'like', 'MDM-DEMO-%')->orWhere('asset_code', 'like', 'AYU-%'))
            ->get(['id', 'asset_type'])
            ->each(function ($asset) use ($defaults): void {
                if (isset($defaults[$asset->asset_type])) DB::table('assets')->where('id', $asset->id)->update(['specifications' => json_encode($defaults[$asset->asset_type], JSON_UNESCAPED_UNICODE)]);
            });
    }

    public function down(): void
    {
        DB::table('assets')->where(fn ($query) => $query->where('asset_code', 'like', 'MDM-DEMO-%')->orWhere('asset_code', 'like', 'AYU-%'))->update(['specifications' => null]);
    }
};
