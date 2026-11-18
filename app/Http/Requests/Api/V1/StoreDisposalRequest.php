<?php

namespace App\Http\Requests\Api\V1;

use App\Models\DisposalRequest as Disposal;
use Illuminate\Foundation\Http\FormRequest;

class StoreDisposalRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('create', Disposal::class) ?? false; }
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
            'cause' => ['required', 'string', 'max:120'],
            'technical_evaluation_id' => ['nullable', 'integer', 'exists:technical_evaluations,id'],
            'documentation_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
