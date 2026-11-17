<?php

namespace App\Http\Requests\Api\V1;

use App\Models\DisposalRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreDisposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DisposalRequest::class) ?? false;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:1000']];
    }
}
