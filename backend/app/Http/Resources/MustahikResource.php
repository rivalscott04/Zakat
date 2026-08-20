<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MustahikResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'mustahik_number' => $this->mustahik_number, 'mustahik_type' => $this->mustahik_type, 'full_name' => $this->full_name, 'display_name' => $this->display_name, 'gender' => $this->gender, 'birth_date' => $this->birth_date?->toDateString(), 'marital_status' => $this->marital_status, 'phone' => $this->phone, 'email' => $this->email, 'identity_type' => $this->identity_type, 'status' => $this->status, 'verification_status' => $this->verification_status, 'eligibility_status' => $this->eligibility_status, 'registered_at' => $this->registered_at?->toDateString(), 'identities' => $this->whenLoaded('identities'), 'addresses' => $this->whenLoaded('addresses'), 'asnaf' => $this->whenLoaded('asnaf'), 'profile' => $this->whenLoaded('profile')];
    }
}
