<?php

namespace App\Http\Requests;

class OverrideProgramEligibilityRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['result' => ['required', 'in:eligible,not_eligible,partially_eligible,needs_review'], 'reason' => ['required', 'string', 'max:2000']];
    }
}
