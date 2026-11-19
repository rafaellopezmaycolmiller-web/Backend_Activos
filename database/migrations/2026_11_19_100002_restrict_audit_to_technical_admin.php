<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('code', ['audit.view', 'audit.export', 'audit.manage'])->pluck('id');
        $adminRoleId = DB::table('roles')->where('code', 'administrador_tecnico')->value('id');

        if ($permissionIds->isNotEmpty() && $adminRoleId !== null) {
            DB::table('permission_role')
                ->whereIn('permission_id', $permissionIds)
                ->where('role_id', '!=', $adminRoleId)
                ->delete();
        }
    }

    public function down(): void
    {
        // Permission restoration is intentionally left to IdentityAccessSeeder.
    }
};
