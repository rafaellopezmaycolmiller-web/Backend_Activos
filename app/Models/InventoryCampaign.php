<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'area_id', 'created_by', 'status', 'started_at', 'closed_at', 'notes'])]
class InventoryCampaign extends Model
{
    use HasFactory;

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(InventoryCheck::class, 'campaign_id');
    }

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'closed_at' => 'datetime'];
    }
}
