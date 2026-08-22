<?php

namespace App\Http\Requests;

/** PRD 19G §22 — parameter laporan divalidasi ulang di service sesuai definisinya. */
class RunReportRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'parameters' => ['sometimes', 'array'],
            'queue' => ['sometimes', 'boolean'],
        ];
    }
}
