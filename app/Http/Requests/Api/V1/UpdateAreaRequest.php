<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Area;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $area = $this->route('area');

        return $area instanceof Area
            && ($this->user()?->can('update', $area) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Area $area */
        $area = $this->route('area');

        return [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('areas', 'id'),
                Rule::notIn([$area->getKey()]),
            ],
            'code' => [
                'required',
                'string',
                'max:30',
                'alpha_dash:ascii',
                Rule::unique('areas')->ignore($area),
            ],
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
