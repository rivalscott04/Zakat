<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZakatCalculationSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'zakat_type' => $this->zakat_type_snapshot, 'zakat_rule' => $this->zakat_rule_snapshot, 'nisab' => $this->nisab_snapshot, 'haul' => $this->haul_snapshot, 'rate' => $this->rate_snapshot, 'reference_value' => $this->reference_value_snapshot, 'parameters' => $this->parameter_snapshot, 'formula' => $this->formula_snapshot, 'result' => $this->result_snapshot];
    }
}
