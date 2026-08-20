<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 12Q §42. */
class DistributionBeneficiaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_id' => $this->batch_id,
            'distribution_id' => $this->distribution_id,
            'mustahik_id' => $this->mustahik_id,
            'approved_amount' => $this->approved_amount,
            'distributed_amount' => $this->distributed_amount,
            'status' => $this->status,
            'failure_reason' => $this->failure_reason,
            'failure_note' => $this->failure_note,
            'mustahik' => $this->whenLoaded('mustahik'),
        ];
    }
}
