<?php

namespace App\Http\Requests;

class StoreProgramOutputRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['output_code' => ['required', 'string', 'max:60'], 'name' => ['required', 'string', 'max:150'], 'target_value' => ['required', 'numeric', 'gte:0'], 'actual_value' => ['nullable', 'numeric', 'gte:0'], 'unit' => ['required', 'string', 'max:30'], 'period_id' => ['nullable', 'string', 'exists:program_periods,id'], 'status' => ['nullable', 'in:active,completed,cancelled']];
    }
}
