<?php

namespace App\Http\Resources;

use App\Models\NotificationDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 16U §43. */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'notification_number' => $this->notification_number,
            'event_name' => $this->event_name,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'read_at' => $this->read_at?->toIso8601String(),
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'deliveries' => $this->whenLoaded('deliveries', fn () => $this->deliveries->map(fn (NotificationDelivery $delivery) => [
                'channel' => $delivery->channel->value,
                'status' => $delivery->status->value,
                'attempt_count' => $delivery->attempt_count,
                'max_attempts' => $delivery->max_attempts,
                'error_message' => $delivery->error_message,
                'sent_at' => $delivery->sent_at?->toIso8601String(),
            ])),
        ];
    }
}
