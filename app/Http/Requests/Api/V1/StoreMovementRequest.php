<?php

namespace App\Http\Requests\Api\V1;

use App\Models\MovementRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MovementRequest::class) ?? false;
    }

    public function rules(): array
    {
        return ['asset_id' => ['required', 'integer', Rule::exists('assets', 'id')], 'to_area_id' => ['required', 'integer', Rule::exists('areas', 'id')->where('is_active', true)], 'to_location_id' => ['nullable', 'integer', Rule::exists('asset_locations', 'id')->where('is_active', true)], 'reason' => ['required', 'string', 'max:500']];
    }
}
