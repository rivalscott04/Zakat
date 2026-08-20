<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'template_code' => $this->template_code, 'name' => $this->name, 'description' => $this->description, 'assessment_type' => $this->assessment_type, 'mustahik_type' => $this->mustahik_type, 'version' => $this->version, 'status' => $this->status, 'effective_from' => $this->effective_from?->toDateString(), 'effective_until' => $this->effective_until?->toDateString(), 'schema' => $this->schema];
    }
}
