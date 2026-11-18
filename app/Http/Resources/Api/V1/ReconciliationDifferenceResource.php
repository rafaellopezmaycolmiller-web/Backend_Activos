<?php
namespace App\Http\Resources\Api\V1;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class ReconciliationDifferenceResource extends JsonResource { public function toArray(Request $request): array { return ['id'=>$this->id,'reconciliation_run_id'=>$this->reconciliation_run_id,'asset_id'=>$this->asset_id,'asset_code'=>$this->asset_code,'difference_type'=>$this->difference_type,'system_snapshot'=>$this->system_snapshot,'reference_snapshot'=>$this->reference_snapshot,'status'=>$this->status,'reviewed_by'=>$this->reviewed_by,'reviewed_at'=>$this->reviewed_at,'resolution_notes'=>$this->resolution_notes,'asset'=>$this->whenLoaded('asset')]; } }
