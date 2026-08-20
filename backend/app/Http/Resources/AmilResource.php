<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 02 §44 — data amil untuk halaman manajemen. */
class AmilResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'business_number' => $this->business_number,
            'name' => $this->name,
            'employee_number' => $this->employee_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status->value,
            'joined_at' => $this->joined_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'has_user_account' => $this->user_id !== null,
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'assignments' => AmilAssignmentResource::collection($this->whenLoaded('assignments')),
            'active_assignments' => AmilAssignmentResource::collection($this->whenLoaded('activeAssignments')),
        ];
    }
}
