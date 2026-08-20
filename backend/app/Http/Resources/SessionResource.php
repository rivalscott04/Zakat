<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 01 §18 — metadata session aktif. */
class SessionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'last_activity_at' => CarbonImmutable::createFromTimestamp($this->last_activity, 'UTC')->toIso8601String(),
            'created_at' => $this->created_at,
            'is_current' => $this->id === $request->session()->getId(),
        ];
    }
}
