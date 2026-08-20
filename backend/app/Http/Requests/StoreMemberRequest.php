<?php

namespace App\Http\Requests;

use App\Enums\MemberType;
use Illuminate\Validation\Rule;

/** PRD 02 §31 — penambahan member organisasi. */
class StoreMemberRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'ulid'],
            'member_type' => ['required', Rule::enum(MemberType::class)],
        ];
    }
}
