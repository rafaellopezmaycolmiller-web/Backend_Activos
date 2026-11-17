<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Area;
use App\Models\Asset;
use App\Models\Custodianship;
use App\Models\DisposalRequest;
use App\Models\InventoryCampaign;
use App\Models\InventoryCheck;
use App\Models\MovementRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'actor' => $this->whenLoaded('actor', fn () => $this->actor === null ? null : [
                'id' => $this->actor->getKey(),
                'name' => $this->actor->name,
                'email' => $this->actor->email,
                'job_title' => $this->actor->job_title,
                'area' => $this->actor->area === null ? null : [
                    'id' => $this->actor->area->getKey(),
                    'code' => $this->actor->area->code,
                    'name' => $this->actor->area->name,
                ],
                'roles' => $this->actor->roles->map(fn ($role): array => [
                    'id' => $role->getKey(),
                    'code' => $role->code,
                    'name' => $role->name,
                ])->values(),
            ]),
            'area' => new AreaResource($this->whenLoaded('area')),
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject' => $this->whenLoaded('subject', fn (): ?array => $this->subjectSummary()),
            'request_id' => $this->request_id,
            'ip_address' => $this->ip_address,
            'metadata' => $this->metadata,
            'occurred_at' => $this->occurred_at->toIso8601String(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function subjectSummary(): ?array
    {
        $subject = $this->subject;

        if (! $subject instanceof Model) {
            return null;
        }

        return match (true) {
            $subject instanceof Asset => $this->assetSubject($subject, 'asset', 'Ficha del activo'),
            $subject instanceof MovementRequest => $this->assetSubject(
                $subject->asset,
                'movement',
                sprintf(
                    'Movimiento N.° %s · %s → %s',
                    $subject->getKey(),
                    $subject->fromArea?->name ?? 'Origen',
                    $subject->toArea?->name ?? 'Destino',
                ),
                $subject,
            ),
            $subject instanceof DisposalRequest => $this->assetSubject(
                $subject->asset,
                'disposal',
                'Solicitud de baja N.° '.$subject->getKey(),
                $subject,
            ),
            $subject instanceof Custodianship => $this->assetSubject(
                $subject->asset,
                'custody',
                'Asignación de custodia N.° '.$subject->getKey(),
                $subject,
            ),
            $subject instanceof InventoryCheck => $this->assetSubject(
                $subject->asset,
                'inventory_check',
                'Verificación de inventario N.° '.$subject->getKey(),
                $subject,
            ),
            $subject instanceof InventoryCampaign => [
                'id' => (string) $subject->getKey(),
                'type' => 'inventory_campaign',
                'label' => $subject->name,
                'reference' => $subject->code,
                'context' => 'Campaña de inventario',
                'asset' => null,
            ],
            $subject instanceof User => [
                'id' => (string) $subject->getKey(),
                'type' => 'user',
                'label' => $subject->name,
                'reference' => $subject->email,
                'context' => $subject->job_title,
                'asset' => null,
            ],
            $subject instanceof Area => [
                'id' => (string) $subject->getKey(),
                'type' => 'area',
                'label' => $subject->name,
                'reference' => $subject->code,
                'context' => 'Área municipal',
                'asset' => null,
            ],
            default => [
                'id' => (string) $subject->getKey(),
                'type' => 'record',
                'label' => $subject->getAttribute('name') ?? class_basename($subject),
                'reference' => $subject->getAttribute('code'),
                'context' => 'Registro relacionado',
                'asset' => null,
            ],
        };
    }

    /** @return array<string, mixed> */
    private function assetSubject(?Asset $asset, string $type, string $context, ?Model $subject = null): array
    {
        return [
            'id' => (string) ($subject?->getKey() ?? $asset?->getKey() ?? $this->subject_id),
            'type' => $type,
            'label' => $asset?->name ?? 'Activo no disponible',
            'reference' => $asset?->asset_code,
            'context' => $context,
            'asset' => $asset === null ? null : [
                'id' => $asset->getKey(),
                'name' => $asset->name,
                'asset_code' => $asset->asset_code,
            ],
        ];
    }
}
