<?php

namespace App\Http\Requests;

use App\Enums\OrganizationType;
use Illuminate\Validation\Rule;

/** PRD 02 §34 dan §35 — validasi organisasi. */
class StoreOrganizationRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+$/i', Rule::unique('organizations', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'organization_type' => ['required', Rule::enum(OrganizationType::class)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'website' => ['nullable', 'url', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3', 'alpha'],
            'timezone' => ['nullable', 'timezone'],
            'locale' => ['nullable', 'string', 'max:10'],
            'parent_id' => ['nullable', 'string', 'ulid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper($this->input('code'))]);
        }

        if (is_string($this->input('currency'))) {
            $this->merge(['currency' => strtoupper($this->input('currency'))]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['code.regex' => 'Organization code hanya boleh berisi huruf dan angka.'];
    }
}
