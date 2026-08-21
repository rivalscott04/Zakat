<?php

namespace App\Http\Requests;

/** PRD 13B §5 — provider code immutable setelah dibuat. */
class UpdatePaymentProviderRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sandbox_mode' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'nullable', 'array'],
            'webhook_secret' => ['sometimes', 'nullable', 'string', 'min:32', 'max:255'],
            'generate_webhook_secret' => ['sometimes', 'boolean'],
        ];
    }
}
