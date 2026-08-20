<?php

namespace App\Http\Requests;

class StoreZakatCalculationRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'muzaki_id' => ['required', 'ulid'],
            'zakat_type_id' => ['required', 'ulid'],
            'calculation_date' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:calculation_date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'inputs' => ['nullable', 'array'],
        ];
    }
}
