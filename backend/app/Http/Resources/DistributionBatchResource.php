<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 12P §39. */
class DistributionBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'name' => $this->name,
            'program_id' => $this->program_id,
            'fund_id' => $this->fund_id,
            'distribution_type' => $this->distribution_type->value,
            'total_amount' => $this->total_amount,
            'total_beneficiary' => $this->total_beneficiary,
            'status' => $this->status->value,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'allowed_transitions' => collect($this->status->allowedNext())->map(fn ($status) => $status->value)->values(),
            'fund' => $this->whenLoaded('fund'),
            'program' => $this->whenLoaded('program'),
            'beneficiaries' => DistributionBeneficiaryResource::collection($this->whenLoaded('beneficiaries')),
        ];
    }
}
