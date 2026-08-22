<?php

namespace App\Http\Requests;

use App\Enums\ReportExportFormat;
use Illuminate\Validation\Rule;

/** PRD 19I §26. */
class ExportReportRunRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['format' => ['required', Rule::enum(ReportExportFormat::class)]];
    }
}
