<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'assessment_number' => $this->assessment_number, 'assessment_request_id' => $this->assessment_request_id, 'mustahik_id' => $this->mustahik_id, 'assessment_type' => $this->assessment_type, 'assessor_id' => $this->assessor_id, 'assessment_date' => $this->assessment_date?->toDateString(), 'status' => $this->status, 'total_score' => $this->total_score, 'result' => $this->result, 'recommendation' => $this->recommendation, 'recommendation_reason' => $this->recommendation_reason, 'submitted_at' => $this->submitted_at, 'approved_at' => $this->approved_at, 'previous_assessment_id' => $this->previous_assessment_id, 'mustahik' => $this->whenLoaded('mustahik'), 'request' => $this->whenLoaded('request'), 'template' => $this->whenLoaded('template'), 'answers' => $this->whenLoaded('answers'), 'reviews' => $this->whenLoaded('reviews')];
    }
}
