<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZakatCalculationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'calculation_number' => $this->calculation_number,
            'muzaki_id' => $this->muzaki_id,
            'zakat_type_id' => $this->zakat_type_id,
            'zakat_rule_id' => $this->zakat_rule_id,
            'rule_version' => $this->rule_version,
            'calculation_date' => $this->calculation_date?->toDateString(),
            'valid_until' => $this->valid_until?->toDateString(),
            'formula_code' => $this->formula_code,
            'formula_version' => $this->formula_version,
            'parent_calculation_id' => $this->parent_calculation_id,
            'status' => $this->status?->value,
            'eligibility_status' => $this->eligibility_status?->value,
            'gross_amount' => $this->gross_amount,
            'deduction_amount' => $this->deduction_amount,
            'net_amount' => $this->net_amount,
            'nisab_amount' => $this->nisab_amount,
            'zakat_rate' => $this->zakat_rate,
            'zakat_amount' => $this->zakat_amount,
            'currency' => $this->currency,
            'result_data' => $this->result_data,
            'inputs' => ZakatCalculationInputResource::collection($this->whenLoaded('inputs')),
            'snapshot' => new ZakatCalculationSnapshotResource($this->whenLoaded('snapshot')),
            'muzaki' => new MuzakiResource($this->whenLoaded('muzaki')),
            'type' => new ZakatTypeResource($this->whenLoaded('type')),
            'rule' => new ZakatRuleResource($this->whenLoaded('rule')),
            'adjustments' => $this->whenLoaded('adjustments'),
            'versions' => $this->whenLoaded('versions'),
        ];
    }
}
