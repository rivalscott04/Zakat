<?php

namespace App\Http\Resources;

use App\Models\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 19R §44. */
class ReportRunResource extends JsonResource
{
    public function __construct($resource, private readonly bool $withSnapshot = false)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'run_number' => $this->run_number,
            'report_id' => $this->report_id,
            'report_name' => $this->whenLoaded('report', fn () => $this->report?->name),
            'report_code' => $this->whenLoaded('report', fn () => $this->report?->report_code),
            'parameters' => $this->parameters,
            'status' => $this->status->value,
            'row_count' => $this->row_count,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'error_message' => $this->error_message,
            'exports' => $this->whenLoaded('exports', fn () => $this->exports->map(fn (ReportExport $export) => [
                'id' => $export->id,
                'format' => $export->format->value,
                'file_size' => $export->file_size,
                'expires_at' => $export->expires_at?->toIso8601String(),
                'download_count' => $export->download_count,
            ])),
            // Snapshot bisa besar, jadi hanya ikut pada tampilan detail.
            $this->mergeWhen($this->withSnapshot, fn () => ['snapshot' => $this->snapshot_data]),
        ];
    }
}
