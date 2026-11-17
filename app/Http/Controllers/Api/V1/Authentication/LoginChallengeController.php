<?php

namespace App\Http\Controllers\Api\V1\Authentication;

use App\Actions\Authentication\CompleteLogin;
use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Authentication\VerifyLoginChallengeRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\LoginChallenge;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginChallengeController extends Controller
{
    public function store(
        VerifyLoginChallengeRequest $request,
        CompleteLogin $completeLogin,
        AuditLogger $auditLogger,
    ): UserResource {
        $validationMessage = null;

        $user = DB::transaction(function () use (
            $request,
            $completeLogin,
            $auditLogger,
            &$validationMessage,
        ): ?User {
            $challenge = LoginChallenge::query()
                ->with('user.area')
                ->lockForUpdate()
                ->find($request->string('challenge_id')->toString());

            if (
                $challenge === null
                || ! $challenge->isAvailable()
                || ($challenge->ip_address !== null && $challenge->ip_address !== $request->ip())
            ) {
                $validationMessage = 'El código venció o ya no está disponible.';

                return null;
            }

            if (! Hash::check($request->string('code')->toString(), $challenge->code_hash)) {
                $attemptsLeft = max(0, $challenge->attempts_left - 1);
                $challenge->forceFill(['attempts_left' => $attemptsLeft])->save();

                $auditLogger->record(
                    AuditEventType::MfaFailed,
                    subject: $challenge->user,
                    metadata: [
                        'challenge_id' => $challenge->getKey(),
                        'attempts_left' => $attemptsLeft,
                    ],
                    area: $challenge->user->area,
                );

                $validationMessage = 'El código no es válido.';

                return null;
            }

            $challenge->forceFill(['consumed_at' => now()])->save();
            $completeLogin->handle($challenge->user, $challenge->remember);

            return $challenge->user;
        });

        if ($user === null) {
            throw ValidationException::withMessages([
                'code' => $validationMessage,
            ]);
        }

        return new UserResource(
            $user->load('area', 'roles.permissions'),
        );
    }
}
