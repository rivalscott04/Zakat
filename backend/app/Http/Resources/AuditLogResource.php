<?php

namespace App\Http\Resources;

use App\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PRD 17P dan 17Q.
 *
 * Nilai lama dan baru hanya ikut pada tampilan detail dan hanya untuk pemegang
 * `audit.view_sensitive` (PRD 17U §39). AuditService sudah menyaring field
 * rahasia saat menulis (PRD 17H §17), ini lapisan kedua saat membaca.
 */
class AuditLogResource extends JsonResource
{
    public function __construct($resource, private readonly bool $detailed = false)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $user = $request->user();
        $maySeeValues = $this->detailed && $user?->hasPermissionTo('audit.view_sensitive', OrganizationContext::id());

        return [
            'id' => $this->id,
            'audit_number' => $this->audit_number,
            'event_name' => $this->event_name,
            'event_category' => $this->event_category,
            'module_code' => $this->module_code,
            'severity' => $this->severity,
            'action' => $this->action,
            'description' => $this->description,
            'entity_type' => $this->entity_type ? class_basename($this->entity_type) : null,
            'entity_id' => $this->entity_id,
            'entity_reference' => $this->entity_reference,
            'actor_id' => $this->actor_id,
            'actor_name' => $this->actor_name,
            'actor_type' => $this->actor_type,
            'ip_address' => $this->ip_address,
            'request_id' => $this->request_id,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'has_changes' => $this->old_values !== null || $this->new_values !== null,
            $this->mergeWhen($maySeeValues, fn () => [
                'old_values' => $this->old_values,
                'new_values' => $this->new_values,
                'metadata' => $this->metadata,
                'user_agent' => $this->user_agent,
            ]),
        ];
    }
}
