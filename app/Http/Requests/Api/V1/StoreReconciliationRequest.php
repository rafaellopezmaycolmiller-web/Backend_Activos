<?php
namespace App\Http\Requests\Api\V1;
use App\Models\ReconciliationRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreReconciliationRequest extends FormRequest {
    public function authorize(): bool { return $this->user()?->can('create', ReconciliationRun::class) ?? false; }
    public function rules(): array { return ['name'=>['required','string','max:180'],'code'=>['required','string','max:50','alpha_dash:ascii',Rule::unique('reconciliation_runs')],'area_id'=>['nullable','integer',Rule::exists('areas','id')->where('is_active',true)],'inventory_campaign_id'=>['nullable','integer',Rule::exists('inventory_campaigns','id')],'file'=>['required','file','mimes:csv,txt','max:5120'],'notes'=>['nullable','string','max:1000']]; }
}
