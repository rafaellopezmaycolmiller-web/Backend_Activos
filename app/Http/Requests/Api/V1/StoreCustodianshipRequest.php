<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Custodianship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustodianshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Custodianship::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
