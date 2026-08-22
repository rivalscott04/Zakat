<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 14O §39. */
class ReconciliationSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'session_number' => $this->session_number,
            'bank_account_id' => $this->bank_account_id,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'opening_balance' => $this->opening_balance,
            'closing_balance' => $this->closing_balance,
            'matched_amount' => $this->matched_amount,
            'unmatched_amount' => $this->unmatched_amount,
            'difference_amount' => $this->difference_amount,
            'status' => $this->status,
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
