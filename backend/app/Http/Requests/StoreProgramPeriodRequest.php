<?php

namespace App\Http\Requests;

class StoreProgramPeriodRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['period_code' => ['required', 'string', 'max:30'], 'name' => ['required', 'string', 'max:120'], 'start_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after_or_equal:start_date'], 'target_beneficiary' => ['nullable', 'integer', 'min:0']];
    }
}
