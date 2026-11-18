<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ReviewDisposalRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('disposals.review') ?? false; }
    public function rules(): array { return ['decision_notes' => ['required', 'string', 'max:5000'], 'decision' => ['required', 'in:pending_authority,rejected']]; }
}
