<?php

namespace App\Http\Requests;

use App\Enums\CalculationMethod;
use Illuminate\Validation\Rule;

class StoreZakatTypeRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['zakat_category_id' => ['required', 'ulid', 'exists:zakat_categories,id'], 'code' => ['required', 'string', 'uppercase', 'max:50'], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'calculation_method' => ['required', Rule::enum(CalculationMethod::class)], 'sort_order' => ['nullable', 'integer', 'min:0']];
    }
}
