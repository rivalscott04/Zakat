<?php

namespace App\Http\Requests;

class StoreCollectionFromCalculationRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['calculation_id' => ['required', 'ulid'], 'due_date' => ['nullable', 'date', 'after_or_equal:today'], 'notes' => ['nullable', 'string', 'max:5000']];
    }
}
