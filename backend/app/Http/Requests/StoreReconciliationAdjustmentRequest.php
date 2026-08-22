<?php

namespace App\Http\Requests;

use App\Support\OrganizationContext;
use Illuminate\Validation\Rule;

/** PRD 14S §47 dan §48. */
class StoreReconciliationAdjustmentRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'reconciliation_session_id' => [
                'required', 'string', 'ulid',
                Rule::exists('reconciliation_sessions', 'id')->where('organization_id', OrganizationContext::id()),
            ],
            'bank_transaction_id' => [
                'nullable', 'string', 'ulid',
                Rule::exists('bank_transactions', 'id')->where('organization_id', OrganizationContext::id()),
            ],
            'adjustment_type' => ['required', 'in:BANK_FEE,CORRECTION,ROUNDING,OTHER'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'reason' => ['required', 'string', 'max:2000'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
