<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Custodianship;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Custodianship>
 */
class CustodianshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(), 'user_id' => User::factory(), 'assigned_by' => User::factory(),
            'started_at' => now(), 'status' => 'active', 'notes' => fake()->optional()->sentence(),
        ];
    }
}
