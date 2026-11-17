<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Authentication;

use App\AuditEventType;
use App\Models\LoginChallenge;
use App\Models\User;
use App\Notifications\LoginVerificationCode;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesAuthorizedUsers;
use Tests\TestCase;

class SessionControllerTest extends TestCase
{
    use CreatesAuthorizedUsers;
    use LazilyRefreshDatabase;

    public function test_valid_credentials_create_session_and_return_user(): void
    {
        $user = User::factory()->create([
            'email' => 'usuario@morales.gob.pe',
            'password' => 'Password123!',
        ]);

        $response = $this
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/login', [
                'email' => 'USUARIO@MORALES.GOB.PE',
                'password' => 'Password123!',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.email', 'usuario@morales.gob.pe')
            ->assertJsonPath('data.id', $user->getKey());
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_events', [
            'event' => AuditEventType::LoginSucceeded->value,
            'actor_id' => $user->getKey(),
        ]);
    }

    public function test_invalid_password_returns_422_and_records_failure(): void
    {
        $user = User::factory()->create([
            'email' => 'usuario@morales.gob.pe',
            'password' => 'Password123!',
        ]);

        $response = $this
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'Incorrecta123!',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
        $this->assertGuest();
        $this->assertSame(1, $user->fresh()->failed_login_attempts);
        $this->assertDatabaseHas('audit_events', [
            'event' => AuditEventType::LoginFailed->value,
            'subject_id' => (string) $user->getKey(),
        ]);
    }

    public function test_fifth_invalid_password_locks_account(): void
    {
        $user = User::factory()->create([
            'email' => 'usuario@morales.gob.pe',
            'password' => 'Password123!',
            'failed_login_attempts' => 4,
        ]);

        $this
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'Incorrecta123!',
            ])
            ->assertUnprocessable();

        $user->refresh();

        $this->assertSame(5, $user->failed_login_attempts);
        $this->assertTrue($user->locked_until->isFuture());
    }

    public function test_mfa_account_returns_challenge_without_authenticating(): void
    {
        Notification::fake();
        $user = User::factory()->mfaRequired()->create([
            'email' => 'jefatura@morales.gob.pe',
            'password' => 'Password123!',
        ]);

        $response = $this
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'Password123!',
            ]);

        $response
            ->assertAccepted()
            ->assertJsonPath('requires_mfa', true)
            ->assertJsonStructure(['challenge_id', 'expires_at']);
        $this->assertGuest();
        $this->assertSame(1, LoginChallenge::query()->whereBelongsTo($user)->count());
        Notification::assertSentTo($user, LoginVerificationCode::class);
    }

    public function test_authenticated_user_can_logout_and_event_is_audited(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Sesión cerrada correctamente.');

        $this->assertGuest('web');
        $this->assertDatabaseHas('audit_events', [
            'event' => AuditEventType::Logout->value,
            'actor_id' => $user->getKey(),
        ]);
    }
}
