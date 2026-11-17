<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'area_id' => Area::factory(),
            'name' => fake()->name(),
            'document_number' => fake()->unique()->numerify('########'),
            'job_title' => fake()->jobTitle(),
            'phone' => fake()->numerify('9########'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password123!'),
            'is_active' => true,
            'mfa_required' => false,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function mfaRequired(): static
    {
        return $this->state(fn (): array => ['mfa_required' => true]);
    }

    public function locked(): static
    {
        return $this->state(fn (): array => [
            'failed_login_attempts' => 5,
            'locked_until' => now()->addMinutes(15),
        ]);
    }
}
