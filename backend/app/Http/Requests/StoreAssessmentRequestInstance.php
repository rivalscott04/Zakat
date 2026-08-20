<?php

namespace App\Http\Requests;

class StoreAssessmentRequestInstance extends ApiRequest
{
    public function rules(): array
    {
        return ['assessment_request_id' => ['required', 'string', 'exists:assessment_requests,id'], 'template_id' => ['nullable', 'string', 'exists:assessment_templates,id'], 'assessor_id' => ['nullable', 'string'], 'assessment_date' => ['nullable', 'date']];
    }
}
