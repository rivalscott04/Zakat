<?php

namespace App\Http\Requests;

/** PRD 12L §30 dan 12M §31 — realisasi beserta detail penyalurannya. */
class CompleteDistributionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'distribution_date' => ['nullable', 'date'],
            'receipt_number' => ['nullable', 'string', 'max:60'],
            'bank_transfer' => ['nullable', 'array'],
            'bank_transfer.bank_name' => ['required_with:bank_transfer', 'string', 'max:100'],
            'bank_transfer.account_holder_name' => ['required_with:bank_transfer', 'string', 'max:150'],
            'bank_transfer.account_number' => ['required_with:bank_transfer', 'string', 'max:40'],
            'bank_transfer.transfer_reference' => ['nullable', 'string', 'max:100'],
        ];
    }
}
