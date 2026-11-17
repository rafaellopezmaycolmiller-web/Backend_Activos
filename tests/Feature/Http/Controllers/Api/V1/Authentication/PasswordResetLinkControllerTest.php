<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Authentication;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetLinkControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_existing_account_receives_reset_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'usuario@morales.gob.pe']);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Si la cuenta existe, recibirá instrucciones para restablecer la contraseña.',
            );

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_account_receives_same_generic_response(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'desconocido@morales.gob.pe',
        ])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Si la cuenta existe, recibirá instrucciones para restablecer la contraseña.',
            );

        Notification::assertNothingSent();
    }
}
