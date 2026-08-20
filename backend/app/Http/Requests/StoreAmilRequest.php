<?php

namespace App\Http\Requests;

/** PRD 02 §17 dan §37 — pembuatan amil. */
class StoreAmilRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // PRD 02 §18 — amil boleh dicatat tanpa user account.
            'user_id' => ['nullable', 'string', 'ulid'],
            'employee_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'joined_at' => ['nullable', 'date'],
        ];
    }
}
