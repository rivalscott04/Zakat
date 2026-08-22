<?php

namespace App\Http\Requests;

use App\Support\OrganizationContext;
use Illuminate\Validation\Rule;

/** PRD 14K dan 14L — pencocokan manual dan sebagian. */
class MatchBankTransactionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'reconciliation_transaction_id' => [
                'required', 'string', 'ulid',
                Rule::exists('reconciliation_transactions', 'id')->where('organization_id', OrganizationContext::id()),
            ],
            'matched_amount' => ['nullable', 'numeric', 'gt:0', 'max:999999999999'],
        ];
    }
}
