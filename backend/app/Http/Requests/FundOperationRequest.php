<?php

namespace App\Http\Requests;

class FundOperationRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['amount' => ['required', 'numeric', 'gt:0'], 'target_type' => ['nullable', 'string', 'max:30'], 'target_id' => ['nullable', 'ulid'], 'reason' => ['required', 'string', 'max:1000'], 'description' => ['nullable', 'string', 'max:1000'], 'source_type' => ['nullable', 'string', 'max:40'], 'source_id' => ['nullable', 'ulid'], 'expires_at' => ['nullable', 'date', 'after:now']];
    }
}
