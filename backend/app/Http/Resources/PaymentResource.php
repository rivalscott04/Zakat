<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 13T §38 dan §39. */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'provider_id' => $this->provider_id,
            'provider_reference' => $this->provider_reference,
            'internal_reference' => $this->internal_reference,
            'source_type' => $this->source_type->value,
            'source_id' => $this->source_id,
            'payer_name' => $this->payer_name,
            'payer_email' => $this->payer_email,
            'payer_phone' => $this->payer_phone,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method?->value,
            'payment_url' => $this->payment_url,
            'status' => $this->status->value,
            'allowed_transitions' => collect($this->status->allowedNext())->map(fn ($s) => $s->value)->values(),
            'refundable_amount' => $this->refundableAmount(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'verification_reason' => $this->verification_reason,
            'cancellation_reason' => $this->cancellation_reason,
            'failure_reason' => $this->failure_reason,
            'failure_note' => $this->failure_note,
            'provider' => $this->whenLoaded('provider'),
            'webhooks' => PaymentWebhookResource::collection($this->whenLoaded('webhooks')),
            'refunds' => PaymentRefundResource::collection($this->whenLoaded('refunds')),
            'reconciliations' => $this->whenLoaded('reconciliations'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
