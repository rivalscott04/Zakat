<?php

namespace App\Http\Requests;

use App\Enums\DistributionType;
use App\Support\OrganizationContext;
use Illuminate\Validation\Rule;

/** PRD 12F §15 — pengajuan penyaluran sebelum distribution dibuat. */
class StoreDistributionRequestRequest extends ApiRequest
{
    public function rules(): array
    {
        $organizationId = OrganizationContext::id();
        $scoped = fn (string $table) => Rule::exists($table, 'id')->where('organization_id', $organizationId);

        return [
            'mustahik_id' => ['required', 'string', 'ulid', $scoped('mustahiks')],
            'fund_id' => ['required', 'string', 'ulid', $scoped('funds')],
            'program_id' => ['nullable', 'string', 'ulid', $scoped('programs')],
            'assessment_id' => ['nullable', 'string', 'ulid', $scoped('assessments')],
            'distribution_type' => ['required', Rule::enum(DistributionType::class)],
            'requested_amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'reason' => ['required', 'string', 'max:2000'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
        ];
    }
}
