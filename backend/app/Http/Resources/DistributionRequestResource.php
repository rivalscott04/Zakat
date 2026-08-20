<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistributionRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'request_number' => $this->request_number, 'mustahik_id' => $this->mustahik_id, 'fund_id' => $this->fund_id, 'distribution_type' => $this->distribution_type, 'requested_amount' => $this->requested_amount, 'currency' => $this->currency, 'reason' => $this->reason, 'priority' => $this->priority, 'status' => $this->status, 'mustahik' => $this->whenLoaded('mustahik'), 'fund' => $this->whenLoaded('fund')];
    }
}
