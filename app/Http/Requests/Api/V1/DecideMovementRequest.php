<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class DecideMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['rejection_reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:500'], 'decision' => ['required', 'in:approved,rejected']];
    }
}
