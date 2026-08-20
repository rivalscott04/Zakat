<?php

namespace App\Http\Requests;

use App\Enums\DistributionFailureReason;
use Illuminate\Validation\Rule;

/** PRD 12T §48. */
class FailDistributionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'failure_reason' => ['required', Rule::enum(DistributionFailureReason::class)],
            'failure_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
