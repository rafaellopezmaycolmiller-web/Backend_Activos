<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetDocument>
 */
class AssetDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['asset_id' => Asset::factory(), 'document_type' => 'other', 'file_name' => 'document.pdf', 'disk' => 'private', 'path' => 'assets/demo/document.pdf', 'mime_type' => 'application/pdf', 'size' => 100, 'sha256' => hash('sha256', fake()->uuid()), 'uploaded_by' => null];
    }
}
