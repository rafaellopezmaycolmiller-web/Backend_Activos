<?php

namespace App\Http\Controllers\Api\V1\Authentication;

use App\Actions\Authentication\CompleteLogin;
use App\Actions\Authentication\IssueLoginChallenge;
use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Authentication\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SessionController extends Controller
{
    public function store(
        LoginRequest $request,
        IssueLoginChallenge $issueLoginChallenge,
        CompleteLogin $completeLogin,
        AuditLogger $auditLogger,
    ): JsonResponse|UserResource {
        $user = User::query()
            ->with('area')
            ->where('email', $request->string('email')->toString())
            ->first();

        $passwordMatches = $user === null
            ? Hash::check(
                $request->string('password')->toString(),
                '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.',
            )
            : Hash::check($request->string('password')->toString(), $user->password);

        if (
            $user === null
            || ! $passwordMatches
            || ! $user->is_active
            || $user->locked_until?->isFuture()
        ) {
            if ($user !== null && ! $passwordMatches && $user->is_active) {
                $failedAttempts = $user->failed_login_attempts + 1;
                $lockedUntil = $failedAttempts >= config('security.login.max_attempts')
                    ? now()->addMinutes(config('security.login.lock_minutes'))
                    : null;

                $user->forceFill([
                    'failed_login_attempts' => $failedAttempts,
                    'locked_until' => $lockedUntil,
                ])->save();
            }

            $auditLogger->record(
                AuditEventType::LoginFailed,
                subject: $user,
                metadata: ['reason' => 'credentials_or_account_status'],
                area: $user?->area,
            );

            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son válidas o la cuenta no está disponible.',
            ]);
        }

        $remember = $request->boolean('remember');

        if ($user->mfa_required && config('security.mfa.enabled')) {
            try {
                $challenge = $issueLoginChallenge->handle($user, $remember);
            } catch (Throwable $exception) {
                report($exception);

                throw ValidationException::withMessages([
                    'email' => 'No se pudo enviar el código SMS. Revise la configuración de mensajería.',
                ]);
            }

            return response()->json([
                'message' => 'Se envió un código de verificación.',
                'requires_mfa' => true,
                'challenge_id' => $challenge->getKey(),
                'expires_at' => $challenge->expires_at->toIso8601String(),
                'delivery_channel' => config('services.twilio.channel', 'sms'),
                'destination_hint' => 'celular terminado en '.Str::take((string) $user->phone, -4),
            ], 202);
        }

        $completeLogin->handle($user, $remember);

        return new UserResource(
            $user->load('area', 'roles.permissions'),
        );
    }

    public function destroy(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $auditLogger->record(
            AuditEventType::Logout,
            actor: $user,
            subject: $user,
            area: $user->area,
        );

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }
}
