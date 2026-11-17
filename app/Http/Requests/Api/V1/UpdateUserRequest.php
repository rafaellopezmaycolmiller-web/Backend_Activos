<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Role;
use App\Models\User;
use App\RoleScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $targetUser = $this->route('user');

        return $targetUser instanceof User
            && ($this->user()?->can('update', $targetUser) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $targetUser */
        $targetUser = $this->route('user');

        return [
            'area_id' => [
                'required',
                'integer',
                Rule::exists('areas', 'id')->where('is_active', true),
            ],
            'name' => ['required', 'string', 'max:160'],
            'document_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users')->ignore($targetUser),
            ],
            'job_title' => ['required', 'string', 'max:120'],
            'phone' => [
                Rule::requiredIf(fn (): bool => $this->assignsTechnicalAdministratorRole()),
                'nullable',
                'string',
                'regex:/^\+519\d{8}$/',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('users')->ignore($targetUser),
            ],
            'is_active' => ['required', 'boolean'],
            'role_assignments' => ['required', 'array', 'min:1'],
            'role_assignments.*.role_id' => ['required', 'integer', 'distinct', Rule::exists('roles', 'id')],
            'role_assignments.*.scope' => ['required', new Enum(RoleScope::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower($this->string('email')->trim()->toString()),
            'phone' => $this->normalizedPhone(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'El teléfono celular es obligatorio para el Administrador de TI.',
            'phone.regex' => 'Ingrese un celular peruano válido, por ejemplo +51906239450.',
        ];
    }

    private function assignsTechnicalAdministratorRole(): bool
    {
        $roleIds = collect($this->input('role_assignments', []))->pluck('role_id')->filter();

        return Role::query()
            ->whereKey($roleIds)
            ->where('code', 'administrador_tecnico')
            ->exists();
    }

    private function normalizedPhone(): ?string
    {
        $phone = preg_replace('/[\s()\-]+/', '', $this->string('phone')->trim()->toString()) ?? '';

        if (preg_match('/^9\d{8}$/', $phone) === 1) {
            return '+51'.$phone;
        }

        if (preg_match('/^519\d{8}$/', $phone) === 1) {
            return '+'.$phone;
        }

        return $phone === '' ? null : $phone;
    }
}
