<?php

namespace App\Models;

use Database\Factories\AssetCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'description', 'is_active'])]

class AssetCategory extends Model
{
    /**
     * Categories allowed by the approved project scope.
     *
     * @var array<int, string>
     */
    public const PROJECT_SCOPE_CODES = ['TEC', 'MOB'];

    /** @use HasFactory<AssetCategoryFactory> */
    use HasFactory;

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'category_id');
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
