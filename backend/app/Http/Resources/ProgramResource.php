<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'program_code' => $this->program_code, 'name' => $this->name, 'short_name' => $this->short_name, 'description' => $this->description, 'program_type' => $this->program_type, 'start_date' => $this->start_date?->toDateString(), 'end_date' => $this->end_date?->toDateString(), 'target_beneficiary' => $this->target_beneficiary, 'capacity_limit' => $this->capacity_limit, 'status' => $this->status, 'visibility' => $this->visibility, 'budgets' => $this->whenLoaded('budgets'), 'enrollments' => $this->whenLoaded('enrollments')];
    }
}
