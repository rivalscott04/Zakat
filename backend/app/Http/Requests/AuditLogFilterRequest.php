<?php

namespace App\Http\Requests;

use App\Enums\AuditEventCategory;
use App\Enums\AuditSeverity;
use Illuminate\Validation\Rule;

/** PRD 17P §30 — penyaringan daftar audit. */
class AuditLogFilterRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'event_category' => ['sometimes', 'nullable', Rule::enum(AuditEventCategory::class)],
            'module_code' => ['sometimes', 'nullable', 'string', 'max:30'],
            'severity' => ['sometimes', 'nullable', Rule::enum(AuditSeverity::class)],
            'actor_id' => ['sometimes', 'nullable', 'string', 'ulid'],
            'entity_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'entity_id' => ['sometimes', 'nullable', 'string', 'ulid'],
            'request_id' => ['sometimes', 'nullable', 'string', 'max:40'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.config('zakat.pagination.max_per_page')],
        ];
    }
}
