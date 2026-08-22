<?php

namespace App\Http\Requests;

/** PRD 19J §30 dan §31. */
class StoreReportTemplateRequest extends ApiRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'template_code' => [$required, 'string', 'max:60', 'regex:/^[A-Za-z0-9]+$/'],
            'name' => [$required, 'string', 'max:150'],
            'report_id' => [$required, 'string', 'ulid'],
            'configuration' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return ['template_code.regex' => 'Template code hanya boleh huruf dan angka, tanpa dash (PRD 19J §31).'];
    }
}
