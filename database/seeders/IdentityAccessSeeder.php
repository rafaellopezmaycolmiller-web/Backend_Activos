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
            ['code' => 'audit.manage', 'name' => 'Corregir eventos de auditoría', 'module' => 'audit'],
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
            'administrador_tecnico' => [
                'dashboard.view',
                'users.view', 'users.create', 'users.update', 'roles.view', 'roles.assign',
                'areas.view', 'areas.manage', 'audit.view', 'audit.export', 'audit.manage',
                'assets.view', 'assets.create', 'assets.update', 'asset_categories.view', 'asset_categories.manage', 'asset_statuses.view', 'locations.view', 'locations.manage', 'asset_documents.view', 'asset_documents.upload',
                'movements.view', 'custody.view', 'inventory.view', 'disposals.view',
            ],
            'jefe_patrimonio' => [
                'dashboard.view',
                'areas.view',
                'audit.view',
                'assets.view', 'assets.create', 'assets.update', 'asset_categories.view', 'asset_categories.manage', 'asset_statuses.view', 'locations.view', 'locations.manage', 'asset_documents.view', 'asset_documents.upload',
                'movements.view', 'movements.request', 'movements.approve', 'movements.receive', 'custody.view', 'custody.assign',
                'inventory.view', 'inventory.create', 'inventory.check', 'inventory.manage', 'disposals.view', 'disposals.request', 'disposals.approve',
            ],
            'asistente_patrimonio' => ['dashboard.view', 'areas.view', 'assets.view', 'assets.create', 'assets.update', 'asset_categories.view', 'asset_statuses.view', 'locations.view', 'asset_documents.view', 'asset_documents.upload', 'movements.view', 'movements.request', 'custody.view', 'inventory.view', 'inventory.create', 'inventory.check'],
            'asistente_ti' => ['dashboard.view', 'areas.view', 'users.view', 'assets.view', 'assets.create', 'assets.update', 'asset_categories.view', 'asset_statuses.view', 'locations.view', 'asset_documents.view', 'asset_documents.upload', 'audit.view'],
            'asistente_oci' => ['dashboard.view', 'areas.view', 'audit.view', 'audit.export', 'assets.view', 'asset_categories.view', 'asset_statuses.view', 'locations.view', 'asset_documents.view', 'movements.view', 'custody.view', 'inventory.view', 'disposals.view'],
            'oci_auditor' => [
                'dashboard.view', 'users.view', 'areas.view', 'roles.view', 'audit.view', 'audit.export',
                'assets.view', 'asset_categories.view', 'asset_statuses.view', 'locations.view', 'asset_documents.view',
                'movements.view', 'custody.view', 'inventory.view', 'disposals.view',
            ],
        ];

        $roleNames = [
            'administrador_tecnico' => 'Administrador de TI',
            'jefe_patrimonio' => 'Jefe de Patrimonio',
            'asistente_patrimonio' => 'Asistente de Patrimonio',
            'asistente_ti' => 'Asistente de TI',
            'asistente_oci' => 'Asistente de OCI',
            'oci_auditor' => 'OCI / Auditor',
        ];

        $roleDescriptions = [
            'administrador_tecnico' => 'Administra cuentas, accesos y configuración; puede apoyar el registro de activos sin aprobar procesos patrimoniales.',
            'jefe_patrimonio' => 'Gestiona el ciclo patrimonial y autoriza movimientos, inventarios y bajas.',
            'asistente_patrimonio' => 'Registra y actualiza activos, documentos, movimientos e inventarios bajo supervisión.',
            'asistente_ti' => 'Apoya la operación técnica y el registro de activos desde TI, sin administrar cuentas.',
            'asistente_oci' => 'Consulta información y trazabilidad institucional desde OCI, sin modificar registros.',
            'oci_auditor' => 'Consulta información y trazabilidad institucional sin modificar registros.',
        ];

        foreach ($rolePermissions as $code => $permissionCodes) {
            $role = Role::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $roleNames[$code],
                    'description' => $roleDescriptions[$code],
                    'is_system' => true,
                ],
            );

            $role->permissions()->sync(
                $permissions->only($permissionCodes)->pluck('id')->all(),
            );
        }

        Role::query()
            ->where('is_system', true)
            ->whereNotIn('code', array_keys($rolePermissions))
            ->whereDoesntHave('users')
            ->each(fn (Role $role) => $role->delete());

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
            ['code' => 'PAT', 'name' => 'Patrimonio', 'description' => 'Registra, actualiza y controla los bienes patrimoniales de la municipalidad.'],
            ['code' => 'LOG', 'name' => 'Logística y Almacén', 'description' => 'Coordina la recepción, almacenamiento y entrega de equipos y mobiliario.'],
            ['code' => 'TI', 'name' => 'Tecnologías de la Información', 'description' => 'Administra los accesos al sistema y brinda soporte a las áreas usuarias.'],
            ['code' => 'OGA', 'name' => 'Oficina General de Administración', 'description' => 'Supervisa la gestión administrativa relacionada con los bienes municipales.'],
            ['code' => 'OCI', 'name' => 'Órgano de Control Institucional', 'description' => 'Consulta la trazabilidad y verifica el cumplimiento de los procesos internos.'],
        ] as $area) {
            Area::query()->updateOrCreate(
                ['code' => $area['code']],
                [
                    'parent_id' => $municipality->getKey(),
                    'name' => $area['name'],
                    'description' => $area['description'],
                    'is_active' => true,
                ],
            );
        }
    }
}
