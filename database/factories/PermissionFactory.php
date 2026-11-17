<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    public function definition(): array
    {
        $module = fake()->randomElement(['users', 'areas', 'roles', 'audit']);
        $action = fake()->unique()->lexify('action_????');

        return [
            'code' => $module.'.'.$action,
            'name' => fake()->sentence(3),
            'module' => $module,
            'description' => fake()->sentence(),
        ];
    }
}
