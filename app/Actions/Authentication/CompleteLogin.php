<?php

namespace App\Actions\Authentication;

use App\AuditEventType;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompleteLogin
{
    public function __construct(
        private Request $request,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(User $user, bool $remember = false): void
    {
        Auth::guard('web')->login($user, $remember);
        $this->request->session()->regenerate();

        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ])->save();

        $this->auditLogger->record(
            AuditEventType::LoginSucceeded,
            actor: $user,
            subject: $user,
            area: $user->area,
        );
    }
}
