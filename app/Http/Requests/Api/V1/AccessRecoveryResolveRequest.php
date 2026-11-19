<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccessRecoveryResolveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasGlobalPermission('access_recovery.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'resolution_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
