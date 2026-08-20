<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class AdjustZakatCalculationRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['adjustment_type' => ['required', Rule::in(['increase', 'decrease', 'override'])], 'adjustment_amount' => ['required', 'numeric', 'min:0'], 'reason' => ['required', 'string', 'max:1000']];
    }
}
