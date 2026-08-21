<?php

namespace App\Http\Requests;

/** PRD 13K §22 — verifikasi manual wajib beralasan. */
class VerifyPaymentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:5', 'max:2000']];
    }
}
