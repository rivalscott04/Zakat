<?php

namespace App\Http\Requests;

use App\Enums\MuzakiType;
use Illuminate\Validation\Rule;

class StoreMuzakiRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'muzaki_type' => ['required', Rule::enum(MuzakiType::class)],
            'display_name' => ['required', 'string', 'max:255'],
            'registration_source' => ['nullable', 'string', 'max:20'],
            'profile' => ['nullable', 'array'],
            'profile.full_name' => ['nullable', 'string', 'max:255'],
            'profile.legal_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
