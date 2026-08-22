<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 18V §38. */
class TransparencyReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_number' => $this->report_number,
            'title' => $this->title,
            'report_type' => $this->report_type,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'snapshot_id' => $this->snapshot_id,
            'snapshot_number' => $this->whenLoaded('snapshot', fn () => $this->snapshot?->snapshot_number),
            'status' => $this->status->value,
            'notes' => $this->notes,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
