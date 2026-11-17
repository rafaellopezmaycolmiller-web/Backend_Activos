<?php

namespace App\Models;

use Database\Factories\LoginChallengeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'code_hash',
    'attempts_left',
    'expires_at',
    'consumed_at',
    'remember',
    'ip_address',
    'user_agent',
])]
#[Hidden(['code_hash'])]
class LoginChallenge extends Model
{
    /** @use HasFactory<LoginChallengeFactory> */
    use HasFactory;

    use HasUuids;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAvailable(): bool
    {
        return $this->consumed_at === null
            && $this->attempts_left > 0
            && $this->expires_at->isFuture();
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'remember' => 'boolean',
        ];
    }
}
