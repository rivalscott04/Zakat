<?php

namespace App\Http\Requests;

/** PRD 01 §8 — user menetapkan password dari undangan. */
class AcceptInvitationRequest extends ApiRequest
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
