<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Authentication;

use App\AuditEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class NewPasswordControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_token_resets_password_and_records_audit_event(): void
    {
        $user = User::factory()->create(['password' => 'Password123!']);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NuevaPassword123!',
            'password_confirmation' => 'NuevaPassword123!',
        ])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'La contraseña fue restablecida. Ya puede iniciar sesión.',
            );

        $this->assertTrue(Hash::check('NuevaPassword123!', $user->fresh()->password));
        $this->assertDatabaseHas('audit_events', [
            'event' => AuditEventType::PasswordResetCompleted->value,
            'subject_id' => (string) $user->getKey(),
        ]);
    }

    public function test_invalid_token_returns_422_without_changing_password(): void
    {
        $user = User::factory()->create(['password' => 'Password123!']);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'token-invalido',
            'email' => $user->email,
            'password' => 'NuevaPassword123!',
            'password_confirmation' => 'NuevaPassword123!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertTrue(Hash::check('Password123!', $user->fresh()->password));
    }
}
