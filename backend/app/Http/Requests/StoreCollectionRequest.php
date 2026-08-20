<?php

namespace App\Http\Requests;

class StoreCollectionRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['muzaki_id' => ['required', 'ulid'], 'zakat_type_id' => ['required', 'ulid'], 'expected_amount' => ['required', 'numeric', 'gt:0'], 'currency' => ['nullable', 'string', 'size:3'], 'collection_date' => ['nullable', 'date'], 'due_date' => ['nullable', 'date', 'after_or_equal:collection_date'], 'source' => ['nullable', 'in:calculator,manual,self_service,import,api,integration'], 'calculation_id' => ['nullable', 'ulid'], 'notes' => ['nullable', 'string', 'max:5000'], 'reason' => ['required_if:source,manual', 'nullable', 'string', 'max:1000']];
    }
}
