<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** PRD 02 §30 — perubahan organisasi termasuk parent_id. */
class UpdateOrganizationRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'required', 'string', 'max:20', 'regex:/^[A-Z0-9]+$/i', Rule::unique('organizations', 'code')->ignore($this->route('organizationId'))],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'legal_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3', 'alpha'],
            'timezone' => ['sometimes', 'nullable', 'timezone'],
            'locale' => ['sometimes', 'nullable', 'string', 'max:10'],
            'parent_id' => ['sometimes', 'nullable', 'string', 'ulid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper($this->input('code'))]);
        }
    }
}
