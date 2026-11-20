<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Database\Seeders\DemoScenarioSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoScenarioSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reseeding_restores_access_to_a_locked_demo_assistant(): void
    {
        $password = 'TestingDemoPassword-2026!';
        $previousPassword = env('DEMO_ASSISTANT_PASSWORD');
        putenv("DEMO_ASSISTANT_PASSWORD={$password}");

        try {
            $this->seed();

            $user = User::query()->where('email', 'ana.rivera@moralesmuni.gob.pe')->firstOrFail();
            $user->forceFill([
                'password' => 'DifferentPassword-2026!',
                'is_active' => false,
                'failed_login_attempts' => 5,
                'locked_until' => now()->addMinutes(15),
            ])->save();

            $this->seed(DemoScenarioSeeder::class);

            $user->refresh();
            $this->assertTrue(Hash::check($password, $user->password));
            $this->assertTrue($user->is_active);
            $this->assertSame(0, $user->failed_login_attempts);
            $this->assertNull($user->locked_until);
        } finally {
            putenv($previousPassword === null
                ? 'DEMO_ASSISTANT_PASSWORD'
                : "DEMO_ASSISTANT_PASSWORD={$previousPassword}");
        }
    }
}
