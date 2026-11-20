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

        // En local se carga automáticamente para que la demo sea reproducible.
        // En producción solo se activa mediante DEMO_SCENARIO_ENABLED=true.
        if (app()->environment('local') || (bool) env('DEMO_SCENARIO_ENABLED', true)) {
            $this->call(DemoScenarioSeeder::class);
            $this->call(ProcessDemoSeeder::class);
        }
    }
}
