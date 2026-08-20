<?php

namespace App\Http\Requests;

class StoreAssessmentTemplateRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['template_code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9_]+$/'], 'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string'], 'assessment_type' => ['required', 'string', 'max:30'], 'mustahik_type' => ['nullable', 'string', 'max:30'], 'schema' => ['nullable', 'array'], 'effective_from' => ['nullable', 'date'], 'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from']];
    }
}
