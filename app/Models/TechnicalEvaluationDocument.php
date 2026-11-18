<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['technical_evaluation_id', 'uploaded_by', 'file_name', 'path', 'mime_type', 'size', 'sha256'])]
class TechnicalEvaluationDocument extends Model
{
    public function evaluation(): BelongsTo { return $this->belongsTo(TechnicalEvaluation::class, 'technical_evaluation_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
