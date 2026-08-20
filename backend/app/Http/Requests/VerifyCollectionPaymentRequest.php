<?php

namespace App\Http\Requests;

class VerifyCollectionPaymentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['status' => ['required', 'in:verified,settled,failed,cancelled,refunded']];
    }
}
