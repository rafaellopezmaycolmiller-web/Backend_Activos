<?php

namespace App\Actions\Authentication;

use App\AuditEventType;
use App\Models\LoginChallenge;
use App\Models\User;
use App\Notifications\LoginVerificationCode;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class IssueLoginChallenge
{
    public function __construct(
        private Request $request,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(User $user, bool $remember): LoginChallenge
    {
        $user->loginChallenges()
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $challenge = $user->loginChallenges()->create([
            'code_hash' => Hash::make($code),
            'attempts_left' => config('security.mfa.max_attempts'),
            'expires_at' => now()->addMinutes(config('security.mfa.expires_minutes')),
            'consumed_at' => null,
            'remember' => $remember,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);

        $user->notify(new LoginVerificationCode(
            code: $code,
            expiresMinutes: config('security.mfa.expires_minutes'),
        ));

        $this->auditLogger->record(
            AuditEventType::MfaChallengeIssued,
            subject: $user,
            metadata: ['challenge_id' => $challenge->getKey()],
            area: $user->area,
        );

        return $challenge;
    }
}
