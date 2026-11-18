<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
#[Fillable(['code','name','area_id','inventory_campaign_id','source_file_name','source_path','created_by','status','executed_at','notes'])]
class ReconciliationRun extends Model {
    public function differences(): HasMany { return $this->hasMany(ReconciliationDifference::class); }
    public function area(): BelongsTo { return $this->belongsTo(Area::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    protected function casts(): array { return ['executed_at'=>'datetime']; }
}
