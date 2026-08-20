<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** PRD 01 §31 — perubahan profil user. Status dan role punya endpoint sendiri. */
class UpdateUserRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $userId = $this->route('userId');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at')],
            'username' => ['sometimes', 'nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')->ignore($userId)->whereNull('deleted_at')],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
        ];
    }
}
