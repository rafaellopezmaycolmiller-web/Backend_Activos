<?php

namespace App\Http\Controllers\Api\V1;

use App\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AccessRecoveryRequestCreateRequest;
use App\Http\Requests\Api\V1\AccessRecoveryResolveRequest;
use App\Models\AccessRecoveryRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccessRecoveryController extends Controller
{
    public function store(AccessRecoveryRequestCreateRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $email = strtolower($request->string('email')->trim()->toString());
        $user = User::query()->where('email', $email)->where('is_active', true)->first();

        if ($user !== null) {
            $recovery = AccessRecoveryRequest::query()->where('user_id', $user->id)->where('status', 'pending')->first();
            if ($recovery === null) {
                $recovery = AccessRecoveryRequest::create([
                    'user_id' => $user->id,
                    'area_id' => $user->area_id,
                    'email' => $email,
                ]);
                $auditLogger->record(AuditEventType::AccessRecoveryRequested, subject: $user, area: $user->area);
            }
        }

        return response()->json(['message' => 'Si la cuenta está registrada, la solicitud fue enviada al área de TI.']);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && ($user->hasGlobalPermission('access_recovery.manage') || $user->roles()->whereIn('code', ['administrador_tecnico', 'asistente_ti'])->exists()), 403);

        return response()->json(['data' => AccessRecoveryRequest::query()->with(['user.area', 'resolver'])->latest()->get()]);
    }

    public function resolve(AccessRecoveryResolveRequest $request, AccessRecoveryRequest $accessRecoveryRequest, AuditLogger $auditLogger): JsonResponse
    {
        abort_if($accessRecoveryRequest->status !== 'pending', 409, 'La solicitud ya fue atendida.');
        $actor = $request->user();
        $status = $request->string('status')->toString();
        $temporaryPassword = null;

        DB::transaction(function () use ($accessRecoveryRequest, $actor, $status, $request, $auditLogger, &$temporaryPassword): void {
            if ($status === 'approved') {
                $temporaryPassword = Str::random(20);
                $accessRecoveryRequest->user->forceFill([
                    'password' => $temporaryPassword,
                    'remember_token' => Str::random(60),
                ])->save();
            }
            $accessRecoveryRequest->update([
                'status' => $status,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'resolution_note' => $request->string('resolution_note')->trim()->toString() ?: null,
            ]);
            $auditLogger->record(
                $status === 'approved' ? AuditEventType::AccessRecoveryApproved : AuditEventType::AccessRecoveryRejected,
                actor: $actor,
                subject: $accessRecoveryRequest->user,
                area: $accessRecoveryRequest->user->area,
                metadata: ['request_id' => $accessRecoveryRequest->id],
            );
        });

        return response()->json([
            'message' => $status === 'approved'
                ? 'Solicitud aprobada. Entregue la contraseña temporal por un canal institucional verificado.'
                : 'Solicitud rechazada correctamente.',
            'temporary_password' => $temporaryPassword,
        ]);
    }
}
