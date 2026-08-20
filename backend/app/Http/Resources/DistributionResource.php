<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistributionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'distribution_number' => $this->distribution_number, 'distribution_type' => $this->distribution_type, 'source_type' => $this->source_type, 'program_id' => $this->program_id, 'mustahik_id' => $this->mustahik_id, 'fund_id' => $this->fund_id, 'currency' => $this->currency, 'requested_amount' => $this->requested_amount, 'approved_amount' => $this->approved_amount, 'distributed_amount' => $this->distributed_amount, 'distribution_date' => $this->distribution_date?->toDateString(), 'scheduled_date' => $this->scheduled_date?->toDateString(), 'status' => $this->status, 'priority' => $this->priority, 'description' => $this->description, 'mustahik' => $this->whenLoaded('mustahik'), 'fund' => $this->whenLoaded('fund'), 'items' => $this->whenLoaded('items')];
    }
}
