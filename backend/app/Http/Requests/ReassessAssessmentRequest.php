<?php

namespace App\Http\Requests;

class ReassessAssessmentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['priority' => ['nullable', 'in:low,normal,high,urgent'], 'reason' => ['required', 'string', 'max:2000'], 'assessor_id' => ['nullable', 'string']];
    }
}
