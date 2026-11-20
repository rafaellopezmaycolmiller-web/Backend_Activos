<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Asset::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'asset_code' => ['required', 'string', 'max:40', 'alpha_dash:ascii', Rule::unique('assets')],
            'inventory_code' => ['nullable', 'string', 'max:40', Rule::unique('assets')],
            'category_id' => ['required', 'integer', Rule::exists('asset_categories', 'id')->where('is_active', true)->whereIn('code', AssetCategory::PROJECT_SCOPE_CODES)],
            'asset_type' => ['required', 'string', 'max:80'],
            'specifications' => ['nullable', 'array'], 'specifications.*' => ['nullable', 'string', 'max:255'],
            'status_id' => ['required', 'integer', Rule::exists('asset_statuses', 'id')->where('is_active', true)],
            'area_id' => ['required', 'integer', Rule::exists('areas', 'id')->where('is_active', true)],
            'location_id' => ['nullable', 'integer', Rule::exists('asset_locations', 'id')->where('is_active', true)],
            'responsible_name' => ['nullable', 'string', 'max:180'], 'responsible_document' => ['nullable', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string', 'max:5000'],
            'brand' => ['nullable', 'string', 'max:100'], 'model' => ['nullable', 'string', 'max:120'],
            'serial_number' => ['nullable', 'string', 'max:120'], 'acquisition_date' => ['nullable', 'date'],
            'acquisition_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'useful_life_years' => ['nullable', 'integer', 'min:1', 'max:100'], 'is_active' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
