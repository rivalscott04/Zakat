<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZakatCalculationInputResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'parameter_code' => $this->parameter_code, 'value' => $this->value, 'unit' => $this->unit, 'currency' => $this->currency, 'source' => $this->source];
    }
}
