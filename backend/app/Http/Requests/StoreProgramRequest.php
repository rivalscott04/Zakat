<?php

namespace App\Http\Requests;

class StoreProgramRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150'], 'short_name' => ['nullable', 'string', 'max:80'], 'description' => ['nullable', 'string'], 'category_id' => ['nullable', 'string', 'exists:program_categories,id'], 'program_type' => ['required', 'in:assistance,empowerment,scholarship,emergency,development,service,campaign,custom'], 'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'target_beneficiary' => ['nullable', 'integer', 'min:0'], 'capacity_limit' => ['nullable', 'integer', 'min:1'], 'waitlist_enabled' => ['nullable', 'boolean'], 'visibility' => ['nullable', 'in:internal,public']];
    }
}
