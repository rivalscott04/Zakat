<?php

namespace App\Http\Requests;

/** PRD 14H §22 — pencatatan transaksi internal secara manual. */
class StoreInternalTransactionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'transaction_reference' => ['required', 'string', 'max:80'],
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'direction' => ['required', 'in:INFLOW,OUTFLOW'],
            'currency' => ['nullable', 'string', 'size:3', 'alpha'],
        ];
    }
}
