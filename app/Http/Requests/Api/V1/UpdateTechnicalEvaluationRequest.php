<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTechnicalEvaluationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('evaluations.update') ?? false; }
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:assigned,evaluated,reviewed'],
            'physical_condition' => ['nullable', 'string', 'max:120'],
            'diagnosis' => ['required_if:status,evaluated,reviewed', 'nullable', 'string', 'max:10000'],
            'recommendations' => ['nullable', 'string', 'max:10000'],
            'observations' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
