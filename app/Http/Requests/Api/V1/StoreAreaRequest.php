<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Area;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Area::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['required', 'integer', Rule::exists('areas', 'id')],
            'code' => ['required', 'string', 'max:30', 'alpha_dash:ascii', Rule::unique('areas')],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Str::upper($this->string('code')->trim()->toString()),
        ]);
    }
}
