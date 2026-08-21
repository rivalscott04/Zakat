<?php

namespace App\Http\Requests;

/** PRD 13O §27. */
class StorePaymentRefundRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
