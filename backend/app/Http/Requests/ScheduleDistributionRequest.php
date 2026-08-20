<?php

namespace App\Http\Requests;

use App\Enums\DistributionScheduleType;
use Illuminate\Validation\Rule;

/** PRD 12N §34. */
class ScheduleDistributionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'schedule_type' => ['nullable', Rule::enum(DistributionScheduleType::class)],
            'scheduled_date' => ['required', 'date'],
            'amount' => ['nullable', 'numeric', 'gt:0'],
        ];
    }
}
