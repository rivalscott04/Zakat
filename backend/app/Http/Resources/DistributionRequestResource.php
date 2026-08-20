<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 12F §15. */
class DistributionRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'mustahik_id' => $this->mustahik_id,
            'program_id' => $this->program_id,
            'assessment_id' => $this->assessment_id,
            'fund_id' => $this->fund_id,
            'distribution_type' => $this->distribution_type->value,
            'requested_amount' => $this->requested_amount,
            'currency' => $this->currency,
            'reason' => $this->reason,
            'priority' => $this->priority,
            'status' => $this->status,
            'requested_at' => $this->requested_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'distribution_id' => $this->distribution_id,
            'mustahik' => $this->whenLoaded('mustahik'),
            'fund' => $this->whenLoaded('fund'),
        ];
    }
}
