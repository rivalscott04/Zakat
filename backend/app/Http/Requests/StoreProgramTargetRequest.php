<?php

namespace App\Http\Requests;

class StoreProgramTargetRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['target_type' => ['required', 'in:beneficiary,financial,activity,output,outcome,custom'], 'name' => ['required', 'string', 'max:150'], 'target_value' => ['required', 'numeric', 'gte:0'], 'current_value' => ['nullable', 'numeric', 'gte:0'], 'unit' => ['required', 'string', 'max:30'], 'period_id' => ['nullable', 'string', 'exists:program_periods,id']];
    }
}
