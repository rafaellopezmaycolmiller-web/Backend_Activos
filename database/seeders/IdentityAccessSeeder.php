<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class IdentityAccessSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            ['code' => 'dashboard.view', 'name' => 'Ver panel institucional', 'module' => 'dashboard'],
            ['code' => 'users.view', 'name' => 'Consultar usuarios', 'module' => 'identity'],
            ['code' => 'users.create', 'name' => 'Crear usuarios', 'module' => 'identity'],
            ['code' => 'users.update', 'name' => 'Actualizar usuarios', 'module' => 'identity'],
            ['code' => 'roles.view', 'name' => 'Consultar roles y permisos', 'module' => 'identity'],
            ['code' => 'roles.assign', 'name' => 'Asignar roles y alcances', 'module' => 'identity'],
            ['code' => 'areas.view', 'name' => 'Consultar áreas', 'module' => 'organization'],
            ['code' => 'areas.manage', 'name' => 'Administrar áreas', 'module' => 'organization'],
            ['code' => 'audit.view', 'name' => 'Consultar auditoría', 'module' => 'audit'],
            ['code' => 'audit.export', 'name' => 'Exportar auditoría', 'module' => 'audit'],
            ['code' => 'assets.view', 'name' => 'Consultar activos', 'module' => 'assets'],
            ['code' => 'assets.create', 'name' => 'Registrar activos', 'module' => 'assets'],
            ['code' => 'assets.update', 'name' => 'Actualizar activos', 'module' => 'assets'],
            ['code' => 'asset_categories.view', 'name' => 'Consultar categorías', 'module' => 'assets'],
            ['code' => 'asset_categories.manage', 'name' => 'Administrar categorías', 'module' => 'assets'],
            ['code' => 'asset_statuses.view', 'name' => 'Consultar estados', 'module' => 'assets'],
            ['code' => 'locations.view', 'name' => 'Consultar ubicaciones', 'module' => 'assets'],
            ['code' => 'locations.manage', 'name' => 'Administrar ubicaciones', 'module' => 'assets'],
            ['code' => 'asset_documents.view', 'name' => 'Consultar documentos de activos', 'module' => 'assets'],
            ['code' => 'asset_documents.upload', 'name' => 'Cargar documentos de activos', 'module' => 'assets'],
            ['code' => 'movements.view', 'name' => 'Consultar movimientos', 'module' => 'movements'],
            ['code' => 'movements.request', 'name' => 'Solicitar movimientos', 'module' => 'movements'],
            ['code' => 'movements.approve', 'name' => 'Aprobar movimientos', 'module' => 'movements'],
            ['code' => 'movements.receive', 'name' => 'Recibir movimientos', 'module' => 'movements'],
            ['code' => 'custody.view', 'name' => 'Consultar custodias', 'module' => 'custody'],
            ['code' => 'custody.assign', 'name' => 'Asignar custodias', 'module' => 'custody'],
            ['code' => 'inventory.view', 'name' => 'Consultar inventarios', 'module' => 'inventory'],
            ['code' => 'inventory.create', 'name' => 'Crear campañas de inventario', 'module' => 'inventory'],
            ['code' => 'inventory.check', 'name' => 'Registrar verificaciones físicas', 'module' => 'inventory'],
            ['code' => 'inventory.manage', 'name' => 'Gestionar campañas de inventario', 'module' => 'inventory'],
            ['code' => 'disposals.view', 'name' => 'Consultar bajas', 'module' => 'disposals'],
            ['code' => 'disposals.request', 'name' => 'Solicitar bajas', 'module' => 'disposals'],
            ['code' => 'disposals.approve', 'name' => 'Aprobar bajas', 'module' => 'disposals'],
        ])->mapWithKeys(function (array $attributes): array {
            $permission = Permission::query()->updateOrCreate(
                ['code' => $attributes['code']],
                $attributes,
            );

            return [$permission->code => $permission];
        });

        $rolePermissions = [
            'administrador_tecnico' => $permissions->keys()->all(),
            'jefe_patrimonio' => [
                'dashboard.view',
                'users.view',
                'roles.view',
                'areas.view',
                'audit.view',
                'assets.view', 'assets.create', 'assets.update', 'asset_categories.view', 'asset_categories.manage', 'asset_statuses.view', 'locations.view', 'locations.manage', 'asset_documents.view', 'asset_documents.upload',
                'movements.view', 'movements.request', 'movements.approve', 'movements.receive', 'custody.view', 'custody.assign',
                'inventory.view', 'inventory.create', 'inventory.check', 'inventory.manage', 'disposals.view', 'disposals.request', 'disposals.approve',
            ],
            'asistente_patrimonio' => ['dashboard.view', 'areas.view', 'assets.view', 'assets.create', 'assets.update', 'asset_categories.view', 'asset_statuses.view', 'locations.view', 'asset_documents.view', 'asset_documents.upload', 'movements.view', 'movements.request', 'custody.view', 'inventory.view', 'inventory.create', 'inventory.check'],
            'logistica_almacen' => ['dashboard.view', 'areas.view', 'assets.view', 'assets.create', 'asset_categories.view', 'asset_statuses.view', 'locations.view', 'asset_documents.view', 'asset_documents.upload'],
            'informatica_mantenimiento' => ['dashboard.view', 'areas.view', 'assets.view', 'assets.update', 'asset_categories.view', 'asset_statuses.view', 'locations.view', 'asset_documents.view', 'asset_documents.upload'],
            'comision_inventario' => ['dashboard.view', 'areas.view', 'assets.view', 'asset_categories.view', 'asset_statuses.view', 'locations.view', 'asset_documents.view', 'inventory.view', 'inventory.create', 'inventory.check'],
            'responsable_area' => ['dashboard.view', 'areas.view', 'assets.view', 'asset_statuses.view', 'locations.view', 'asset_documents.view', 'movements.view', 'movements.request', 'movements.receive', 'custody.view', 'inventory.view', 'inventory.check'],
            'gerencia_oga' => ['dashboard.view', 'areas.view', 'audit.view', 'assets.view', 'asset_categories.view', 'asset_statuses.view', 'locations.view', 'asset_documents.view'],
            'oci_auditor' => ['dashboard.view', 'users.view', 'areas.view', 'roles.view', 'audit.view', 'audit.export', 'assets.view', 'asset_categories.view', 'asset_statuses.view', 'locations.view', 'asset_documents.view'],
        ];

        $roleNames = [
            'administrador_tecnico' => 'Administrador técnico',
            'jefe_patrimonio' => 'Jefe de Patrimonio',
            'asistente_patrimonio' => 'Asistente de Patrimonio',
            'logistica_almacen' => 'Logística / Almacén',
            'informatica_mantenimiento' => 'Informática / Mantenimiento',
            'comision_inventario' => 'Comisión de Inventario',
            'responsable_area' => 'Responsable de área',
            'gerencia_oga' => 'Gerencia / OGA',
            'oci_auditor' => 'OCI / Auditor',
        ];

        foreach ($rolePermissions as $code => $permissionCodes) {
            $role = Role::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $roleNames[$code],
                    'description' => 'Rol institucional administrado por el sistema.',
                    'is_system' => true,
                ],
            );

            $role->permissions()->sync(
                $permissions->only($permissionCodes)->pluck('id')->all(),
            );
        }

        $municipality = Area::query()->updateOrCreate(
            ['code' => 'MDM'],
            [
                'parent_id' => null,
                'name' => 'Municipalidad Distrital de Morales',
                'description' => 'Raíz organizacional configurable.',
                'is_active' => true,
            ],
        );

        foreach ([
            ['code' => 'PAT', 'name' => 'Patrimonio'],
            ['code' => 'LOG', 'name' => 'Logística y Almacén'],
            ['code' => 'TI', 'name' => 'Tecnologías de la Información'],
            ['code' => 'OGA', 'name' => 'Oficina General de Administración'],
            ['code' => 'OCI', 'name' => 'Órgano de Control Institucional'],
        ] as $area) {
            Area::query()->updateOrCreate(
                ['code' => $area['code']],
                [
                    'parent_id' => $municipality->getKey(),
                    'name' => $area['name'],
                    'description' => 'Área inicial pendiente de validación con el organigrama vigente.',
                    'is_active' => true,
                ],
            );
        }
    }
}
