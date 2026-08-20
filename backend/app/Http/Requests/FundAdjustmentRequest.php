<?php

namespace App\Http\Requests;

class FundAdjustmentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['fund_id' => ['required', 'ulid'], 'adjustment_type' => ['required', 'in:increase,decrease,correction,reversal'], 'amount' => ['required', 'numeric', 'gt:0'], 'reason' => ['required', 'string', 'max:1000'], 'reference' => ['nullable', 'string', 'max:100']];
    }
}
