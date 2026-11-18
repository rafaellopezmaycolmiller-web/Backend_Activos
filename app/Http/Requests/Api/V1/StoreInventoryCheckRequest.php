<?php

namespace App\Http\Requests\Api\V1;

use App\Models\InventoryCheck;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', InventoryCheck::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', Rule::exists('assets', 'id')->where('is_active', true)],
            'result' => ['required', Rule::in(['found', 'not_found', 'location_mismatch'])],
            'observed_area_id' => ['nullable', 'integer', Rule::exists('areas', 'id')->where('is_active', true)],
            'observed_location_id' => ['nullable', 'integer', Rule::exists('asset_locations', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
