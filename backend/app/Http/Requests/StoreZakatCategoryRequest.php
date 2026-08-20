<?php

namespace App\Http\Requests;

class StoreZakatCategoryRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['code' => ['required', 'string', 'uppercase', 'max:50'], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'sort_order' => ['nullable', 'integer', 'min:0']];
    }
}
