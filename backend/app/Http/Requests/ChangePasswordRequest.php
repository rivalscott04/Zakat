<?php

namespace App\Http\Requests;

/** PRD 01 §15 — ganti password oleh user yang sedang login. */
class ChangePasswordRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'different:current_password', static::passwordRule()],
        ];
    }
}
