<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 14E §13. */
class BankTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bank_account_id' => $this->bank_account_id,
            'bank_statement_id' => $this->bank_statement_id,
            'transaction_reference' => $this->transaction_reference,
            'transaction_date' => $this->transaction_date?->toDateString(),
            'description' => $this->description,
            'debit_amount' => $this->debit_amount,
            'credit_amount' => $this->credit_amount,
            'balance' => $this->balance,
            'currency' => $this->currency,
            'match_status' => $this->match_status,
            'duplicate_status' => $this->duplicate_status,
            'matches' => $this->whenLoaded('matches'),
        ];
    }
}
