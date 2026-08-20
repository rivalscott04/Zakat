<?php

namespace App\Http\Requests;

class RecalculateZakatCalculationRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'muzaki_id' => ['sometimes', 'ulid'],
            'zakat_type_id' => ['sometimes', 'ulid'],
            'calculation_date' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:calculation_date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'inputs' => ['nullable', 'array'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
