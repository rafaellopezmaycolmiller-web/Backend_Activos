<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['reconciliation_run_id','asset_id','asset_code','difference_type','system_snapshot','reference_snapshot','status','reviewed_by','reviewed_at','resolution_notes'])]
class ReconciliationDifference extends Model {
    public function run(): BelongsTo { return $this->belongsTo(ReconciliationRun::class, 'reconciliation_run_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    protected function casts(): array { return ['system_snapshot'=>'array','reference_snapshot'=>'array','reviewed_at'=>'datetime']; }
}
