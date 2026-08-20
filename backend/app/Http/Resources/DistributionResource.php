<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 12AD §69 — detail distribution. */
class DistributionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'distribution_number' => $this->distribution_number,
            'distribution_type' => $this->distribution_type->value,
            'source_type' => $this->source_type->value,
            'status' => $this->status->value,
            'priority' => $this->priority,
            'program_id' => $this->program_id,
            'program_enrollment_id' => $this->program_enrollment_id,
            'mustahik_id' => $this->mustahik_id,
            'assessment_id' => $this->assessment_id,
            'fund_id' => $this->fund_id,
            'batch_id' => $this->batch_id,
            'currency' => $this->currency,
            'requested_amount' => $this->requested_amount,
            'approved_amount' => $this->approved_amount,
            'distributed_amount' => $this->distributed_amount,
            'remaining_amount' => $this->remainingAmount(),
            'distribution_date' => $this->distribution_date?->toDateString(),
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'description' => $this->description,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'reversed_at' => $this->reversed_at?->toIso8601String(),
            'reversal_reason' => $this->reversal_reason,
            'failure_reason' => $this->failure_reason,
            'failure_note' => $this->failure_note,
            'retry_count' => $this->retry_count,
            'allowed_transitions' => collect($this->status->allowedNext())->map(fn ($status) => $status->value)->values(),
            'mustahik' => $this->whenLoaded('mustahik'),
            'fund' => $this->whenLoaded('fund'),
            'program' => $this->whenLoaded('program'),
            'items' => $this->whenLoaded('items'),
            'reservations' => $this->whenLoaded('reservations'),
            'cash_details' => $this->whenLoaded('cashDetails'),
            'bank_transfers' => DistributionBankTransferResource::collection($this->whenLoaded('bankTransfers')),
            'schedules' => $this->whenLoaded('schedules'),
            'proofs' => $this->whenLoaded('proofs'),
            'confirmation' => $this->whenLoaded('confirmation'),
        ];
    }
}
