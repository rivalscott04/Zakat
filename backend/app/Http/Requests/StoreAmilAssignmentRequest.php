<?php

namespace App\Http\Requests;

/** PRD 02 §33 — assignment operasional amil. */
class StoreAmilAssignmentRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'assignment_type' => ['required', 'string', 'max:50'],
            'started_at' => ['nullable', 'date'],
        ];
    }
}
