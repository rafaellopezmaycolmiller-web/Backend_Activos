<?php

namespace App\Models;

use Database\Factories\AssetStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'description', 'is_active'])]

class AssetStatus extends Model
{
    /** @use HasFactory<AssetStatusFactory> */
    use HasFactory;

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'status_id');
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
