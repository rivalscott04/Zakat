<?php

namespace App\Http\Requests;

/** PRD 01 §23 — pembuatan role organisasi. */
class StoreRoleRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z][A-Z0-9_]*$/i'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['string', 'ulid'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['code.regex' => 'Role code hanya boleh berisi huruf, angka, dan underscore.'];
    }
}
