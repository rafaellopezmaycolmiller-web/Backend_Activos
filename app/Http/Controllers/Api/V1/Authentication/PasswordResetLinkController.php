<?php

namespace App\Http\Controllers\Api\V1\Authentication;

use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Authentication\ForgotPasswordRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function store(
        ForgotPasswordRequest $request,
        AuditLogger $auditLogger,
    ): JsonResponse {
        $email = $request->string('email')->toString();
        $user = User::query()->with('area')->where('email', $email)->first();

        Password::sendResetLink(['email' => $email]);

        $auditLogger->record(
            AuditEventType::PasswordResetRequested,
            subject: $user,
            area: $user?->area,
        );

        return response()->json([
            'message' => 'Si la cuenta existe, recibirá instrucciones para restablecer la contraseña.',
        ]);
    }
}
