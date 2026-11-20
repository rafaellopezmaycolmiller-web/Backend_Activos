<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccessRecoveryResolveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && ($user->hasGlobalPermission('access_recovery.manage') || $user->roles()->whereIn('code', ['administrador_tecnico', 'asistente_ti'])->exists());
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'resolution_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
