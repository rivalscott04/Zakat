<?php

namespace App\Http\Requests;

class ReviewAssessmentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['decision' => ['required', 'in:approve,return,reject,escalate'], 'notes' => ['nullable', 'string', 'max:2000']];
    }
}
