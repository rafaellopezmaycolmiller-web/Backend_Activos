<?php

namespace App\Http\Controllers\Api\V1\Authentication;

use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Authentication\ResetPasswordRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    public function store(
        ResetPasswordRequest $request,
        AuditLogger $auditLogger,
    ): JsonResponse {
        $status = Password::reset(
            $request->safe()->only(['email', 'password', 'password_confirmation', 'token']),
            function (User $user, string $password) use ($auditLogger): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                ])->save();

                DB::table('sessions')->where('user_id', $user->getKey())->delete();
                event(new PasswordReset($user));

                $auditLogger->record(
                    AuditEventType::PasswordResetCompleted,
                    subject: $user,
                    area: $user->area,
                );
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'El enlace de recuperación no es válido o ha vencido.',
            ]);
        }

        return response()->json([
            'message' => 'La contraseña fue restablecida. Ya puede iniciar sesión.',
        ]);
    }
}
