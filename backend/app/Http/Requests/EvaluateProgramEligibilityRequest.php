<?php

namespace App\Http\Requests;

class EvaluateProgramEligibilityRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['mustahik_id' => ['required', 'string', 'exists:mustahiks,id'], 'assessment_id' => ['nullable', 'string', 'exists:assessments,id']];
    }
}
