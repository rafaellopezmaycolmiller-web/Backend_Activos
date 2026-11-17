<?php

namespace App\Http\Requests\Api\V1;

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
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users')],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->numbers()->symbols(),
            ],
            'is_active' => ['required', 'boolean'],
            'mfa_required' => ['required', 'boolean'],
            'role_assignments' => ['required', 'array', 'min:1'],
            'role_assignments.*.role_id' => ['required', 'integer', 'distinct', Rule::exists('roles', 'id')],
            'role_assignments.*.scope' => ['required', new Enum(RoleScope::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower($this->string('email')->trim()->toString()),
        ]);
    }
}
