<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 16U §45. */
class NotificationRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_name' => $this->event_name,
            'template_id' => $this->template_id,
            'template_code' => $this->whenLoaded('template', fn () => $this->template?->template_code),
            'channels' => $this->channels ?? [],
            'recipient_strategy' => $this->recipient_strategy->value,
            'recipient_config' => $this->recipient_config,
            'priority' => $this->priority->value,
            'enabled' => $this->enabled,
        ];
    }
}
