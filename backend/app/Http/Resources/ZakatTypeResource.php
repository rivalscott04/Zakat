<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZakatTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'organization_id' => $this->organization_id, 'zakat_category_id' => $this->zakat_category_id, 'code' => $this->code, 'name' => $this->name, 'description' => $this->description, 'calculation_method' => $this->calculation_method?->value, 'status' => $this->status, 'sort_order' => $this->sort_order, 'category' => new ZakatCategoryResource($this->whenLoaded('category')), 'rules' => ZakatRuleResource::collection($this->whenLoaded('rules'))];
    }
}
