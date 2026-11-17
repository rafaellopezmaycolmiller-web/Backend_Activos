<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\AssetLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetLocation>
 */
class AssetLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['area_id' => Area::factory(), 'code' => fake()->unique()->lexify('LOC-???'), 'name' => fake()->words(2, true), 'description' => fake()->sentence(), 'is_active' => true];
    }
}
