<?php

namespace App\Http\Requests\Api\V1;

use App\Models\TechnicalEvaluation;
use Illuminate\Foundation\Http\FormRequest;

class StoreTechnicalEvaluationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('evaluations.create') ?? false; }
    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'reason' => ['required', 'string', 'max:1000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'observations' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
