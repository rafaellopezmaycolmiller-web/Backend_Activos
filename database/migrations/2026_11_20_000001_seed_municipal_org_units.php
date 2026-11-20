<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Area;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('areas')) return;

        $root = Area::query()->firstOrCreate(['code' => 'MDM'], ['parent_id' => null, 'name' => 'Municipalidad Distrital de Morales', 'description' => 'Raíz organizacional.', 'is_active' => true]);
        Area::query()->firstOrCreate(['code' => 'OGA'], ['parent_id' => $root->id, 'name' => 'Oficina General de Administración', 'description' => 'Gestión administrativa municipal.', 'is_active' => true]);

        $units = [
            ['code' => 'GM', 'name' => 'Gerencia Municipal', 'parent' => 'MDM'],
            ['code' => 'SG', 'name' => 'Secretaría General', 'parent' => 'MDM'],
            ['code' => 'OAJ', 'name' => 'Oficina General de Asesoría Jurídica', 'parent' => 'MDM'],
            ['code' => 'OGPP', 'name' => 'Oficina General de Planeamiento y Presupuesto', 'parent' => 'MDM'],
            ['code' => 'GAT', 'name' => 'Gerencia de Administración Tributaria', 'parent' => 'MDM'],
            ['code' => 'GDE', 'name' => 'Gerencia de Desarrollo Territorial y Económico', 'parent' => 'MDM'],
            ['code' => 'GSMGA', 'name' => 'Gerencia de Servicios Municipales y Gestión Ambiental', 'parent' => 'MDM'],
            ['code' => 'GDS', 'name' => 'Gerencia de Desarrollo Social', 'parent' => 'MDM'],
            ['code' => 'RRHH', 'name' => 'Oficina de Recursos Humanos', 'parent' => 'OGA'],
            ['code' => 'ABAST', 'name' => 'Oficina de Abastecimiento, Logística y Almacén', 'parent' => 'OGA'],
            ['code' => 'CONT-PAT', 'name' => 'Oficina de Contabilidad y Patrimonio', 'parent' => 'OGA'],
            ['code' => 'TES', 'name' => 'Oficina de Tesorería', 'parent' => 'OGA'],
            ['code' => 'IMAGEN', 'name' => 'Oficina de Imagen Institucional', 'parent' => 'SG'],
            ['code' => 'ARCH', 'name' => 'Archivo', 'parent' => 'SG'],
            ['code' => 'TRAMDOC', 'name' => 'Trámite Documentario', 'parent' => 'SG'],
            ['code' => 'INF-SIS', 'name' => 'Oficina de Informática y Sistemas', 'parent' => 'SG'],
            ['code' => 'REG-COB', 'name' => 'Registro y Cobranza Tributaria', 'parent' => 'GAT'],
            ['code' => 'EJEC-COA', 'name' => 'Ejecución Coactiva', 'parent' => 'GAT'],
            ['code' => 'FISC-TRIB', 'name' => 'Fiscalización Tributaria', 'parent' => 'GAT'],
            ['code' => 'PRES-RAC', 'name' => 'Presupuesto y Racionalización', 'parent' => 'OGPP'],
            ['code' => 'PLAN-EST', 'name' => 'Planeamiento y Estadística', 'parent' => 'OGPP'],
            ['code' => 'POL-FISC', 'name' => 'Policía Municipal y Fiscalización', 'parent' => 'GSMGA'],
            ['code' => 'GA', 'name' => 'Gestión Ambiental', 'parent' => 'GSMGA'],
            ['code' => 'LIMP-PJ', 'name' => 'Limpieza Pública, Parques y Jardines', 'parent' => 'GSMGA'],
            ['code' => 'SSC', 'name' => 'Seguridad Ciudadana y Serenazgo', 'parent' => 'GSMGA'],
            ['code' => 'INFRA-OT', 'name' => 'Infraestructura y Ordenamiento Territorial', 'parent' => 'GDE'],
            ['code' => 'MAQ-MANT', 'name' => 'Maquinaria y Mantenimiento', 'parent' => 'GDE'],
        ];

        foreach ($units as $unit) {
            $parentId = Area::query()->where('code', $unit['parent'])->value('id') ?? $root->id;
            Area::query()->updateOrCreate(
                ['code' => $unit['code']],
                ['parent_id' => $parentId, 'name' => $unit['name'], 'description' => 'Unidad organizacional referencial basada en instrumentos oficiales publicados; confirmar cambios con la municipalidad.', 'is_active' => true],
            );
        }
    }

    public function down(): void
    {
        Area::query()->whereIn('code', ['GM','SG','OAJ','OGPP','GAT','GDE','GSMGA','GDS','RRHH','ABAST','CONT-PAT','TES','IMAGEN','ARCH','TRAMDOC','INF-SIS','REG-COB','EJEC-COA','FISC-TRIB','PRES-RAC','PLAN-EST','POL-FISC','GA','LIMP-PJ','SSC','INFRA-OT','MAQ-MANT'])->delete();
    }
};
