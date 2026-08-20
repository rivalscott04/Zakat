<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'organization_id' => $this->organization_id, 'fund_code' => $this->fund_code, 'name' => $this->name, 'fund_type' => $this->fund_type, 'category' => $this->category, 'restriction_type' => $this->restriction_type, 'status' => $this->status?->value, 'currency' => $this->currency, 'opening_balance' => $this->opening_balance, 'current_balance' => $this->current_balance, 'available_balance' => $this->available_balance, 'reserved_balance' => $this->reserved_balance, 'allocated_balance' => $this->allocated_balance, 'distributed_balance' => $this->distributed_balance, 'movements' => $this->whenLoaded('movements'), 'allocations' => $this->whenLoaded('allocations'), 'reservations' => $this->whenLoaded('reservations')];
    }
}
