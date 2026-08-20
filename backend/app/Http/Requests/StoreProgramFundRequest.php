<?php

namespace App\Http\Requests;

class StoreProgramFundRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['fund_id' => ['required', 'string', 'exists:funds,id'], 'priority' => ['nullable', 'integer', 'min:0']];
    }
}
