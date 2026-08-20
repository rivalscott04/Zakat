<?php

namespace App\Http\Requests;

class UpdateProgramBudgetRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['budget_amount' => ['sometimes', 'numeric', 'gt:0'], 'currency' => ['sometimes', 'string', 'size:3'], 'status' => ['sometimes', 'in:draft,active,suspended,closed']];
    }
}
