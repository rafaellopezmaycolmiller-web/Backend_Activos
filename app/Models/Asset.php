<?php

namespace App\Models;

use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['asset_code', 'inventory_code', 'category_id', 'status_id', 'area_id', 'location_id', 'name', 'description', 'specifications', 'brand', 'model', 'serial_number', 'acquisition_date', 'acquisition_value', 'useful_life_years', 'current_version', 'created_by', 'updated_by', 'is_active'])]

class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AssetStatus::class, 'status_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(AssetLocation::class, 'location_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssetDocument::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AssetVersion::class)->latest();
    }

    public function movementRequests(): HasMany
    {
        return $this->hasMany(MovementRequest::class);
    }

    public function custodianships(): HasMany
    {
        return $this->hasMany(Custodianship::class);
    }

    public function technicalEvaluations(): HasMany
    {
        return $this->hasMany(TechnicalEvaluation::class);
    }

    public function disposalRequests(): HasMany
    {
        return $this->hasMany(DisposalRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function casts(): array
    {
        return ['acquisition_date' => 'date', 'acquisition_value' => 'decimal:2', 'specifications' => 'array', 'is_active' => 'boolean'];
    }
}
