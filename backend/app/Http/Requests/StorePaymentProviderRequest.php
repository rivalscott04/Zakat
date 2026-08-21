<?php

namespace App\Http\Requests;

use App\Support\OrganizationContext;
use Illuminate\Validation\Rule;

/** PRD 13B §4 dan §5. */
class StorePaymentProviderRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'provider_code' => [
                'required', 'string', 'max:30', 'regex:/^[A-Z0-9_]+$/i',
                Rule::unique('payment_providers', 'provider_code')->where('organization_id', OrganizationContext::id()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'driver' => ['required', 'string', 'max:30'],
            'sandbox_mode' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'nullable', 'array'],
            'webhook_secret' => ['sometimes', 'nullable', 'string', 'min:32', 'max:255'],
            'generate_webhook_secret' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('provider_code'))) {
            $this->merge(['provider_code' => strtoupper($this->input('provider_code'))]);
        }
    }

    public function messages(): array
    {
        return ['provider_code.regex' => 'Provider code hanya boleh huruf, angka, dan underscore.'];
    }
}
