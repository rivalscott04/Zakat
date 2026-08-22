<?php

namespace App\Http\Requests;

/** PRD 16H §17 dan §18. */
class SaveNotificationEmailConfigRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'driver' => ['required', 'in:smtp,ses,postmark,resend,log'],
            'host' => ['required_if:driver,smtp', 'nullable', 'string', 'max:255'],
            'port' => ['required_if:driver,smtp', 'nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'from_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'from_email' => ['required', 'email', 'max:255'],
            'encryption' => ['sometimes', 'nullable', 'in:tls,ssl'],
        ];
    }
}
