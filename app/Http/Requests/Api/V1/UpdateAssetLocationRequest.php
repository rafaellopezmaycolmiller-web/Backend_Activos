<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasGlobalPermission('locations.manage') ?? false;
    }

    public function rules(): array
    {
        return ['area_id' => ['required', 'integer', Rule::exists('areas', 'id')->where('is_active', true)], 'code' => ['required', 'string', 'max:30', 'alpha_dash:ascii'], 'name' => ['required', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:500'], 'is_active' => ['required', 'boolean']];
    }
}
