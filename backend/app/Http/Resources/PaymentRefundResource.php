<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 13O §27. */
class PaymentRefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'refund_number' => $this->refund_number,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'status' => $this->status->value,
            'rejection_reason' => $this->rejection_reason,
            'requested_at' => $this->requested_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
        ];
    }
}
