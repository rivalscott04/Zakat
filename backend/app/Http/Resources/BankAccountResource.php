<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 14C §8 — nomor rekening hanya keluar dalam bentuk mask. */
class BankAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_code' => $this->account_code,
            'bank_name' => $this->bank_name,
            'account_name' => $this->account_name,
            'account_number_masked' => $this->account_number_masked,
            'currency' => $this->currency,
            'opening_balance' => $this->opening_balance,
            'current_balance' => $this->current_balance,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
