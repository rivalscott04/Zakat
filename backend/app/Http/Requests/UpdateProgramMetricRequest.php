<?php

namespace App\Http\Requests;

class UpdateProgramMetricRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['current_value' => ['nullable', 'numeric', 'gte:0'], 'actual_value' => ['nullable', 'numeric', 'gte:0'], 'status' => ['nullable', 'string', 'max:20'], 'measurement_date' => ['nullable', 'date']];
    }
}
