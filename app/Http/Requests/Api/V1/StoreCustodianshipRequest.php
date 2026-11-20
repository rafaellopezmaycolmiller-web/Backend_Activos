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
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'responsible_name' => ['nullable', 'string', 'max:180'],
            'responsible_document' => ['nullable', 'string', 'max:30'],
            'area_id' => ['nullable', 'integer', Rule::exists('areas', 'id')->where('is_active', true)],
            'location_id' => ['nullable', 'integer', Rule::exists('asset_locations', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
