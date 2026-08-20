<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZakatRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'organization_id' => $this->organization_id, 'zakat_type_id' => $this->zakat_type_id, 'rule_code' => $this->rule_code, 'name' => $this->name, 'description' => $this->description, 'version' => $this->version, 'status' => $this->status?->value, 'effective_from' => $this->effective_from?->toDateString(), 'effective_until' => $this->effective_until?->toDateString(), 'type' => new ZakatTypeResource($this->whenLoaded('type')), 'rates' => $this->whenLoaded('rates'), 'nisab' => $this->whenLoaded('nisab'), 'haul' => $this->whenLoaded('haul'), 'parameters' => $this->whenLoaded('parameters'), 'formula_definitions' => $this->whenLoaded('formulaDefinitions')];
    }
}
