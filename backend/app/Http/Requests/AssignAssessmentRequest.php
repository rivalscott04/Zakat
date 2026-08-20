<?php

namespace App\Http\Requests;

class AssignAssessmentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['assessor_id' => ['required', 'string'], 'due_date' => ['nullable', 'date']];
    }
}
