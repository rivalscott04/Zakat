<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Baris general ledger. Bentuk per barisnya sama seperti sebelum dipaginasi. */
class LedgerLineResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'journal_number' => $this->journal->journal_number,
            'journal_date' => $this->journal->journal_date?->toDateString(),
            'account_code' => $this->account->account_code,
            'account_name' => $this->account->account_name,
            'debit_amount' => $this->debit_amount,
            'credit_amount' => $this->credit_amount,
        ];
    }
}
