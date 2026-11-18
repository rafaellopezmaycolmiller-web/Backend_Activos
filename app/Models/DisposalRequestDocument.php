<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['disposal_request_id','uploaded_by','file_name','path','mime_type','size','sha256'])]
class DisposalRequestDocument extends Model {
    public function disposalRequest(): BelongsTo { return $this->belongsTo(DisposalRequest::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
