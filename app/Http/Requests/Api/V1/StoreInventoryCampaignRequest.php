<?php

namespace App\Http\Requests\Api\V1;

use App\Models\InventoryCampaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', InventoryCampaign::class) ?? false;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:40', 'alpha_dash:ascii', Rule::unique('inventory_campaigns')], 'name' => ['required', 'string', 'max:180'], 'area_id' => ['required', 'integer', Rule::exists('areas', 'id')->where('is_active', true)], 'notes' => ['nullable', 'string', 'max:1000']];
    }
}
