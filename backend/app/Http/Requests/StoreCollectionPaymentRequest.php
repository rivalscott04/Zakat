<?php

namespace App\Http\Requests;

class StoreCollectionPaymentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['payment_reference' => ['required', 'string', 'max:80'], 'amount' => ['required', 'numeric', 'gt:0'], 'currency' => ['nullable', 'string', 'size:3'], 'payment_method' => ['required', 'in:CASH,BANK_TRANSFER,VIRTUAL_ACCOUNT,QRIS,EWALLET,CARD,PAYMENT_GATEWAY,OTHER'], 'payment_instrument' => ['nullable', 'string', 'max:60'], 'payment_date' => ['nullable', 'date'], 'metadata' => ['nullable', 'array']];
    }
}
