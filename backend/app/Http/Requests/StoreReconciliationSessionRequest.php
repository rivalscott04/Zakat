<?php

namespace App\Http\Requests;

use App\Support\OrganizationContext;
use Illuminate\Validation\Rule;

/** PRD 14O §39. */
class StoreReconciliationSessionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'bank_account_id' => [
                'required', 'string', 'ulid',
                Rule::exists('bank_accounts', 'id')->where('organization_id', OrganizationContext::id()),
            ],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'opening_balance' => ['nullable', 'numeric', 'max:999999999999'],
            'closing_balance' => ['nullable', 'numeric', 'max:999999999999'],
        ];
    }
}
