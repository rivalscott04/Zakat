<?php

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\ReportExportFormat;
use App\Enums\ReportFrequency;
use Illuminate\Validation\Rule;

/** PRD 19K §33. */
class StoreReportScheduleRequest extends ApiRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'report_id' => [$required, 'string', 'ulid'],
            'name' => [$required, 'string', 'max:150'],
            'frequency' => [$required, Rule::enum(ReportFrequency::class)],
            'schedule_configuration' => ['sometimes', 'nullable', 'array'],
            'parameters' => ['sometimes', 'nullable', 'array'],
            'output_format' => ['sometimes', Rule::enum(ReportExportFormat::class)],
            'recipient_configuration' => ['sometimes', 'nullable', 'array'],
            'recipient_configuration.user_ids' => ['sometimes', 'array'],
            'recipient_configuration.user_ids.*' => ['string', 'ulid'],
            'recipient_configuration.channels' => ['sometimes', 'array'],
            'recipient_configuration.channels.*' => [Rule::enum(NotificationChannel::class)],
        ];
    }
}
