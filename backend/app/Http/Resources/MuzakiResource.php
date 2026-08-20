<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MuzakiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'business_number' => $this->business_number,
            'muzaki_type' => $this->muzaki_type?->value,
            'status' => $this->status?->value,
            'display_name' => $this->display_name,
            'registration_source' => $this->registration_source,
            'registered_at' => $this->registered_at?->toIso8601String(),
            'profile' => $this->when($this->relationLoaded('individualProfile'), $this->individualProfile ?? $this->organizationProfile),
            'contacts' => $this->whenLoaded('contacts'),
            'addresses' => $this->whenLoaded('addresses'),
            'preferences' => $this->whenLoaded('preference'),
            'identities' => $this->when($request->user()?->can('muzaki.view_sensitive'), $this->whenLoaded('identities')),
        ];
    }
}
