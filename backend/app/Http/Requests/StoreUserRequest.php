<?php

namespace App\Http\Requests;

use App\Enums\MemberType;
use Illuminate\Validation\Rule;

/** PRD 01 §30 dan §48 — pembuatan user oleh administrator. */
class StoreUserRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'username' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:32'],
            'member_type' => ['nullable', Rule::enum(MemberType::class)],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['string', 'ulid'],
        ];
    }
}
