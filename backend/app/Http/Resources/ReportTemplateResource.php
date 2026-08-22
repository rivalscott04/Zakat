<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 19R §46. */
class ReportTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'template_code' => $this->template_code,
            'name' => $this->name,
            'report_id' => $this->report_id,
            'report_name' => $this->whenLoaded('report', fn () => $this->report?->name),
            'configuration' => $this->configuration,
            'status' => $this->status->value,
        ];
    }
}
