<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Asset;
use App\Models\MovementRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovementRequest>
 */
class MovementRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(), 'from_area_id' => Area::factory(), 'to_area_id' => Area::factory(),
            'requested_by' => User::factory(), 'status' => 'pending', 'reason' => fake()->sentence(), 'requested_at' => now(),
        ];
    }
}
