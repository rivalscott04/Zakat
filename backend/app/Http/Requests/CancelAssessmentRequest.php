<?php

namespace App\Http\Requests;

class CancelAssessmentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:2000']];
    }
}
