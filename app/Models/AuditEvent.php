<?php

namespace App\Models;

use Database\Factories\AuditEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'actor_id',
    'area_id',
    'event',
    'subject_type',
    'subject_id',
    'request_id',
    'ip_address',
    'user_agent',
    'metadata',
    'occurred_at',
])]
class AuditEvent extends Model
{
    /** @use HasFactory<AuditEventFactory> */
    use HasFactory;

    public $timestamps = false;

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Los eventos de auditoría son inmutables.');
        });

        static::deleting(function (): never {
            throw new LogicException('Los eventos de auditoría no pueden eliminarse.');
        });
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
