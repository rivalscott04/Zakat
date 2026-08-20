<?php

namespace App\Http\Requests;

/** PRD 01 §16 — penetapan password baru memakai reset token. */
class ResetPasswordRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', static::passwordRule()],
        ];
    }
}
