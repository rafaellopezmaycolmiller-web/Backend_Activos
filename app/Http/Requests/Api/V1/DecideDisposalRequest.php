<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class DecideDisposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['decision' => ['required', 'in:approved,rejected'], 'rejection_reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:1000']];
    }
}
