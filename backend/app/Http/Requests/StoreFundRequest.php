<?php

namespace App\Http\Requests;

class StoreFundRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['fund_code' => ['required', 'string', 'max:40'], 'name' => ['required', 'string', 'max:150'], 'fund_type' => ['required', 'in:zakat,infaq,sedekah,amil,wakaf,non_halal,other'], 'category' => ['nullable', 'string', 'max:50'], 'restriction_type' => ['nullable', 'in:restricted,unrestricted,designated,temporarily_restricted,custom'], 'currency' => ['nullable', 'string', 'size:3'], 'opening_balance' => ['nullable', 'numeric', 'min:0']];
    }
}
