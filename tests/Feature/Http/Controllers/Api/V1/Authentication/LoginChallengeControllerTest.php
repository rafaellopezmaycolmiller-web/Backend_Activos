<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Authentication;

use App\AuditEventType;
use App\Models\LoginChallenge;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesAuthorizedUsers;
use Tests\TestCase;

class LoginChallengeControllerTest extends TestCase
{
    use CreatesAuthorizedUsers;
    use LazilyRefreshDatabase;

    public function test_valid_code_consumes_challenge_and_authenticates_user(): void
    {
        $user = User::factory()->mfaRequired()->create();
        $challenge = LoginChallenge::factory()->for($user)->create([
            'code_hash' => Hash::make('482913'),
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/mfa', [
                'challenge_id' => $challenge->getKey(),
                'code' => '482913',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $user->getKey());
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($challenge->fresh()->consumed_at);
        $this->assertDatabaseHas('audit_events', [
            'event' => AuditEventType::LoginSucceeded->value,
            'actor_id' => $user->getKey(),
        ]);
    }

    public function test_invalid_code_returns_422_and_reduces_attempts(): void
    {
        $user = User::factory()->mfaRequired()->create();
        $challenge = LoginChallenge::factory()->for($user)->create([
            'code_hash' => Hash::make('482913'),
            'attempts_left' => 5,
            'ip_address' => '127.0.0.1',
        ]);

        $this
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/mfa', [
                'challenge_id' => $challenge->getKey(),
                'code' => '000000',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);

        $this->assertGuest();
        $this->assertSame(4, $challenge->fresh()->attempts_left);
        $this->assertDatabaseHas('audit_events', [
            'event' => AuditEventType::MfaFailed->value,
            'subject_id' => (string) $user->getKey(),
        ]);
    }

    public function test_expired_code_returns_422_without_authenticating(): void
    {
        $challenge = LoginChallenge::factory()->expired()->create([
            'ip_address' => '127.0.0.1',
        ]);

        $this
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/mfa', [
                'challenge_id' => $challenge->getKey(),
                'code' => '123456',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);

        $this->assertGuest();
    }
}
