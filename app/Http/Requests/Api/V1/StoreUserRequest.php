<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Role;
use App\Models\User;
use App\RoleScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'area_id' => [
                'required',
                'integer',
                Rule::exists('areas', 'id')->where('is_active', true),
            ],
            'name' => ['required', 'string', 'max:160'],
            'document_number' => ['required', 'string', 'max:20', Rule::unique('users')],
            'job_title' => ['required', 'string', 'max:120'],
            'phone' => [
                Rule::requiredIf(fn (): bool => $this->assignsTechnicalAdministratorRole()),
                'nullable',
                'string',
                'regex:/^\+519\d{8}$/',
            ],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users')],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->numbers()->symbols(),
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
