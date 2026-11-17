<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetVersion>
 */
class AssetVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['asset_id' => Asset::factory(), 'version' => 1, 'action' => 'created', 'snapshot' => [], 'changed_by' => null, 'reason' => null];
    }
}
