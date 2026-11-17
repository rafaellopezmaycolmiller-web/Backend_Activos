<?php

namespace App\Http\Requests\Api\V1;

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
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('users')->ignore($targetUser),
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
