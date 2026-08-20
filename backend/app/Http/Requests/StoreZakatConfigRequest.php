<?php

namespace App\Http\Requests;

class StoreZakatConfigRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'rate_type' => ['sometimes', 'string', 'max:20'], 'rate_value' => ['sometimes', 'numeric'], 'unit' => ['sometimes', 'string', 'max:20'],
            'effective_from' => ['sometimes', 'date'], 'effective_until' => ['nullable', 'date'], 'nisab_type' => ['sometimes', 'string', 'max:20'], 'reference_type' => ['nullable', 'string', 'max:20'], 'reference_value' => ['nullable', 'numeric'], 'quantity' => ['nullable', 'numeric'], 'currency' => ['nullable', 'string', 'size:3'],
            'haul_type' => ['sometimes', 'string', 'max:20'], 'duration' => ['nullable', 'integer', 'min:0'], 'duration_unit' => ['nullable', 'string', 'max:10'], 'calendar_type' => ['nullable', 'string', 'max:15'],
            'parameter_code' => ['sometimes', 'string', 'max:60'], 'name' => ['sometimes', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'data_type' => ['sometimes', 'string', 'max:20'], 'is_required' => ['nullable', 'boolean'], 'default_value' => ['nullable', 'array'], 'validation_rules' => ['nullable', 'array'],
            'formula_code' => ['sometimes', 'string', 'max:80'], 'formula_version' => ['sometimes', 'integer', 'min:1'], 'formula_type' => ['sometimes', 'string', 'max:30'], 'expression' => ['sometimes', 'string', 'max:255'], 'input_schema' => ['nullable', 'array'], 'output_schema' => ['nullable', 'array'],
            'reference_code' => ['sometimes', 'string', 'max:50'], 'value' => ['sometimes', 'numeric'], 'source' => ['nullable', 'string', 'max:50'], 'effective_at' => ['sometimes', 'date'], 'expires_at' => ['nullable', 'date'],
        ];
    }
}
