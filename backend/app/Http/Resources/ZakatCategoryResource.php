<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZakatCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'organization_id' => $this->organization_id, 'code' => $this->code, 'name' => $this->name, 'description' => $this->description, 'status' => $this->status, 'sort_order' => $this->sort_order, 'types' => ZakatTypeResource::collection($this->whenLoaded('types'))];
    }
}
