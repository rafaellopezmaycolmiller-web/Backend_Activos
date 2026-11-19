<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            IdentityAccessSeeder::class,
            AssetCatalogSeeder::class,
            InitialAccessSeeder::class,
        ]);

        // Solo se carga con DEMO_SCENARIO_ENABLED=true en un entorno de demostración.
        if ((bool) env('DEMO_SCENARIO_ENABLED', false)) {
            $this->call(DemoScenarioSeeder::class);
        }
    }
}
