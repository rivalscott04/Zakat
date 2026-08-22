<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 16U §47. */
class NotificationBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'name' => $this->name,
            'total_recipient' => $this->total_recipient,
            'total_success' => $this->total_success,
            'total_failed' => $this->total_failed,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'notifications' => NotificationResource::collection($this->whenLoaded('notifications')),
        ];
    }
}
