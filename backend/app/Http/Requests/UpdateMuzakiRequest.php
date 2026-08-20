<?php

namespace App\Http\Requests;

class UpdateMuzakiRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['display_name' => ['sometimes', 'string', 'max:255'], 'registration_source' => ['sometimes', 'string', 'max:20']];
    }
}
