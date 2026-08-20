<?php

namespace App\Http\Requests;

class StoreMustahikAddressRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['address_type' => ['nullable', 'in:home,temporary,work,other'], 'address_line' => ['required', 'string'], 'province_code' => ['nullable', 'string', 'max:20'], 'regency_code' => ['nullable', 'string', 'max:20'], 'district_code' => ['nullable', 'string', 'max:20'], 'village_code' => ['nullable', 'string', 'max:20'], 'postal_code' => ['nullable', 'string', 'max:10'], 'latitude' => ['nullable', 'numeric'], 'longitude' => ['nullable', 'numeric'], 'is_primary' => ['boolean']];
    }
}
