<?php

namespace App\Http\Requests;

use App\Enums\ReportCategory;
use App\Enums\ReportVisibility;
use Illuminate\Validation\Rule;

/** PRD 19C §5 dan §7. */
class StoreReportRequest extends ApiRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'report_code' => [$required, 'string', 'max:60', 'regex:/^[A-Za-z0-9]+$/'],
            'name' => [$required, 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'category' => [$required, Rule::enum(ReportCategory::class)],
            'report_type' => ['sometimes', 'string', 'max:30'],
            'data_source' => ['sometimes', 'nullable', 'string', 'max:60'],
            'visibility' => ['sometimes', Rule::enum(ReportVisibility::class)],
        ];
    }

    public function messages(): array
    {
        return ['report_code.regex' => 'Report code hanya boleh huruf dan angka, tanpa dash (PRD 19C §7).'];
    }
}
