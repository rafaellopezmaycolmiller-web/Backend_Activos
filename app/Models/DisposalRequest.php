<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['asset_id', 'requested_by', 'decided_by', 'reviewed_by', 'technical_evaluation_id', 'status', 'authority_status', 'reason', 'cause', 'documentation_notes', 'rejection_reason', 'decision_notes', 'requested_at', 'decided_at', 'reviewed_at'])]
class DisposalRequest extends Model
{
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function technicalEvaluation(): BelongsTo { return $this->belongsTo(TechnicalEvaluation::class); }
    public function documents(): HasMany { return $this->hasMany(DisposalRequestDocument::class); }

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'decided_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }
}
