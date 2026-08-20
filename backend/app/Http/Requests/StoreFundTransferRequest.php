<?php

namespace App\Http\Requests;

class StoreFundTransferRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['source_fund_id' => ['required', 'ulid'], 'destination_fund_id' => ['required', 'ulid'], 'amount' => ['required', 'numeric', 'gt:0'], 'reason' => ['required', 'string', 'max:1000']];
    }
}
