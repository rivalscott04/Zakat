<?php

namespace App\Http\Requests;

class FundReconciliationRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['external_balance' => ['required', 'numeric', 'min:0'], 'reconciliation_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:1000']];
    }
}
