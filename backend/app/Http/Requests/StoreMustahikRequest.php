<?php

namespace App\Http\Requests;

class StoreMustahikRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['mustahik_type' => ['nullable', 'in:individual,household,group,organization'], 'full_name' => ['required', 'string', 'max:150'], 'display_name' => ['nullable', 'string', 'max:150'], 'gender' => ['nullable', 'string', 'max:20'], 'birth_date' => ['nullable', 'date'], 'marital_status' => ['nullable', 'string', 'max:20'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'], 'identity_type' => ['nullable', 'string', 'max:30'], 'identity_number' => ['nullable', 'string', 'max:100'], 'address' => ['nullable', 'array'], 'profile' => ['nullable', 'array']];
    }
}
