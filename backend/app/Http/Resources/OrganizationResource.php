<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 02 §42 — detail organisasi. */
class OrganizationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_number' => $this->business_number,
            'code' => $this->code,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'organization_type' => $this->organization_type->value,
            'status' => $this->status->value,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'parent' => new OrganizationSummaryResource($this->whenLoaded('parent')),
            'children_count' => $this->whenCounted('children'),
            'members_count' => $this->whenCounted('members'),
            'amils_count' => $this->whenCounted('amils'),
            'addresses' => OrganizationAddressResource::collection($this->whenLoaded('addresses')),
            'contacts' => OrganizationContactResource::collection($this->whenLoaded('contacts')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
