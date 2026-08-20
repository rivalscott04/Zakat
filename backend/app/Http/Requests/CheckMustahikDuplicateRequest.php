<?php

namespace App\Http\Requests;

class CheckMustahikDuplicateRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['full_name' => ['required', 'string'], 'birth_date' => ['nullable', 'date'], 'identity_number' => ['nullable', 'string'], 'phone' => ['nullable', 'string']];
    }
}
