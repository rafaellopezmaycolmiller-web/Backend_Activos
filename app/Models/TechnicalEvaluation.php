<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['asset_id', 'requested_by', 'assigned_to', 'status', 'reason', 'physical_condition', 'diagnosis', 'recommendations', 'observations', 'requested_at', 'assigned_at', 'evaluated_at', 'reviewed_at'])]
class TechnicalEvaluation extends Model
{
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function specialist(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function documents(): HasMany { return $this->hasMany(TechnicalEvaluationDocument::class); }
    public function disposals(): HasMany { return $this->hasMany(DisposalRequest::class); }
    protected function casts(): array { return ['requested_at' => 'datetime', 'assigned_at' => 'datetime', 'evaluated_at' => 'datetime', 'reviewed_at' => 'datetime']; }
}
