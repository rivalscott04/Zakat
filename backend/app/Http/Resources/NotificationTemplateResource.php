<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 16U §44. */
class NotificationTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'template_code' => $this->template_code,
            'name' => $this->name,
            'channel' => $this->channel->value,
            'subject' => $this->subject,
            'content' => $this->content,
            'locale' => $this->locale,
            'status' => $this->status->value,
            'variables' => $this->variables ?? [],
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
