<?php

namespace App\Models;

use Database\Factories\AssetDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['asset_id', 'document_type', 'file_name', 'disk', 'path', 'mime_type', 'size', 'sha256', 'uploaded_by', 'metadata'])]

class AssetDocument extends Model
{
    /** @use HasFactory<AssetDocumentFactory> */
    use HasFactory;

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
