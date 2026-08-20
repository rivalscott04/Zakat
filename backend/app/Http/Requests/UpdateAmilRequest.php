<?php

namespace App\Http\Requests;

/** PRD 02 §32 — perubahan amil. Status memakai endpoint tersendiri. */
class UpdateAmilRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'user_id' => ['sometimes', 'nullable', 'string', 'ulid'],
            'employee_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
        ];
    }
}
