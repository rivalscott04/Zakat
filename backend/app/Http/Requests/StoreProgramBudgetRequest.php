<?php

namespace App\Http\Requests;

class StoreProgramBudgetRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['fund_id' => ['required', 'string', 'exists:funds,id'], 'budget_amount' => ['required', 'numeric', 'gt:0'], 'currency' => ['nullable', 'string', 'size:3']];
    }
}
