<?php

namespace App\Http\Requests;

use App\Enums\DistributionSourceType;
use App\Enums\DistributionType;
use App\Support\OrganizationContext;
use Illuminate\Validation\Rule;

/** PRD 12C §5 dan 12G §17. */
class StoreDistributionRequest extends ApiRequest
{
    public function rules(): array
    {
        $organizationId = OrganizationContext::id();

        // Rule exists di-scope ke organisasi aktif supaya keberadaan data milik
        // organisasi lain tidak bisa diprobe lewat pesan validasi (PRD 22).
        $scoped = fn (string $table) => Rule::exists($table, 'id')->where('organization_id', $organizationId);

        return [
            'mustahik_id' => ['required', 'string', 'ulid', $scoped('mustahiks')],
            'fund_id' => ['required', 'string', 'ulid', $scoped('funds')],
            'program_id' => ['nullable', 'string', 'ulid', $scoped('programs')],
            'program_enrollment_id' => ['nullable', 'string', 'ulid', 'exists:program_enrollments,id'],
            'assessment_id' => ['nullable', 'string', 'ulid', $scoped('assessments')],
            'distribution_type' => ['required', Rule::enum(DistributionType::class)],
            'source_type' => ['nullable', Rule::enum(DistributionSourceType::class)],
            'requested_amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'scheduled_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'items' => ['nullable', 'array'],
            'items.*.item_code' => ['required_with:items', 'string', 'max:40'],
            'items.*.item_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:1000'],
            'items.*.quantity' => ['nullable', 'numeric', 'gt:0'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.unit_value' => ['nullable', 'numeric', 'gte:0'],
        ];
    }
}
