<?php

namespace App\Http\Requests;

use App\Enums\MemberType;
use Illuminate\Validation\Rule;

/** PRD 02 §31 — perubahan member type. Status memakai endpoint tersendiri. */
class UpdateMemberRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['member_type' => ['required', Rule::enum(MemberType::class)]];
    }
}
