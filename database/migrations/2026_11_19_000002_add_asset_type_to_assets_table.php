<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->string('asset_type', 80)->nullable()->after('category_id')->index();
        });

        foreach (DB::table('assets')->select(['id', 'name'])->get() as $asset) {
            $name = mb_strtolower((string) $asset->name);
            $type = match (true) {
                str_contains($name, 'laptop') || str_contains($name, 'portátil') => 'Laptop',
                str_contains($name, 'computadora') || str_contains($name, 'estación de trabajo') => 'Computadora de escritorio',
                str_contains($name, 'impresora') => 'Impresora',
                str_contains($name, 'monitor') => 'Monitor',
                str_contains($name, 'escáner') || str_contains($name, 'escaner') => 'Escáner',
                str_contains($name, 'proyector') => 'Proyector',
                str_contains($name, 'switch') => 'Switch de red',
                str_contains($name, 'ups') || str_contains($name, 'respaldo eléctrico') => 'UPS',
                str_contains($name, 'servidor') => 'Servidor',
                str_contains($name, 'lector') => 'Lector de códigos',
                str_contains($name, 'tablet') => 'Tablet',
                str_contains($name, 'silla') => 'Silla',
                str_contains($name, 'escritorio') => 'Escritorio',
                str_contains($name, 'mesa') => 'Mesa',
                str_contains($name, 'archivador') => 'Archivador',
                str_contains($name, 'armario') => 'Armario',
                str_contains($name, 'estantería') || str_contains($name, 'estanteria') => 'Estantería',
                str_contains($name, 'carretilla') || str_contains($name, 'carro') => 'Carretilla',
                str_contains($name, 'módulo') || str_contains($name, 'modulo') => 'Módulo de archivo',
                default => null,
            };
            if ($type !== null) DB::table('assets')->where('id', $asset->id)->update(['asset_type' => $type]);
        }
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropColumn('asset_type');
        });
    }
};
