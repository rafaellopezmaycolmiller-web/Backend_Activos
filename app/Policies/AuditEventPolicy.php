<?php

namespace App\Policies;

use App\Models\AuditEvent;
use App\Models\User;

class AuditEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('audit.view');
    }

    public function view(User $user, AuditEvent $auditEvent): bool
    {
        return $user->hasGlobalPermission('audit.view')
            || ($user->hasPermission('audit.view') && $user->area_id === $auditEvent->area_id);
    }
}
