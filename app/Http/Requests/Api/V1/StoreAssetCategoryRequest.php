<?php

namespace App\Http\Requests\Api\V1;

use App\Models\AssetCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasGlobalPermission('asset_categories.manage') ?? false;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:30', 'alpha_dash:ascii', Rule::in(AssetCategory::PROJECT_SCOPE_CODES), Rule::unique('asset_categories')], 'name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:500'], 'is_active' => ['required', 'boolean']];
    }
}
