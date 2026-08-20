<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'organization_id' => $this->organization_id, 'collection_number' => $this->collection_number, 'muzaki_id' => $this->muzaki_id, 'calculation_id' => $this->calculation_id, 'zakat_type_id' => $this->zakat_type_id, 'zakat_rule_id' => $this->zakat_rule_id, 'collection_date' => $this->collection_date?->toDateString(), 'due_date' => $this->due_date?->toDateString(), 'status' => $this->status?->value, 'currency' => $this->currency, 'expected_amount' => $this->expected_amount, 'paid_amount' => $this->paid_amount, 'remaining_amount' => $this->remaining_amount, 'payment_count' => $this->payment_count, 'source' => $this->source?->value, 'overpayment_status' => $this->overpayment_status, 'source_snapshot' => $this->source_snapshot, 'notes' => $this->notes, 'cancellation_reason' => $this->cancellation_reason, 'muzaki' => new MuzakiResource($this->whenLoaded('muzaki')), 'type' => new ZakatTypeResource($this->whenLoaded('type')), 'items' => $this->whenLoaded('items'), 'payments' => $this->whenLoaded('payments'), 'allocations' => $this->whenLoaded('allocations')];
    }
}
