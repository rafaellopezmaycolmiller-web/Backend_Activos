<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use App\RoleScope;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $accounts = [
            [
                'name' => 'Administrador de Desarrollo',
                'document_number' => '00000001',
                'job_title' => 'Administrador técnico',
                'email' => 'admin@ccm.test',
                'area' => 'TI',
                'role' => 'administrador_tecnico',
                'scope' => RoleScope::Global,
                'mfa_required' => true,
                'password' => config('demo_users.admin_password'),
            ],
            [
                'name' => 'Jefatura de Patrimonio',
                'document_number' => '00000002',
                'job_title' => 'Jefe de Patrimonio',
                'email' => 'patrimonio@ccm.test',
                'area' => 'PAT',
                'role' => 'jefe_patrimonio',
                'scope' => RoleScope::OwnArea,
                'mfa_required' => true,
                'password' => config('demo_users.patrimony_password'),
            ],
            [
                'name' => 'Auditor de Demostración',
                'document_number' => '00000003',
                'job_title' => 'Auditor OCI',
                'email' => 'auditor@ccm.test',
                'area' => 'OCI',
                'role' => 'oci_auditor',
                'scope' => RoleScope::Global,
                'mfa_required' => false,
                'password' => config('demo_users.auditor_password'),
            ],
        ];

        foreach ($accounts as $account) {
            if (! is_string($account['password']) || $account['password'] === '') {
                throw new RuntimeException('Falta configurar una contraseña para los usuarios demostrativos.');
            }

            $area = Area::query()->where('code', $account['area'])->firstOrFail();
            $role = Role::query()->where('code', $account['role'])->firstOrFail();

            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'area_id' => $area->getKey(),
                    'name' => $account['name'],
                    'document_number' => $account['document_number'],
                    'job_title' => $account['job_title'],
                    'phone' => null,
                    'password' => $account['password'],
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'mfa_required' => $account['mfa_required'],
                ],
            );

            $user->roles()->sync([
                $role->getKey() => ['scope' => $account['scope']->value],
            ]);
        }
    }
}
