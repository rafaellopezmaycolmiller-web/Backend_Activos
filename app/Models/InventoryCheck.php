<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['campaign_id', 'asset_id', 'checked_by', 'result', 'observed_area_id', 'observed_location_id', 'checked_at', 'notes'])]
class InventoryCheck extends Model
{
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(InventoryCampaign::class, 'campaign_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function observedArea(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'observed_area_id');
    }

    public function observedLocation(): BelongsTo
    {
        return $this->belongsTo(AssetLocation::class, 'observed_location_id');
    }

    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }
}
