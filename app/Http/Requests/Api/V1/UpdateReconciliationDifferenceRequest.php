<?php
namespace App\Http\Requests\Api\V1;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateReconciliationDifferenceRequest extends FormRequest {
    public function authorize(): bool { return $this->user()?->hasPermission('reconciliation.review') ?? false; }
    public function rules(): array { return ['status'=>['required',Rule::in(['pending','in_review','resolved','pending_external'])],'resolution_notes'=>['nullable','string','max:1000']]; }
}
