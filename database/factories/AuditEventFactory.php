<?php

namespace Database\Factories;

use App\AuditEventType;
use App\Models\Area;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuditEvent>
 */
class AuditEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'actor_id' => User::factory(),
            'area_id' => Area::factory(),
            'event' => AuditEventType::LoginSucceeded->value,
            'subject_type' => User::class,
            'subject_id' => '1',
            'request_id' => (string) Str::uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'metadata' => ['source' => 'factory'],
            'occurred_at' => now(),
        ];
    }
}
