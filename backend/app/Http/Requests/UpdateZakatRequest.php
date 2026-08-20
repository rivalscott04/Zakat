<?php

namespace App\Http\Requests;

class UpdateZakatRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['name' => ['sometimes', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string'], 'sort_order' => ['sometimes', 'integer', 'min:0']];
    }
}
