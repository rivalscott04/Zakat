<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PRD 13I §18. Payload mentah sengaja tidak ikut supaya isi notifikasi provider
 * tidak terekspos lewat UI; penelusuran penuh dilakukan lewat database.
 */
class PaymentWebhookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'event_type' => $this->event_type,
            'signature_valid' => $this->signature_valid,
            'status' => $this->status->value,
            'error_message' => $this->error_message,
            'received_at' => $this->received_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
        ];
    }
}
