<?php

namespace App\Http\Requests;

class StoreProgramCategoryRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['category_code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_]+$/'], 'name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string'], 'parent_id' => ['nullable', 'string'], 'sort_order' => ['nullable', 'integer', 'min:0']];
    }
}
