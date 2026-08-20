<?php

namespace App\Http\Requests;

class StoreZakatRuleRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['zakat_type_id' => ['required', 'ulid', 'exists:zakat_types,id'], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'version' => ['required', 'integer', 'min:1'], 'effective_from' => ['required', 'date'], 'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from']];
    }
}
