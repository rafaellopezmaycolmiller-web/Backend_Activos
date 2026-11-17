<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['asset_code' => fake()->unique()->bothify('MDM-###-????'), 'inventory_code' => fake()->unique()->bothify('INV-#####'), 'category_id' => AssetCategory::factory(), 'status_id' => AssetStatus::factory(), 'area_id' => Area::factory(), 'name' => fake()->words(3, true), 'description' => fake()->sentence(), 'brand' => fake()->company(), 'model' => fake()->bothify('MODEL-##'), 'serial_number' => fake()->unique()->bothify('SN-########'), 'is_active' => true, 'current_version' => 1];
    }
}
