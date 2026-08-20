<?php

namespace App\Http\Requests;

class StoreProgramEnrollmentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['mustahik_id' => ['required', 'string', 'exists:mustahiks,id'], 'assessment_id' => ['nullable', 'string', 'exists:assessments,id'], 'eligibility_result' => ['nullable', 'string', 'max:30'], 'notes' => ['nullable', 'string']];
    }
}
