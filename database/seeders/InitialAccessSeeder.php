<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use App\RoleScope;
use Illuminate\Database\Seeder;
use RuntimeException;

class InitialAccessSeeder extends Seeder
{
    public function run(): void
    {
        $temporaryPassword = config('initial_access.password');

        if (! is_string($temporaryPassword) || $temporaryPassword === '') {
            throw new RuntimeException('Falta configurar la contraseña temporal de los accesos iniciales.');
        }

        $accounts = [
            [
                'name' => 'Administrador del Sistema',
                'job_title' => 'Administrador técnico',
                'email' => 'administrador@moralesmuni.gob.pe',
                'area' => 'TI',
                'role' => 'administrador_tecnico',
                'phone' => '+51906239450',
                'mfa_required' => false,
            ],
            [
                'name' => 'Responsable de Patrimonio',
                'job_title' => 'Responsable de control patrimonial',
                'email' => 'patrimonio@moralesmuni.gob.pe',
                'area' => 'PAT',
                'role' => 'jefe_patrimonio',
                'phone' => null,
                'mfa_required' => false,
            ],
            [
                'name' => 'Auditoría OCI',
                'job_title' => 'Auditor OCI',
                'email' => 'oci@moralesmuni.gob.pe',
                'area' => 'OCI',
                'role' => 'oci_auditor',
                'phone' => null,
                'mfa_required' => false,
            ],
        ];

        foreach ($accounts as $account) {
            $area = Area::query()->where('code', $account['area'])->firstOrFail();
            $role = Role::query()->where('code', $account['role'])->firstOrFail();

            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'area_id' => $area->getKey(),
                    'name' => $account['name'],
                    'document_number' => null,
                    'job_title' => $account['job_title'],
                    'phone' => $account['phone'],
                    'password' => $temporaryPassword,
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'mfa_required' => $account['mfa_required'],
                ],
            );

            $user->roles()->sync([
                $role->getKey() => ['scope' => RoleScope::Global->value],
            ]);
        }
    }
}
