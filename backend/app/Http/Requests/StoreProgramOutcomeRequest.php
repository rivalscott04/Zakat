<?php

namespace App\Http\Requests;

class StoreProgramOutcomeRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['outcome_code' => ['required', 'string', 'max:60'], 'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string'], 'measurement_method' => ['nullable', 'string'], 'target_value' => ['required', 'numeric', 'gte:0'], 'actual_value' => ['nullable', 'numeric', 'gte:0'], 'unit' => ['required', 'string', 'max:30'], 'measurement_date' => ['nullable', 'date'], 'status' => ['nullable', 'in:active,completed,cancelled']];
    }
}
