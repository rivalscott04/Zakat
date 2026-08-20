<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'request_number' => $this->request_number, 'mustahik_id' => $this->mustahik_id, 'assessment_type' => $this->assessment_type, 'priority' => $this->priority, 'reason' => $this->reason, 'requested_at' => $this->requested_at, 'due_date' => $this->due_date?->toDateString(), 'status' => $this->status, 'assessor_id' => $this->assessor_id, 'assigned_at' => $this->assigned_at, 'notes' => $this->notes, 'mustahik' => $this->whenLoaded('mustahik'), 'assessments' => $this->whenLoaded('assessments')];
    }
}
