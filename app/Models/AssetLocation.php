<?php

namespace App\Models;

use Database\Factories\AssetLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['area_id', 'code', 'name', 'description', 'is_active'])]

class AssetLocation extends Model
{
    /** @use HasFactory<AssetLocationFactory> */
    use HasFactory;

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'location_id');
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
