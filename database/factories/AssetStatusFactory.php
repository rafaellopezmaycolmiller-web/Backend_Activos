<?php

namespace Database\Factories;

use App\Models\AssetStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetStatus>
 */
class AssetStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['code' => fake()->unique()->lexify('STATUS-???'), 'name' => fake()->words(2, true), 'description' => fake()->sentence(), 'is_active' => true];
    }
}
