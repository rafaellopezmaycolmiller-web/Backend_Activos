<?php
namespace App\Http\Resources\Api\V1;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class ReconciliationRunResource extends JsonResource {
    public function toArray(Request $request): array { $diffs=$this->whenLoaded('differences'); return ['id'=>$this->id,'code'=>$this->code,'name'=>$this->name,'area_id'=>$this->area_id,'area'=>$this->whenLoaded('area'),'source_file_name'=>$this->source_file_name,'created_by'=>$this->created_by,'status'=>$this->status,'executed_at'=>$this->executed_at,'notes'=>$this->notes,'differences'=>$diffs,'total_differences'=>$this->when(isset($this->total_differences),$this->total_differences),'pending_differences'=>$this->when(isset($this->pending_differences),$this->pending_differences),'resolved_differences'=>$this->when(isset($this->resolved_differences),$this->resolved_differences)]; }
}
