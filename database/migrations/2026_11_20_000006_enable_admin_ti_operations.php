<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::query()->where('code', 'administrador_tecnico')->first();
        if (! $role) return;
        $permissions = Permission::query()->whereIn('code', [
            'movements.view', 'movements.request', 'movements.approve', 'movements.receive',
            'custody.view', 'custody.assign', 'inventory.view', 'inventory.create', 'inventory.check', 'inventory.manage',
            'reconciliation.view', 'reconciliation.create', 'reconciliation.review',
            'disposals.view', 'disposals.request', 'disposals.approve', 'disposals.review',
            'evaluations.view', 'evaluations.create', 'evaluations.assign', 'evaluations.update',
        ])->pluck('id');
        $role->permissions()->syncWithoutDetaching($permissions);
    }

    public function down(): void
    {
        // Los permisos del administrador técnico se mantienen como parte del rol del sistema.
    }
};
