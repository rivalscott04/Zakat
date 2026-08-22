<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 18V §37. */
class TransparencySnapshotResource extends JsonResource
{
    public function __construct($resource, private readonly bool $withData = false)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'snapshot_number' => $this->snapshot_number,
            'snapshot_type' => $this->snapshot_type->value,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'status' => $this->status->value,
            'verification_status' => $this->verification_status?->value,
            'verification_notes' => $this->verification_notes,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'revocation_reason' => $this->revocation_reason,
            // Sengaja bukan `data`: JsonResource menganggap key `data` di tingkat
            // atas sebagai envelope yang sudah jadi, lalu membuang field lainnya.
            $this->mergeWhen($this->withData, fn () => ['snapshot_data' => $this->data]),
        ];
    }
}
