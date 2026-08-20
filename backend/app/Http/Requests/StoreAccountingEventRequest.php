<?php

namespace App\Http\Requests;

class StoreAccountingEventRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['event_type' => ['required', 'string', 'max:50'], 'source_type' => ['required', 'string', 'max:50'], 'source_id' => ['required', 'ulid'], 'reference_number' => ['nullable', 'string', 'max:100'], 'event_date' => ['required', 'date'], 'payload' => ['nullable', 'array']];
    }
}
