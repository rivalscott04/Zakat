<?php

namespace App\Http\Requests;

class UpdateAssessmentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['assessment_date' => ['nullable', 'date'], 'recommendation' => ['nullable', 'in:eligible,not_eligible,needs_review,emergency_support,reassessment_required,program_recommended,document_required'], 'recommendation_reason' => ['nullable', 'string', 'max:2000'], 'answers' => ['nullable', 'array'], 'answers.*.question_code' => ['required', 'string', 'max:80'], 'answers.*.question_id' => ['nullable', 'string'], 'answers.*.answer_value' => ['nullable', 'string'], 'answers.*.answer_data' => ['nullable', 'array'], 'answers.*.score' => ['nullable', 'numeric'], 'answers.*.notes' => ['nullable', 'string'], 'answers.*.question_snapshot' => ['nullable', 'array']];
    }
}
