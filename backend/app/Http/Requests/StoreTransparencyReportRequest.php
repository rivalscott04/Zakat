<?php

namespace App\Http\Requests;

/** PRD 18O §23. */
class StoreTransparencyReportRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'report_type' => ['required', 'in:MONTHLY,QUARTERLY,YEARLY,CUSTOM'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'snapshot_id' => ['required', 'string', 'ulid'],
            'document_id' => ['sometimes', 'nullable', 'string', 'ulid'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
