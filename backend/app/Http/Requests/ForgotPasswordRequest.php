<?php

namespace App\Http\Requests;

/** PRD 01 §16 — permintaan reset password. */
class ForgotPasswordRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        // Tidak memakai rule `exists`: keberadaan email tidak boleh bisa diprobe.
        return ['email' => ['required', 'string', 'email', 'max:255']];
    }
}
