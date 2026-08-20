<?php

namespace App\Http\Requests;

class StoreJournalRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['accounting_period_id' => ['required', 'ulid'], 'journal_date' => ['required', 'date'], 'journal_type' => ['nullable', 'in:system,manual,adjustment,reversal,opening,closing,transfer,refund'], 'reference_number' => ['nullable', 'string', 'max:100'], 'description' => ['required', 'string'], 'currency' => ['nullable', 'string', 'size:3'], 'lines' => ['required', 'array', 'min:2'], 'lines.*.account_id' => ['required', 'ulid'], 'lines.*.description' => ['nullable', 'string'], 'lines.*.debit_amount' => ['nullable', 'numeric', 'min:0'], 'lines.*.credit_amount' => ['nullable', 'numeric', 'min:0']];
    }
}
