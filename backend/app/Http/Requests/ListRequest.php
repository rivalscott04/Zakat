<?php

namespace App\Http\Requests;

/** Query parameter standar untuk endpoint list (PRD 00 §23 — wajib paginasi). */
class ListRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.config('zakat.pagination.max_per_page')],
        ];
    }
}
