<?php

namespace App\Http\Requests;

class StoreProgramCommitmentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['program_budget_id' => ['required', 'string', 'exists:program_budgets,id'], 'enrollment_id' => ['nullable', 'string', 'exists:program_enrollments,id'], 'distribution_id' => ['nullable', 'string'], 'amount' => ['required', 'numeric', 'gt:0'], 'currency' => ['nullable', 'string', 'size:3'], 'reason' => ['nullable', 'string']];
    }
}
