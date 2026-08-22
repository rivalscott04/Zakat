<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 19R §47. */
class ReportScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'report_name' => $this->whenLoaded('report', fn () => $this->report?->name),
            'name' => $this->name,
            'frequency' => $this->frequency->value,
            'parameters' => $this->parameters,
            'output_format' => $this->output_format->value,
            'recipient_configuration' => $this->recipient_configuration,
            'status' => $this->status->value,
            'last_run_at' => $this->last_run_at?->toIso8601String(),
            'next_run_at' => $this->next_run_at?->toIso8601String(),
        ];
    }
}
