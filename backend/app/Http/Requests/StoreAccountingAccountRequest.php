<?php

namespace App\Http\Requests;

class StoreAccountingAccountRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['account_code' => ['required', 'string', 'max:30'], 'account_name' => ['required', 'string', 'max:150'], 'account_type' => ['required', 'in:asset,liability,equity,revenue,expense,fund,control,memorandum'], 'account_category' => ['nullable', 'string', 'max:30'], 'parent_id' => ['nullable', 'ulid'], 'normal_balance' => ['required', 'in:debit,credit'], 'is_postable' => ['boolean'], 'description' => ['nullable', 'string']];
    }
}
