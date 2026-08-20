<?php

namespace App\Http\Requests;

class StoreAssessmentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['mustahik_id' => ['required', 'string', 'exists:mustahiks,id'], 'assessment_type' => ['required', 'in:initial,reassessment,program,distribution,emergency,verification,followup,complaint,custom'], 'priority' => ['nullable', 'in:low,normal,high,urgent'], 'reason' => ['nullable', 'string', 'max:2000'], 'due_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:2000']];
    }
}
