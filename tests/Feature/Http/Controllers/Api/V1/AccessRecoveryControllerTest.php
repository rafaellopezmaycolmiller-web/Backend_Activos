<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Models\AccessRecoveryRequest;
use App\Models\User;
use App\RoleScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesAuthorizedUsers;
use Tests\TestCase;

class AccessRecoveryControllerTest extends TestCase
{
    use CreatesAuthorizedUsers;
    use LazilyRefreshDatabase;

    public function test_approving_recovery_resets_password_and_unlocks_account(): void
    {
        $manager = $this->createAuthorizedUser(['access_recovery.manage'], RoleScope::Global);
        $lockedUser = User::factory()->locked()->create();
        $recovery = AccessRecoveryRequest::query()->create([
            'user_id' => $lockedUser->getKey(),
            'area_id' => $lockedUser->area_id,
            'email' => $lockedUser->email,
        ]);

        $response = $this
            ->actingAs($manager)
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/access-recovery-requests/'.$recovery->getKey(), [
                'status' => 'approved',
                'resolution_note' => 'Identidad verificada por TI.',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Solicitud aprobada. Entregue la contraseña temporal por un canal institucional verificado.')
            ->assertJsonStructure(['temporary_password']);

        $temporaryPassword = $response->json('temporary_password');
        $lockedUser->refresh();
        $recovery->refresh();

        $this->assertIsString($temporaryPassword);
        $this->assertTrue(Hash::check($temporaryPassword, $lockedUser->password));
        $this->assertSame(0, $lockedUser->failed_login_attempts);
        $this->assertNull($lockedUser->locked_until);
        $this->assertSame('approved', $recovery->status);
        $this->assertSame($manager->getKey(), $recovery->resolved_by);
        $this->assertDatabaseHas('audit_events', [
            'event' => AuditEventType::AccessRecoveryApproved->value,
            'actor_id' => $manager->getKey(),
            'subject_id' => (string) $lockedUser->getKey(),
        ]);
    }
}
