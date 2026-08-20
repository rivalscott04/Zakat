<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'program_code' => $this->program_code, 'name' => $this->name, 'short_name' => $this->short_name, 'description' => $this->description, 'program_type' => $this->program_type, 'start_date' => $this->start_date?->toDateString(), 'end_date' => $this->end_date?->toDateString(), 'target_beneficiary' => $this->target_beneficiary, 'capacity_limit' => $this->capacity_limit, 'waitlist_enabled' => $this->waitlist_enabled, 'status' => $this->status, 'visibility' => $this->visibility, 'budgets' => $this->whenLoaded('budgets'), 'enrollments' => $this->whenLoaded('enrollments'), 'periods' => $this->whenLoaded('periods'), 'funds' => $this->whenLoaded('funds'), 'eligibility_rules' => $this->whenLoaded('eligibilityRules'), 'waitlists' => $this->whenLoaded('waitlists'), 'activities' => $this->whenLoaded('activities'), 'targets' => $this->whenLoaded('targets'), 'outputs' => $this->whenLoaded('outputs'), 'outcomes' => $this->whenLoaded('outcomes'), 'commitments' => $this->whenLoaded('commitments')];
    }
}
